<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'SUPER_MEMBER_LIST',
        'title' => '超级会员卡',
        'parent' => 'MEMBER_LEVEL_ROOT',
        'url' => 'supermember://shop/membercard/lists',
        'is_show' => 1,
        'sort' => 2,
        'child_list' => [
            [
                'name' => 'SUPER_MEMBER_ADD',
                'title' => '会员卡添加',
                'url' => 'supermember://shop/membercard/add',
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'SUPER_MEMBER_EDIT',
                'title' => '会员卡编辑',
                'url' => 'supermember://shop/membercard/edit',
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'SUPER_MEMBER_DELETE',
                'title' => '会员卡删除',
                'url' => 'supermember://shop/membercard/delete',
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'SUPER_MEMBER_STATUS',
                'title' => '会员卡状态变更',
                'url' => 'supermember://shop/membercard/status',
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'SUPER_MEMBER_RECOMMEND',
                'title' => '会员卡推荐',
                'url' => 'supermember://shop/membercard/recommend',
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'SUPER_MEMBER_AGREEMENT',
                'title' => '开卡协议',
                'url' => 'supermember://shop/membercard/agreement',
                'is_show' => 0,
                'type' => 'button',
            ],
        ]
    ],
    [
        'name' => 'SUPER_MEMBER_INDEX',
        'title' => '会员卡数据',
        'parent' => 'STAT_SHOP',
        'url' => 'supermember://shop/membercard/index',
        'picture' => 'addon/supermember/shop/view/public/img/member_card_new.png',
        'picture_selected' => 'addon/supermember/shop/view/public/img/member_card_select.png',
        'is_show' => 1,
    ],
    [
        'name' => 'SUPER_MEMBER_ORDER',
        'title' => '会员卡订单',
        'parent' => 'ORDER_MANAGE',
        'url' => 'supermember://shop/membercard/order',
        'picture' => 'app/shop/view/public/img/icon_new/member_card_order.png',
        'picture_selected' => 'app/shop/view/public/img/icon_new/member_card_order_selected.png',
        'is_show' => 1,
        'sort' => 3,
        'child_list' => [
            [
                'name' => 'SUPER_MEMBER_ORDER_DETAIL',
                'title' => '订单详情',
                'url' => 'supermember://shop/membercard/orderdetail',
                'is_show' => 0,
                'type' => 'button',
            ]
        ]
    ]
];
