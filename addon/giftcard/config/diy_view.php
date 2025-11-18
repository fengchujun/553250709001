<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */
return [

    // 自定义模板页面类型，格式：[ 'title' => '页面类型名称', 'name' => '页面标识', 'path' => '页面路径', 'value' => '页面数据，json格式' ]
    'template' => [],

    // 后台自定义组件——装修
    'util' => [],

    // 自定义页面路径
    'link' => [
        [
            'name' => 'GiftCARD',
            'title' => '礼品卡',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'GIFTCARDMANAGE',
                    'title' => '礼品卡专区',
                    'wap_url' => '/pages_promotion/giftcard/index',
                    'web_url' => '',
                    'sort' => 1
                ],
                [
                    'name' => 'MYGIFTCARD',
                    'title' => '我的卡包',
                    'wap_url' => '/pages_promotion/giftcard/list',
                    'web_url' => '',
                    'sort' => 2
                ],
                [
                    'name' => 'GIFTCARD_RECEIVE_LIST',
                    'title' => '收到的卡片',
                    'wap_url' => '/pages_promotion/giftcard/receive_list',
                    'web_url' => '',
                    'sort' => 3
                ],
                [
                    'name' => 'GIFTCARD_GIVE_LIST',
                    'title' => '送出的卡片',
                    'wap_url' => '/pages_promotion/giftcard/give_list',
                    'web_url' => '',
                    'sort' => 4
                ],
                [
                    'name' => 'GIFTCARD_EXCHANGE',
                    'title' => '卡密激活',
                    'wap_url' => '/pages_promotion/giftcard/exchange',
                    'web_url' => '',
                    'sort' => 5
                ],
            ]
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