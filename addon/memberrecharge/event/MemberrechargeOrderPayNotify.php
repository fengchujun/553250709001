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
 * 充值订单回调
 */
class MemberrechargeOrderPayNotify
{

    public function handle($data)
    {
        $model = new MemberrechargeOrder();
        $res = $model->orderPay($data);
        return $res;
    }

}