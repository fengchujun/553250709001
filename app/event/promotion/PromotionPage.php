<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\promotion;

/**
 * 营销活动页面
 */
class PromotionPage
{
    public function handle($params)
    {
        $page_list = [
            [
                'name' => 'GOODS_DETAIL',
                'title' => '商品详情',
                'wap_url' => '/pages/goods/detail?goods_id=$goods_id',
                'web_url' => '',
            ],
        ];
        return $page_list;
    }
}