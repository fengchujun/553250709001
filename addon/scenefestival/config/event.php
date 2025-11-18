<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\scenefestival\event\ShowPromotion',
        ],
        'cronOpenFestival' => [
            'addon\scenefestival\event\OpenFestival',
        ],
        'cronCloseFestival' => [
            'addon\scenefestival\event\CloseFestival',
        ],
    ],

    'subscribe' => [
    ],
];
