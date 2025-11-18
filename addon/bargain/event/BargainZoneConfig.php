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
 * 活动专区——砍价页面配置
 */
class BargainZoneConfig
{

    public function handle($params)
    {
        if (empty($params) || $params[ 'name' ] == 'bargain') {
            $data = [
                'name' => 'bargain', // 标识
                'title' => '砍价', // 名称
                'url' => 'shop/adv/lists?keyword=NS_BARGAIN', // 自定义跳转链接
                'preview' => 'addon/bargain/shop/view/public/img/zone_preview.png', // 预览图
                // 页面配置
                'value' => [
                    'bg_color' => '#F0353E'
                ],
            ];
            return $data;
        }
    }
}