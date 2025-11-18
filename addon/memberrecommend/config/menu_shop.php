<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'MEMBER_RECOMMEND',
        'title' => '邀请奖励',
        'url' => 'memberrecommend://shop/memberrecommend/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_RECOMMEND_DETAIL',
                'title' => '活动详情',
                'url' => 'memberrecommend://shop/memberrecommend/detail',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_RECOMMEND_ADD',
                'title' => '添加活动',
                'url' => 'memberrecommend://shop/memberrecommend/add',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_RECOMMEND_EDIT',
                'title' => '编辑活动',
                'url' => 'memberrecommend://shop/memberrecommend/edit',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_RECOMMEND_CLOSE',
                'title' => '关闭活动',
                'url' => 'memberrecommend://shop/memberrecommend/close',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_RECOMMEND_DELETE',
                'title' => '删除活动',
                'url' => 'memberrecommend://shop/memberrecommend/delete',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ],
            [
                'name' => 'PROMOTION_RECOMMEND_RECEIVE',
                'title' => '邀请奖励记录',
                'url' => 'memberrecommend://shop/memberrecommend/receive',
                'sort' => 1,
                'is_show' => 0,
                'type' => 'button',
            ]
        ]
    ],
];