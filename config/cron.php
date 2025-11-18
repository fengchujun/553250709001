<?php

return [
    'default' => 'default',//url URL接口启动 cli 命令启动  default 系统任务
    'tasks' => [
        \app\command\Schedule::class
    ]
];