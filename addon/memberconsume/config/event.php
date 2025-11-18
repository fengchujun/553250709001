<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        'OrderPayAfter'          => [
            'addon\memberconsume\event\OrderPayAfter',
        ],
        'GiftCardOrderPay'          => [
            'addon\memberconsume\event\OrderPayAfter',
        ],
        'BlindboxGoodsOrderPay'          => [
            'addon\memberconsume\event\OrderPayAfter',
        ],
        //会员行为事件
        'MemberAction'      => [
            'addon\memberconsume\event\MemberAction',
        ],
        //展示活动
        'ShowPromotion'     => [
            'addon\memberconsume\event\ShowPromotion',
        ],

        'MemberAccountFromType' => [
            'addon\memberconsume\event\MemberAccountFromType',
        ],

        'MemberAccountRule' => [
            'addon\memberconsume\event\MemberAccountRule',
        ],

        'OrderRefundFinish' => [
            'addon\memberconsume\event\OrderRefundFinish'
        ],
        'PointRule' => [
            'addon\memberconsume\event\PointRule',
        ]
    ],

    'subscribe' => [
    ],
];
