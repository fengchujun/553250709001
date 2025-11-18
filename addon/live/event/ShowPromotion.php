<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\live\event;

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
                    'name'        => 'live',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type'   => 'tool',
                    //展示主题
                    'title'       => '小程序直播',
                    //展示介绍
                    'description' => '商家直播带货渠道',
                    //展示图标
                    'icon'        => 'addon/live/icon.png',
                    //跳转链接
                    'url'         => 'live://shop/room/index',
                ]
            ]
        ];
        return $data;
    }
}