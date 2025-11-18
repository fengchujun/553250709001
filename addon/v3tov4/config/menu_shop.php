<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'TOOL_UPGRADE',
        'title' => 'v3Tov4迁移',
        'url' => 'v3tov4://shop/upgrade/index',
        'parent' => 'PROMOTION_TOOL',
        'is_show' => 1,
        'picture' => 'addon/v3tov4/shop/view/public/img/migration_new.png',
        'picture_selected' => 'addon/v3tov4/shop/view/public/img/migration_select.png',
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'TOOL_UPGRADE_DATA',
                'title' => '迁移数据',
                'url' => 'v3tov4://shop/upgrade/index',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => []
            ],
            [
                'name' => 'TOOL_UPGRADE_LOG',
                'title' => '迁移日志',
                'url' => 'v3tov4://shop/upgrade/log',
                'is_show' => 1,
                'sort' => 2,
                'child_list' => []
            ]
        ]
    ],
];
