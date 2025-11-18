<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bale\model\share;

use app\model\share\WchatShareBase as BaseModel;
use addon\bale\model\Bale as BaleModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '打包一口价',
            'config_key' => 'WCHAT_SHARE_CONFIG_BALE_PROMOTE',
            'path' => [ '/pages_promotion/bale/detail' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 9;

    /**
     * 打包一口价分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ] ?? 0;
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);

        if (isset($query[ 'id' ]) || isset($query[ 'bale_id' ])) {
            $id = $query['id'] ?? $query['bale_id'];
            $bale_model = new BaleModel();
            $bale_detail = $bale_model->getBaleDetail($id, $site_id)[ 'data' ];
            if (!empty($bale_detail)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $bale_detail[ 'name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', sprintf("%.2f", $bale_detail[ 'price' ] / $bale_detail[ 'num' ]), $config_data[ 'value' ][ 'desc' ]);
                $link = $this->getShareLink($param);
                $image_url = $bale_detail[ 'sku_list' ][ 0 ][ 'sku_image' ] ?? '';

                $data = [
                    'title' => $title,
                    'desc' => $desc,
                    'link' => $link,
                    'imgUrl' => $image_url,
                ];
                return [
                    'permission' => [
                        'hideOptionMenu' => false,
                        'hideMenuItems' => [],
                    ],
                    'data' => $data,//分享内容
                ];
            }
        }
    }
}
