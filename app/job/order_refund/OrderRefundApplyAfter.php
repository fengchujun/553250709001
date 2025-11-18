<?php

namespace app\job\order_refund;

use app\model\order\OrderCreate;
use app\model\order\orderrefund\Apply;
use app\model\system\Cron;
use think\facade\Log;
use think\queue\Job;

/**
 * 订单申请退款后事件
 */
class OrderRefundApplyAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            //退款申请后事件
            Apply::after($data);
        } catch (\Exception $e) {
            Log::write(__CLASS__.$e->getMessage());
            $job->delete();
        }
    }

}
