<?php
// 事件定义文件
return [
	'bind' => [

	],

	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\jielong\event\ShowPromotion',
		],

        //开启接龙
        'OpenJielong' => [
            'addon\jielong\event\OpenJielong',
        ],

        //关闭接龙
        'CloseJielong' => [
            'addon\jielong\event\CloseJielong',
        ],

        // 商品营销活动类型
        'GoodsPromotionType' => [
            'addon\jielong\event\GoodsPromotionType',
        ],
        //订单创建之后
        'OrderCreateAfter' => [
            'addon\jielong\event\OrderCreateAfter',
        ],
        //订单支付
        'OrderPay' => [
            'addon\jielong\event\OrderPay',
        ],
        //订单关闭
        'OrderClose' => [
            'addon\jielong\event\OrderClose',
        ]
	],

	'subscribe' => [
	],
];
