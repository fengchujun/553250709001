<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\event;

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
        if (addon_is_exit('shopcomponent', $param['site_id'])) {
            $data = [
                'title' => '微信视频号',
                'description' => '实现小程序与视频号的连接',
                'url' => 'shopcomponent://shop/goods/lists',
                'icon' => 'addon/shopcomponent/icon.png'
            ];
            return $data;
        }
    }
}