<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\notes\event;

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
                    'name' => 'notes',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type' => 'tool',
                    //展示主题
                    'title' => '店铺笔记',
                    //展示介绍
                    'description' => '好物分享引流促成交易',
                    //展示图标
                    'icon' => 'addon/notes/icon.png',
                    //跳转链接
                    'url' => 'notes://shop/notes/lists',
                ]
            ]

        ];
        return $data;
    }
}