<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //满减开启
        'OpenManjian'   => [
            'addon\manjian\event\OpenManjian',
        ],
        //满减关闭
        'CloseManjian'  => [
            'addon\manjian\event\CloseManjian',
        ],
        //展示活动
        'ShowPromotion' => [
            'addon\manjian\event\ShowPromotion',
        ],
        // 订单支付
        'OrderPayAfter' => [
            'addon\manjian\event\OrderPayAfter'
        ],
        //会员账户类型(操作来源)
        'MemberAccountFromType' => [
            'addon\manjian\event\MemberAccountFromType',
        ],
        //退款后收回奖励
        'OrderRefundAllFinish' => [
            'addon\manjian\event\OrderRefundAllFinish'
        ]
    ],

    'subscribe' => [
    ],
];
