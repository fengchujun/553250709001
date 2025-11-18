<?php

namespace app\job\system;

use app\model\system\Cron;
use think\facade\Log;
use think\facade\Queue;
use think\queue\Job;

/**
 * 校验任务
 */
class CheckJob
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            $file = $data['file'];
            file_put_contents($file, time());
            //todo 部署一个8秒后再校验一次删除这个文件
            Queue::later(8, 'app\job\system\DeleteJob', $data);
        } catch (\Exception $e) {
            Log::write($e->getMessage());
            $job->delete();
        }
    }

}
