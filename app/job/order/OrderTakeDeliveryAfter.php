<?php

namespace app\job\order;

use think\facade\Log;
use think\queue\Job;

class OrderTakeDeliveryAfter
{
    /**
     * 订单收货后自动执行事件
     * @param Job $job
     * @param $data
     */
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            event('OrderTakeDeliveryAfter', [ 'order_id' => $data[ 'order_id' ], 'site_id' => $data[ 'site_id' ] ]);

        } catch (\Exception $e) {
            Log::write($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}
