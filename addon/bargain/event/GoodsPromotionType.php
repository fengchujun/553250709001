<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\event;

/**
 * 活动类型
 */
class GoodsPromotionType
{

    /**
     * 活动类型
     * @return array
     */
    public function handle()
    {
        return [ 'name' => '砍价', 'short' => '砍', 'type' => 'bargain', 'color' => '#F58760', 'url' => 'bargain://shop/bargain/lists' ];
    }
}