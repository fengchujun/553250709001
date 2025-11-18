<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;
use app\model\system\Site;
use app\model\upload\Upload;
use extend\Poster as PosterExtend;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 接龙海报 用户端
     */
    public function goods($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        //根据不同的app_type 生成不同的分享地址 二维码
        try {
            $goods_info = $this->getGoodsInfo($qrcode_param[ 'jielong_id' ], $site_id);
            if (empty($goods_info)) return $this->error('未获取到商品信息');
            $weapp_status = 0;
            //判断是否绑定小程序
            if ($app_type == 'weapp') {
                $config = new ConfigModel();
                $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
                if (!empty($res[ 'data' ])) {
                    if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                        return $this->success([ "status" => 2 ]);
                    } else {
                        $weapp_status = 1;
                    }
                } else {
                    return $this->success([ "status" => 2 ]);
                }
            }

            $qrcode_info = $this->getGoodsQrcode($app_type, $page, $qrcode_param, $promotion_type, $site_id);

            if ($qrcode_info[ 'code' ] < 0) return $qrcode_info;

            if (!empty($qrcode_param[ 'source_member' ])) {
                $member_info = $this->getMemberInfo($qrcode_param[ 'source_member' ]);
            }

            //平台配置信息
            $site_model = new Site();
            $site_info = $site_model->getSiteInfo([ [ "site_id", "=", $site_id ] ]);
            $site_name = $site_info[ 'data' ][ 'site_name' ];

            $jielong_info = $goods_info[ 'jielong_info' ];
            $goods_info = $goods_info[ 'list' ];
            $poster = new PosterExtend(600, 960);

            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data' => [
                        'upload/poster/bg/jielong.png',
                        0,
                        0,
                        600,
                        960,
                        'square',
                        true,
                        1
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码/太阳码
                    'data' => [
                        $qrcode_info[ 'data' ][ 'path' ],
                        $weapp_status ? 383 : 413,  //x
                        $weapp_status ? 740 : 770,  //y
                        $weapp_status ? 165 : 135,
                        $weapp_status ? 165 : 135,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 接龙时间
                    'data' => [
                        $jielong_info[ 'jielong_time' ],
                        18,
                        [ 255, 95, 75 ],
                        60,
                        845,
                        500,
                        1,
                        true
                    ]
                ],
                [
                    'action' => 'imageText', // 接龙状态
                    'data' => [
                        $jielong_info[ 'jielong_status_name' ],
                        18,
                        [ 18, 18, 18 ],
                        270,
                        845,
                        500,
                        1,
                        true
                    ]
                ],
                [
                    'action' => 'imageCircularCopy', // 写入店铺头像
                    'data' => [
                        !empty($site_info[ 'data' ][ 'logo_square' ]) ? $site_info[ 'data' ][ 'logo_square' ] : 'public/uniapp/shop_img.png',
                        30,
                        40,
                        80,
                        80
                    ]
                ],
                [
                    'action' => 'imageText', // 写入店铺名称
                    'data' => [
                        !empty($site_name) ? $site_name : '单商户v5',
                        22,
                        [ 255, 255, 255 ],
                        130,
                        80,
                        440,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入接龙语
                    'data' => [
                        '"这个接龙不错，快和我一起参与吧！"',
                        18,
                        [ 255, 255, 255 ],
                        130,
                        115,
                        440,
                        1
                    ]
                ]
            ];

            $goods_option = [];
            $y = 0;
            foreach ($goods_info as &$v) {
                array_push($goods_option,
                    [
                        'action' => 'imageCopy', // 商品图
                        'data' => [
                            $v[ 'goods_image' ],
                            60,
                            190 + $y,//y
                            140,
                            140,
                            'square',
                            30,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品名称
                        'data' => [
                            $v[ 'goods_name' ],
                            16,
                            [ 89, 89, 89 ],
                            218,
                            225 + $y,//y
                            330,
                            2,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品促销语
                        'data' => [
                            $v[ 'introduction' ],
                            12,
                            [ 205, 205, 205 ],
                            218,
                            287 + $y,//y
                            330,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品售价
                        'data' => [
                            '¥ ' . $v[ 'discount_price' ],
                            20,
                            [ 255, 95, 75 ],
                            218,
                            326 + $y,//y
                            500,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品原价
                        'data' => [
                            '¥ ' . $v[ 'market_price' ],
                            16,
                            [ 205, 205, 205 ],
                            340,
                            326 + $y,//y
                            500,
                            1,
                            true,
                            !empty($v[ 'is_market_price' ]) ? 1 : 0,
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 删除线
                        'data' => [
                            'upload/poster/bg/del_line.png',
                            337,
                            283 + $y,//y
                            85,
                            64,
                            'square',
                            true,
                            !empty($v[ 'is_market_price' ]) ? 1 : 0,
                        ]
                    ]
                );
                $y += 180;
            }

            $option = array_merge($option, $goods_option);
            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;
            $res = $option_res->jpeg('upload/poster/jielong', 'goods_' . $promotion_type . '_' . $qrcode_param[ 'jielong_id' ] . '_' . time() . '_' . $app_type);

            if ($res[ 'code' ] == 0) {
                $upload = new Upload($site_id);
                $cloud_res = $upload->fileCloud($res[ 'data' ][ 'path' ]);
                if ($cloud_res[ 'code' ] >= 0) {
                    if ($app_type == 'weapp') {
                        return $this->success([ "poster_path" => $cloud_res[ 'data' ] . '?code=' . uniqid(), "qrcode_path" => $qrcode_info[ 'data' ][ 'path' ], "status" => $weapp_status ]);
                    } else {
                        return $this->success([ "poster_path" => $cloud_res[ 'data' ] . '?code=' . uniqid(), "qrcode_path" => $qrcode_info[ 'data' ][ 'path' ], "qrcode_url" => $qrcode_info[ 'data' ][ 'url' ] ]);
                    }
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
     * 接龙海报 管理端
     */
    public function goodsShop($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        //根据不同的app_type 生成不同的分享地址 二维码
        try {
            $goods_info = $this->getGoodsInfo($qrcode_param[ 'jielong_id' ], $site_id);
            if (empty($goods_info)) return $this->error('未获取到商品信息');

            //判断是否绑定小程序
            $config = new ConfigModel();
            $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);

            $status = 0;
            if (!empty($res[ 'data' ])) {
                if (!empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                    $status = 1;
                }
            }

            //平台配置信息
            $site_model = new Site();
            $site_info = $site_model->getSiteInfo([ [ "site_id", "=", $site_id ] ]);
            $site_name = $site_info[ 'data' ][ 'site_name' ];

            $h5_qrcode_info = $this->getGoodsQrcode('h5', $page, $qrcode_param, $promotion_type, $site_id);
            if ($h5_qrcode_info[ 'code' ] < 0) return $h5_qrcode_info;

            $jielong_info = $goods_info[ 'jielong_info' ];
            $goods_info = $goods_info[ 'list' ];
            $poster = new PosterExtend(600, 960);
            $poster_2 = new PosterExtend(600, 960);
            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data' => [
                        'upload/poster/bg/jielong.png',
                        0,
                        0,
                        600,
                        960,
                        'square',
                        true,
                        1
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码
                    'data' => [
                        $h5_qrcode_info[ 'data' ][ 'path' ],
                        413,  //x
                        770,  //y
                        135,
                        135,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 接龙时间
                    'data' => [
                        $jielong_info[ 'jielong_time' ],
                        18,
                        [ 255, 95, 75 ],
                        60,
                        845,
                        500,
                        1,
                        true
                    ]
                ],
                [
                    'action' => 'imageText', // 接龙状态
                    'data' => [
                        $jielong_info[ 'jielong_status_name' ],
                        18,
                        [ 18, 18, 18 ],
                        270,
                        845,
                        500,
                        1,
                        true
                    ]
                ],
                [
                    'action' => 'imageCircularCopy', // 写入店铺头像
                    'data' => [
                        !empty($site_info[ 'data' ][ 'logo_square' ]) ? $site_info[ 'data' ][ 'logo_square' ] : 'public/uniapp/shop_img.png',
                        30,
                        40,
                        80,
                        80
                    ]
                ],
                [
                    'action' => 'imageText', // 写入店铺名称
                    'data' => [
                        !empty($site_name) ? $site_name : '单商户v5',
                        22,
                        [ 255, 255, 255 ],
                        130,
                        80,
                        440,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入接龙语
                    'data' => [
                        '"这个接龙不错，快和我一起参与吧！"',
                        18,
                        [ 255, 255, 255 ],
                        130,
                        115,
                        440,
                        1
                    ]
                ]
            ];

            $goods_option = [];
            $y = 0;
            foreach ($goods_info as &$v) {
                array_push($goods_option,
                    [
                        'action' => 'imageCopy', // 商品图
                        'data' => [
                            $v[ 'goods_image' ],
                            60,
                            190 + $y,//y
                            140,
                            140,
                            'square',
                            30,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品名称
                        'data' => [
                            $v[ 'goods_name' ],
                            16,
                            [ 89, 89, 89 ],
                            218,
                            225 + $y,//y
                            330,
                            2,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品促销语
                        'data' => [
                            $v[ 'introduction' ],
                            12,
                            [ 205, 205, 205 ],
                            218,
                            287 + $y,//y
                            330,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品售价
                        'data' => [
                            '¥ ' . $v[ 'discount_price' ],
                            20,
                            [ 255, 95, 75 ],
                            218,
                            326 + $y,//y
                            500,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入商品原价
                        'data' => [
                            '¥ ' . $v[ 'market_price' ],
                            16,
                            [ 205, 205, 205 ],
                            340,
                            326 + $y,//y
                            500,
                            1,
                            true,
                            !empty($v[ 'is_market_price' ]) ? 1 : 0,
                        ]
                    ],
                    [
                        'action' => 'imageCopy', // 删除线
                        'data' => [
                            'upload/poster/bg/del_line.png',
                            337,
                            283 + $y,//y
                            85,
                            64,
                            'square',
                            true,
                            !empty($v[ 'is_market_price' ]) ? 1 : 0,
                        ]
                    ]
                );
                $y += 180;
            }

            $option = array_merge($option, $goods_option);
            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/jielong', 'goods_' . $promotion_type . '_' . $qrcode_param[ 'jielong_id' ] . rand(0, 99999) . '_' . 'h5');

            if ($status) {
                $weapp_qrcode_info = $this->getGoodsQrcode('weapp', $page, $qrcode_param, $promotion_type, $site_id);
                if ($weapp_qrcode_info[ 'code' ] < 0) return $weapp_qrcode_info;
                $option_2 = [
                    [
                        'action' => 'imageCopy', // 背景图
                        'data' => [
                            'upload/poster/bg/jielong.png',
                            0,
                            0,
                            600,
                            960,
                            'square',
                            true,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageCopy', //太阳码
                        'data' => [
                            $weapp_qrcode_info[ 'data' ][ 'path' ],
                            383,  //x
                            740,  //y
                            165,
                            165,
                            'square',
                            0,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 接龙时间
                        'data' => [
                            $jielong_info[ 'jielong_time' ],
                            18,
                            [ 255, 95, 75 ],
                            60,
                            845,
                            500,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageText', // 接龙状态
                        'data' => [
                            $jielong_info[ 'jielong_status_name' ],
                            18,
                            [ 18, 18, 18 ],
                            270,
                            845,
                            500,
                            1,
                            true
                        ]
                    ],
                    [
                        'action' => 'imageCircularCopy', // 写入店铺头像
                        'data' => [
                            'public/uniapp/shop_img.png',
                            30,
                            40,
                            80,
                            80
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入店铺名称
                        'data' => [
                            !empty($site_name) ? $site_name : '单商户v5',
                            22,
                            [ 255, 255, 255 ],
                            130,
                            80,
                            440,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入接龙语
                        'data' => [
                            '"这个接龙不错，快和我一起参与吧！"',
                            18,
                            [ 255, 255, 255 ],
                            130,
                            115,
                            440,
                            1
                        ]
                    ]
                ];

                $goods_option_2 = [];
                $y_2 = 0;
                foreach ($goods_info as &$v) {
                    array_push($goods_option_2,
                        [
                            'action' => 'imageCopy', // 商品图
                            'data' => [
                                $v[ 'goods_image' ],
                                60,
                                190 + $y_2,//y
                                140,
                                140,
                                'square',
                                30,
                                1
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入商品名称
                            'data' => [
                                $v[ 'goods_name' ],
                                16,
                                [ 89, 89, 89 ],
                                218,
                                225 + $y_2,//y
                                330,
                                2,
                                true
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入商品促销语
                            'data' => [
                                $v[ 'introduction' ],
                                12,
                                [ 205, 205, 205 ],
                                218,
                                287 + $y_2,//y
                                330,
                                1,
                                true
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入商品售价
                            'data' => [
                                '¥ ' . $v[ 'discount_price' ],
                                20,
                                [ 255, 95, 75 ],
                                218,
                                326 + $y_2,//y
                                500,
                                1,
                                true
                            ]
                        ],
                        [
                            'action' => 'imageText', // 写入商品原价
                            'data' => [
                                '¥ ' . $v[ 'market_price' ],
                                16,
                                [ 205, 205, 205 ],
                                340,
                                326 + $y_2,//y
                                500,
                                1,
                                true,
                                !empty($v[ 'is_market_price' ]) ? 1 : 0,
                            ]
                        ],
                        [
                            'action' => 'imageCopy', // 删除线
                            'data' => [
                                'upload/poster/bg/del_line.png',
                                337,
                                283 + $y_2,//y
                                85,
                                64,
                                'square',
                                true,
                                !empty($v[ 'is_market_price' ]) ? 1 : 0,
                            ]
                        ]
                    );
                    $y_2 += 180;
                }

                $option_2 = array_merge($option_2, $goods_option_2);
                $option_res_2 = $poster_2->create($option_2);
                if (is_array($option_res_2)) return $option_res_2;
                $res_2 = $option_res_2->jpeg('upload/poster/jielong', 'goods_' . $promotion_type . '_' . $qrcode_param[ 'jielong_id' ] . rand(0, 999999) . '_' . 'weapp');

            }

            //if中的三元运算用法 true or 1==1 均可
            if ($res[ 'code' ] == 0 && ( $status ? $res_2[ 'code' ] == 0 : true )) {
                $upload = new Upload($site_id);
                $cloud_res = $upload->fileCloud($res[ 'data' ][ 'path' ]);

                if ($status) {
                    $cloud_res_2 = $upload->fileCloud($res_2[ 'data' ][ 'path' ]);
                }

                if ($cloud_res[ 'code' ] >= 0 && ( $status ? $cloud_res_2[ 'code' ] >= 0 : 1 == 1 )) {
                    $data = [
                        'h5_poster_path' => $cloud_res[ 'data' ],
                        'h5_qrcode_path' => $h5_qrcode_info[ 'data' ][ 'path' ],
                        'h5_qrcode_url' => $h5_qrcode_info[ 'data' ][ 'url' ],
                        'weapp_poster_path' => $status ? $cloud_res_2[ 'data' ] : '',
                        'weapp_qrcode_path' => $status ? $weapp_qrcode_info[ 'data' ][ 'path' ] : '',
                        'status' => $status,
                        'code' => uniqid()
                    ];
                    return $this->success($data);
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
        $info = model('member')->getInfo([ 'member_id' => $member_id ], 'nickname,headimg');
        return $info;
    }

    /**
     * 获取商品信息
     * @param unknown $jielong_id
     */
    private function getGoodsInfo($jielong_id, $site_id)
    {
        $condition = [
            [ 'pjg.jielong_id', '=', $jielong_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $site_id ]
        ];
        $alias = 'pjg';
        $join = [
            [ 'goods g', 'pjg.goods_id = g.goods_id', 'inner' ],
            [ 'goods_sku gs', 'g.sku_id = gs.sku_id', 'inner' ]
        ];
        $field = 'g.goods_image,g.goods_name,g.goods_stock,gs.price,gs.market_price,gs.discount_price,g.introduction';
        //接龙活动中商品信息
        $list = model('promotion_jielong_goods')->pageList($condition, $field, 'id asc', 1, 3, $alias, $join);
        $info = model('promotion_jielong')->getInfo([ 'jielong_id' => $jielong_id ], 'status,start_time,end_time');
        if ($info[ 'status' ] == 0) {
            $list[ 'jielong_info' ][ 'jielong_status_name' ] = '开始';
            $list[ 'jielong_info' ][ 'jielong_time' ] = date("m月-d日 H:i:s", $info[ 'start_time' ]);
        } else {
            $list[ 'jielong_info' ][ 'jielong_status_name' ] = '结束';
            $list[ 'jielong_info' ][ 'jielong_time' ] = date("m月d日 H:i:s", $info[ 'end_time' ]);
        }

        //获取第一张图片
        foreach ($list[ 'list' ] as &$v) {
            $v[ 'goods_image' ] = explode(',', $v[ 'goods_image' ])[ 0 ];
            $v[ 'introduction' ] = $v[ 'introduction' ] ?: '精选好物，等你来抢';
            $v[ 'is_market_price' ] = 1;
            if ($v[ 'market_price' ] == 0) {
                $v[ 'is_market_price' ] = 0;
            }

            //判断是否是全路径
//            if (!preg_match('/(http:\/\/)|(https:\/\/)/i', $v['goods_image'])) {
//                $v['goods_image'] = __ROOT__.'/'.$v['goods_image'];
//            }
        }
        return $list;
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
        $res = event('Qrcode', [
            'site_id' => $site_id,
            'app_type' => $app_type,
            'type' => 'create',
            'data' => [ 'jielong_id' => $qrcode_param[ 'jielong_id' ] ],
            'page' => $page,
            'qrcode_path' => 'upload/qrcode/jielong',
//            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param['jielong_id'] . '_' . $qrcode_param['source_member'] . '_' . $site_id,
            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param[ 'jielong_id' ] . '_' . $site_id,
        ], true);
        return $res;
    }


    /**
     * 获取接龙推广
     * @param $page
     * @param $qrcode_param
     * @param $promotion_type
     * @param $site_id
     * @return array
     */
    public function getSolitaireQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {

        $params = [
            'site_id' => $site_id,
            'data' => [ 'jielong_id' => $qrcode_param[ 'jielong_id' ] ],
            'page' => $page,
            'promotion_type' => $promotion_type,
            'app_type' => $app_type,
            'h5_path' => $page . '?jielong_id=' . $qrcode_param[ 'jielong_id' ],
            'qrcode_path' => 'upload/qrcode/jielong',
            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param[ 'jielong_id' ] . '_' . $site_id,
        ];

        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}