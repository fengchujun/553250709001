<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\form\event\ShowPromotion',
        ],
        'OrderPayment' => [
            'addon\form\event\OrderPayment',
        ],
        'OrderCreateAfter' => [
            'addon\form\event\OrderCreateAfter',
        ]
    ],

    'subscribe' => [
    ],
];
