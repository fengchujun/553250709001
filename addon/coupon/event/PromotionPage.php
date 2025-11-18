<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\coupon\event;

/**
 * 营销活动页面
 */
class PromotionPage
{
    public function handle($params)
    {
        $page_list = [
            [
                'name' => 'COUPON_DETAIL',
                'title' => '优惠券详情',
                'wap_url' => '/pages_tool/goods/coupon_receive?coupon_type_id=$coupon_type_id',
                'web_url' => '',
            ],
        ];
        return $page_list;
    }
}