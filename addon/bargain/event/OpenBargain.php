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

use addon\bargain\model\Bargain;

/**
 * 启动活动
 */
class OpenBargain
{

    public function handle($params)
    {
        $bargain = new Bargain();
        $res = $bargain->cronOpenBargain($params[ 'relate_id' ]);
        return $res;
    }
}