<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\manjian\event;

use addon\manjian\model\Order;
use think\facade\Log;

/**
 * 订单完成
 */
class OrderPayAfter
{

    public function handle($params)
    {
        $order = new Order();
        $order->orderPay($params);
    }
}