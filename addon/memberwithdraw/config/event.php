<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //转账结果通知
        'PayTransferNotify' => [
            'addon\memberwithdraw\event\WithdrawTransferNotify',
        ],
        'PayTransferCheck' => [
            'addon\memberwithdraw\event\WithdrawTransferCheck',
        ],
    ],

    'subscribe' => [
    ],
];
