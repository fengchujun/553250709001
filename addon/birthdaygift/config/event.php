<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\birthdaygift\event\ShowPromotion',
        ],
        'PointRule' => [
            'addon\birthdaygift\event\PointRule',
        ],
        //活动开启
        'OpenBirthdayGift'   => [
            'addon\birthdaygift\event\OpenBirthdayGift',
        ],
        //活动关闭
        'CloseBirthdayGift'  => [
            'addon\birthdaygift\event\CloseBirthdayGift',
        ],
    ],

    'subscribe' => [
    ],
];
