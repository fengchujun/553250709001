<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointexchange\model\share;

use addon\pointexchange\model\Exchange as ExchangeModel;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '积分商城',
            'config_key' => 'WCHAT_SHARE_CONFIG_POINTEXCHANGE_LIST',
            'path' => [ '/pages_promotion/point/list' ],
            'method_prefix' => 'goodsList',
        ],
        [
            'title' => '积分商品',
            'config_key' => 'WCHAT_SHARE_CONFIG_POINTEXCHANGE_DETAIL',
            'path' => [ '/pages_promotion/point/detail' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 6;

    /**
     * 积分商城列表
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
     * 积分商城列表分享配置
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
                'title' => "积分商城",
                'desc' => "积分兑换更优惠哦",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/pointexchange/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 积分商城详情
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ] ?? 0;
        parse_str(parse_url($param[ 'url' ])[ 'query' ] ?? '', $query);

        if (isset($query[ 'id' ])) {
            $id = $query[ 'id' ];
            $exchange_model = new ExchangeModel();
            $exchange_info = $exchange_model->getExchangeInfo($id, 'type, type_id');
            $exchange_info = $exchange_info[ 'data' ];
            $condition = [
                [ 'peg.id', '=', $id ],
                [ 'peg.site_id', '=', $site_id ],
                [ 'peg.state', '=', 1 ],
            ];
            $exchange_detail = $exchange_model->getExchangeDetail($condition, $exchange_info[ 'type' ])[ 'data' ];
            if (!empty($exchange_detail)) {
                $image_url = '';
                $title = '';
                switch ( $exchange_detail[ 'type' ] ) {
                    case 1://商品
                        $title = $exchange_detail[ 'sku_name' ];
                        $image_url = $exchange_detail[ 'sku_image' ];
                        break;
                    case 2://优惠券
                    case 3://红包
                        $title = $exchange_detail[ 'name' ];
                        $image_url = $exchange_detail[ 'image' ];
                        break;
                }
                if ($image_url) {
                    $image_url = img($image_url);
                } else {
                    $image_url = $this->getDefaultShareIcon();
                }
                $exchange_condition = [];
                if ($exchange_detail[ 'point' ] > 0) $exchange_condition[] = "{$exchange_detail['point']}积分";
                if ($exchange_detail[ 'exchange_price' ] > 0) $exchange_condition[] = "￥{$exchange_detail['exchange_price']}";
                $desc = "仅需" . join("+", $exchange_condition) . "即可兑换";
                $link = $this->getShareLink($param);

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
