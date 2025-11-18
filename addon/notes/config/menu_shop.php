<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_NOTES',
        'title' => '店铺笔记',
        'url' => 'notes://shop/notes/lists',
        'parent' => 'PROMOTION_TOOL',
        'picture' => 'addon/notes/shop/view/public/img/distribution_new.png',
        'picture_selected' => 'addon/notes/shop/view/public/img/distribution_select.png',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'PROMOTION_NOTES_LISTS',
                'title' => '笔记列表',
                'url' => 'notes://shop/notes/lists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_NOTES_ADD',
                        'title' => '添加笔记',
                        'url' => 'notes://shop/notes/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_EDIT',
                        'title' => '编辑笔记',
                        'url' => 'notes://shop/notes/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_DELETE',
                        'title' => '删除笔记',
                        'url' => 'notes://shop/notes/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_MODIFYSORT',
                        'title' => '修改排序',
                        'url' => 'notes://shop/notes/modifySort',
                        'sort' => 4,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ]
            ],
            [
                'name' => 'PROMOTION_NOTES_DRAFTS',
                'title' => '草稿箱',
                'url' => 'notes://shop/notes/drafts',
                'is_show' => 1,
                'is_control' => 1,
                'sort' => 2,
                'child_list' => []
            ],
            [
                'name' => 'PROMOTION_NOTES_GROUP',
                'title' => '笔记分组',
                'url' => 'notes://shop/group/lists',
                'is_show' => 1,
                'sort' => 3,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_ADD',
                        'title' => '添加分组',
                        'url' => 'notes://shop/group/add',
                        'sort' => 1,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_EDIT',
                        'title' => '编辑分组',
                        'url' => 'notes://shop/group/edit',
                        'sort' => 2,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_DELETE',
                        'title' => '删除分组',
                        'url' => 'notes://shop/group/delete',
                        'sort' => 3,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_MODIFYSORT',
                        'title' => '修改排序',
                        'url' => 'notes://shop/group/modifySort',
                        'sort' => 4,
                        'is_show' => 0,
                        'type' => 'button',
                    ],
                ]
            ]
        ]
    ],

];