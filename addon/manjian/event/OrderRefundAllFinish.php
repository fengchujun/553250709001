<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\manjian\event;

use addon\coupon\model\MemberCoupon;
use app\dict\member_account\AccountDict;
use app\model\member\MemberAccount;

/**
 * 订单完全退款后收回奖励
 */
class OrderRefundAllFinish
{

    public function handle($params)
    {
        $order_info = $params['order_info'];
        $member_id = $order_info['member_id'];
        $site_id = $order_info['site_id'];
        //散客不参与
        if ($member_id > 0) {
            $order_id = $order_info['order_id'];
            $list = model('promotion_mansong_record')->getList([['order_id', '=', $order_id], ['status', '=', 1]]);
            if (!empty($list)) {
                $member_coupon_model = new MemberCoupon();
                foreach ($list as $item) {
                    try {
                        // 发放积分
                        $point = $item[ 'point' ] ?? 0;
                        if ($point > 0) {
                            $member_account = new Memberaccount();
                            $member_account->addMemberAccount($site_id, $member_id, AccountDict::point, -$point, 'point_cancel', $item[ 'manjian_id' ], '活动奖励取消');
                        }
                        // 发放优惠券
                        $coupon = $item['coupon'] ?? '';
                        $coupon_num = $item['coupon_num'] ?? '';
                        if ($coupon && $coupon_num) {
                            $coupon_list = explode(',', $coupon);
                            $coupon_num = explode(',', $coupon_num);
                            $coupon_data = [];
                            foreach ($coupon_list as $k => $coupon_item) {
                                $coupon_data[] = [
                                    'coupon_type_id' => $coupon_item,
                                    'num' => $coupon_num[ $k ] ?? 1
                                ];
                            }
                            $member_coupon_model->cancelByPromotion([
                                'coupon_data' => $coupon_data,
                                'member_id' => $member_id,
                            ]);
                        }
                        // 定义为收回奖励
                        model('promotion_mansong_record')->update([ 'status' => 2 ], [ [ 'id', '=', $item[ 'id' ] ] ]);
                        model('promotion_mansong_record')->commit();
                    } catch (\Exception $e) {
                        model('promotion_mansong_record')->rollback();
                    }
                }
            }
        }
    }
}