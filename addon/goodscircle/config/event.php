<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\goodscircle\event\ShowPromotion',
        ],
        'WeappMenu'     => [
            'addon\goodscircle\event\WeappMenu',
        ]
    ],

    'subscribe' => [
    ],
];
