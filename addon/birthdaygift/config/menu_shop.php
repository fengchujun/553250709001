<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_BIRTHDAYGIFT',
        'title' => '生日有礼',
        'url' => 'birthdaygift://shop/birthdaygift/lists',
        'picture' => '',
        'picture_selected' => '',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'sort' => 101,
        'child_list' => [
            [
                'name' => 'PROMOTION_BIRTHDAYGIFT_LIST',
                'title' => '生日有礼',
                'url' => 'birthdaygift://shop/birthdaygift/lists',
                'is_show' => 0,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_ADD_BIRTHDAYGIFT',
                        'title' => '添加生日有礼活动',
                        'url' => 'birthdaygift://shop/birthdaygift/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_EDIT_BIRTHDAYGIFT',
                        'title' => '编辑生日有礼活动',
                        'url' => 'birthdaygift://shop/birthdaygift/edit',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_DETAIL_BIRTHDAYGIFT',
                        'title' => '生日有礼活动详情',
                        'url' => 'birthdaygift://shop/birthdaygift/detail',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_DELETE_BIRTHDAYGIFT',
                        'title' => '生日有礼活动删除',
                        'url' => 'birthdaygift://shop/birthdaygift/delete',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_CLOSE_BIRTHDAYGIFT',
                        'title' => '关闭生日有礼活动',
                        'url' => 'birthdaygift://shop/birthdaygift/close',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_BIRTHDAYGIFT_RECORD_BIRTHDAYGIFT',
                        'title' => '生日有礼活动领取',
                        'url' => 'birthdaygift://shop/record/lists',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ]

            ],
        ]
    ],

];