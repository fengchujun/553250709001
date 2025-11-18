<?php
// 事件定义文件
return [
	'bind' => [
	
	],
	
	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\bale\event\ShowPromotion',
		],
        'OpenBale' => [
            'addon\bale\event\OpenBale',
        ],
        'CloseBale' => [
            'addon\bale\event\CloseBale',
        ],
        // 订单营销活动类型
        'OrderPromotionType' => [
            'addon\bale\event\OrderPromotionType',
        ]
	],
	
	'subscribe' => [
	],
];
