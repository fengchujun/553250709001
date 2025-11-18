<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //活动开启
        'OpenRecommend'   => [
            'addon\memberrecommend\event\OpenRecommend',
        ],
        //活动关闭
        'CloseRecommend'  => [
            'addon\memberrecommend\event\CloseRecommend',
        ],
        //展示活动
        'ShowPromotion' => [
            'addon\memberrecommend\event\ShowPromotion',
        ],
        'MemberAccountFromType' => [
            'addon\memberrecommend\event\MemberAccountFromType',
        ],
        //会员注册后执行事件
        'MemberRegister' => [
            'addon\memberrecommend\event\MemberRegister'
        ],
    ],

    'subscribe' => [
    ],
];
