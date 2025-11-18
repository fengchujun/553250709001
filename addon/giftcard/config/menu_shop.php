<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_GIFTCARD',
        'title' => '礼品卡',
        'url' => 'giftcard://shop/giftcard/lists',
        'picture' => 'addon/giftcard/shop/view/public/img/distribution_new.png',
        'picture_selected' => 'addon/giftcard/shop/view/public/img/distribution_select.png',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_GIFTCARD_LIST',
                'title' => '礼品卡列表',
                'url' => 'giftcard://shop/giftcard/lists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_GIFTCARD_ADD',
                        'title' => '添加礼品卡',
                        'url' => 'giftcard://shop/giftcard/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_EDIT',
                        'title' => '编辑礼品卡',
                        'url' => 'giftcard://shop/giftcard/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_DELETE',
                        'title' => '删除礼品卡',
                        'url' => 'giftcard://shop/giftcard/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_DETAIL',
                        'title' => '礼品卡详情',
                        'url' => 'giftcard://shop/giftcard/detail',
                        'sort' => 4,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_IMPORT',
                        'title' => '制卡',
                        'url' => 'giftcard://shop/cardimport/lists',
                        'sort' => 5,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_IMPORT_DETELE',
                        'title' => '删除',
                        'url' => 'giftcard://shop/cardimport/delete',
                        'sort' => 6,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_CARD_LISTS',
                        'title' => '查看卡密',
                        'url' => 'giftcard://shop/card/lists',
                        'sort' => 6,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_CARD_DETAIL',
                        'title' => '详情',
                        'url' => 'giftcard://shop/card/detail',
                        'sort' => 7,
                        'is_show' => 0,
                        'type' => 'button'
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_GIFTCARD_CATEGORY_LIST',
                'title' => '分组列表',
                'url' => 'giftcard://shop/category/lists',
                'is_show' => 1,
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_GIFTCARD_CATEGORY_ADD',
                        'title' => '添加分组',
                        'url' => 'giftcard://shop/category/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_CATEGORY_EDIT',
                        'title' => '编辑分组',
                        'url' => 'giftcard://shop/category/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_CATEGORY_DELETE',
                        'title' => '删除分组',
                        'url' => 'giftcard://shop/category/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ]
                ],
            ],
            [
                'name' => 'PROMOTION_GIFTCARD_MEDIA_LIST',
                'title' => '素材列表',
                'url' => 'giftcard://shop/media/lists',
                'is_show' => 1,
                'sort' => 3,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_GIFTCARD_MEDIA_ADD',
                        'title' => '添加素材',
                        'url' => 'giftcard://shop/media/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_MEDIA_EDIT',
                        'title' => '编辑素材',
                        'url' => 'giftcard://shop/media/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_GIFTCARD_MEDIA_DELETE',
                        'title' => '删除素材',
                        'url' => 'giftcard://shop/media/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ]
            ],
        ]
    ],
    [
        'name' => 'PROMOTION_GIFTCARD_BUYORDER',
        'title' => '礼品卡订单',
        'url' => 'giftcard://shop/order/order',
        'is_show' => 1,
        'sort' => 4,
        'parent' => 'ORDER_MANAGE',
        'child_list' => [
            [
                'name' => 'PROMOTION_GIFTCARD_BUYORDER_DETAIL',
                'title' => '订单详情',
                'url' => 'giftcard://shop/order/detail',
                'sort' => 3,
                'is_show' => 0,

            ],
        ]

    ],

];