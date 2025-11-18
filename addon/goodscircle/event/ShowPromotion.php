<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\goodscircle\event;

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
                    'name'        => 'goodscircle',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type'   => 'tool',
                    //展示主题
                    'title'       => '微信圈子',
                    //展示介绍
                    'description' => '由于微信接口限制已停用，该插件无法使用，客户分享到微信好物圈',
                    //展示图标
                    'icon'        => 'addon/goodscircle/icon.png',
                    //跳转链接
                    'url'         => 'goodscircle://shop/config/index',
                ]
            ]
        ];
        return $data;
    }
}