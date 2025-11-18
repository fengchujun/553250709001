<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'SHOP_VIRTUALEVALUATION_ROOT',
        'title' => '虚拟评价',
        'url' => 'virtualevaluation://shop/comment/goodslists',
        'picture' => 'addon/virtualevaluation/shop/view/public/img/comment.png',
        'picture_selected' => 'addon/virtualevaluation/shop/view/public/img/comment_selected.png',
        'parent' => 'PROMOTION_TOOL',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'SHOP_VIRTUALEVALUATION_GOODS',
                'title' => '商品列表',
                'url' => 'virtualevaluation://shop/comment/goodslists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'SHOP_STOCK_BATCH_ADD',
                        'title' => '批量添加评论',
                        'url' => 'virtualevaluation://shop/comment/batchadd',
                        'is_show' => 0,
                        'sort' => 1,
                        'type' => 'button',
                    ],
                ]
            ],
            [
                'name' => 'SHOP_VIRTUAL_STOCK',
                'title' => '虚拟评价库',
                'url' => 'virtualevaluation://shop/comment/stock',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'SHOP_STOCK_EDIT',
                        'title' => '编辑',
                        'url' => 'virtualevaluation://shop/comment/getcontents',
                        'is_show' => 0,
                        'sort' => 1,
                        'type' => 'button',
                    ],
                ]
            ],
        ]
    ]
];
