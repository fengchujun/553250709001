<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_DIVIDETICKET',
        'title' => '好友瓜分券',
        'url' => 'divideticket://shop/divideticket/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_DIVIDETICKET_LIST',
                'title' => '好友瓜分券',
                'url' => 'divideticket://shop/divideticket/lists',
                'sort' => 1,
                'is_show' => 0,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_OPERATE',
                        'title' => '运营',
                        'url' => 'divideticket://shop/divideticket/operate',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'DIVIDETICKET_GROUP_PREFECTURE',
                        'title' => '邀请记录',
                        'is_show' => '',
                        'url' => 'divideticket://shop/divideticket/groupmember',
                        'sort' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_ADD',
                        'title' => '添加活动',
                        'url' => 'divideticket://shop/divideticket/add',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_EDIT',
                        'title' => '编辑活动',
                        'url' => 'divideticket://shop/divideticket/edit',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_CLOSE',
                        'title' => '关闭活动',
                        'url' => 'divideticket://shop/divideticket/close',
                        'sort' => 4,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_DELETE',
                        'title' => '删除活动',
                        'url' => 'divideticket://shop/divideticket/delete',
                        'sort' => 5,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_DIVIDETICKET_DETAIL',
                        'title' => '删除活动',
                        'url' => 'divideticket://shop/divideticket/detail',
                        'sort' => 6,
                        'is_show' => 0,
                        'type' => 'button',
                    ]
                ]
            ],
        ]
    ]
];
