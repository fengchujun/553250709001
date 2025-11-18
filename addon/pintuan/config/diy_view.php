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
            'name' => 'Pintuan',
            'title' => '拼团',
            'type' => 'PROMOTION',
            'value' => '{"style":"style-1","sources":"initial","count":6,"goodsId":[],"ornament":{"type":"default","color":"#EDEDED"},"nameLineMode":"single","template":"horizontal-slide","btnStyle":{"text":"去拼团","textColor":"#FFFFFF","theme":"default","aroundRadius":25,"control":false,"support":false,"bgColorStart":"#FF1544","bgColorEnd":"#FF1544"},"imgAroundRadius":10,"saleStyle":{"color":"#FF1544","control":false,"support":false},"slideMode":"scroll","theme":"default","goodsNameStyle":{"color":"#303133","control":true,"fontWeight":false},"priceStyle":{"mainColor":"#FF1544","mainControl":true,"lineColor":"#999CA7","lineControl":true,"lineSupport":true},"titleStyle":{"virtualNum": 999,"bgColorStart":"#6236FF","bgColorEnd":"#0091FF","isShow":true,"leftStyle":"img","leftImg":"addon/pintuan/component/view/pintuan/img/horizontal_slide_name.png","style":"style-1","styleName":"风格1","leftText":"超值拼团","fontSize":16,"fontWeight":true,"textColor":"#FFFFFF","more":"查看更多","moreColor":"#FFFFFF","moreFontSize":12,"backgroundImage":"addon/pintuan/component/view/pintuan/img/horizontal_slide_bg.png"},"goodsMarginType":"default","goodsMarginNum":10,"groupStyle":{"color":"#FFFFFF","control":true,"support":true,"bgColorStart":"#FA2379","bgColorEnd":"#FF4F61"}}',
            'sort' => '30001',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconfont iconpintuan1'
        ]
    ],

    // 自定义页面路径
    'link' => [
        [
            'name' => 'PINTUAN',
            'title' => '拼团',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'PINTUAN_PREFECTURE',
                    'title' => '拼团专区',
                    'wap_url' => '/pages_promotion/pintuan/list',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'MY_PINTUAN',
                    'title' => '我的拼团',
                    'wap_url' => '/pages_promotion/pintuan/my_spell',
                    'web_url' => '',
                    'sort' => 0
                ],
            ]
        ],
        [
            'name' => 'PINTUAN_GOODS',
            'title' => '拼团商品',
            'parent' => 'COMMODITY',
            'wap_url' => '',
            'web_url' => '',
            'child_list' => []
        ],
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