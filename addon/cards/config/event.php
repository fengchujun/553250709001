<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\cards\event\ShowPromotion',
        ],

        'PromotionType' => [
            'addon\cards\event\PromotionType',
        ],

        'MemberAccountFromType' => [
            'addon\cards\event\MemberAccountFromType',
        ],
    ],

    'subscribe' => [
    ],
];
