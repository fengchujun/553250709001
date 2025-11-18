<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\model\share;

use addon\jielong\model\Jielong as JielongModel;
use app\model\share\WchatShareBase as BaseModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '接龙分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_JIELONG_PROMOTE',
            'path' => [ '/pages_promotion/jielong/jielong' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 4;

    /**
     * 接龙分享
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ] ?? 0;
        $member_id = $param[ 'member_id' ] ?? 0;
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);
        if (isset($query[ 'jielong_id' ]) || isset($query[ 'id' ])) {
            $jielong_id = $query['id'] ?? $query['jielong_id'];
            $condition = [
                [ 'pjg.jielong_id', '=', $jielong_id ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ],
                [ 'g.site_id', '=', $site_id ]
            ];
            $jielong_model = new JielongModel();
            $sku_info = $jielong_model->getJielongActivityDetail($condition, 1, PAGE_LIST_ROWS, '', '', $jielong_id)[ 'data' ];
            if (!empty($sku_info)) {
                $config_method = preg_replace('/Data$/', 'Config', __FUNCTION__);
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->$config_method($param);
                $title = str_replace('{goods_name}', $sku_info[ 'info' ][ 'jielong_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $sku_info[ 'list' ][ 0 ][ 'price' ], $config_data[ 'value' ][ 'desc' ]);
                $desc = str_replace('\n', '\r\n', $desc);
                $link = $parse_res[ 'scheme' ] . '://' . $parse_res[ 'host' ] . $parse_res[ 'path' ] . '?jielong_id=' . $jielong_id;
                if (!empty($member_id)) $link .= '&source_member=' . $member_id;
                $image_url = $sku_info[ 'list' ][ 0 ][ 'goods_image' ];

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