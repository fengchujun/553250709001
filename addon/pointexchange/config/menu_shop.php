<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_POINGEXCHANGE_ROOT',
        'title' => '积分商城',
        'url' => 'pointexchange://shop/exchange/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => 'addon/pointexchange/shop/view/public/img/point_site.png',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_POINGEXCHANGE',
                'title' => '积分商品',
                'url' => 'pointexchange://shop/exchange/lists',
                'is_show' => 0,
                'is_control' => 1,
                'is_icon' => 0,
                'picture' => '',
                'picture_select' => '',
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_POINGEXCHANGE_ADD',
                        'title' => '添加商品',
                        'url' => 'pointexchange://shop/exchange/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_POINGEXCHANGE_EDIT',
                        'title' => '编辑商品',
                        'url' => 'pointexchange://shop/exchange/edit',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_POINGEXCHANGE_CLOSE',
                        'title' => '下架商品',
                        'url' => 'pointexchange://shop/exchange/close',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_POINGEXCHANGE_DELETE',
                        'title' => '删除商品',
                        'url' => 'pointexchange://shop/exchange/delete',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],

                ]
            ],
        ]
    ],
    [
        'name' => 'PROMOTION_POINGEXCHANGE_ORDER_LISTS',
        'title' => '积分兑换订单',
        'url' => 'pointexchange://shop/pointexchange/lists',
        'parent' => 'ORDER_MANAGE',
        'sort' => 6,
        'is_show' => 1,
        'child_list' => [
            [
                'name' => 'PROMOTION_POINGEXCHANGE_DETAIL',
                'title' => '订单详情',
                'url' => 'pointexchange://shop/pointexchange/detail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],

        ]
    ],

];
