<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\memberrecharge\event\ShowPromotion',
        ],
        //订单支付回调事件
        'MemberrechargeOrderPayNotify' => [
            'addon\memberrecharge\event\MemberrechargeOrderPayNotify',
        ],
        //关闭订单事件
        'MemberrechargeOrderClose' => [
            'addon\memberrecharge\event\MemberrechargeOrderClose',
        ],

        'MemberAccountFromType' => [
            'addon\memberrecharge\event\MemberAccountFromType',
        ],
        'PrinterTemplateType' => [
            'addon\memberrecharge\event\PrinterTemplateType',
        ],
        'PrinterHtml' => [
            'addon\memberrecharge\event\PrinterHtml',
        ],
        //打印数据
        'PrinterContent' => [
            'addon\memberrecharge\event\PrinterContent',
        ],
        'IncomeStatistics' => [
            'addon\memberrecharge\event\IncomeStatistics'
        ],
        // 收银订单奖励和计算
        'CashierCalculate' => [
            'addon\memberrecharge\event\CashierCalculate',
        ],
        //通过支付信息获取手机端订单详情路径
        'WapOrderDetailPathByPayInfo' => [
            'addon\memberrecharge\event\WapOrderDetailPathByPayInfo',
        ],
    ],

    'subscribe' => [
    ],
];
