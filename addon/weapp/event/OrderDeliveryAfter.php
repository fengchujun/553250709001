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
 * 订单发货完成，小程序发货信息录入
 */
class OrderDeliveryAfter
{
    public function handle($data)
    {
        //支付后立即调用发货接口，微信会提示订单不存在，所以延迟一分钟执行，如果是
        //{"errcode":10060001,"errmsg":"支付单不存在 rid: 66235dcf-4803e8cf-5c30a69e"}
        //如果是物流发货和同城配送不会有问题，自提订单和虚拟订单有可能支付后就立即发货
        (new Cron())->addCron(1, 0, "订单发货后小程序发货", "OrderDeliveryAfterWeappDelivery", time() + 60, $data[ 'order_id' ]);
    }
}