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

use addon\groupbuy\model\Groupbuy;

/**
 * 关闭活动
 */
class CloseGroupbuy
{

    public function handle($params)
    {
        $groupbuy = new Groupbuy();
        $res      = $groupbuy->cronCloseGroupbuy($params['relate_id']);
        return $res;
    }
}