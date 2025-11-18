<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\divideticket\event\ShowPromotion',
        ],
        #关闭活动
        'CloseDivideTicket' => [
            'addon\divideticket\event\CloseDivideticket',
        ],
        #更改瓜分活动状态
        'CronChangeDivideticketStatus' => [
            'addon\divideticket\event\CronChangeDivideticketStatus'
        ],
        #关闭到时的瓜分任务
        'DivideticketLaunchClose'=>[
          'addon\divideticket\event\DivideticketLaunchClose'
        ],
        #模拟瓜分
        'DivideticketSimulation'=>[
            'addon\divideticket\event\DivideticketSimulation'
        ]
    ],

    'subscribe' => [
    ],
];
