<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_SECKILL',
        'title' => '限时秒杀',
        'url' => 'seckill://shop/seckill/goodslist',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_SECKILL_GOODS_LIST',
                'title' => '商品管理',
                'url' => 'seckill://shop/seckill/goodslist',
                'parent' => 'PROMOTION_CENTER',
                'is_show' => 1,
                'is_control' => 1,
                'is_icon' => 0,
                'picture' => '',
                'picture_select' => '',
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_SECKILL_GOODS_ADD',
                        'title' => '添加商品',
                        'url' => 'seckill://shop/seckill/addgoods',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SECKILL_GOODS_UPDATE',
                        'title' => '编辑商品',
                        'url' => 'seckill://shop/seckill/updategoods',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SECKILL_GOODS_DELETE',
                        'title' => '删除商品',
                        'url' => 'seckill://shop/seckill/deletegoods',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ],
            ],
            [
                'name' => 'PROMOTION_SECKILL_TIME',
                'title' => '场次管理',
                'url' => 'seckill://shop/seckill/lists',
                'parent' => 'PROMOTION_CENTER',
                'is_show' => 1,
                'is_control' => 1,
                'is_icon' => 0,
                'picture' => '',
                'picture_select' => '',
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_SECKILL_ADD',
                        'title' => '添加场次',
                        'url' => 'seckill://shop/seckill/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SECKILL_ADD',
                        'title' => '编辑场次',
                        'url' => 'seckill://shop/seckill/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SECKILL_DELETE',
                        'title' => '删除场次',
                        'url' => 'seckill://shop/seckill/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ],
            ],
        ]
    ],

];
