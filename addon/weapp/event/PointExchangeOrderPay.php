<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\event;

use app\model\system\Cron;

/**
 * 礼品卡订单支付后
 */
class PointExchangeOrderPay
{
    public function handle($param)
    {
        //类型 1商品 2优惠券 3红包 商品类型要在真实发货后才同步发货消息
        if($param['type'] != 1 && $param['exchange_price'] > 0){
            return (new Cron())->addCron(1, 0, "小程序虚拟发货", "WeappVirtualDelivery", time() + 60, $param['out_trade_no']);
        }
    }
}