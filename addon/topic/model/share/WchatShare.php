<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\model\share;

use addon\topic\model\Topic as TopicModel;
use app\model\share\WchatShareBase as BaseModel;
use addon\topic\model\TopicGoods as TopicGoodsModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '专题列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_TOPIC_LIST',
            'path' => [ '/pages_promotion/topics/list' ],
            'method_prefix' => 'topicList',
        ],
        [
            'title' => '专题详情',
            'config_key' => 'WCHAT_SHARE_CONFIG_TOPIC_DETAIL',
            'path' => [ '/pages_promotion/topics/detail' ],
            'method_prefix' => 'topicDetail',
        ],
        [
            'title' => '专题商品详情',
            'config_key' => 'WCHAT_SHARE_CONFIG_TOPIC_GOODS_DETAIL',
            'path' => [ '/pages_promotion/topics/goods_detail' ],
            'method_prefix' => 'goodsDetail',
        ],
    ];

    protected $sort = 9;

    /**
     * 专题列表
     * @param $param
     * @return array
     */
    protected function topicListShareData($param)
    {
        //跳转路径
        $link = $this->getShareLink($param);
        $config_data = $this->topicListShareConfig($param)[ 'value' ];

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
     * 专题列表分享配置
     * @param $param
     * @return array
     */
    public function topicListShareConfig($param)
    {
        $site_id = $param[ 'site_id' ];
        $config = $param[ 'config' ];

        $config_model = new ConfigModel();
        $data = $config_model->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', $config[ 'config_key' ] ] ])[ 'data' ];
        if (empty($data[ 'value' ])) {
            $data[ 'value' ] = [
                'title' => "专题列表",
                'desc' => "更多活动\n快来参与",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/topic/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 专题详情
     * @param $param
     * @return array
     */
    protected function topicDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ];
        //链接参数
        parse_str(parse_url($param[ 'url' ])[ 'query' ] ?? '', $query);
        if (isset($query[ 'topic_id' ])) {
            $topic_model = new TopicModel();
            $topic_detail = $topic_model->getTopicDetail([
                [ 'topic_id', '=', $query[ 'topic_id' ] ],
                [ 'site_id', '=', $site_id ],
            ])[ 'data' ];
            if (!empty($topic_detail)) {
                //跳转路径
                $link = $this->getShareLink($param);
                $data = [
                    'link' => $link,
                    'desc' => $topic_detail[ 'remark' ],
                    'imgUrl' => img($topic_detail[ 'topic_adv' ]),
                    'title' => $topic_detail[ 'topic_name' ],
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

    /**
     * 专题分享数据
     * @param $param
     * @return array
     */
    protected function goodsDetailShareData($param)
    {
        $url = $param[ 'url' ];

        $parse_res = parse_url($url);
        parse_str($parse_res[ 'query' ] ?? '', $query);

        if (isset($query[ 'id' ]) || isset($query[ 'topic_id' ])) {
            $id = $query['id'] ?? $query['topic_id'];
            $goods = new TopicGoodsModel();
            $condition = [
                [ 'ptg.id', '=', $id ],
                [ 'pt.status', '=', 2 ]
            ];
            $sku_info = $goods->getTopicGoodsDetail($condition)[ 'data' ];
            if (!empty($sku_info)) {
                $config_model = new \app\model\share\WchatShare();
                $config_data = $config_model->goodsDetailShareConfig($param);

                $title = str_replace('{goods_name}', $sku_info[ 'sku_name' ], $config_data[ 'value' ][ 'title' ]);
                $desc = str_replace('{price}', $sku_info[ 'topic_price' ], $config_data[ 'value' ][ 'desc' ]);
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
