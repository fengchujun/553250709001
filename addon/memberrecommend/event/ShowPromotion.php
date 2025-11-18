<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecommend\event;

/**
 * 活动展示
 */
class ShowPromotion
{

    /**
     * 活动展示
     * @return array
     */
    public function handle()
    {
        $data = [
            'shop' => [
                [
                    //插件名称
                    'name' => 'memberrecommend',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type' => 'member',
                    //展示主题
                    'title' => '邀请奖励',
                    //展示介绍
                    'description' => '邀请新客发放奖励',
                    //展示图标
                    'icon' => 'addon/memberrecommend/icon.png',
                    //跳转链接
                    'url' => 'memberrecommend://shop/memberrecommend/lists',
                ]
            ]

        ];
        return $data;
    }
}