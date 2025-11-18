<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\api\controller;

use addon\topic\model\Poster;
use addon\topic\model\Topic as TopicModel;
use addon\topic\model\TopicGoods as TopicGoodsModel;
use app\api\controller\BaseApi;
use app\model\goods\GoodsApi;
use app\model\system\Promotion as PromotionModel;

/**
 * 专题活动商品
 */
class Topicgoods extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {
        $this->checkToken();
        $id = $this->params['topic_id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $topic_goods_model = new TopicGoodsModel();
        $condition = [
            [ 'ptg.id', '=', $id ],
            [ 'pt.status', '=', 2 ]
        ];
        $goods_sku_detail = $topic_goods_model->getTopicGoodsDetail($condition)[ 'data' ];

        if (empty($goods_sku_detail)) return $this->response($this->error());

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            $goods_spec_format = $topic_goods_model->getGoodsSpecFormat($goods_sku_detail[ 'topic_id' ], $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        // 处理公共数据
        $goods_sku_api = new GoodsApi();
        $goods_sku_api->handleGoodsDetailData($res[ 'goods_sku_detail' ], $this->member_id, $this->site_id);

        return $this->response($this->success($res));

    }

    /**
     * 查询商品SKU集合
     * @return false|string
     */
    public function goodsSku()
    {
        $goods_id = $this->params['goods_id'] ?? 0;
        $topic_id = $this->params['topic_id'] ?? 0;

        if (empty($topic_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $topic_goods_model = new TopicGoodsModel();
        $condition = [
            [ 'ptg.topic_id', '=', $topic_id ],
            [ 'ptg.goods_id', '=', $goods_id ],
            [ 'pt.status', '=', 2 ]
        ];
        $list = $topic_goods_model->getTopicGoodsSkuList($condition);
        if (!empty($list[ 'data' ])) {
            foreach ($list[ 'data' ] as $k => $v) {
                if (!empty($v[ 'goods_spec_format' ])) {
                    $goods_spec_format = $topic_goods_model->getGoodsSpecFormat($v[ 'topic_id' ], $this->site_id, $v[ 'goods_spec_format' ]);
                    $list[ 'data' ][ $k ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
                }
            }
        }

        return $this->response($list);
    }

    /**
     * 列表信息
     */
    public function page()
    {
        $topic_id = $this->params['topic_id'] ?? 0;
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;

        if (empty($topic_id)) {
            return $this->response($this->error('', 'REQUEST_TOPIC_ID'));
        }
        $condition = [
            [ 'nptg.topic_id', '=', $topic_id ],
            [ 'ngs.goods_state', '=', 1 ],
            [ 'ngs.is_delete', '=', 0 ],
            [ 'nptg.default', '=', 1 ]
        ];
        $order = 'nptg.id asc';
        $topic_goods_model = new TopicGoodsModel();

        $topic_model = new TopicModel();
        $info = $topic_model->getTopicInfo([ [ "topic_id", "=", $topic_id ] ], 'bg_color,topic_adv,topic_name');
        $info = $info[ 'data' ];

        $res = $topic_goods_model->getTopicGoodsPageList($condition, $page, $page_size, $order);
//        $res[ 'data' ][ 'bg_color' ] = $info[ 'bg_color' ];
        $res[ 'data' ][ 'topic_adv' ] = $info[ 'topic_adv' ];
        $res[ 'data' ][ 'topic_name' ] = $info[ 'topic_name' ];

        $promotion_model = new PromotionModel();
        $zone_config = $promotion_model->getPromotionZoneConfig('topic', $this->site_id)[ 'data' ][ 'value' ];
        $res[ 'data' ][ 'bg_color' ] = $zone_config[ 'bg_color' ];

        return $this->response($res);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'topic';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $this->member_id;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }

}