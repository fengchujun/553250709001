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
    'util' => [],

    // 自定义页面路径
    'link' => [],

    // 自定义图标库
    'icon_library' => [],

    // uni-app 组件，格式：[ 'name' => '组件名称/文件夹名称', 'path' => '文件路径/目录路径' ]，多个逗号隔开，自定义组件名称前缀必须是diy-，也可以引用第三方组件
    'component' => [],

    // uni-app 页面，多个逗号隔开
    'pages' => [],

    // 模板信息，格式：'title' => '模板名称', 'name' => '模板标识', 'cover' => '模板封面图', 'preview' => '模板预览图', 'desc' => '模板描述'
    'info' => [
        'title' => '官方模板二', // 模板名称
        'name' => 'official_default_plane', // 模板标识
        'cover' => 'addon/diy_default2/shop/view/public/img/cover.png', // 模板封面图
        'preview' => 'addon/diy_default2/shop/view/public/img/preview.png', // 模板预览图
        'desc' => '商城简约而不失时尚，适合大部分商城运营。', // 模板描述
    ],

    // 主题风格配色，格式可以自由定义扩展，【在uni-app中通过：this.themeStyle... 获取定义的颜色字段，例如：this.themeStyle.main_color】
    'theme' => [],

    // 自定义页面数据，格式：[ 'title' => '页面名称', 'name' => "页面标识", 'value' => [页面数据，json格式] ]
    'data' => [
        [
            'title' => '官方模板二',
            'name' => "DIY_VIEW_INDEX",
            'value' => [
                "global" => [
                    "title" => "官方模板二",
                    "pageBgColor" => "#F3F3F3",
                    "topNavColor" => "#FFFFFF",
                    "topNavBg" => false,
                    "navBarSwitch" => true,
                    "textNavColor" => "#333333",
                    "topNavImg" => "",
                    "moreLink" => [
                        "name" => ""
                    ],
                    "openBottomNav" => true,
                    "navStyle" => 1,
                    "textImgPosLink" => "center",
                    "mpCollect" => false,
                    "popWindow" => [
                        "imageUrl" => "",
                        "count" => -1,
                        "show" => 0,
                        "link" => [
                            "name" => ""
                        ],
                        "imgWidth" => "",
                        "imgHeight" => ""
                    ],
                    "bgUrl" => "",
                    "imgWidth" => "",
                    "imgHeight" => "",
                    "template" => [
                        "pageBgColor" => "",
                        "textColor" => "#303133",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "elementBgColor" => "",
                        "elementAngle" => "round",
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 0,
                            "both" => 0
                        ]
                    ]
                ],
                "value" => [
                    [
                        "id" => "5wtw72w1wj80",
                        'addonName' => '',
                        "componentName" => "Search",
                        "componentTitle" => "搜索框",
                        "isDelete" => 0,
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 10,
                            "bottom" => 10,
                            "both" => 12
                        ],
                        "title" => "请输入搜索关键词",
                        "textAlign" => "left",
                        "borderType" => 2,
                        "searchImg" => "",
                        "searchStyle" => 1,
                        "searchLink" => [
                            "name" => ""
                        ],
                        "pageBgColor" => "#FFFFFF",
                        "textColor" => "#303133",
                        "componentBgColor" => "",
                        "elementBgColor" => "#F6F9FF",
                        "iconType" => "img",
                        "icon" => "",
                        "style" => [
                            "fontSize" => "60",
                            "iconBgColor" => [],
                            "iconBgColorDeg" => 0,
                            "iconBgImg" => "",
                            "bgRadius" => 0,
                            "iconColor" => [
                                "#000000"
                            ],
                            "iconColorDeg" => 0
                        ],
                        "imageUrl" => "",
                        "positionWay" => "static"
                    ],
                    [
                        "id" => "2o7za2qmi900",
                        "list" => [
                            [
                                "link" => [
                                    "name" => ""
                                ],
                                "imageUrl" => 'addon/diy_default2/shop/view/public/img/banner.png',
                                "imgWidth" => "750",
                                "imgHeight" => "320",
                                "id" => "17vtbffhsvsw0"
                            ]
                        ],
                        "indicatorIsShow" => true,
                        "indicatorColor" => "#ffffff",
                        "carouselStyle" => "circle",
                        "indicatorLocation" => "center",
                        'addonName' => '',
                        "componentName" => "ImageAds",
                        "componentTitle" => "图片广告",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 0,
                            "both" => 0
                        ]
                    ],
                    [
                        "id" => "113ohzka4n40",
                        "mode" => "graphic",
                        "type" => "img",
                        "showStyle" => "fixed",
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "rowCount" => 5,
                        "pageCount" => 2,
                        "carousel" => [
                            "type" => "circle",
                            "color" => "#FFFFFF"
                        ],
                        "imageSize" => 40,
                        "aroundRadius" => 25,
                        "font" => [
                            "size" => 14,
                            "weight" => 500,
                            "color" => "#303133"
                        ],
                        "list" => [
                            [
                                "title" => "团购",
                                "icon" => "icondiy icon-system-groupbuy-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#FF5715",
                                        "#FF4116"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "GROUPBUY_PREFECTURE",
                                    "title" => "团购专区",
                                    "wap_url" => "/pages_promotion/groupbuy/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "1e67vek25rhc0"
                            ],
                            [
                                "title" => "拼团",
                                "icon" => "icondiy icon-system-pintuan-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#58BCFF",
                                        "#1379FF"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "PINTUAN_PREFECTURE",
                                    "title" => "拼团专区",
                                    "wap_url" => "/pages_promotion/pintuan/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "b1kn8ktvs440"
                            ],
                            [
                                "title" => "秒杀",
                                "icon" => "icondiy icon-system-seckill-time",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#FF9100"
                                    ],
                                    "iconBgColorDeg" => 0,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "SECKILL_PREFECTURE",
                                    "title" => "秒杀专区",
                                    "wap_url" => "/pages_promotion/seckill/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => true,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83288",
                                    "bgColorEnd" => "#FE3523"
                                ],
                                "id" => "1cgq3tjxkc800"
                            ],
                            [
                                "title" => " 积分",
                                "icon" => "icondiy icon-system-point-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#02CC96",
                                        "#43EEC9"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "INTEGRAL_STORE",
                                    "title" => "积分商城",
                                    "wap_url" => "/pages_promotion/point/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "1v6a5arxdyqo0"
                            ],
                            [
                                "title" => "专题活动",
                                "icon" => "icondiy icon-system-topic-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#BE79FF",
                                        "#7B00FF"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "THEMATIC_ACTIVITIES_LIST",
                                    "title" => "专题活动列表",
                                    "wap_url" => "/pages_promotion/topics/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "x7bqn51r8hs0"
                            ],
                            [
                                "title" => "砍价",
                                "icon" => "icondiy icon-system-bargain-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#5BBDFF",
                                        "#2E87FD"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "BARGAIN_PREFECTURE",
                                    "title" => "砍价专区",
                                    "wap_url" => "/pages_promotion/bargain/list",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "140zkvoseuw00"
                            ],
                            [
                                "title" => "领券",
                                "icon" => "icondiy icon-system-get-coupon",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#BE79FF",
                                        "#7B00FF"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "COUPON_PREFECTURE",
                                    "title" => "优惠券专区",
                                    "wap_url" => "/pages_tool/goods/coupon",
                                    "parent" => "MARKETING_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "5sjwa1q2t5k0"
                            ],
                            [
                                "title" => "文章",
                                "icon" => "icondiy icon-system-article-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#FF8052",
                                        "#FF4830"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "SHOPPING_ARTICLE",
                                    "title" => "文章",
                                    "wap_url" => "/pages_tool/article/list",
                                    "parent" => "MALL_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "4aforgjwmdw0"
                            ],
                            [
                                "title" => "公告",
                                "icon" => "icondiy icon-system-notice-nav",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#FFCC26",
                                        "#FF9F29"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "SHOPPING_NOTICE",
                                    "title" => "公告",
                                    "wap_url" => "/pages_tool/notice/list",
                                    "parent" => "MALL_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "myo9oz46yj40"
                            ],
                            [
                                "title" => "帮助",
                                "icon" => "icondiy icon-system-help",
                                "imageUrl" => "",
                                "iconType" => "icon",
                                "style" => [
                                    "fontSize" => 50,
                                    "iconBgColor" => [
                                        "#02CC96",
                                        "#43EEC9"
                                    ],
                                    "iconBgColorDeg" => 90,
                                    "iconBgImg" => "public/static/ext/diyview/img/icon_bg/bg_04.png",
                                    "bgRadius" => 19,
                                    "iconColor" => [
                                        "#FFFFFF"
                                    ],
                                    "iconColorDeg" => 0
                                ],
                                "link" => [
                                    "name" => "SHOPPING_HELP",
                                    "title" => "帮助",
                                    "wap_url" => "/pages_tool/help/list",
                                    "parent" => "MALL_LINK"
                                ],
                                "label" => [
                                    "control" => false,
                                    "text" => "热门",
                                    "textColor" => "#FFFFFF",
                                    "bgColorStart" => "#F83287",
                                    "bgColorEnd" => "#FE3423"
                                ],
                                "id" => "h4nt4orc9i80"
                            ]
                        ],
                        'addonName' => '',
                        "componentName" => "GraphicNav",
                        "componentTitle" => "图文导航",
                        "isDelete" => 0,
                        "pageBgColor" => "#FFFFFF",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 0,
                            "both" => 12
                        ]
                    ],
                    [
                        "id" => "3tegcfvyijk0",
                        "style" => "5",
                        "sources" => "initial",
                        "styleName" => "风格五",
                        "couponIds" => [],
                        "count" => 6,
                        "previewList" => [],
                        "nameColor" => "#303133",
                        "moneyColor" => "#FF0000",
                        "limitColor" => "#999999",
                        "btnStyle" => [
                            "textColor" => "#FFFFFF",
                            "bgColor" => "#303133",
                            "text" => "立即领取",
                            "aroundRadius" => 5,
                            "isBgColor" => true,
                            "isAroundRadius" => true
                        ],
                        "bgColor" => "",
                        "isName" => true,
                        "couponBgColor" => "",
                        "couponBgUrl" => "",
                        "couponType" => "img",
                        "ifNeedBg" => true,
                        'addonName' => 'coupon',
                        "componentName" => "Coupon",
                        "componentTitle" => "优惠券",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "elementBgColor" => "",
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 10,
                            "bottom" => 10,
                            "both" => 12
                        ]
                    ],
                    [
                        "id" => "3acr0xjm1c80",
                        'addonName' => '',
                        "componentName" => "RubikCube",
                        "componentTitle" => "魔方",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 10,
                        "bottomAroundRadius" => 10,
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ],
                        "list" => [
                            [
                                "imageUrl" => 'addon/diy_default2/shop/view/public/img/mf_left.png',
                                "imgWidth" => "338",
                                "imgHeight" => "450",
                                "previewWidth" => 187.5,
                                "previewHeight" => "249.63px",
                                "link" => [
                                    "name" => ""
                                ]
                            ],
                            [
                                "imageUrl" => 'addon/diy_default2/shop/view/public/img/mf_right1.png',
                                "imgWidth" => "354",
                                "imgHeight" => "220",
                                "previewWidth" => 187.5,
                                "previewHeight" => "124.82px",
                                "link" => [
                                    "name" => ""
                                ]
                            ],
                            [
                                "imageUrl" => 'addon/diy_default2/shop/view/public/img/mf_right2.png',
                                "imgWidth" => "354",
                                "imgHeight" => "22",
                                "previewWidth" => 187.5,
                                "previewHeight" => "124.82px",
                                "link" => [
                                    "name" => ""
                                ]
                            ]
                        ],
                        "mode" => "row1-lt-of2-rt",
                        "imageGap" => 10,
                        "elementAngle" => "round"
                    ],
                    [
                        "id" => "68p4o1plca80",
                        "style" => "style-1",
                        "sources" => "initial",
                        "styleName" => "风格1",
                        "count" => 6,
                        'addonName' => '',
                        "componentName" => "GoodsRecommend",
                        "componentTitle" => "商品推荐",
                        "isDelete" => 0,
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "topElementAroundRadius" => 10,
                        "bottomElementAroundRadius" => 10,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ],
                        "nameLineMode" => "single",
                        "sortWay" => "default",
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "imgAroundRadius" => 0,
                        "goodsNameStyle" => [
                            "color" => "#303133",
                            "control" => true,
                            "fontWeight" => false,
                            "support" => true
                        ],
                        "saleStyle" => [
                            "color" => "#999CA7",
                            "control" => true,
                            "support" => true
                        ],
                        "theme" => "default",
                        "priceStyle" => [
                            "mainColor" => "#FF1544",
                            "mainControl" => true,
                            "lineColor" => "#999CA7",
                            "lineControl" => false,
                            "lineSupport" => false
                        ],
                        "goodsId" => [],
                        "categoryId" => 0,
                        "categoryName" => "请选择",
                        "topStyle" => [
                            "title" => "今日推荐",
                            "subTitle" => "大家都在买",
                            "icon" => [
                                "value" => "icondiy icon-system-tuijian",
                                "color" => "#FF3D3D",
                                "bgColor" => ""
                            ],
                            "color" => "#303133",
                            "subColor" => "#999CA7",
                            "support" => true
                        ],
                        "bgUrl" => "",
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "elementBgColor" => "#FFFFFF",
                        "elementAngle" => "round",
                        "labelStyle" => [
                            "support" => false,
                            "bgColor" => "#FF504D",
                            "title" => "新人专享",
                            "color" => "#FFFFFF"
                        ]
                    ],
                    [
                        "id" => "3moj09pl5c20",
                        "style" => "style-1",
                        "sources" => "initial",
                        "count" => 6,
                        "goodsId" => [],
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "nameLineMode" => "single",
                        "template" => "row1-of1",
                        "goodsMarginType" => "default",
                        "goodsMarginNum" => 10,
                        "btnStyle" => [
                            "text" => "去秒杀",
                            "textColor" => "#FFFFFF",
                            "theme" => "default",
                            "aroundRadius" => 25,
                            "control" => true,
                            "support" => true,
                            "bgColorStart" => "#FF7B1D",
                            "bgColorEnd" => "#FF1544"
                        ],
                        "imgAroundRadius" => 10,
                        "saleStyle" => [
                            "color" => "#999CA7",
                            "control" => true,
                            "support" => true
                        ],
                        "progressStyle" => [
                            "control" => true,
                            "support" => true,
                            "currColor" => "#FDBE6C",
                            "bgColor" => "#FCECD7"
                        ],
                        "titleStyle" => [
                            "backgroundImage" => "addon/seckill/component/view/seckill/img/style_title_3_bg.png",
                            "isShow" => true,
                            "leftStyle" => "img",
                            "leftImg" => "addon/seckill/component/view/seckill/img/style_title_3_name.png",
                            "style" => "style-3",
                            "styleName" => "风格3",
                            "leftText" => "限时秒杀",
                            "fontSize" => 16,
                            "fontWeight" => true,
                            "textColor" => "#FFFFFF",
                            "bgColorStart" => "#FA6400",
                            "bgColorEnd" => "#FF287A",
                            "more" => "更多",
                            "moreColor" => "#FFFFFF",
                            "moreFontSize" => 12,
                            "moreSupport" => true,
                            "timeBgColor" => "",
                            "timeImageUrl" => "",
                            "colonColor" => "#FFFFFF",
                            "numBgColorStart" => "#FFFFFF",
                            "numBgColorEnd" => "#FFFFFF",
                            "numTextColor" => "#FD3B54"
                        ],
                        "slideMode" => "scroll",
                        "theme" => "default",
                        "priceStyle" => [
                            "mainColor" => "#FF1745",
                            "mainControl" => true,
                            "lineColor" => "#999CA7",
                            "lineControl" => true,
                            "lineSupport" => true
                        ],
                        "goodsNameStyle" => [
                            "color" => "#303133",
                            "control" => true,
                            "fontWeight" => false
                        ],
                        'addonName' => 'seckill',
                        "componentName" => "Seckill",
                        "componentTitle" => "秒杀",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 0,
                        "elementBgColor" => "#FFFFFF",
                        "elementAngle" => "round",
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ]
                    ],
                    [
                        "id" => "z9bdab3tm34",
                        "style" => "style-1",
                        "sources" => "initial",
                        "count" => 6,
                        "goodsId" => [],
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "nameLineMode" => "single",
                        "template" => "horizontal-slide",
                        "goodsMarginType" => "default",
                        "goodsMarginNum" => 10,
                        "btnStyle" => [
                            "text" => "去拼团",
                            "textColor" => "#FFFFFF",
                            "theme" => "default",
                            "aroundRadius" => 25,
                            "control" => false,
                            "support" => false,
                            "bgColorStart" => "#FF1544",
                            "bgColorEnd" => "#FF1544"
                        ],
                        "imgAroundRadius" => 10,
                        "saleStyle" => [
                            "color" => "#FF1544",
                            "control" => false,
                            "support" => false
                        ],
                        "groupStyle" => [
                            "color" => "#FFFFFF",
                            "control" => true,
                            "support" => true,
                            "bgColorStart" => "#FA2379",
                            "bgColorEnd" => "#FF4F61"
                        ],
                        "priceStyle" => [
                            "mainColor" => "#FF1544",
                            "mainControl" => true,
                            "lineColor" => "#999CA7",
                            "lineControl" => true,
                            "lineSupport" => true
                        ],
                        "slideMode" => "scroll",
                        "theme" => "default",
                        "goodsNameStyle" => [
                            "color" => "#303133",
                            "control" => true,
                            "fontWeight" => false
                        ],
                        "titleStyle" => [
                            "bgColorStart" => "#6236FF",
                            "bgColorEnd" => "#0091FF",
                            "isShow" => true,
                            "leftStyle" => "img",
                            "leftImg" => "addon/pintuan/component/view/pintuan/img/horizontal_slide_name.png",
                            "style" => "style-1",
                            "styleName" => "风格1",
                            "leftText" => "超值拼团",
                            "fontSize" => 16,
                            "fontWeight" => true,
                            "textColor" => "#FFFFFF",
                            "more" => "查看更多",
                            "moreColor" => "#FFFFFF",
                            "moreFontSize" => 12,
                            "backgroundImage" => "addon/pintuan/component/view/pintuan/img/horizontal_slide_bg.png"
                        ],
                        'addonName' => 'pintuan',
                        "componentName" => "Pintuan",
                        "componentTitle" => "拼团",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "#FFFFFF",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 10,
                        "elementBgColor" => "",
                        "elementAngle" => "round",
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ]
                    ],
                    [
                        "id" => "xwdnfttfj7k",
                        "style" => "style-1",
                        "sources" => "initial",
                        "count" => 6,
                        "goodsId" => [],
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "nameLineMode" => "single",
                        "template" => "horizontal-slide",
                        "goodsMarginType" => "default",
                        "goodsMarginNum" => 10,
                        "btnStyle" => [
                            "text" => "立即抢购",
                            "textColor" => "#FFFFFF",
                            "theme" => "default",
                            "aroundRadius" => 25,
                            "control" => false,
                            "support" => false,
                            "bgColorStart" => "#FF7B1D",
                            "bgColorEnd" => "#FF1544"
                        ],
                        "imgAroundRadius" => 5,
                        "saleStyle" => [
                            "color" => "#FFFFFF",
                            "control" => true,
                            "support" => true
                        ],
                        "slideMode" => "scroll",
                        "theme" => "default",
                        "goodsNameStyle" => [
                            "color" => "#303133",
                            "control" => true,
                            "fontWeight" => false
                        ],
                        "priceStyle" => [
                            "mainColor" => "#FF1745",
                            "mainControl" => true,
                            "lineColor" => "#999CA7",
                            "lineControl" => true,
                            "lineSupport" => true
                        ],
                        "titleStyle" => [
                            "bgColorStart" => "#FF209D",
                            "bgColorEnd" => "#B620E0",
                            "isShow" => true,
                            "leftStyle" => "img",
                            "leftImg" => "",
                            "style" => "style-1",
                            "styleName" => "风格1",
                            "leftText" => "疯狂砍价",
                            "fontSize" => 16,
                            "fontWeight" => true,
                            "textColor" => "#FFFFFF",
                            "more" => "更多",
                            "moreColor" => "#FFFFFF",
                            "moreFontSize" => 12,
                            "backgroundImage" => ""
                        ],
                        'addonName' => 'bargain',
                        "componentName" => "Bargain",
                        "componentTitle" => "砍价",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "#FFFFFF",
                        "componentAngle" => "round",
                        "topAroundRadius" => 0,
                        "bottomAroundRadius" => 10,
                        "elementBgColor" => "",
                        "elementAngle" => "round",
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ]
                    ],
                    [
                        "id" => "5zj6d48bxks0",
                        'addonName' => '',
                        "componentName" => "ImageAds",
                        "componentTitle" => "图片广告",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 5,
                        "bottomAroundRadius" => 5,
                        "topElementAroundRadius" => 0,
                        "bottomElementAroundRadius" => 0,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 10,
                            "both" => 12
                        ],
                        "list" => [
                            [
                                "link" => [
                                    "name" => ""
                                ],
                                "imageUrl" => 'addon/diy_default2/shop/view/public/img/gg.png',
                                "imgWidth" => "702",
                                "imgHeight" => "252",
                                "id" => "ccnb530uc5k0"
                            ]
                        ],
                        "indicatorIsShow" => true,
                        "indicatorColor" => "#ffffff",
                        "carouselStyle" => "circle",
                        "indicatorLocation" => "center"
                    ],
                    [
                        "id" => "xwdnfttfj6k",
                        "style" => "style-3",
                        "sources" => "initial",
                        "count" => 6,
                        "goodsId" => [],
                        "ornament" => [
                            "type" => "default",
                            "color" => "#EDEDED"
                        ],
                        "nameLineMode" => "multiple",
                        "template" => "row1-of2",
                        "goodsMarginType" => "default",
                        "goodsMarginNum" => 6,
                        "btnStyle" => [
                            "fontWeight" => false,
                            "padding" => 0,
                            "cartEvent" => "detail",
                            "text" => "购买",
                            "textColor" => "#FFFFFF",
                            "theme" => "default",
                            "aroundRadius" => 25,
                            "control" => false,
                            "support" => false,
                            "bgColor" => "#FF6A00",
                            "style" => "button",
                            "iconDiy" => [
                                "iconType" => "icon",
                                "icon" => "",
                                "style" => [
                                    "fontSize" => "60",
                                    "iconBgColor" => [],
                                    "iconBgColorDeg" => 0,
                                    "iconBgImg" => "",
                                    "bgRadius" => 0,
                                    "iconColor" => [
                                        "#000000"
                                    ],
                                    "iconColorDeg" => 0
                                ]
                            ]
                        ],
                        "imgAroundRadius" => 0,
                        "saleStyle" => [
                            "color" => "#999CA7",
                            "control" => true,
                            "support" => true
                        ],
                        "slideMode" => "slide",
                        "theme" => "default",
                        "goodsNameStyle" => [
                            "color" => "#303133",
                            "control" => true,
                            "fontWeight" => false
                        ],
                        "priceStyle" => [
                            "mainColor" => "#FF6A00",
                            "mainControl" => true,
                            "lineColor" => "#999CA7",
                            "lineControl" => false,
                            "lineSupport" => false
                        ],
                        'addonName' => '',
                        "componentName" => "GoodsList",
                        "componentTitle" => "商品列表",
                        "isDelete" => 0,
                        "pageBgColor" => "",
                        "componentBgColor" => "",
                        "componentAngle" => "round",
                        "topAroundRadius" => 10,
                        "bottomAroundRadius" => 10,
                        "elementBgColor" => "#FFFFFF",
                        "elementAngle" => "round",
                        "topElementAroundRadius" => 10,
                        "bottomElementAroundRadius" => 10,
                        "margin" => [
                            "top" => 0,
                            "bottom" => 0,
                            "both" => 12
                        ],
                        "categoryId" => 0,
                        "categoryName" => "请选择",
                        "sortWay" => "default",
                        "tag" => [
                            "text" => "隐藏",
                            "value" => "hidden"
                        ]
                    ]
                ]
            ]
        ]
    ]
];