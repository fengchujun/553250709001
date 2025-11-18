<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\model\share;

use addon\pintuan\model\Pintuan;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '拼团分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_PINTUAN_PROMOTE',
            'path' => [ '/pages_promotion/pintuan/detail' ],
            'method_prefix' => 'goodsDetail',
        ],
        [
            'title' => '拼团列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_PINTUAN_LIST_PROMOTE',
            'path' => [ '/pages_promotion/pintuan/list' ],
            'method_prefix' => 'goodsList',
        ],
    ];

    protected $sort = 6;

    /**
     * 拼团列表
     * @param $param
     * @return array
     */
    protected function goodsListShareData($param)
    {
        //跳转路径
        $link = $this->getShareLink($param);
        $config_data = $this->goodsListShareConfig($param)[ 'value' ];

        $data = [
            'link' => $link,
            'desc' => $config_data[ 'desc' ],
            'imgUrl' => $config_data[ 'imgUrl' ],
            'title' => $config_data[ 'title' ]
        ];
        return [
            'permission' => [
                'hideOptionMenu' => false,
                'hideMenuItems' => [],
            ],
            'data' => $data,//分享内容
        ];
    }

    /**
     * 拼团列表分享配置
     * @param $param
     * @return array
     */
    public function goodsListShareConfig($param)
    {
        $site_id = $param[ 'site_id' ];
        $config = $param[ 'config' ];

        $config_model = new ConfigModel();
        $data = $config_model->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', $config[ 'config_key' ] ] ])[ 'data' ];
        if (empty($data[ 'value' ])) {
            $data[ 'value' ] = [
                'title' => "拼团列表",
                'desc' => "拼实惠\n团惊喜",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/pintuan/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 拼团分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);

        if (isset($query[ 'pintuan_id' ]) || isset($query[ 'id' ])) {
            $pintuan_id = $query['pintuan_id'] ?? $query['id'];
            $goods = new Pintuan();
            $sku_info = $goods->getPintuanGoodsDetail([ [ 'ppg.pintuan_id', '=', $pintuan_id ] ])[ 'data' ];
            if (!empty($sku_info)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $sku_info[ 'sku_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $sku_info[ 'pintuan_price' ], $config_data[ 'value' ][ 'desc' ]);
                $link = $this->getShareLink($param);
                $image_url = $sku_info[ 'sku_image' ];

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
