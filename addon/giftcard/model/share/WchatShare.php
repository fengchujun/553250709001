<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\model\share;

use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '礼品卡列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_GIGTCARD_LIST_PROMOTE',
            'path' => [ '/pages_promotion/giftcard/index' ],
            'method_prefix' => 'goodsList',
        ],
        [
            'title' => '礼品卡详情',
            'config_key' => 'WCHAT_SHARE_CONFIG_GIGTCARD_PROMOTE',
            'path' => [ '/pages_promotion/giftcard/card_info' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 5;

    /**
     * 礼品卡分享数据
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
     * 礼品卡列表分享配置
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
                'title' => "礼品卡列表",
                'desc' => "购卡送好礼",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/giftcard/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 礼品卡分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ] ?? 0;
        $url = $param[ 'url' ];

        //跳转路径
        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);

        if (isset($query[ 'id' ]) || isset($query[ 'giftcard_id' ])) {
            $giftcard_id = $query['id'] ?? $query['giftcard_id'];
            $giftcard_model = new GiftCardModel();
            $field = 'id,card_name,selling_price,point,goods_ids,balance,card_count,max_buy,time_type,youxiao_time,youxiao_day,card_cover,site_id,is_balance';
            $sku_info = $giftcard_model->getGiftcardGoodsInfo([ [ 'site_id', '=', $site_id ], [ 'id', '=', $giftcard_id ] ], $field)[ 'data' ];
            if (!empty($sku_info)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $sku_info[ 'card_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $sku_info[ 'selling_price' ], $config_data[ 'value' ][ 'desc' ]);
                $link = $this->getShareLink($param);
                $image_url = $sku_info[ 'card_cover' ];

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
