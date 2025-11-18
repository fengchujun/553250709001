<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\turntable\event\ShowPromotion',
        ],

        'PromotionType'  => [
            'addon\turntable\event\PromotionType',
        ],

        'MemberAccountFromType' => [
            'addon\turntable\event\MemberAccountFromType',
        ]
    ],

    'subscribe' => [
    ],
];
