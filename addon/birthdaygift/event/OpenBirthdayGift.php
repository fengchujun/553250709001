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

use addon\birthdaygift\model\BirthdayGift;

/**
 * 启动活动
 */
class OpenBirthdayGift
{

    public function handle($params)
    {
        $model = new BirthdayGift();
        $res     = $model->cronOpenBirthdayGift($params['relate_id']);
        return $res;
    }
}