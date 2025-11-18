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
 * 小程序菜单
 */
class WeappMenu
{

    /**
     * 小程序菜单
     *
     * @return multitype:number unknown
     */
    public function handle()
    {
        $data = [
            'title'       => '微信圈子',
            'description' => '朋友间的好物分享',
            'url'         => 'goodscircle://shop/config/index',
            'icon'        => 'addon/goodscircle/icon.png'
        ];
        return $data;
    }
}