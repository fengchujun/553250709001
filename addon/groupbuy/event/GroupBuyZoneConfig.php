<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\event;


/**
 * 活动专区——团购页面配置
 */
class GroupBuyZoneConfig
{

    public function handle($params)
    {
        if (empty($params) || $params[ 'name' ] == 'groupbuy') {
            $data = [
                'name' => 'groupbuy', // 标识
                'title' => '团购', // 名称
                'url' => 'shop/adv/lists?keyword=NS_GROUPBUY', // 自定义跳转链接
                'preview' => 'addon/groupbuy/shop/view/public/img/zone_preview.png', // 预览图
                // 页面配置
                'value' => [
                    'bg_color' => '#E64136'
                ],
            ];
            return $data;
        }
    }

}