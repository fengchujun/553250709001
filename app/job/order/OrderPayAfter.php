<?php

namespace app\job\order;

use think\facade\Log;
use think\queue\Job;

class OrderPayAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            (new \app\model\order\event\OrderPay())->after($data);

        } catch (\Exception $e) {
            Log::write($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}
