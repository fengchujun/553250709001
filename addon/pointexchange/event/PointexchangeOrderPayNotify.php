<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */


namespace addon\pointexchange\event;

use addon\pointexchange\model\Order;
/**
 * 积分兑换订单异步回调执行
 */
class PointexchangeOrderPayNotify
{
    
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $order = new Order();
        $order->orderPay($data);
	}
	
}