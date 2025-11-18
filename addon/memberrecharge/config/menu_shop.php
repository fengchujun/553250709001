<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_MEMBERRECHARGE_LIST',
        'title' => '充值套餐',
        'url' => 'memberrecharge://shop/memberrecharge/lists',
        'parent' => 'MEMBER_ACCOUNT_BALANCE',
        'is_show' => 1,
        'is_control' => 1,
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_ADD',
                'title' => '添加充值套餐',
                'url' => 'memberrecharge://shop/memberrecharge/add',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_EDIT',
                'title' => '编辑充值套餐',
                'url' => 'memberrecharge://shop/memberrecharge/edit',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_DETAIL',
                'title' => '充值套餐详情',
                'url' => 'memberrecharge://shop/memberrecharge/detail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_INVALID',
                'title' => '停用充值套餐',
                'url' => 'memberrecharge://shop/memberrecharge/invalid',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_DELETE',
                'title' => '删除充值套餐',
                'url' => 'memberrecharge://shop/memberrecharge/delete',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_CARD_LISTS',
                'title' => '开卡列表',
                'url' => 'memberrecharge://shop/memberrecharge/cardlists',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_CARD_DETAIL',
                'title' => '开卡详情',
                'url' => 'memberrecharge://shop/memberrecharge/carddetail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_SET_CONFIG',
                'title' => '充值开关',
                'url' => 'memberrecharge://shop/memberrecharge/setConfig',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],

        ]

    ],
    [
        'name' => 'PROMOTION_MEMBERRECHARGE_ORDER_LISTS',
        'title' => '充值订单',
        'url' => 'memberrecharge://shop/memberrecharge/orderlists',
        'parent' => 'ORDER_MANAGE',
        'is_show' => 1,
        'is_control' => 1,
        'sort' => 2,
        'is_icon' => 0,
        'picture' => 'app/shop/view/public/img/menu_icon/icon13.png',
        'picture_selected' => 'app/shop/view/public/img/icon/refill_order.png',
        'child_list' => [
            [
                'name' => 'PROMOTION_MEMBERRECHARGE_ORDER_DETAIL',
                'title' => '订单详情',
                'url' => 'memberrecharge://shop/memberrecharge/orderdetail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ]
        ]

    ],

];