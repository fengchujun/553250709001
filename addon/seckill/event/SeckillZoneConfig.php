<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\event;


/**
 * 活动专区——限时秒杀页面配置
 */
class SeckillZoneConfig
{

    public function handle($params)
    {
        if (empty($params) || $params[ 'name' ] == 'seckill') {
            $data = [
                'name' => 'seckill', // 标识
                'title' => '限时秒杀', // 名称
                'url' => 'shop/adv/lists?keyword=NS_SECKILL', // 自定义跳转链接
                'preview' => 'addon/seckill/shop/view/public/img/zone_preview.png', // 预览图
                // 页面配置
                'value' => [
                    'bg_color' => '#F83530'
                ],
            ];
            return $data;
        }
    }

}