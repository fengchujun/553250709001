<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\cron\controller;

use app\Controller;
use app\dict\system\ScheduleDict;
use app\model\system\Cron;
use think\facade\Cache;
use think\facade\Log;

/**
 * 计划任务
 * @author Administrator
 */
class Task extends Controller
{

    /**
     * php自动执行事件
     */
    public function cronExecute()
    {
        $url = url('cron/task/execute');
        http($url, 1);
    }

    /**
     *检测自动任务是否正在进行
     */
    public function checkCron()
    {
        $cron_model = new Cron();
        $result = $cron_model->checkSchedule();
        return $result;
    }

    /**
     * 运行计划任务(用于服务器系统计划任务调用)
     */
    public function run()
    {
        if (config('cron.default') == ScheduleDict::url) {
            $cron_model = new Cron();
            $cron_model->execute(ScheduleDict::url);
            echo 1;
        }
    }

    /**
     * 执行计划任务(单独计划任务)
     */
    public function execute()
    {
        if (config('cron.default') == ScheduleDict::default) {
            ignore_user_abort(true);
            set_time_limit(0);
            //设置计划任务标识
            Log::write('检测事件执行：' . date('Y-m-d H:i:s', time()));
            $last_time = Cache::get('cron_last_load_time');
            if (empty($last_time)) {
                $last_time = 0;
            }
            $time = time();
            if (($time - $last_time) < 30) {
                Log::write('防止多次执行');
                exit();//跳出
            }
            Cache::set('cron_last_load_time', time());
            $cron_model = new Cron();
            $cron_model->execute('default');
            sleep(30);
            $url = url('cron/task/execute');
            http($url, 1);
            exit();
        }
    }
}