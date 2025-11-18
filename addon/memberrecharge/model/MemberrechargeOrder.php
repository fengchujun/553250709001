<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\model;

use addon\weapp\model\Weapp;
use app\model\BaseModel;
use addon\coupon\model\CouponType;
use app\model\member\Member;
use app\model\system\Stat;
use think\facade\Cache;
use think\facade\Log;

/**
 * 订单
 */
class MemberrechargeOrder extends BaseModel
{

    /**
     * 基础支付方式(不考虑实际在线支付方式或者货到付款方式)
     * @var unknown
     */
    public $pay_type = [

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
        $onlinepay = event('PayType');
        if (!empty($onlinepay)) {
            foreach ($onlinepay as $k => $v) {
                $pay_type[ $v[ 'pay_type' ] ] = $v[ 'pay_type_name' ];
            }
        }
        $trade_pay_type_list = event('TradePayType', []);
        if (!empty($trade_pay_type_list)) {
            foreach ($trade_pay_type_list as $k => $v) {
                if (!empty($v)) {
                    $pay_type = array_merge($pay_type, $v);
                }
            }
        }
        return $pay_type;
    }

    public function addMemberRechargeOrder($data)
    {
        $res = model('member_recharge_order')->add($data);
        return $this->success($res);
    }

    /**
     * 订单详情
     * @param array $condition
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getMemberRechargeOrderInfo($condition = [], $field = '*', $alias = 'a', $join = [])
    {
        $order = model('member_recharge_order')->getInfo($condition, $field, $alias, $join);
        if ($order) {
            //获取优惠券信息
            $coupon_id = $order[ 'coupon_id' ] ?? 0;
            if ($coupon_id > 0) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term,type';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $order[ 'coupon_id' ] ] ], $coupon_field);
                $order[ 'coupon_list' ] = $coupon;
            }

        }

        return $this->success($order);
    }

    /**
     * 订单列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getMemberRechargeOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('member_recharge_order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        if (!empty($list[ 'list' ])) {
            $coupon_ids = '';
            foreach ($list[ 'list' ] as $k => $v) {
                if (!empty($v[ 'coupon_id' ])) {
                    $coupon_ids = empty($coupon_ids) ? $v[ 'coupon_id' ] : $coupon_ids . ',' . $v[ 'coupon_id' ];
                }
            }
            $coupon_list = [];
            if (!empty($coupon_ids)) {
                $coupon_list = model('promotion_coupon_type')->getList([ [ 'coupon_type_id', 'in', $coupon_ids ] ], 'coupon_type_id,coupon_name');

            }
            if (!empty($coupon_list)) {
                $key = array_column($coupon_list, 'coupon_type_id');
                $coupon_list = array_combine($key, $coupon_list);
            }

            foreach ($list[ 'list' ] as $k => $v) {
                $list[ 'list' ][ $k ][ 'coupon_name' ] = '';
                $coupon_name = '';
                if (!empty($v[ 'coupon_id' ])) {
                    $coupon_array = explode(',', $v[ 'coupon_id' ]);
                    foreach ($coupon_array as $value) {
                        $coupon_name_value = $coupon_list[ $value ][ 'name' ] ?? '';
                        $coupon_name .= ',' . $coupon_name_value;
                    }
                    $coupon_name = ltrim($coupon_name, ',');
                }
                $list[ 'list' ][ $k ][ 'coupon_name' ] = $coupon_name;
            }
        }
        return $this->success($list);
    }

    /**
     * 支付回调
     * @param $data
     * @return array|\multitype
     */
    public function orderPay($data)
    {
        $order_field = 'order_id,recharge_id,recharge_name,order_no,cover_img,face_value,buy_price,point,growth,coupon_id,price,pay_type,status,create_time,pay_time,member_id,member_img,nickname,site_id,out_trade_no,order_from';
        $order_info = $this->getMemberRechargeOrderInfo([ [ 'out_trade_no', '=', $data[ 'out_trade_no' ] ] ], $order_field)[ 'data' ];
        if ($order_info[ 'status' ] == 1) {
            model('member_recharge_order')->startTrans();
            try {

                $pay_list = $this->getPayType();
                $pay_type_name = '';
                if (!empty($data[ 'pay_type' ])) {
                    $pay_type_name = $pay_list[ $data[ 'pay_type' ] ];
                }

                //修改订单状态
                $order_data = [
                    'pay_type' => $data[ 'pay_type' ],
                    'pay_type_name' => $pay_type_name,
                    'pay_time' => time(),
                    'price' => $order_info[ 'buy_price' ],
                    'status' => 2
                ];
                $res = model('member_recharge_order')->update($order_data, [ [ 'out_trade_no', '=', $data[ 'out_trade_no' ] ] ]);

                //添加开卡记录
                $card_model = new MemberRechargeCard();
                $order_info[ 'use_status' ] = 2;
                $order_info[ 'use_time' ] = time();
                $card_model->addMemberRechargeCard($order_info);

                //发放礼包
                $card_model->addMemberAccount($order_info);

                //获取套餐信息
                $recharge_model = new Memberrecharge();
                $recharge_info = $recharge_model->getMemberRechargeInfo([ [ 'recharge_id', '=', $order_info[ 'recharge_id' ] ] ], 'sale_num,coupon_id');
                //增加发放数
                if (!empty($recharge_info[ 'data' ])) {
                    $sale_num = $recharge_info[ 'data' ][ 'sale_num' ] + 1;
                    $recharge_model->editMemberRecharge([ [ 'recharge_id', '=', $order_info[ 'recharge_id' ] ] ], [ 'sale_num' => $sale_num ]);
                }
                event('MemberRechargeOrderPay', $order_info);
                $is_stat = $data[ 'is_stat' ] ?? true;
                if ($is_stat) {
                    $stat_model = new Stat();
                    $stat_res = $stat_model->switchStat([
                        'type' => 'recharge',
                        'data' => [
                            'order_id' => $order_info[ 'order_id' ],
                            'site_id' => $order_info[ 'site_id' ]
                        ]
                    ]);
                }
                model('member_recharge_order')->commit();
                return $this->success($res);
            } catch (\Exception $e) {
                model('member_recharge_order')->rollback();
                dd($e->getMessage() . $e->getFile() . $e->getLine());
                Log::write('memberrechargeerr' . json_encode($e->getMessage() . $e->getFile() . $e->getLine()));
                return $this->error('', $e->getMessage());
            }
        } else {
            return $this->success(true);
        }

    }

    /**
     * 定时关闭订单
     * @param $order_id
     * @return array
     */
    public function cronMemberRechargeOrderClose($order_id)
    {
        //获取订单信息
        $order_info = $this->getMemberRechargeOrderInfo([ [ 'order_id', '=', $order_id ] ], 'status')[ 'data' ];
        if (empty($order_info)) {
            $res = true;
        } else {
            if ($order_info[ 'status' ] == 1) {
                //删除订单
                $res = model('member_recharge_order')->delete([ [ 'order_id', '=', $order_id ] ]);
                Cache::tag("member_recharge_order")->clear();
            } else {
                $res = true;
            }
        }
        return $this->success($res);
    }

    /**
     * 获取总数
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @param null $group
     * @return array
     */
    public function getOrderCount($where = [], $field = '*', $alias = 'a', $join = null, $group = null)
    {
        $res = model('member_recharge_order')->getCount($where, $field, $alias, $join, $group);
        return $this->success($res);
    }

    /**
     * 获取总和
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getOrderSum($where = [], $field = '*', $alias = 'a', $join = null)
    {
        $res = model('member_recharge_order')->getSum($where, $field, $alias, $join);
        return $this->success($res);
    }
}