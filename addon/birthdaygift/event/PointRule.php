<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\birthdaygift\event;

/**
 * 积分规则
 */
class PointRule
{

    public function handle($data)
    {
        $info = model("promotion_birthdaygift")->getInfo([ ['status', '=', 1], ['site_id', '=', $data['site_id']]], '*');

        $data = [
            'title' => '会员生日礼',
            'content' => empty($info) || !strstr($info['type'], 'point') ? '-' : "会员生日，赠送" . $info['point'] . "积分",
            'url' => 'birthdaygift://shop/birthdaygift/lists',
            'update_time' => empty($info) ? 0 : $info['update_time']
        ];
        return $data;
    }
}