<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supermember\model;

use addon\coupon\model\Coupon;
use app\dict\member_account\AccountDict;
use app\model\member\Member;
use app\model\member\MemberAccount;
use app\model\member\MemberLevel;
use app\model\system\Cron;
use app\model\system\Pay;
use app\model\system\Stat;
use think\facade\Cache;
use app\model\BaseModel;
use app\model\order\Config;

/**
 * 会员卡订单
 */
class MemberLevelOrder extends BaseModel
{
    // 订单待支付
    const ORDER_CREATE = 0;

    // 订单已支付
    const ORDER_PAY = 1;

    // 订单已关闭
    const ORDER_CLOSE = -1;

    /**
     * 基础支付方式(不考虑实际在线支付方式或者货到付款方式)
     * @var unknown
     */
    public $pay_type = [
        'ONLINE_PAY' => '在线支付',
        'offlinepay' => '线下支付',
        'BALANCE' => '余额支付'
    ];

    /**
     * 获取支付方式
     * @return unknown
     */
    public function getPayType()
    {
        //获取订单基础的其他支付方式
        $pay_type = $this->pay_type;
        //获取当前所有在线支付方式
        $onlinepay = event('PayType', []);
        if (!empty($onlinepay)) {
            foreach ($onlinepay as $k => $v) {
                $pay_type[ $v[ 'pay_type' ] ] = $v[ 'pay_type_name' ];
            }
        }
        return $pay_type;
    }

    /**
     * 订单创建
     * @param $data
     */
    public function create($data)
    {
        //获取用户头像
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $data[ 'member_id' ] ] ], 'headimg,nickname,member_level')[ 'data' ];

        // 获取卡信息
        $level_info = model('member_level')->getInfo([ [ 'level_id', '=', $data[ 'level_id' ] ], [ 'site_id', '=', $data[ 'site_id' ] ] ]);
        if (empty($level_info)) return $this->error('', '未获取到会员卡信息');
        if ($level_info[ 'level_type' ] == 0) return $this->error('', '免费卡无需购买');

        $charge_rule = json_decode($level_info[ 'charge_rule' ], true);
        if (!isset($charge_rule[ $data[ 'period_unit' ] ]) && empty($charge_rule[ $data[ 'period_unit' ] ])) return $this->error('', '该会员卡未配置该收费规则');

        $order_money = $charge_rule[ $data[ 'period_unit' ] ];

        $pay = new Pay();
        $out_trade_no = $data['out_trade_no'] ?? $pay->createOutTradeNo($data['member_id']);
        $order_no = $this->createOrderNo($data[ 'site_id' ], $data[ 'member_id' ]);

        $order_type = $member_info[ 'member_level' ] == $data[ 'level_id' ] ? 2 : 1; // 1购卡 2续费

        model('member_level_order')->startTrans();
        try {
            $create_data = [
                'site_id' => $data[ 'site_id' ],
                'order_no' => $order_no,
                'out_trade_no' => $out_trade_no,
                'level_name' => $level_info[ 'level_name' ],
                'level_id' => $level_info[ 'level_id' ],
                'order_type' => $order_type,
                'charge_type' => $level_info[ 'charge_type' ],
                'period_unit' => $data[ 'period_unit' ],
                'buy_num' => 1,
                'order_money' => $order_money,
                'buyer_id' => $data[ 'member_id' ],
                'nickname' => $member_info[ 'nickname' ],
                'headimg' => $member_info[ 'headimg' ],
                'create_time' => time()
            ];
            // 添加订单
            $order_id = model('member_level_order')->add($create_data);

            if (!isset($data[ 'out_trade_no' ])) {
                //生成支付单据
                $pay_body = $order_type == 1 ? '购买会员卡' : '会员卡续费';
                $pay->addPay($data[ 'site_id' ], $out_trade_no, "", $pay_body, $pay_body, $order_money, '', 'MemberLevelOrderPayNotify', '', $order_id, $data[ 'member_id' ]);
            }

            //计算订单自动关闭时间
            $config_model = new Config();
            $order_config_result = $config_model->getOrderEventTimeConfig($data[ 'site_id' ]);
            $order_config = $order_config_result[ "data" ];
            $now_time = time();
            if (!empty($order_config)) {
                $execute_time = $now_time + $order_config[ "value" ][ "auto_close" ] * 60;//自动关闭时间
            } else {
                $execute_time = $now_time + 3600;//尚未配置  默认一天
            }
            $cron_model = new Cron();
            $cron_model->addCron(1, 0, "订单自动关闭", "MemberLevelOrderClose", $execute_time, $order_id);

            model("member_level_order")->commit();
            return $this->success([ 'out_trade_no' => $out_trade_no, 'order_id' => $order_id ]);
        } catch (\Exception $e) {
            model('member_level_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 会员卡订单支付回调
     * @param $data
     */
    public function orderPay($data)
    {
        $order_info = model('member_level_order')->getInfo([ [ 'out_trade_no', '=', $data[ 'out_trade_no' ] ] ]);
        if (!empty($order_info) && $order_info[ 'order_status' ] == self::ORDER_CREATE) {
            model('member_level_order')->startTrans();
            try {
                $pay_list = $this->getPayType();
                $order_id = $order_info[ 'order_id' ];
                $site_id = $order_info[ 'site_id' ];
                $pay_type_name = '';
                if (!empty($data[ 'pay_type' ])) {
                    $pay_type_name = $pay_list[ $data[ 'pay_type' ] ];
                }

                //修改订单状态
                $order_data = [
                    'pay_type' => $data[ 'pay_type' ],
                    'pay_type_name' => $pay_type_name,
                    'pay_time' => time(),
                    'order_status' => self::ORDER_PAY
                ];
                $res = model('member_level_order')->update($order_data, [ [ 'out_trade_no', '=', $data[ 'out_trade_no' ] ] ]);

                // 会员卡付费类型是充值
                $member_account = new MemberAccount();
                if ($order_info[ 'charge_type' ] == 1) {
                    $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'buyer_id' ], AccountDict::balance, $order_info[ 'order_money' ], 'memberlevel', '0', '会员开卡充值');
                }

                // 查询会员信息
                $member_info = model('member')->getInfo([ [ 'member_id', '=', $order_info[ 'buyer_id' ] ] ]);
                // 查询会员卡信息
                $level_info = model('member_level')->getInfo([ [ 'level_id', '=', $order_info[ 'level_id' ] ] ]);

                // 如果是首次开卡发放开卡礼包
                $count = model('member_level_records')->getCount([ [ 'after_level_id', '=', $level_info[ 'level_id' ] ], [ 'member_id', '=', $order_info[ 'buyer_id' ] ] ]);
                if ($count == 0) {
                    //赠送红包
                    if ($level_info[ 'send_balance' ] > 0) {
                        $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'buyer_id' ], AccountDict::balance, $level_info[ 'send_balance' ], 'memberlevel', '会员开卡得红包' . $level_info[ 'send_balance' ], '会员开卡奖励发放');
                    }
                    //赠送积分
                    if ($level_info[ 'send_point' ] > 0) {
                        $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'buyer_id' ], 'point', $level_info[ 'send_point' ], 'memberlevel', '会员开卡得积分' . $level_info[ 'send_point' ], '会员开卡奖励发放');
                    }
                    //给用户发放优惠券
                    if (!empty($level_info[ 'send_coupon' ])) {
                        $coupon_array = explode(',', $level_info[ 'send_coupon' ]);
                        $coupon_model = new Coupon();
                        $coupon_array = array_map(function($value) {
                            return [ 'coupon_type_id' => $value, 'num' => 1 ];
                        }, $coupon_array);
                        $coupon_model->giveCoupon($coupon_array, $order_info[ 'site_id' ], $order_info[ 'buyer_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
                    }
                }

                if ($member_info[ 'member_level' ] != $level_info[ 'level_id' ]) {
                    if ($order_info[ 'period_unit' ] == 'quarter') {
                        $expire_time = strtotime("+3 month");
                    } else {
                        $expire_time = strtotime("+{$order_info['buy_num']} {$order_info['period_unit']}");
                    }
                    // 添加会员卡变更记录
                    $member_level_model = new MemberLevel();
                    $member_level_model->addMemberLevelChangeRecord($order_info[ 'buyer_id' ], $order_info[ 'site_id' ], $level_info[ 'level_id' ], $expire_time, 'buy', $order_info[ 'buyer_id' ], 'member', $member_info[ 'nickname' ]);
                } else {
                    $old_expire_time = date('Y-m-d', $member_info[ 'level_expire_time' ]);
                    if ($order_info[ 'period_unit' ] == 'quarter') {
                        $expire_time = strtotime("{$old_expire_time} +3 month");
                    } else {
                        $expire_time = strtotime("{$old_expire_time} +{$order_info['buy_num']} {$order_info['period_unit']}");
                    }
                    // 更新会员卡过期时间
                    model('member')->update([ 'level_expire_time' => $expire_time ], [ [ 'member_id', '=', $order_info[ 'buyer_id' ] ] ]);
                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'MemberLevelAutoExpire' ], [ 'relate_id', '=', $order_info[ 'buyer_id' ] ] ]);
                    $cron->addCron(1, 0, "会员卡自动过期", "MemberLevelAutoExpire", $expire_time, $order_info[ 'buyer_id' ]);
                }

                event('MemberLevelOrderPay', $order_info);

                $stat_model = new Stat();
                $stat_model->switchStat([ 'type' => 'member_level_order', 'data' => [ 'order_id' => $order_id, 'site_id' => $site_id ] ]);
                model('member_level_order')->commit();
                return $this->success();
            } catch (\Exception $e) {
                model('member_level_order')->rollback();
                return $this->error('', $e->getMessage());
            }
        }
    }

    /**
     * 生成订单编号
     * @param $site_id
     * @param int $member_id
     * @return string
     */
    public function createOrderNo($site_id, $member_id = 0)
    {
        $time_str = date('YmdHi');
        $max_no = Cache::get($site_id . "_" . $member_id . "_" . $time_str);
        if (empty($max_no)) {
            $max_no = 1;
        } else {
            $max_no = $max_no + 1;
        }
        $order_no = $time_str . $member_id . sprintf("%03d", $max_no);
        Cache::set($site_id . "_" . $member_id . "_" . $time_str, $max_no);
        return $order_no;
    }

    /**
     * 关闭会员卡订单
     */
    public function closeLevelOrder($order_id)
    {
        $res = model('member_level_order')->update([ 'order_status' => self::ORDER_CLOSE ], [ [ 'order_id', '=', $order_id ], [ 'order_status', '=', self::ORDER_CREATE ] ]);
        return $this->success($res);
    }

    /**
     * 获取会员卡订单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getLevelOrderPageList($condition = [], $page = 1, $list_rows = PAGE_LIST_ROWS, $field = '*', $order = 'create_time desc', $alias = 'a', $join = [])
    {
        $list = model('member_level_order')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取会员卡订单信息
     * @param array $where
     * @param bool $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getLevelOrderInfo($where = [], $field = '*', $alias = 'a', $join = null)
    {
        $data = model('member_level_order')->getInfo($where, $field, $alias, $join);
        return $this->success($data);
    }

    /**
     * 线下支付
     * @param $out_trade_no
     * @return array
     */
    public function offlinePay($out_trade_no)
    {
        model('member_level_order')->startTrans();
        try {
            $pay_model = new Pay();
            $result = $pay_model->onlinePay($out_trade_no, "offlinepay", '', '');
            if ($result[ "code" ] < 0) {
                model('order')->rollback();
                return $result;
            }
            model('order')->commit();
            return $result;
        } catch (\Exception $e) {
            model('member_level_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 查询订单总数
     * @param $condition
     * @return array
     */
    public function getOrderCount($condition, $field = '*', $alias = 'a', $join = null, $group = null)
    {
        $count = model('member_level_order')->getCount($condition, $field, $alias, $join, $group);
        return $this->success($count);
    }

    /**
     * 查询订单总数
     * @param $condition
     * @param $filed
     * @return array
     */
    public function getOrderSum($condition, $filed)
    {
        $sum = model('member_level_order')->getSum($condition, $filed);
        return $this->success($sum);
    }
}