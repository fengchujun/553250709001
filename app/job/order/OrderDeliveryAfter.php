<?php

namespace app\job\order;

use think\facade\Log;
use think\queue\Job;

class OrderDeliveryAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            event('OrderDeliveryAfter', ['order_id' => $data[ 'order_id' ], 'site_id' => $data[ 'site_id' ]]);
        } catch (\Exception $e) {
            Log::write($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}
