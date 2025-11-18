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
use app\model\order\OrderCron;
use app\model\system\Cron;

/**
 * 订单自动关闭
 */
class CronOrderClose
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {
        $order = new OrderCommon();
        $order_info = $order->getOrderInfo([ ['order_id', '=', $data['relate_id'] ] ], '*')['data'] ?? [];
        if (!empty($order_info) && $order_info['order_status'] == 0 && $order_info['pay_type'] != 'offlinepay') {
            $result = $order->orderClose($data['relate_id'], [], '长时间未支付,订单自动关闭');//订单自动关闭
            //todo 如果关闭失败,就再创建一个自动关闭任务
            if(empty($result) && empty($result['code']) && $result['code'] < 0){
                OrderCron::close(['site_id' => $order_info['site_id'], 'order_id' => $order_info['order_id']]);
            }
            return $result;
        }
    }
}