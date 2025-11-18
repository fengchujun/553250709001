<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_TOPIC',
        'title' => '专题活动',
        'url' => 'topic://shop/topic/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_TOPIC_LIST',
                'title' => '活动管理',
                'url' => 'topic://shop/topic/lists',
                'parent' => 'PROMOTION_CENTER',
                'is_show' => 0,
                'is_control' => 1,
                'is_icon' => 0,
                'picture' => '',
                'picture_select' => '',
                'sort' => 100,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_TOPIC_ADD',
                        'title' => '添加活动',
                        'url' => 'topic://shop/topic/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_TOPIC_EDIT',
                        'title' => '编辑活动',
                        'url' => 'topic://shop/topic/edit',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_TOPIC_DELETE',
                        'title' => '删除活动',
                        'url' => 'topic://shop/topic/delete',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_TOPIC_CLOSE',
                        'title' => '关闭活动',
                        'url' => 'topic://shop/topic/close',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_TOPIC_DETAIL',
                        'title' => '查看活动',
                        'url' => 'topic://shop/topic/detail',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ]
                ]
            ],
        ]
    ],
];
