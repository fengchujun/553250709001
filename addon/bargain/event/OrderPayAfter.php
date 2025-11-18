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
 * 活动展示
 */
class OrderPayAfter
{

    /**
     * 活动展示
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if ($param['promotion_type'] == 'bargain') {
            $bargain_order = new Bargain();
            $res = $bargain_order->orderPay($param);
            return $res;
        }
    }
}