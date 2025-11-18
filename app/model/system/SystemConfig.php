<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use think\facade\Queue;

/**
 * 系统配置
 */
class SystemConfig extends BaseModel
{

    /**
     * 系统配置
     * @param int $site_id
     * @return array
     */
    public function getSystemConfig($site_id = 0)
    {
        return $this->success([ 'is_open_queue' => 0 ]);
    }


    /**
     * 校验消息队列是否正常运行
     * @return bool
     */
    public function checkJob()
    {
        $queue_default = config('queue.default');
        if($queue_default != 'sync'){
            $secret = uniqid('', true);
            $file = root_path('runtime') . $secret . '.job';
            try {
                Queue::push('app\job\system\CheckJob', [ 'file' => $file ]);
            } catch ( \Exception $e) {
                return false;
            }
            sleep(3);
            if (file_exists($file)) {
                @unlink($file);
                return true;
            }
            return false;
        }else{
            return true;
        }
    }
}