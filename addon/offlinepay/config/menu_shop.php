<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'OFFLINE_PAY_CONFIG',
        'title' => '线下支付编辑',
        'url' => 'offlinepay://shop/pay/config',
        'parent' => 'CONFIG_PAY',
        'is_show' => 0,
        'is_control' => 1,
        'is_icon' => 0,
        'sort' => 1,
        'type' => 'button',
    ],
    [
        'name' => 'OFFLINE_PAY_LIST',
        'title' => '线下支付',
        'url' => 'offlinepay://shop/pay/lists',
        'parent' => 'ORDER_MANAGE',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'sort' => 8,
        'child_list' => [
            [
                'name' => 'OFFLINE_PAY_AUDIT_PASS',
                'title' => '审核通过',
                'url' => 'offlinepay://shop/pay/auditpass',
                'is_show' => 0,
                'is_control' => 1,
                'is_icon' => 0,
                'sort' => 1,
                'type' => 'button',
            ],
            [
                'name' => 'OFFLINE_PAY_AUDIT_REFUSE',
                'title' => '审核拒绝',
                'url' => 'offlinepay://shop/pay/auditrefuse',
                'is_show' => 0,
                'is_control' => 1,
                'is_icon' => 0,
                'sort' => 2,
                'type' => 'button',
            ],
        ]
    ],
];
