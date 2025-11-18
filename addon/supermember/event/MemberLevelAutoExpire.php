<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\supermember\event;

use app\model\member\MemberLevel;

/**
 * 会员卡过期
 */
class MemberLevelAutoExpire
{

    public function handle($param)
    {
        $order = new MemberLevel();
        $res   = $order->memberLevelExpire($param['relate_id']);
        return $res;
    }

}