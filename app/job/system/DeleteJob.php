<?php

namespace app\job\system;

use think\facade\Log;
use think\queue\Job;

/**
 * 任务删除事件
 */
class DeleteJob
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            $file = $data['file'];
            @unlink($file);
            return true;
        } catch (\Exception $e) {
            Log::write($e->getMessage());
            $job->delete();
        }
    }

}
