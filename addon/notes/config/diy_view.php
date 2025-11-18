<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */
return [

    // 自定义模板页面类型，格式：[ 'title' => '页面类型名称', 'name' => '页面标识', 'path' => '页面路径', 'value' => '页面数据，json格式' ]
    'template' => [],

    // 后台自定义组件——装修
    'util' => [
        [
            'name' => 'Notes',
            'title' => '店铺笔记',
            'type' => 'SYSTEM',
            'value' => '{"style":1,"styleName": "风格一","sources":"initial","noteId":[],"count":"3","notesLabel":1,"readNum":1,"uploadTime":1,"titleTextColor":"#333333","moreTextColor":"#858585","title":"店铺笔记","more":"查看更多","contentBgColor":"#FFFFFF"}',
            'sort' => '10050',
            'support_diy_view' => '',
            'max_count' => 1,
            'icon' => 'iconfont iconbiji',
        ]
    ],

    // 自定义页面路径
    'link' => [
        [
            'name' => 'NOTES',
            'title' => '店铺笔记',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'NOTES_PREFECTURE',
                    'title' => '店铺笔记',
                    'wap_url' => '/pages_tool/store_notes/note_list',
                    'web_url' => '',
                    'sort' => 0
                ]
            ]
        ]
    ],

    // 自定义图标库
    'icon_library' => [],

    // uni-app 组件，格式：[ 'name' => '组件名称/文件夹名称', 'path' => '文件路径/目录路径' ]，多个逗号隔开，自定义组件名称前缀必须是diy-，也可以引用第三方组件
    'component' => [],

    // uni-app 页面，多个逗号隔开
    'pages' => [],

    // 模板信息，格式：'title' => '模板名称', 'name' => '模板标识', 'cover' => '模板封面图', 'preview' => '模板预览图', 'desc' => '模板描述'
    'info' => [],

    // 主题风格配色，格式可以自由定义扩展，【在uni-app中通过：this.themeStyle... 获取定义的颜色字段，例如：this.themeStyle.main_color】
    'theme' => [],

    // 自定义页面数据，格式：[ 'title' => '页面名称', 'name' => "页面标识", 'value' => [页面数据，json格式] ]
    'data' => []
];