<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_SCENEFESTIVAL',
        'title' => '节日有礼',
        'url' => 'scenefestival://shop/scenefestival/lists',
        'picture' => '',
        'picture_selected' => '',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'sort' => 102,
        'child_list' => [
            [
                'name' => 'PROMOTION_SCENEFESTIVAL_LIST',
                'title' => '节日有礼',
                'url' => 'scenefestival://shop/scenefestival/lists',
                'is_show' => 0,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_ADD_SCENEFESTIVAL',
                        'title' => '添加节日有礼活动',
                        'url' => 'scenefestival://shop/scenefestival/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_EDIT_SCENEFESTIVAL',
                        'title' => '编辑节日有礼活动',
                        'url' => 'scenefestival://shop/scenefestival/edit',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_DETAIL_SCENEFESTIVAL',
                        'title' => '节日有礼活动详情',
                        'url' => 'scenefestival://shop/scenefestival/detail',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_DELETE_SCENEFESTIVAL',
                        'title' => '节日有礼活动删除',
                        'url' => 'scenefestival://shop/scenefestival/delete',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_CLOSE_SCENEFESTIVAL',
                        'title' => '关闭节日有礼活动',
                        'url' => 'scenefestival://shop/scenefestival/close',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_SCENEFESTIVAL_RECORD_SCENEFESTIVAL',
                        'title' => '节日有礼活动领取',
                        'url' => 'scenefestival://shop/record/lists',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ]

            ],
        ]
    ],

];