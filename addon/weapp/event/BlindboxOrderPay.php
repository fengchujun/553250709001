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
class BlindboxOrderPay
{
    public function handle($param)
    {
        return (new Cron())->addCron(1, 0, "小程序虚拟发货", "WeappVirtualDelivery", time() + 60, $param['out_trade_no']);
    }
}