<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberconsume\model;

use app\dict\order_refund\OrderRefundDict;
use app\model\member\MemberAccount as MemberAccountModel;
use app\model\order\OrderCommon as OrderCommonModel;
use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use addon\coupon\model\CouponType;
use addon\coupon\model\Coupon;

/**
 * 会员消费
 */
class Consume extends BaseModel
{
    /**
     * 会员消费设置
     * @param $data
     * @param $is_use
     * @param $site_id
     * @return array
     */
    public function setConfig($data, $is_use, $site_id)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '会员消费设置', $is_use, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'MEMBER_CONSUME_CONFIG' ] ]);
        return $res;
    }

    /**
     * 会员消费设置
     * @param $site_id
     * @return array
     */
    public function getConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'MEMBER_CONSUME_CONFIG' ] ]);

        //数据处理
        if (empty($res[ 'data' ][ 'value' ])) $res[ 'data' ][ 'value' ] = [];
        $default_value = [
            //奖励回收
            'is_recovery_reward' => 0,
            //积分
            'is_return_point' => 0,
            'return_point_rate' => 0,
            //成长值
            'is_return_growth' => 0,
            'return_growth_rate' => 0,
            //优惠券
            'is_return_coupon' => 0,
            'return_coupon' => '',
        ];
        foreach ($res['data']['value'] as $key=>$val){
            if(!isset($default_value[$key])) unset($res['data']['value'][$key]);
        }
        $res['data']['value'] = array_merge($default_value, $res['data']['value']);

        //优惠券数据
        $coupon_list = [];
        if ($res[ 'data' ][ 'value' ][ 'is_return_coupon' ] == 1) {
            $coupon = new CouponType();
            $coupon_list = $coupon->getCouponTypeList([ [ 'site_id', '=', $site_id ], [ 'status', '=', 1 ], [ 'coupon_type_id', 'in', $res[ 'data' ][ 'value' ][ 'return_coupon' ] ] ])[ 'data' ];
        }
        $res[ 'data' ][ 'value' ][ 'coupon_list' ] = $coupon_list;

        return $res;
    }

    /**
     * 获取订单信息
     * @param $out_trade_no
     * @return array
     */
    public function getOrderInfo($out_trade_no)
    {
        $pay_info = model('pay')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ], [ 'pay_status', '=', 2 ] ]);
        if (empty($pay_info)) return $this->error('', '支付信息未找到');

        $order_info = [];
        switch ( $pay_info[ 'event' ] ) {
            case 'OrderPayNotify':
                //普通订单
                $order = new OrderCommonModel();
                $order_info = $order->getOrderInfo([ [ 'out_trade_no', '=', $out_trade_no ]], 'order_id, order_money, site_id, member_id, order_type')[ 'data' ];
                break;
            case 'GiftCardOrderPayNotify':
                //礼品卡
                $order_info = model('giftcard_order')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ]], 'order_id,order_money, site_id, member_id');
                break;
            case 'BlindboxGoodsOrderPayNotify':
                //盲盒
                $order_info = model('blindbox_order')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ]], 'order_id,price as order_money, site_id, member_id');
                break;
            case 'CashierOrderPayNotify':
                //收银订单
                $order = new OrderCommonModel();
                $order_info = $order->getOrderInfo([ [ 'out_trade_no', '=', $out_trade_no ], [ 'member_id', '>', 0 ], [ 'cashier_order_type', 'in', [ 'goods', 'card' ] ]], 'order_id, order_money, site_id, member_id, order_type, cashier_order_type, order_scene')[ 'data' ];
                break;
        }
        return $this->success($order_info);
    }

    /**
     * 消费记录发放
     * @param $param
     * @param int $order_id
     * @return array
     */
    public function memberConsume($param, $order_id = 0)
    {
        $out_trade_no = $param[ 'out_trade_no' ];

        //支付信息
        $pay_info = model('pay')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ], [ 'pay_status', '=', 2 ] ]);
        if (empty($pay_info)) return $this->error('', '支付信息未找到');

        //订单信息
        $order_info_res = $this->getOrderInfo($param['out_trade_no']);
        if($order_info_res['code'] < 0) return $order_info_res;
        $order_info = $order_info_res['data'];
        if (empty($order_info)) return $this->success();

        //检测是否发放过
        $count = model('promotion_consume_record')->getCount([ [ 'out_trade_no', '=', $param[ 'out_trade_no' ] ], [ 'order_id', '=', $order_id ] ]);
        if (!empty($count)) {
            return $this->success();
        }

        $consume_config = $this->getConfig($order_info[ 'site_id' ])[ 'data' ];
        if($consume_config['is_use'] == 0) return $this->success();

        $member_account_model = new MemberAccountModel();
        $consume_data = [];

        // 发放积分
        $consume_config = $consume_config[ 'value' ];
        if (!empty($consume_config[ 'return_point_rate' ])) {
            $adjust_num = intval($consume_config[ 'return_point_rate' ] / 100 * $order_info[ 'order_money' ]);
            if ($adjust_num > 0) {
                $remark = '活动奖励发放';
                $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'point', $adjust_num, 'memberconsume', $order_info[ 'order_id' ], $remark);
                $consume_data[] = [
                    'site_id' => $order_info[ 'site_id' ],
                    'type' => 'point',
                    'value' => $adjust_num,
                    'order_id' => $order_info[ 'order_id' ],
                    'member_id' => $order_info[ 'member_id' ],
                    'out_trade_no' => $pay_info[ 'out_trade_no' ],
                    'remark' => $remark,
                    'config' => json_encode($consume_config),
                    'create_time' => time()
                ];
            }
        }

        // 发放成长值
        if (!empty($consume_config[ 'return_growth_rate' ])) {
            $adjust_num = intval($consume_config[ 'return_growth_rate' ] / 100 * $order_info[ 'order_money' ]);
            if ($adjust_num > 0) {
                $remark = '活动奖励发放';
                $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'growth', $adjust_num, 'memberconsume', $order_info[ 'order_id' ], $remark);
                $consume_data[] = [
                    'site_id' => $order_info[ 'site_id' ],
                    'type' => 'growth',
                    'value' => $adjust_num,
                    'order_id' => $order_info[ 'order_id' ],
                    'member_id' => $order_info[ 'member_id' ],
                    'out_trade_no' => $pay_info[ 'out_trade_no' ],
                    'remark' => $remark,
                    'config' => json_encode($consume_config),
                    'create_time' => time()
                ];
            }
        }

        // 发放优惠券
        if (!empty($consume_config[ 'is_return_coupon' ]) && !empty($consume_config[ 'return_coupon' ])) {
            $coupon_type = new CouponType();
            $coupon_list = $coupon_type->getCouponTypeList([ [ 'site_id', '=', $order_info[ 'site_id' ] ], [ 'status', '=', 1 ], [ 'coupon_type_id', 'in', $consume_config[ 'return_coupon' ] ] ])[ 'data' ];
            $coupon = new Coupon();
            foreach ($coupon_list as $k => $v) {
                $res = $coupon->giveCoupon([ [ 'coupon_type_id' => $v[ 'coupon_type_id' ], 'num' => 1 ] ], $order_info[ 'site_id' ], $order_info[ 'member_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
                if ($res[ 'code' ] >= 0) {
                    if ($v[ 'at_least' ] > 0) {
                        $remark = '满' . $v[ 'at_least' ] . ( $v[ 'type' ] == 'discount' ? '打' . $v[ 'discount' ] : '减' . $v[ 'money' ] );
                    } else {
                        $remark = '无门槛' . ( $v[ 'type' ] == 'discount' ? '打' . $v[ 'discount' ] : '减' . $v[ 'money' ] );
                    }

                    $consume_data[] = [
                        'site_id' => $order_info[ 'site_id' ],
                        'type' => 'coupon',
                        'value' => $v[ 'coupon_type_id' ],
                        'order_id' => $order_info[ 'order_id' ],
                        'member_id' => $order_info[ 'member_id' ],
                        'out_trade_no' => $pay_info[ 'out_trade_no' ],
                        'remark' => $remark,
                        'config' => json_encode($consume_config),
                        'create_time' => time()
                    ];
                }
            }
        }

        if (count($consume_data) > 0) {
            model('promotion_consume_record')->addList($consume_data);
        }

        return $this->success();
    }

    private function returnStatusToZh($status)
    {
        $status_zh = [
            'pay' => '付款',
            'receive' => '收货',
            'complete' => '完成'
        ];
        return $status_zh[ $status ];
    }

    /**
     * 获取消费奖励列表
     * @param $condition
     * @param $field
     * @param $order
     * @return mixed
     */
    public function getConsumeRecordList($condition, $field = '*', $order = '')
    {
        $list = model('promotion_consume_record')->getList($condition, $field, $order);
        return $list;
    }

    /**
     * 奖励记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getConsumeRecordPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('promotion_consume_record')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 奖励回收
     * @param $data
     * @return array
     */
    public function rewardRecovery($data)
    {
        $order_info = $data['order_info'];
        $order_goods_info = $data['order_goods_info'];
        $is_refund_all = $data['is_all_refund'];
        $out_trade_no = $order_info['out_trade_no'];

        $list = model('promotion_consume_record')->getList([ [ 'type', 'in', [ 'point', 'coupon', 'growth' ] ], [ 'out_trade_no', '=', $out_trade_no ], [ 'is_recycled', '=', 0 ] ]);
        if (!empty($list)) {
            $site_id = $list[ 0 ][ 'site_id' ];
            $member_id = $list[ 0 ][ 'member_id' ];

            $consume_config = $this->getConfig($site_id)[ 'data' ];
            // 回收权益
            if ($consume_config[ 'value' ][ 'is_recovery_reward' ]) {
                $member_account = new MemberAccountModel();
                foreach ($list as $item) {
                    // 扣除积分
                    if ($item[ 'type' ] == 'point') {
                        $member_info = model('member')->getInfo([ [ 'member_id', '=', $member_id ] ], 'point');
                        $return_value = $item['value'];
                        if($order_info['order_money'] > 0){
                            $return_value = round(($order_goods_info['goods_money'] / $order_info['goods_money']) * $item['value']);
                        }
                        $point = min($return_value, $member_info['point']);
                        $res = $member_account->addMemberAccount($site_id, $member_id, 'point', -( $point ), 'memberconsume', $item[ 'order_id' ], '订单商品【'.$order_goods_info['sku_name'].'】退款奖励回收');
                        if ($res[ 'code' ] == 0 && $is_refund_all) {
                            model('promotion_consume_record')->update([ 'is_recycled' => 1 ], [ [ 'id', '=', $item[ 'id' ] ] ]);
                        }
                    }

                    // 删除未使用的优惠券
                    if ($item[ 'type' ] == 'coupon') {
                        $coupon = model('promotion_coupon')->getFirstData([ [ 'member_id', '=', $member_id ], [ 'coupon_type_id', '=', $item[ 'value' ] ], [ 'state', '=', 1 ] ], 'coupon_id');
                        if (!empty($coupon)) {
                            $delete_num = model('promotion_coupon')->delete([ [ 'coupon_id', '=', $coupon[ 'coupon_id' ] ] ]);
                            if ($delete_num) {
                                model('promotion_consume_record')->update([ 'is_recycled' => 1 ], [ [ 'id', '=', $item[ 'id' ] ] ]);
                            }
                        }
                    }

                    // 扣除成长值
                    if ($item[ 'type' ] == 'growth') {
                        $member_info = model('member')->getInfo([ [ 'member_id', '=', $member_id ] ], 'growth');
                        $return_value = $item['value'];
                        if($order_info['order_money'] > 0){
                            $return_value = round(($order_goods_info['goods_money'] / $order_info['goods_money']) * $item['value']);
                        }
                        $growth = min($return_value, $member_info['growth']);
                        $remark = '订单商品【'.$order_goods_info['sku_name'].'】退款奖励回收';
                        $res = $member_account->addMemberAccount($site_id, $member_id, 'growth', -( $growth ), 'memberconsume', $item[ 'order_id' ], $remark);
                        if ($res[ 'code' ] == 0 && $is_refund_all) {
                            model('promotion_consume_record')->update([ 'is_recycled' => 1 ], [ [ 'id', '=', $item[ 'id' ] ] ]);
                        }
                    }

                }
            }
        }
    }

}