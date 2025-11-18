<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\pintuan\event\ShowPromotion',
        ],
        //订单支付事件
        'OrderPay' => [
            'addon\pintuan\event\OrderPay',
        ],
        'OrderClose' => [
            'addon\pintuan\event\OrderClose',
        ],
        'PromotionType' => [
            'addon\pintuan\event\PromotionType',
        ],
        //开启拼团活动
        'OpenPintuan' => [
            'addon\pintuan\event\OpenPintuan',
        ],
        //关闭拼团活动
        'ClosePintuan' => [
            'addon\pintuan\event\ClosePintuan',
        ],
        //关闭拼团组
        'ClosePintuanGroup' => [
            'addon\pintuan\event\ClosePintuanGroup',
        ],
        // 商品营销活动类型
        'GoodsPromotionType' => [
            'addon\pintuan\event\GoodsPromotionType',
        ],

        // 商品营销活动信息
        'GoodsPromotion' => [
            'addon\pintuan\event\GoodsPromotion',
        ],

        // 商品列表
        'GoodsListPromotion' => [
            'addon\pintuan\event\GoodsListPromotion',
        ],
        // 商品列表
        'GoodsListCategoryIds' => [
            'addon\pintuan\event\GoodsListCategoryIds',
        ],

        // 订单营销活动类型
        'OrderPromotionType' => [
            'addon\pintuan\event\OrderPromotionType',
        ],
        /**
         * 消息发送
         */
        //消息模板
        'SendMessageTemplate' => [
            //拼团成功
            'addon\pintuan\event\MessagePintuanComplete',

            //拼团失败
            'addon\pintuan\event\MessagePintuanFail',
        ],

        // 活动专区——拼团页面配置
        'PromotionZoneConfig' => [
            'addon\pintuan\event\PintuanZoneConfig',
        ]
    ],

    'subscribe' => [
    ],
];
