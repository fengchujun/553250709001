<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
return [
    'bind' => [

    ],

    'listen' => [
        //支付异步回调
        'PayNotify' => [
            'addon\alipay\event\PayNotify'
        ],
        //支付方式，后台查询
        'PayType' => [
            'addon\alipay\event\PayType'
        ],
        //支付，前台应用
        'Pay' => [
            'addon\alipay\event\Pay'
        ],
        'PayClose' => [
            'addon\alipay\event\PayClose'
        ],
        'PayRefund' => [
            'addon\alipay\event\PayRefund'
        ],
        'PayTransfer' => [
            'addon\alipay\event\PayTransfer'
        ],
        'TransferType' => [
            'addon\alipay\event\TransferType'
        ],
        'AuthcodePay' => [
            'addon\alipay\event\AuthcodePay'
        ],
        'PayOrderQuery' => [
            'addon\alipay\event\PayOrderQuery'
        ],
    ],

    'subscribe' => [
    ],
];
