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
            'name' => 'Seckill',
            'title' => '秒杀',
            'type' => 'PROMOTION',
            'value' => '{"style":"style-1","sources":"initial","count":6,"goodsId":[],"goodsMarginType":"default","goodsMarginNum":10,"ornament":{"type":"default","color":"#EDEDED"},"nameLineMode":"single","template":"row1-of1","btnStyle":{"text":"去秒杀","textColor":"#FFFFFF","theme":"default","aroundRadius":25,"control":true,"support":true,"bgColorStart":"#FF7B1D","bgColorEnd":"#FF1544"},"imgAroundRadius":5,"saleStyle":{"color":"#999CA7","control":true,"support":true},"progressStyle":{"control":true,"support":true,"currColor":"#FDBE6C","bgColor":"#FCECD7"},"titleStyle":{"backgroundImage":"","isShow":true,"leftStyle":"text","leftImg":"","style":"style-1","styleName":"风格1","leftText":"限时秒杀","fontSize":16,"fontWeight":true,"textColor":"#303133","bgColorStart":"#FFFFFF","bgColorEnd":"#FFFFFF","more":"查看更多","moreColor":"#999999","moreFontSize":12,"timeBgColor":"","timeImageUrl":"","moreSupport":true,"colonColor":"#303133","numBgColorStart":"#303133","numBgColorEnd":"#303133","numTextColor":"#FFFFFF"},"slideMode":"scroll","theme":"default","priceStyle":{"mainColor":"#FF1745","mainControl":true,"lineColor":"#999CA7","lineControl":true,"lineSupport":true},"goodsNameStyle":{"color":"#303133","control":true,"fontWeight":false}}',
            'sort' => '30003',
            'support_diy_view' => '',
            'max_count' => 1,
            'icon' => 'iconfont iconmiaosha1'
        ]
    ],

    // 自定义页面路径
    'link' => [
        [
            'name' => 'SECKILL',
            'title' => '秒杀',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'SECKILL_PREFECTURE',
                    'title' => '秒杀专区',
                    'wap_url' => '/pages_promotion/seckill/list',
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