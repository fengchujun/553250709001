<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_JIELONG',
        'title' => '社群接龙',
        'url' => 'jielong://shop/jielong/lists',
        'parent' => 'PROMOTION_CENTER',
        'picture' => '',
        'picture_selected' => '',
        'is_show' => 1,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_JIELONG_LISTS',
                'title' => '接龙列表',
                'url' => 'jielong://shop/jielong/lists',
                'is_show' => 0,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_JIELONG_ADD',
                        'title' => '添加接龙',
                        'url' => 'jielong://shop/jielong/add',
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_JIELONG_EDIT',
                        'title' => '编辑接龙',
                        'url' => 'jielong://shop/jielong/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_JIELONG_DETAIL',
                        'title' => '接龙详情',
                        'url' => 'jielong://shop/jielong/detail',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_JIELONG_DELETE',
                        'title' => '删除接龙',
                        'url' => 'jielong://shop/jielong/delete',
                        'sort' => 4,
                        'is_show' => 0,
                        'type' => 'button',
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_JIELONG_ORDER',
                'title' => '订单列表',
                'url' => 'jielong://shop/order/lists',
                'is_show' => 0,
                'is_control' => 1,
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_JIELONG_ORDER_DETAIL',
                        'title' => '订单详情',
                        'url' => 'jielong://shop/order/detail',
                        'is_show' => 0,
                        'type' => 'button',
                    ]
                ]
            ]
        ]
    ],

];