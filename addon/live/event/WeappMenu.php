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
 * 小程序菜单
 */
class WeappMenu
{

    /**
     * 小程序菜单
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if (addon_is_exit('live', $param[ 'site_id' ])) {
            $data = [
                'title' => '小程序直播',
                'description' => '在小程序中实现直播互动与商品销售闭环',
                'url' => 'live://shop/room/index',
                'icon' => 'addon/live/icon.png'
            ];
            return $data;
        }
    }
}