<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\model\share;

use addon\presale\model\Presale as PresaleModel;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '预售分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_PRESALE_PROMOTE',
            'path' => [ '/pages_promotion/presale/detail' ],
            'method_prefix' => 'goodsDetail',
        ],
        [
            'title' => '预售列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_PRESALE_LIST_PROMOTE',
            'path' => [ '/pages_promotion/presale/list' ],
            'method_prefix' => 'goodsList',
        ],
    ];

    protected $sort = 7;

    /**
     * 预售列表
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
     * 预售列表分享配置
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
                'title' => "预售列表",
                'desc' => "预先抢购\n优惠更多",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/presale/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 预售分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $member_id = $param[ 'member_id' ] ?? 0;
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);

        if (isset($query[ 'id' ])) {
            $id = $query[ 'id' ];
            $presale_model = new PresaleModel();
            $condition = [
                [ 'pp.presale_id', '=', $id ],
                [ 'pp.status', '=', 1 ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];
            $presale_info = $presale_model->getPresaleGoodsDetail($condition)[ 'data' ];
            if (!empty($presale_info)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $presale_info[ 'sku_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $presale_info[ 'presale_deposit' ], $config_data[ 'value' ][ 'desc' ]);
                $link = $parse_res[ 'scheme' ] . '://' . $parse_res[ 'host' ] . $parse_res[ 'path' ] . '?id=' . $id;
                if (!empty($member_id)) $link .= '&source_member=' . $member_id;
                $image_url = $presale_info[ 'sku_image' ];

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
