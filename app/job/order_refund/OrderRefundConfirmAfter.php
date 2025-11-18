<?php

namespace app\job\order_refund;

use app\model\order\orderrefund\Cancel;
use app\model\order\orderrefund\Confirm;
use think\facade\Log;
use think\queue\Job;

/**
 * 订单通过退款后事件
 */
class OrderRefundConfirmAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            //退款审核后事件
            Confirm::after($data);
        } catch (\Exception $e) {
            Log::write(__CLASS__.$e->getMessage());
            $job->delete();
        }
    }

}
