<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //会员行为事件
        'MemberAction'   => [
            'addon\memberregister\event\MemberAction',
        ],
        //展示活动
        'ShowPromotion'  => [
            'addon\memberregister\event\ShowPromotion',
        ],
        //会员注册奖励
        'MemberRegisterAward' => [
            'addon\memberregister\event\MemberRegisterAward',
        ],

        'MemberAccountRule' => [
            'addon\memberregister\event\MemberAccountRule',
        ],
        'MemberReceiveRegisterGift' => [
            'addon\memberregister\event\MemberReceiveRegisterGift',
        ],
        'PointRule' => [
            'addon\memberregister\event\PointRule',
        ]
    ],

    'subscribe' => [
    ],
];
