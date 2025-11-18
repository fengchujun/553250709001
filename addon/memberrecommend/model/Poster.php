<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberrecommend\model;

use app\model\BaseModel;
use extend\Poster as PosterExtend;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 海报
     */
    public function poster($app_type, $page, $qrcode_param, $site_id)
    {
        try {
            $qrcode_info = $this->getQrcode($app_type, $page, $qrcode_param, $site_id);
            if ($qrcode_info['code'] < 0) return $qrcode_info;

            $poster = new PosterExtend(750, 1208);
            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data'   => [
                        'upload/poster/bg/memberrecommend.png',
                        0,
                        0,
                        750,
                        1208,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码
                    'data'   => [
                        $qrcode_info['data']['path'],
                        160,
                        378,
                        430,
                        430,
                        'square',
                        0,
                        1
                    ]
                ]
            ];

            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/memberrecommend', 'member_' . $qrcode_param['source_member'] . '_' . $app_type);
            return $res;
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品二维码
     * @param unknown $app_type 请求类型
     * @param unknown $page uniapp页面路径
     * @param unknown $qrcode_param 二维码携带参数
     */
    private function getQrcode($app_type, $page, $qrcode_param, $site_id)
    {
        $res = event('Qrcode', [
            'site_id'     => $site_id,
            'app_type'    => $app_type,
            'type'        => 'create',
            'data'        => $qrcode_param,
            'page'        => $page,
            'qrcode_path' => 'upload/qrcode/memberrecommend',
            'qrcode_name' => 'member_' . $qrcode_param['source_member'] . '_' . $app_type,
        ], true);
        return $res;
    }
}