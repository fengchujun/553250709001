<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\order;

use app\model\order\OrderCommon;

/**
 * 订单完成之后达到时间自动关闭售后服务
 */
class CronOrderAfterSaleClose
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {
        $order = new OrderCommon();
        //订单自动完成
        return $order->orderAfterSaleClose($data["relate_id"]);
    }
}