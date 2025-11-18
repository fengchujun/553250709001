<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */


namespace addon\v3tov4\event;

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
                    'name' => 'v3tov4',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type' => 'tool',
                    //展示主题
                    'title' => 'v3Tov4迁移数据',
                    //展示介绍
                    'description' => '商城V3版数据迁移到V4版',
                    //展示图标
                    'icon' => 'addon/v3tov4/icon.png',
                    //跳转链接
                    'url' => 'v3tov4://shop/upgrade/index',
                ]
            ]

        ];
	    return $data;
	}
}