<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\model;

use addon\postertemplate\model\PosterTemplate as PosterTemplateModel;
use app\model\BaseModel;
use app\model\system\Site;
use app\model\upload\Upload;
use app\model\web\Config;
use extend\Poster as PosterExtend;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 商品海报
     */
    public function goods($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        try {
            if ($page == '/pages_promotion/bargain/detail') {
                $goods_info = $this->getGoodsInfo($qrcode_param[ 'bargain_id' ]);
                unset($qrcode_param[ 'bargain_id' ]);
            } else {
                $goods_info = $this->getGoodsInfo($qrcode_param[ 'id' ]);
            }
            if (empty($goods_info)) return $this->error('未获取到商品信息');

            $qrcode_info = $this->getGoodsQrcode($app_type, $page, $qrcode_param, $promotion_type, $site_id);
            if ($qrcode_info[ 'code' ] < 0) return $qrcode_info;

            //判断海报是否存在或停用
            $template_info = $this->getTemplateInfo($goods_info[ 'template_id' ]);

            $site_model = new Site();
            $condition = array (
                ["site_id", "=", $site_id]
            );
            $site_info = $site_model->getSiteInfo($condition);

            $member_info = [];
            if (!empty($qrcode_param[ 'source_member' ])) {
                $member_info = $this->getMemberInfo($qrcode_param[ 'source_member' ]);
            }

            $upload_config_model = new Config();
            $upload_config_result = $upload_config_model->getDefaultImg($site_id);

            if (empty($goods_info[ 'template_id' ]) || empty($template_info) || $template_info[ 'template_status' ] == 0) {
                $poster_width = 720;
                $poster_height = 1280;
                $poster = new PosterExtend($poster_width, $poster_height);
                $option = [
                    [
                        'action' => 'imageCopy', // 背景图
                        'data' => [
                            img('upload/poster/bg/promotion_bargain.png'),
                            0,
                            0,
                            720,
                            1280,
                            'square',
                            0,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 商品图
                        'data' => [
                            $goods_info[ 'sku_image' ],
                            86,
                            174,
                            548,
                            548,
                            'square',
                            5,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 二维码
                        'data' => [
                            $qrcode_info[ 'data' ][ 'path' ],
                            273,
                            916,
                            175,
                            175,
                            'square',
                            0,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品价格
                        'data' => [
                            '原价 ¥ ' . $goods_info[ 'price' ] . ' 最低可砍至 ¥ ' . $goods_info[ 'floor_price' ],
                            18,
                            [51, 51, 51],
                            86,
                            828,
                            548,
                            1,
                            false,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品名称
                        'data' => [
                            $goods_info[ 'sku_name' ],
                            25,
                            [34, 34, 34],
                            86,
                            780,
                            548,
                            1,
                            true,
                            1
                        ]
                    ]
                ];

                if (!empty($member_info)) {
                    $member_option = [
                        [
                            'action' => 'imageCircularCopy', // 写入用户头像
                            'data' => [
                                !empty($member_info[ 'headimg' ]) ? $member_info[ 'headimg' ] : $upload_config_result[ 'data' ][ 'value' ][ 'head' ],
                                86,
                                40,
                                101,
                                101
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入分享人昵称
                            'data' => [
                                $member_info[ 'nickname' ],
                                24,
                                [51, 51, 51],
                                210,
                                85,
                                420,
                                1,
                                0,
                                1
                            ]
                        ]
                    ];
                    $option = array_merge($option, $member_option);
                }
            } else {
                $condition = [
                    ['template_id', '=', $goods_info[ 'template_id' ]],
                    ['site_id', '=', $site_id]
                ];
                $poster_template_model = new PosterTemplateModel();
                $poster_data = $poster_template_model->getPosterTemplateInfo($condition);
                $poster_data[ 'data' ][ 'template_json' ] = json_decode($poster_data[ 'data' ][ 'template_json' ], true);
                $poster_width = 720;
                $poster_height = 1280;
                $poster = new PosterExtend($poster_width, $poster_height);
                $fontRate = 0.725;  // 20px 等于 14.5磅，换算比率 1px = 0.725磅
                if (!empty($poster_data[ 'data' ][ 'background' ])) {
                    list($width, $height, $type, $attr) = getimagesize(img($poster_data[ 'data' ][ 'background' ]));
                    $back_ground = [
                        'action' => 'imageCopy', // 写入背景图
                        'data' => [
                            img($poster_data[ 'data' ][ 'background' ]),
                            0,
                            0,
                            $poster_width,
                            $poster_height,
                            'square',
                            true,
                            1
                        ]
                    ];
                } else {
                    $back_ground = [
                        'action' => 'setBackground', // 设背景色
                        'data' => [255, 255, 255]
                    ];
                }
                $ground = [
                    [
                        'action' => 'setBackground',
                        'data' => [255, 255, 255]
                    ]
                ];
                $option = [
                    $back_ground,
                    [
                        'action' => 'imageText', // 写入店铺名称
                        'data' => [
                            $site_info[ 'data' ][ 'site_name' ],
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_name_font_size' ] * $fontRate * 2,
                            hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'store_name_color' ]),
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_name_left' ] * 2,
                            ($poster_data[ 'data' ][ 'template_json' ][ 'store_name_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'store_name_font_size' ]) * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_name_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_name_height' ] * 2,
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_name_is_show' ]
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 店铺logo
                        'data' => [
                            !empty($site_info[ 'data' ][ 'logo_square' ]) ? $site_info[ 'data' ][ 'logo_square' ] : getUrl() . '/app/shop/view/public/img/shop_logo.png',
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_logo_left' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_logo_top' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_logo_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_logo_height' ] * 2,
                            'square',
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'store_logo_is_show' ]
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 写入商品图
                        'data' => [
                            img($goods_info[ 'sku_image' ], 'mid'),
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_left' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_top' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_height' ] * 2,
                            !empty($poster_data[ 'data' ][ 'template_json' ][ 'goods_img_shape' ]) ? $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_shape' ] : 'square',
                            0,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_img_is_show' ]
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品名称
                        'data' => [
                            $goods_info[ 'sku_name' ],
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_name_font_size' ] * $fontRate * 2,
                            hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'goods_name_color' ]),
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_name_left' ] * 2,
                            ($poster_data[ 'data' ][ 'template_json' ][ 'goods_name_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'goods_name_font_size' ]) * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_name_width' ] * 2,
                            1,//文本行数  $poster_data['data']['template_json']['goods_name_height']*2,
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_name_is_show' ]
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 写入商品二维码
                        'data' => [
                            $qrcode_info[ 'data' ][ 'path' ],
                            $poster_data[ 'data' ][ 'qrcode_left' ] * 2,
                            $poster_data[ 'data' ][ 'qrcode_top' ] * 2,
                            $poster_data[ 'data' ][ 'qrcode_width' ] * 2,
                            $poster_data[ 'data' ][ 'qrcode_height' ] * 2,
                            'square',
                            0,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品价格
                        'data' => [
                            '¥' . $goods_info[ 'floor_price' ],
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_font_size' ] * $fontRate * 2,
                            hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'goods_price_color' ]),
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_left' ] * 2,
                            ($poster_data[ 'data' ][ 'template_json' ][ 'goods_price_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_font_size' ]) * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_height' ] * 2,
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_price_is_show' ]
                        ]
                    ],
                ];

                if ($goods_info[ 'price' ] == 0) {
                    $line = '一一一';
                } else {
                    $line = '一一一一';
                }
                $market_price = [
                    [
                        'action' => 'imageText', // 写入商品划线价格
                        'data' => [
                            '¥' . $goods_info[ 'price' ],
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_font_size' ] * $fontRate * 2,
                            hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_color' ]),
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_left' ] * 2,
                            ($poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_font_size' ]) * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_height' ] * 2,
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_is_show' ] ?? 0
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入线
                        'data' => [
                            $line,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_font_size' ] * $fontRate * 2,
                            hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_color' ]),
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_left' ] * 2 - 5,
                            ($poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_font_size' ]) * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_width' ] * 2,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_height' ] * 2,
                            true,
                            $poster_data[ 'data' ][ 'template_json' ][ 'goods_market_price_is_show' ]
                        ]
                    ],
                ];
                $option = array_merge($option, $market_price);

                if (!empty($member_info)) {
                    $member_option = [
                        [
                            'action' => 'imageCopy', // 写入用户头像
                            'data' => [
                                !empty($member_info[ 'headimg' ]) ? $member_info[ 'headimg' ] : $upload_config_result[ 'data' ][ 'value' ][ 'head' ],
                                $poster_data[ 'data' ][ 'template_json' ][ 'headimg_left' ] * 2,
                                $poster_data[ 'data' ][ 'template_json' ][ 'headimg_top' ] * 2,
                                $poster_data[ 'data' ][ 'template_json' ][ 'headimg_width' ] * 2,
                                $poster_data[ 'data' ][ 'template_json' ][ 'headimg_height' ] * 2,
                                !empty($poster_data[ 'data' ][ 'template_json' ][ 'headimg_shape' ]) ? $poster_data[ 'data' ][ 'template_json' ][ 'headimg_shape' ] : 'square',
                                0,
                                $poster_data[ 'data' ][ 'template_json' ][ 'headimg_is_show' ]
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入分享人昵称
                            'data' => [
                                $member_info[ 'nickname' ],
                                $poster_data[ 'data' ][ 'template_json' ][ 'nickname_font_size' ] * $fontRate * 2,
                                hex2rgb($poster_data[ 'data' ][ 'template_json' ][ 'nickname_color' ]),
                                $poster_data[ 'data' ][ 'template_json' ][ 'nickname_left' ] * 2,
                                ($poster_data[ 'data' ][ 'template_json' ][ 'nickname_top' ] + $poster_data[ 'data' ][ 'template_json' ][ 'nickname_font_size' ]) * 2,
                                $poster_data[ 'data' ][ 'template_json' ][ 'nickname_width' ] * 2,
                                $poster_data[ 'data' ][ 'template_json' ][ 'nickname_height' ] * 2,
                                0,
                                $poster_data[ 'data' ][ 'template_json' ][ 'nickname_is_show' ]
                            ]
                        ],
                    ];
                    $option = array_merge($ground, $option, $member_option);
                }
            }

            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/goods', 'goods_' . $promotion_type . '_' . $goods_info[ 'bargain_id' ] . '_' . $qrcode_param[ 'source_member' ] . '_' . time() . '_' . $app_type);
            if ($res[ 'code' ] == 0) {
                $upload = new Upload($site_id);
                $cloud_res = $upload->fileCloud($res[ 'data' ][ 'path' ]);
                if ($cloud_res[ 'code' ] >= 0) {
                    return $this->success(["path" => $cloud_res[ 'data' ]]);
                } else {
                    return $this->error();
                }
            }
            return $res;
        } catch (\Exception $e) {
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 获取用户信息
     * @param unknown $member_id
     */
    private function getMemberInfo($member_id)
    {
        return model('member')->getInfo(['member_id' => $member_id], 'nickname,headimg');
    }

    /**
     * 获取商品信息
     * @param $id
     * @return mixed
     */
    private function getGoodsInfo($id)
    {
        $join = [
            ['goods_sku sku', 'pbg.sku_id = sku.sku_id', 'inner'],
        ];
        $field = 'pbg.bargain_id,pbg.floor_price,sku.sku_name,sku.sku_id,sku.sku_image,sku.template_id,sku.price';
        return model('promotion_bargain_goods')->getInfo(['pbg.bargain_id' => $id], $field, 'pbg', $join);
    }

    /**
     * 获取商品二维码
     * @param unknown $app_type 请求类型
     * @param unknown $page uniapp页面路径
     * @param unknown $qrcode_param 二维码携带参数
     * @param string $promotion_type 活动类型 null为无活动
     */
    private function getGoodsQrcode($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        return event('Qrcode', [
            'site_id' => $site_id,
            'app_type' => $app_type,
            'type' => 'create',
            'data' => $qrcode_param,
            'page' => $page,
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param[ 'l_id' ] . '_' . $qrcode_param[ 'source_member' ] . '_' . $site_id,
        ], true);
    }

    /**
     * 获取海报信息
     * @param unknown $template_id
     */
    private function getTemplateInfo($template_id)
    {
        return model('poster_template')->getInfo(['template_id' => $template_id], 'template_id,template_status');
    }

    /**
     * 分享图片
     * @param $page
     * @param $qrcode_param
     * @param $site_id
     * @return array|\extend\multitype|PosterExtend|string|string[]
     */
    public function shareImg($page, $qrcode_param, $site_id)
    {
        try {
            if ($page == '/pages_promotion/bargain/launch') {
                $goods_info = $this->getGoodsInfo($qrcode_param[ 'bargain_id' ]);
                unset($qrcode_param[ 'bargain_id' ]);
            } else {
                $goods_info = $this->getGoodsInfo($qrcode_param[ 'id' ]);
            }
            if (empty($goods_info)) {
                return $this->error('未获取到商品信息');
            }

            $file_path = 'upload/share_img/bargain_' . $goods_info[ 'bargain_id' ] . '/sku_' . $goods_info[ 'sku_id' ] . '.jpg';
            if (file_exists($file_path)) return $this->success(['path' => $file_path]);

            $poster_width = 600;
            $poster_height = 480;

            $poster = new PosterExtend($poster_width, $poster_height);
            $option = [
                [
                    'action' => 'setBackground', // 设背景色
                    'data' => [255, 255, 255]
                ],
                [
                    'action' => 'imageCopy', // 商品图
                    'data' => [
                        img($goods_info[ 'sku_image' ], 'mid'),
                        30,
                        130,
                        200,
                        200,
                        'square',
                        50,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品名称
                    'data' => [
                        $goods_info[ 'sku_name' ],
                        22,
                        [51, 51, 51],
                        250,
                        170,
                        330,
                        2,
                        false,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品价格
                    'data' => [
                        '砍成价：¥',
                        15,
                        [255, 0, 0],
                        250,
                        295,
                        300,
                        2,
                        false,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品价格
                    'data' => [
                        $goods_info[ 'floor_price' ],
                        32,
                        [255, 0, 0],
                        345,
                        295,
                        300,
                        2,
                        false,
                        1,
                        PUBLIC_PATH . 'static/font/custom.ttf'
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品原价
                    'data' => [
                        '原   价：￥',
                        15,
                        [153, 153, 153],
                        250,
                        330,
                        300,
                        2,
                        false,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品原价
                    'data' => [
                        $goods_info[ 'price' ],
                        16,
                        [153, 153, 153],
                        345,
                        328,
                        300,
                        2,
                        false,
                        1,
                        PUBLIC_PATH . 'static/font/custom.ttf',
                    ]
                ],
                // 划线（两条线）
                [
                    'action' => 'imageline',
                    'data' => [
                        325,
                        320,
                        325 + imagettfbbox(16, 0, PUBLIC_PATH . 'static/font/custom.ttf', '￥ ' . $goods_info[ 'price' ])[ 2 ],
                        320,
                        [153, 153, 153],
                    ]
                ],
                [
                    'action' => 'imageline',
                    'data' => [
                        325,
                        321,
                        325 + imagettfbbox(16, 0, PUBLIC_PATH . 'static/font/custom.ttf', '￥ ' . $goods_info[ 'price' ])[ 2 ],
                        321,
                        [153, 153, 153],
                    ]
                ],
                [
                    'action' => 'imageCopy', // 背景图
                    'data' => [
                        img('upload/share_img/bg/bargain_1.png'),
                        0,
                        0,
                        600,
                        480,
                        'square',
                        0,
                        1
                    ]
                ],
            ];

            $option_res = $poster->create($option);
            if (is_array($option_res)) {
                return $option_res;
            }

            return $option_res->jpeg('upload/share_img/bargain_' . $goods_info[ 'bargain_id' ],
                'sku_' . $goods_info[ 'sku_id' ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除分享图片
     * @param int $bargain_id
     */
    public function clearShareImg(int $bargain_id)
    {
        $dir = 'upload/share_img/bargain_' . $bargain_id;
        @deleteDir($dir);
    }

}