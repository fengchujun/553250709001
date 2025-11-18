<?php
// 事件定义文件
return [
	'bind' => [
	
	],
	
	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\pointexchange\event\ShowPromotion',
		],
		'PointexchangeOrderPayNotify' => [
			'addon\pointexchange\event\PointexchangeOrderPayNotify',
		],
		
		'MemberAccountFromType' => [
			'addon\pointexchange\event\MemberAccountFromType',
		],
        'OrderClose' => [
            'addon\pointexchange\event\OrderClose'
        ],
        // 订单营销活动类型
        'OrderPromotionType' => [
            'addon\pointexchange\event\OrderPromotionType',
        ],
        // 优惠券获取来源
        'CouponGetType' => [
            'addon\pointexchange\event\CouponGetType',
        ],
        //通过支付信息获取手机端订单详情路径
        'WapOrderDetailPathByPayInfo' => [
            'addon\pointexchange\event\WapOrderDetailPathByPayInfo',
        ],
        'DeleteGoodsCheck' => [
            'addon\pointexchange\event\DeleteGoodsCheck',
        ],
	],
	
	'subscribe' => [
	],
];
