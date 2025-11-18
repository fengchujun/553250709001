<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        // 生成获取二维码
        'Qrcode' => [
            'addon\weapp\event\Qrcode'
        ],
        // 开放数据解密
        'DecryptData' => [
            'addon\weapp\event\DecryptData'
        ],
        // 获取手机号
        'PhoneNumber' => [
            'addon\weapp\event\PhoneNumber'
        ],
        // 发货成功
        'OrderDeliveryAfter' => [
            'addon\weapp\event\OrderDeliveryAfter'
        ],
        // 发货成功后小程序发货
        'OrderDeliveryAfterWeappDelivery' => [
            'addon\weapp\event\OrderDeliveryAfterWeappDelivery'
        ],
        // 订单收货后执行事件（异步）
        'OrderTakeDeliveryAfter' => [
            'addon\weapp\event\OrderTakeDeliveryAfter'
        ],
        /************************** 虚拟发货相关 *****************************/
        // 盲盒订单支付后
        'BlindboxOrderPay' => [
            'addon\weapp\event\BlindboxOrderPay'
        ],
        // 礼品卡订单支付后
        'GiftCardOrderPay' => [
            'addon\weapp\event\GiftCardOrderPay'
        ],
        // 充值订单支付后
        'MemberRechargeOrderPay' => [
            'addon\weapp\event\MemberRechargeOrderPay'
        ],
        // 超级会员卡订单支付后
        'MemberLevelOrderPay' => [
            'addon\weapp\event\MemberLevelOrderPay'
        ],
        // 积分兑换订单支付后
        'PointExchangeOrderPay' => [
            'addon\weapp\event\PointExchangeOrderPay'
        ],
        // 小程序虚拟发货
        'WeappVirtualDelivery' => [
            'addon\weapp\event\WeappVirtualDelivery'
        ],
        /************************** 虚拟发货相关 *****************************/
    ],

    'subscribe' => [
    ],
];
