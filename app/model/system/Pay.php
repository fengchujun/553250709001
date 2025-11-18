<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use app\model\member\Member;
use app\model\order\Config as OrderConfig;
use app\model\order\OrderPay;
use think\facade\Cache;
use think\facade\Log;

/**
 * 系统配置类
 */
class Pay extends BaseModel
{
    //支付状态
    const PAY_STATUS_NOT = 0;
    const PAY_STATUS_IN_PROCESS = 1;
    const PAY_STATUS_SUCCESS = 2;
    const PAY_STATUS_CANCEL = -1;
    const PAY_STATUS_CLOSE = -2;

    public $refund_pay_type = array (
        'offline_refund_pay' => '线下退款',
        'online_refund_pay' => '原路退款'
    );

    static function getPayStatus($key = null)
    {
        $arr = [
            [
                'id' => self::PAY_STATUS_NOT,
                'name' => '待支付',
            ],
            [
                'id' => self::PAY_STATUS_IN_PROCESS,
                'name' => '支付中',
            ],
            [
                'id' => self::PAY_STATUS_SUCCESS,
                'name' => '支付成功',
            ],
            [
                'id' => self::PAY_STATUS_CANCEL,
                'name' => '已取消',
            ],
            [
                'id' => self::PAY_STATUS_CLOSE,
                'name' => '已关闭',
            ],
        ];
        if(isset($arr[0][$key])){
            $arr = array_column($arr, null, $key);
        }
        return $arr;
    }

    /********************************************************************支付**********************************************************/

    /**
     * 支付
     * @param string $pay_type 支付方式
     * @param string $out_trade_no 交易号
     * @param string $app_type 请求来源类型
     * @param int $member_id 会员id
     * @param string $return_url 同步回调地址
     * @param int $is_balance 是否使用余额
     * @param int $scene 场景值
     * @return mixed|void
     */
    public function pay($pay_type, $out_trade_no, $app_type, $member_id, $return_url = null, $is_balance = 0, $scene = 0)
    {
        $data = $this->getPayInfo($out_trade_no)[ 'data' ];
        if (empty($data)) return $this->error('', '未获取到支付信息');
        if ($data[ 'pay_status' ] == self::PAY_STATUS_SUCCESS) return $this->success(['pay_success' => 1]);
        if ($data['pay_status'] == self::PAY_STATUS_CLOSE) return $this->error(null, '支付单已关闭');

        $notify_url = addon_url('pay/pay/notify');
        if (empty($return_url)) {
            $return_url = addon_url('pay/pay/payreturn');
        }

        // 是否使用余额
        if ($is_balance) {
            $data[ 'member_id' ] = $member_id;
            $use_res = $this->useBalance($data)[ 'data' ];
            if (isset($use_res[ 'pay_success' ])) return $this->success($use_res);
            $data = $this->getPayInfo($out_trade_no)[ 'data' ];
        }

        $data[ 'app_type' ] = $app_type;
        $data[ 'notify_url' ] = $notify_url;
        $data[ 'return_url' ] = $return_url;
        $data[ 'pay_type' ] = $pay_type;
        $data[ 'member_id' ] = $member_id;
        $data[ 'scene' ] = $scene;
        $res = event('Pay', $data, true);
        if (empty($res)) return $this->error('', '没有可用的支付方式');
        return $res;
    }

    /**
     * 创建支付流水号
     * @param int $member_id
     * @return string
     */
    public function createOutTradeNo($member_id = 0)
    {
        $cache = Cache::get('pay_out_trade_no' . $member_id . time());
        if (empty($cache)) {
            Cache::set('pay_out_trade_no' . $member_id . time(), 1000);
            $cache = Cache::get('pay_out_trade_no' . $member_id . time());
        } else {
            $cache = $cache + 1;
            Cache::set('pay_out_trade_no' . $member_id . time(), $cache);
        }
        $no = time() . rand(1000, 9999) . $member_id . $cache;
        return $no;
    }

    /**
     * 添加支付信息
     * @param int $site_id //站点id  默认平台配置为0
     * @param string $out_trade_no 交易流水号
     * @param string $app_type 支付端口类型
     * @param int $pay_type 支付方式，默认为空
     * @param string $pay_body 支付主体
     * @param string $pay_detail 支付细节
     * @param double $pay_money 支付金额
     * @param string $pay_no 支付账号
     * @param string $notify_url 要求的异步回调网址，实际支付后会进行执行或者回调，可以是事件或者域名
     * @param string $return_url 同步回调网址，知己支付后会进行同步回调
     */
    public function addPay($site_id, $out_trade_no, $pay_type, $pay_body, $pay_detail, $pay_money, $pay_no, $notify_url, $return_url, $relate_id = '', $member_id = 0)
    {
        $data = array (
            'site_id' => $site_id,
            'out_trade_no' => $out_trade_no,
            'pay_body' => $pay_body,
            'pay_detail' => $pay_detail,
            'pay_money' => $pay_money,
            'pay_no' => $pay_no,
            'event' => $notify_url,
            'return_url' => $return_url,
            'pay_status' => 0,
            'create_time' => time(),
            'relate_id' => $relate_id,
            'member_id' => $member_id,
        );
        model('pay')->add($data);
        $result = $this->success();
        if ($pay_money == 0) {
            $result = $this->onlinePay($out_trade_no, $pay_type, '', '');
        }
        return $result;
    }

    /**
     * 在线支付
     * @param $out_trade_no
     * @param $pay_type
     * @param $trade_no
     * @param $pay_addon
     * @param array $log_data
     * @return array|mixed|void
     * @throws \Exception
     */
    public function onlinePay($out_trade_no, $pay_type, $trade_no, $pay_addon, $log_data = [])
    {
        Log::write("在线支付_onlinePay_" . $out_trade_no);
        $pay_type = empty($pay_type) ? 'ONLINE_PAY' : $pay_type;
        $pay_info = $this->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)) return $this->error(null, '支付信息缺失');

        //根据不同的状态执行不同的业务
        switch($pay_info[ 'pay_status' ]){
            case self::PAY_STATUS_NOT:
            case self::PAY_STATUS_IN_PROCESS:
            case self::PAY_STATUS_CANCEL:
                $data = array (
                    'trade_no' => $trade_no,
                    'pay_type' => $pay_type,
                    'pay_addon' => $pay_addon,
                    'pay_time' => time(),
                    'pay_status' => self::PAY_STATUS_SUCCESS
                );
                $res = model('pay')->update($data, [['out_trade_no', '=', $out_trade_no]]);
                if(!$res){
                    Log::write("在线支付更新pay失败_" . $out_trade_no);
                    return $this->error(null, '支付单据更新失败');
                }

                return $this->onlinePayEvent($pay_info['id'], $log_data, 1);
                break;
            case self::PAY_STATUS_SUCCESS:
                return $this->success();
                break;
            case self::PAY_STATUS_CLOSE:
                //退款
                $refund_no = $this->createRefundNo();
                return $this->refund($refund_no, $pay_info['pay_money'], $pay_info['out_trade_no'], '重复支付退款', $pay_info['pay_money'], $pay_info['site_id'], 1, 0, 0);
                break;
            default:
                return $this->error(null, '支付状态有误');
        }
    }

    /**
     * @param $id
     * @param array $log_data
     * @return array|mixed
     * @throws \Exception
     */
    public function onlinePayEvent($id, $log_data = [], $execute_num = 1)
    {
        $pay_info = model('pay')->getInfo([['id', '=', $id]]);
        $return_data = [
            'out_trade_no' => $pay_info['out_trade_no'],
            'trade_no' => $pay_info['trade_no'],
            'pay_type' => $pay_info['pay_type'],
            'log_data' => $log_data,
        ];

        $res = $this->success();
        if (strpos($pay_info[ 'event' ], 'http://') !== 0 || strpos($pay_info[ 'event' ], 'https://') !== 0) {
            $event_res_arr = event($pay_info[ 'event' ], $return_data);
            foreach($event_res_arr as $event_res){
                if(isset($event_res['code']) && $event_res['code'] < 0){
                    $pay_json = json_encode($pay_info['pay_json'], true);
                    if(!is_array($pay_json)) $pay_json = [];
                    $pay_json['event_res'] = $event_res;
                    model('pay')->update(['pay_json' => json_encode($pay_json, JSON_UNESCAPED_UNICODE)], [['out_trade_no', '=', $pay_info['out_trade_no']]]);
                    Log::write("支付回调event失败");
                    Log::write(['pay_info' => $pay_info, 'return_data' => $return_data, 'event_res' => $event_res]);
                    $res = $event_res;
                    break;
                }
            }
        } else {
            http($pay_info[ 'event' ], 1);
        }
        if(in_array($pay_info['pay_type'], ['wechatpay','alipay']) && $res['code'] < 0){
            if($execute_num < 3){
                sleep(3);
                return $this->onlinePayEvent($pay_info, $return_data, $execute_num+1);
            }else if($execute_num == 3){
                //如果执行3次以后还是失败，那就定时任务1分钟后再执行，如果依然失败就不再继续
                (new Cron())->addCron(1, 0, "支付回调", "OnlinePayEvent", time() + 60, $id);
            }
        }
        return $res;
    }

    /**
     * 关闭支付
     * @param $out_trade_no
     * @return array|mixed
     * @throws \think\db\exception\DbException
     */
    public function closePay($out_trade_no)
    {
        $pay_info = $this->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)) return $this->error(null, '支付信息不存在');
        if($pay_info['pay_status'] == self::PAY_STATUS_CLOSE) return $this->success();
        if($pay_info['pay_status'] == self::PAY_STATUS_SUCCESS) return $this->error(null, '已支付单据不可关闭');

        $event_res = event('PayClose', $pay_info);
        foreach ($event_res as $v) {
            if (isset($v[ 'code' ]) && $v[ 'code' ] < 0) {
                return $v;
            }
        }

        model('pay')->startTrans();
        try{
            // 冻结中的余额返还
            if ($pay_info[ 'member_id' ]) {
                if ($pay_info[ 'balance' ]) model('member')->setDec([['site_id', '=', $pay_info[ 'site_id' ]], ['member_id', '=', $pay_info[ 'member_id' ]]], 'balance_lock', $pay_info[ 'balance' ]);
                if ($pay_info[ 'balance_money' ]) model('member')->setDec([['site_id', '=', $pay_info[ 'site_id' ]], ['member_id', '=', $pay_info[ 'member_id' ]]], 'balance_money_lock', $pay_info[ 'balance_money' ]);
            }
            $res = model('pay')->update(['pay_status' => self::PAY_STATUS_CLOSE], [['out_trade_no', '=', $out_trade_no]]);
            if ($res === false) {
                model('pay')->rollback();
                return $this->error(null, '数据修改失败');
            }

            model('pay')->commit();
            return $this->success();
        }catch(\Exception $e){
            model('pay')->rollback();
            return $this->error(null, $e->getMessage());
        }
    }

    /**
     * 重新生成新的pay支付记录
     * @param $out_trade_no
     * @param $pay_money
     * @return array|mixed|void
     */
    public function rewritePay($out_trade_no, $pay_money)
    {
        $pay_info_result = $this->getPayInfo($out_trade_no);
        $pay_info = $pay_info_result[ 'data' ];
        //支付状态 (未支付  未取消)
        if ($pay_info[ 'pay_status' ] == self::PAY_STATUS_NOT) {
//            if (!empty($pay_info[ 'pay_type' ])) {
            $close_result = event('payClose', $pay_info, true);
            if (!empty($close_result[ 'code' ]) && $close_result[ 'code' ] < 0) {
                return $close_result;
            }
//            }
            $new_out_trade_no = $this->createOutTradeNo();
            $data = array (
                'out_trade_no' => $new_out_trade_no,
                'pay_money' => $pay_money
            );
            $res = model('pay')->update($data, [['out_trade_no', '=', $out_trade_no]]);
            if ($res === false) {
                return $this->error('', 'UNKNOW_ERROR');
            } else {

                return $this->success($new_out_trade_no);
            }
        } else if ($pay_info[ 'pay_status' ] == self::PAY_STATUS_CANCEL) {
            $new_out_trade_no = $this->createOutTradeNo();
            $data = array (
                'out_trade_no' => $new_out_trade_no,
                'pay_money' => $pay_money,
                'pay_status' => self::PAY_STATUS_NOT
            );
            $res = model('pay')->update($data, [['out_trade_no', '=', $out_trade_no]]);
            return $this->success($new_out_trade_no);
        } else {
            return $this->error([], '当前支付已完成');
        }
    }

    /**
     * 支付绑定商户信息
     * @param $out_trade_no
     * @param $json_data
     */
    public function clearMchPay($out_trade_no, $curr_pay_type)
    {
        //如果当前方式与之前发起的支付方式不同则要关闭旧的支付，防止一个支付单不同的支付方式都支付成功
        $pay_info = $this->getPayInfo($out_trade_no)['data'];
        $mch_info = json_decode($pay_info['mch_info'], true);
        $bind_pay_type = $mch_info['pay_type'] ?? '';
        if(!empty($bind_pay_type) && $bind_pay_type != $curr_pay_type){
            $event_res = event('PayClose', $pay_info);
            foreach ($event_res as $v) {
                if (isset($v[ 'code' ]) && $v[ 'code' ] < 0) {
                    return $this->error($v, '有其他方式正在支付中，请稍后再试');
                }
            }
        }
        model('pay')->update(['mch_info' => ''], [['out_trade_no', '=', $out_trade_no]]);
        return $this->success();
    }

    /**
     * 支付绑定商户信息
     * @param $out_trade_no
     * @param $json_data
     */
    public function bindMchPay($out_trade_no, $json_data)
    {
        $res = model('pay')->update([
            'mch_info' => json_encode($json_data, JSON_UNESCAPED_UNICODE),
        ], [['out_trade_no', '=', $out_trade_no]]);
        return $this->success($res);
    }

    /**
     * 获取支付方式
     * @param array $params 'pay_scene' => ['wap', 'wechat', 'app', 'pc', 'wechat_applet']
     * @return array
     */
    public function getPayType($params = [])
    {
        $res = event('PayType', $params);
        return $this->success($res);
    }

    /**
     * 获取支付信息详情
     * @param $out_trade_no
     * @return array
     */
    public function getPayInfo($out_trade_no)
    {
        $condition = [['out_trade_no', '=', $out_trade_no]];
        return $this->getInfo($condition, '*');
    }

    /**
     * 获取支付信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getInfo($condition, $field = '*')
    {
        $info = model('pay')->setIsCache(0)->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 支付记录
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getPayPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('pay')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 支付统计
     * @param $condition
     * @return array
     */
    public function getPayStatistics($condition)
    {
        $statistics_array = array (
            'count' => model('pay')->getCount($condition),
            'sum_money' => model('pay')->getSum($condition, 'pay_money')
        );
        return $statistics_array;
    }

    /****************************************************************退款**************************************************************/

    /**
     * 创建退款流水号
     */
    public function createRefundNo()
    {
        $cache = Cache::get('pay_refund_out_trade_no' . time());
        if (empty($cache)) {
            Cache::set('niutk' . time(), 1000);
            $cache = Cache::get('pay_refund_out_trade_no' . time());
        } else {
            $cache = $cache + 1;
            Cache::set('pay_refund_out_trade_no' . time(), $cache);
        }
        $no = date('Ymdhis', time()) . rand(1000, 9999) . $cache;
        return $no;
    }

    /**
     * 原路退款
     * @param $refund_no
     * @param $refund_fee
     * @param $out_trade_no
     * @param $refund_desc
     * @param number $total_fee 实际支付金额
     * @param $site_id
     * @param int $refund_type 退款方式 1 原路退款  2 线下支付
     * @param int $order_goods_id
     * @param int $is_video_number
     * @return array|mixed|void
     */
    public function refund($refund_no, $refund_fee, $out_trade_no, $refund_desc, $total_fee, $site_id, $refund_type, $order_goods_id = 0, $is_video_number = 0)
    {
        //是否是原理退款方式退款
        if ($refund_type == 1) {
            $pay_info_result = $this->getPayInfo($out_trade_no);
            $pay_info = $pay_info_result[ 'data' ];
            if (empty($pay_info))
                return $this->error('', '付款记录不存在！');

            $data = array (
                'refund_no' => $refund_no,
                'refund_fee' => $refund_fee,
                'refund_desc' => $refund_desc,
                'pay_info' => $pay_info,
                'total_fee' => $total_fee,
                'site_id' => $site_id,
                'order_goods_id' => $order_goods_id,
                'is_video_number' => $is_video_number,
                'out_aftersale_id' => 0
            );

            if (!empty($order_goods_id)) {
                $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $order_goods_id]]);
                if (!empty($order_goods_info)) {
                    $data[ 'out_aftersale_id' ] = $order_goods_info[ 'out_aftersale_id' ];
                }
            }
            //退款金额许大于0
            if ($refund_fee > 0 && !in_array($pay_info[ 'pay_type' ], ['offlinepay', 'BALANCE', 'ONLINE_PAY'])) {
                $result = event('PayRefund', $data, true);
                if (empty($result))
                    return $this->error('', '找不到可用的退款方式！');

                if ($result[ 'code' ] < 0)
                    return $result;
            }


        }
        $refund_data = array (
            'refund_no' => $refund_no,
            'refund_fee' => $refund_fee,
            'total_money' => $total_fee,
            'refund_type' => $refund_type,
            'site_id' => $site_id,
            'out_trade_no' => $out_trade_no,
        );
        $this->addRefundPay($refund_data);
        return $this->success();

    }

    /**
     * 添加退款记录
     * @param $data
     * @return array
     */
    public function addRefundPay($data)
    {
        $data[ 'create_time' ] = time();
        $data[ 'refund_detail' ] = '支付交易号:' . $data[ 'out_trade_no' ] . '，退款金额:' . $data[ 'refund_fee' ] . '元';
        $res = model('pay_refund')->add($data);
        if ($res == false) {
            return $this->error($res);
        }
        return $this->success($res);
    }

    /**
     * 查询转账方式
     * @param $site_id
     * @return array
     */
    public function getTransferType($site_id)
    {
        $data = array (
            'bank' => '银行卡'
        );
        $temp_array = event('TransferType', ['site_id' => $site_id]);

        if (!empty($temp_array)) {
            foreach ($temp_array as $k => $v) {
                $data[ $v[ 'type' ] ] = $v[ 'type_name' ];
            }
        }
        return $data;
    }

    /**
     * 使用余额
     * @param $pay_info
     * @return array|mixed|void
     */
    public function useBalance($pay_info)
    {
        // 查询是否可使用余额
        $balance_config = (new OrderConfig())->getBalanceConfig($pay_info[ 'site_id' ])[ 'data' ][ 'value' ];
        if (!$balance_config[ 'balance_show' ]) return $this->success();

        // 查询会员当前可用余额
        $balance_data = (new Member())->getMemberUsableBalance($pay_info[ 'site_id' ], $pay_info[ 'member_id' ]);
        if ($balance_data[ 'code' ] != 0) return $balance_data;
        $balance_data = $balance_data[ 'data' ];
        if ($balance_data[ 'usable_balance' ] <= 0) return $this->success();

        $data = [
            'pay_money' => $pay_info[ 'pay_money' ],
            'member_id' => $pay_info[ 'member_id' ]
        ];

        if ($balance_data[ 'balance' ] > 0) {
            $data[ 'balance' ] = bccomp($balance_data[ 'balance' ], $data[ 'pay_money' ], 2) == 1 ? $data[ 'pay_money' ] : $balance_data[ 'balance' ];
            $data[ 'pay_money' ] -= $data[ 'balance' ];
        }
        if ($balance_data[ 'balance_money' ] > 0 && $data[ 'pay_money' ] > 0) {
            $data[ 'balance_money' ] = bccomp($balance_data[ 'balance_money' ], $data[ 'pay_money' ], 2) == 1 ? $data[ 'pay_money' ] : $balance_data[ 'balance_money' ];
            $data[ 'pay_money' ] -= $data[ 'balance_money' ];
        }

        model('pay')->startTrans();
        try {
            model('pay')->update($data, [['out_trade_no', '=', $pay_info[ 'out_trade_no' ]]]);
            if (isset($data[ 'balance' ]) && $data[ 'balance' ] > 0) model('member')->setInc([['site_id', '=', $pay_info[ 'site_id' ]], ['member_id', '=', $pay_info[ 'member_id' ]]], 'balance_lock', $data[ 'balance' ]);
            if (isset($data[ 'balance_money' ]) && $data[ 'balance_money' ] > 0) model('member')->setInc([['site_id', '=', $pay_info[ 'site_id' ]], ['member_id', '=', $pay_info[ 'member_id' ]]], 'balance_money_lock', $data[ 'balance_money' ]);

            if ($data[ 'pay_money' ] == 0) {
                $res = $this->onlinePay($pay_info[ 'out_trade_no' ], 'BALANCE', '', '');
                if ($res[ 'code' ] != 0) {
                    model('pay')->rollback();
                    return $res;
                }
                model('pay')->commit();
                return $this->success(['pay_success' => 1]);
            }
            model('pay')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('pay')->rollback();
            return $this->error('', '支付冻结余额错误');
        }
    }

    /**
     * 重置支付
     * @param $params
     * @return array|mixed|void
     */
    public function resetPay($params)
    {
        $out_trade_no = $params[ 'out_trade_no' ];
        $pay_info = $this->getPayInfo($out_trade_no)[ 'data' ] ?? [];
        if (empty($pay_info))
            return $this->error();
        $result = event('PayReset', $pay_info, true);//各种插件自己实现
        if (empty($result)) {
            switch ($pay_info[ 'event' ]) {
                case 'OrderPayNotify':
                    $order_pay_model = new OrderPay();
                    $result = $order_pay_model->resetOrderTradeNo(['out_trade_no' => $out_trade_no]);
                    break;
            }
        }
        if ($result[ 'code' ] < 0)
            return $result;

        return $result;
    }

    /**
     * 支付编辑(切勿调用,收银业务专用)
     * @param $data
     * @param $condition
     * @return array
     */
    public function edit($data, $condition)
    {
        model('pay')->update($data, $condition);
        return $this->success();
    }

}
