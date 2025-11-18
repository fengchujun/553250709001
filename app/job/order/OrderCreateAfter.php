<?php

namespace app\job\order;

use app\model\order\OrderCreate;
use think\facade\Log;
use think\queue\Job;

/**
 * 订单创建后事件
 */
class OrderCreateAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            $create_data = $data['create_data'];
            $order_object = (new OrderCreate())->invokeClass($create_data);
            //订单创建后事件
            event('OrderCreateAfter', ['order_object' => $order_object,  'create_data' => $data['create_data']]);
        } catch (\Exception $e) {
            Log::write('OrderCreateAfter_error_'.$e->getMessage());
            $job->delete();
        }
    }

}
