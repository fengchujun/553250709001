<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\giftcard\event\ShowPromotion',
        ],
        'GiftCardOrderClose' => [
            'addon\giftcard\event\GiftCardOrderClose',
        ],
        'GiftCardOrderPayNotify' => [
            'addon\giftcard\event\GiftCardOrderPayNotify',
        ],

        'IncomeStatistics' => [
            'addon\giftcard\event\IncomeStatistics'
        ],
        //统计写入
        'AddStat' => [
            'addon\giftcard\event\AddStat',
        ],
        'CronCardExpire' => [
            'addon\giftcard\event\CronCardExpire'
        ],
        'PayReset' => [
            'addon\giftcard\event\PayReset'
        ],
        'OrderPayAfter' => [
            'addon\giftcard\event\OrderPayAfter'
        ],
        'OrderPromotionType'      => [
            'addon\giftcard\event\OrderPromotionType',
        ],
        //通过支付信息获取手机端订单详情路径
        'WapOrderDetailPathByPayInfo' => [
            'addon\giftcard\event\WapOrderDetailPathByPayInfo',
        ],
    ],

    'subscribe' => [
    ],
];
