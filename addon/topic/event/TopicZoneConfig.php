<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\event;


/**
 * 活动专区——专题活动页面配置
 */
class TopicZoneConfig
{

    public function handle($params)
    {
        if (empty($params) || $params[ 'name' ] == 'topic') {
            $data = [
                'name' => 'topic', // 标识
                'title' => '专题活动', // 名称
                'url' => 'topic://shop/topic/lists', // 自定义跳转链接
                'preview' => 'addon/topic/shop/view/public/img/zone_preview.png', // 预览图
                // 页面配置
                'value' => [
                    'bg_color' => '#F4F4F4'
                ],
            ];
            return $data;
        }
    }

}