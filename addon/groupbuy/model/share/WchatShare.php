<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\model\share;

use addon\groupbuy\model\Groupbuy;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '团购列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_GROUPBUY_LIST_PROMOTE',
            'path' => [ '/pages_promotion/groupbuy/list' ],
            'method_prefix' => 'goodsList',
        ],
        [
            'title' => '团购详情',
            'config_key' => 'WCHAT_SHARE_CONFIG_GROUPBUY_PROMOTE',
            'path' => [ '/pages_promotion/groupbuy/detail' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 4;

    /**
     * 团购列表
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
     * 团购列表分享配置
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
                'title' => "团购列表",
                'desc' => "团购实惠更多",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/groupbuy/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 团购分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);
        if (isset($query[ 'groupbuy_id' ]) || isset($query[ 'id' ])) {
            $groupbuy_id = $query['id'] ?? $query['groupbuy_id'];
            $goods = new Groupbuy();
            $sku_info = $goods->getGroupbuyInfo([ [ 'groupbuy_id', '=', $groupbuy_id ] ])[ 'data' ];
            if (!empty($sku_info)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $sku_info[ 'goods_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $sku_info[ 'groupbuy_price' ], $config_data[ 'value' ][ 'desc' ]);
                $link = $this->getShareLink($param);
                $image_url = $sku_info[ 'goods_image' ];

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
