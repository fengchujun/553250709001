<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\model\membercard;

use addon\giftcard\model\transfer\Blessing;
use app\model\BaseModel;
use app\model\system\Site;
use app\model\upload\Upload;
use extend\Poster as PosterExtend;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 海报
     */
    public function poster($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        try {
            $blessing_model = new Blessing();
            $params = array (
                'no' => $qrcode_param[ 'no' ],
                'member_id' => $qrcode_param[ 'source_member' ]
            );
            $card_info = $blessing_model->getBlessingDetail($params)[ 'data' ] ?? [];
            if (empty($card_info)) return $this->error('未获取到信息');

            $qrcode_info = $this->getQrcode($app_type, $page, $qrcode_param, $promotion_type, $site_id);
            if ($qrcode_info[ 'code' ] < 0) return $qrcode_info;
            //判断海报是否存在或停用
//            $site_model = new Site();
//            $condition = array (
//                [ "site_id", "=", $site_id ]
//            );
//            $site_info = $site_model->getSiteInfo($condition);
            if (!empty($qrcode_param[ 'source_member' ])) {
                $member_info = $this->getMemberInfo($qrcode_param[ 'source_member' ]);
            }

            $poster_width = 720;
            $poster_height = 1280;
            $poster = new PosterExtend($poster_width, $poster_height);
            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data' => [
                        img('upload/poster/bg/promotion_4.jpg'),
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
                        $card_info[ 'card_cover' ],
                        100,
                        300,
                        520,
                        310,
                        'square',
                        5,
                        1
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码
                    'data' => [
                        $qrcode_info[ 'data' ][ 'path' ],
                        100,
                        930,
                        150,
                        150,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入礼品卡名称
                    'data' => [
                        $card_info[ 'card_name' ],
                        25,
                        [34, 34, 34],
                        100,
                        660,
                        540,
                        2,
                        true,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入祝福语
                    'data' => [
                        $card_info[ 'blessing' ],
                        22,
                        [255, 0, 0],
                        150,
                        800,
                        480,
                        1,
                        false,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入固定文字
                    'data' => [
                        '扫码查看礼品详情',
                        22,
                        [51, 51, 51],
                        290,
                        1000,
                        548,
                        1,
                        true,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入有效期
                    'data' => [
                        '有效期：' . ($card_info[ 'valid_time' ] > 0 ? time_to_date($card_info[ 'valid_time' ]) : '永久有效'),
                        18,
                        [51, 51, 51],
                        290,
                        1060,
                        548,
                        1,
                        false,
                        1
                    ]
                ],
            ];

            if (!empty($member_info)) {
                $member_option = [
                    [
                        'action' => 'imageCircularCopy', // 写入用户头像
                        'data' => [
                            !empty($member_info[ 'headimg' ]) ? $member_info[ 'headimg' ] : 'public/static/img/default_img/head.png',
                            86,
                            90,
                            101,
                            101
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入分享人昵称
                        'data' => [
                            $member_info[ 'nickname' ],
                            28,
                            [255, 255, 255],
                            210,
                            135,
                            420,
                            1,
                            true,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText',
                        'data' => [
                            '送给您一张礼品卡',
                            22,
                            [255, 255, 255],
                            200,
                            185,
                            430,
                            1,
                            0,
                            1
                        ]
                    ]
                ];
                $option = array_merge($option, $member_option);
            }

            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/goods', 'goods_' . $promotion_type . '_' . $card_info[ 'no' ] . '_' . $qrcode_param[ 'source_member' ] . '_' . time() . '_' . $app_type);
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
     * @param $member_id
     * @return mixed
     */
    private function getMemberInfo($member_id)
    {
        $info = model('member')->getInfo(['member_id' => $member_id], 'nickname,headimg');
        return $info;
    }

    /**
     * 获取二维码
     * @param unknown $app_type 请求类型
     * @param unknown $page uniapp页面路径
     * @param unknown $qrcode_param 二维码携带参数
     * @param string $promotion_type 活动类型 null为无活动
     */
    private function getQrcode($app_type, $page, $qrcode_param, $promotion_type, $site_id)
    {
        $res = event('Qrcode', [
            'site_id' => $site_id,
            'app_type' => $app_type,
            'type' => 'create',
            'data' => $qrcode_param,
            'page' => $page,
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param[ 'no' ] . '_' . $qrcode_param[ 'source_member' ] . '_' . $site_id,
        ], true);
        return $res;
    }

    /**
     * 获取海报信息
     * @param $template_id
     * @return mixed
     */
    private function getTemplateInfo($template_id)
    {
        $info = model('poster_template')->getInfo(['template_id' => $template_id], 'template_id,template_status');
        return $info;
    }

    /**
     * 删除分享图片
     * @param int $pintuan_id
     */
    public function clearShareImg(int $pintuan_id)
    {
        $dir = 'upload/share_img/pintuan_' . $pintuan_id;
        @deleteDir($dir);
    }
}