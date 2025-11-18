<?php

namespace app\job\order_refund;

use app\model\order\orderrefund\Apply;
use app\model\order\orderrefund\Refuse;
use think\facade\Log;
use think\queue\Job;

/**
 * 订单拒绝退款后事件
 */
class OrderRefundRefuseAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            //退款拒绝后事件
            Refuse::after($data);
        } catch (\Exception $e) {
            Log::write(__CLASS__.$e->getMessage());
            $job->delete();
        }
    }

}
