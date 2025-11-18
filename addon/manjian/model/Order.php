<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\manjian\model;

use addon\coupon\model\Coupon;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\order\Order as BaseOrder;
use app\model\order\OrderCommon;
use think\facade\Log;

class Order extends BaseModel
{
    /**
     * 订单完成发放满减送所送积分
     * @param $order_id
     */
    public function orderPay($order_info)
    {
        $pay_status = $order_info['pay_status'];
        if($pay_status == 1){
            $member_id = $order_info[ 'member_id' ];
            $order_id = $order_info['order_id'];
            //存在散客的情况
            if ($member_id > 0) {
                $mansong_record = model('promotion_mansong_record')->getList([ [ 'order_id', '=', $order_id ], [ 'status', '=', 0 ] ]);
                if (!empty($mansong_record)) {
                    model('promotion_mansong_record')->startTrans();
                    foreach ($mansong_record as $item) {
                        try {
                            // 发放积分
                            if (!empty($item[ 'point' ])) {
                                $member_account = new Memberaccount();
                                $member_account->addMemberAccount($item[ 'site_id' ], $item[ 'member_id' ], 'point', $item[ 'point' ], 'manjian', $item[ 'manjian_id' ], "活动奖励发放");
                            }
                            // 发放优惠券
                            if (!empty($item[ 'coupon' ])) {
                                $coupon = new Coupon();
                                $coupon_list = explode(',', $item[ 'coupon' ]);
                                $coupon_num = explode(',', $item[ 'coupon_num' ]);
                                $coupon_data = [];
                                foreach ($coupon_list as $k => $coupon_item) {
                                    $coupon_data[] = [
                                        'coupon_type_id' => $coupon_item,
                                        'num' => $coupon_num[ $k ] ?? 1
                                    ];
                                }
                                $coupon->giveCoupon($coupon_data, $item[ 'site_id' ], $item[ 'member_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE, $item['id']);
                            }
                            // 变更发放状态
                            model('promotion_mansong_record')->update([ 'status' => 1 ], [ [ 'id', '=', $item[ 'id' ] ] ]);
                            model('promotion_mansong_record')->commit();
                        } catch (\Exception $e) {
                            model('promotion_mansong_record')->rollback();
                        }
                    }
                }
            }
        }
    }
}