<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion'     => [
            'addon\supermember\event\ShowPromotion',
        ],
        // 会员卡订单支付异步回调
        'MemberLevelOrderPayNotify' => [
            'addon\supermember\event\MemberLevelOrderPayNotify'
        ],
        // 订单支付
        "OrderPay" => [
            'addon\supermember\event\MemberLevelOrderPayNotify'
        ],
        // 会员卡订单定时关闭
        'MemberLevelOrderClose' => [
            'addon\supermember\event\MemberLevelOrderClose'
        ],
        // 会员卡自动过期
        'MemberLevelAutoExpire' => [
            'addon\supermember\event\MemberLevelAutoExpire'
        ],
        'CouponGetType' => [
            'addon\supermember\event\CouponGetType'
        ],
        'IncomeStatistics' => [
            'addon\supermember\event\IncomeStatistics'
        ],
        //统计写入
        'AddStat' => [
            'addon\supermember\event\AddStat',
        ],
        //通过支付信息获取手机端订单详情路径
        'WapOrderDetailPathByPayInfo' => [
            'addon\supermember\event\WapOrderDetailPathByPayInfo',
        ],
    ],

    'subscribe' => [
    ],
];
