<?php

namespace app\job\order_refund;

use app\model\order\orderrefund\Apply;
use app\model\order\orderrefund\Cancel;
use think\facade\Log;
use think\queue\Job;

/**
 * 订单取消退款后事件
 */
class OrderRefundCancelAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            //退款取消后事件
            Cancel::after($data);
        } catch (\Exception $e) {
            Log::write(__CLASS__.$e->getMessage());
            $job->delete();
        }
    }

}
