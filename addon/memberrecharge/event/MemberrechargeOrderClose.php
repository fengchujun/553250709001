<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberrecharge\event;

use addon\memberrecharge\model\MemberrechargeOrder;

/**
 * 订单支付回调
 */
class MemberrechargeOrderClose
{

    public function handle($params)
    {
        $order = new MemberrechargeOrder();
        $res   = $order->cronMemberRechargeOrderClose($params['relate_id']);
        return $res;
    }
}