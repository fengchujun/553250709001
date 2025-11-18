<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\pointcash\event;

use addon\pointcash\model\Config;

/**
 * 积分规则
 */
class PointRule
{

    public function handle($data)
    {
        $info   = (new Config())->getPointCashConfig($data['site_id'])['data'];

        $data = [
            'title' => '积分抵现',
            'content' => !$info['is_use'] || !$info['value']['cash_rate'] ? '-' : $info['value']['cash_rate'] . "积分可抵1元",
            'url' => 'pointcash://shop/config/index',
            'update_time' => $info['modify_time']
        ];
        return $data;
    }
}