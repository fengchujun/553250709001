<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_BALE',
        'title' => '打包一口价',
        'url' => 'bale://shop/bale/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_BALE_DETAIL',
                'title' => '活动详情',
                'url' => 'bale://shop/bale/detail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_BALE_ADD',
                'title' => '添加活动',
                'url' => 'bale://shop/bale/add',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_BALE_EDIT',
                'title' => '编辑活动',
                'url' => 'bale://shop/bale/edit',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_BALE_CLOSE',
                'title' => '关闭活动',
                'url' => 'bale://shop/bale/close',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_BALE_DELETE',
                'title' => '删除活动',
                'url' => 'bale://shop/bale/delete',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ]
        ]
    ]
];
