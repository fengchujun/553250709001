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

use addon\supermember\model\MemberLevelOrder;


/**
 * 关闭会员卡订单
 */
class MemberLevelOrderClose
{

    public function handle($param)
    {
        $order = new MemberLevelOrder();
        $res   = $order->closeLevelOrder($param['relate_id']);
        return $res;
    }

}