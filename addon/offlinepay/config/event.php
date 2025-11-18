<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //支付方式，后台查询
        'PayType' => [
            'addon\offlinepay\event\PayType'
        ],
        'Pay' => [
            'addon\offlinepay\event\Pay'
        ],
        'PayClose' => [
            'addon\offlinepay\event\PayClose'
        ],
        'PayRefund' => [
            'addon\offlinepay\event\PayRefund'
        ],
        'SendMessageTemplate' => [
            'addon\offlinepay\event\MessageOfflinepayWaitAudit',
            'addon\offlinepay\event\MessageOfflinepayAuditRefuse',
        ],
    ],

    'subscribe' => [
    ],
];
