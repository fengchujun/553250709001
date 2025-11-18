<?php

namespace app\command;

use app\dict\system\ScheduleDict;
use app\model\system\Cron;
use think\facade\Log;
use yunwuxin\cron\Task;

class Schedule extends Task
{

    public function configure()
    {
        $this->everyMinute(); //设置任务的周期，每分钟执行一次
    }

    /**
     * 执行任务
     * @return void
     */
    protected function execute()
    {
        //...具体的任务执行
        $cron_model = new Cron();
        $cron_model->execute(ScheduleDict::cli);
    }
}
