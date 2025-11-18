<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\model;

use app\model\BaseModel;
use app\model\goods\Goods;

/**
 * 专题活动
 */
class TopicGoods extends BaseModel
{

    /**
     * 添加专题商品
     * @param $topic_id
     * @param $site_id
     * @param $sku_ids
     * @return array
     */
    public function addTopicGoods($topic_id, $site_id, $sku_ids)
    {
        $sku_list = model('goods_sku')->getList([
            [ 'sku_id', 'in', $sku_ids ],
            [ 'site_id', '=', $site_id ],
        ], 'goods_id, sku_id, price');
        $topic_info = model("promotion_topic")->getInfo([ [ 'topic_id', '=', $topic_id ] ], 'start_time, end_time');
        $data = [];
        $goods = new Goods();
        foreach ($sku_list as $val) {
            $goods_count = model("promotion_topic_goods")->getCount([ 'topic_id' => $topic_id, 'sku_id' => $val[ 'sku_id' ] ]);
            if (empty($goods_count)) {
                $data[] = [
                    'topic_id' => $topic_id,
                    'site_id' => $site_id,
                    'sku_id' => $val[ 'sku_id' ],
                    'topic_price' => $val[ 'price' ],
                    'start_time' => $topic_info[ 'start_time' ],
                    'end_time' => $topic_info[ 'end_time' ]
                ];
                $goods->modifyPromotionAddon($val[ 'goods_id' ], [ 'topic' => $topic_id ]);
            }
        }
        model("promotion_topic_goods")->addList($data);

        return $this->success();
    }

    /**
     * 修改专题商品
     * @param $topic_id
     * @param $site_id
     * @param $sku_id
     * @param $price
     * @return array
     */
    public function editTopicGoods($topic_id, $site_id, $sku_id, $price)
    {
        $data = [
            'topic_id' => $topic_id,
            'site_id' => $site_id,
            'sku_id' => $sku_id,
            'topic_price' => $price
        ];
        model("promotion_topic_goods")->update($data, [ [ 'topic_id', '=', $topic_id ], [ 'sku_id', '=', $sku_id ], [ 'site_id', '=', $site_id ] ]);
        return $this->success();
    }

    /**
     * 删除专题商品
     * @param $topic_id
     * @param $site_id
     * @param $sku_id
     * @return array
     */
    public function deleteTopicGoods($topic_id, $site_id, $sku_id)
    {
        $goods_sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $sku_id ] ], 'goods_id');
        $goods = new Goods();
        $goods->modifyPromotionAddon($goods_sku_info[ 'goods_id' ], [ 'topic' => $topic_id ], true);
        model("promotion_topic_goods")->delete([ [ 'topic_id', '=', $topic_id ], [ 'sku_id', '=', $sku_id ], [ 'site_id', '=', $site_id ] ]);
        return $this->success();
    }

    public function getTopicIdByGoodsId($topic_id, $sku_id)
    {
        $info = model("promotion_topic_goods")->getInfo([ [ 'topic_id', '=', $topic_id ], [ 'sku_id', '=', $sku_id ]], 'id');
        return $info;
    }

    /**
     * 获取专题商品详情
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getTopicGoodsDetail($condition, $field = '')
    {
        if (empty($field)) {
            $field = 'sku.goods_id,sku.sku_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.stock,sku.click_num,
            (g.sale_num + g.virtual_sale) as sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.site_id,sku.goods_content,
            sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,
            sku.support_trade_type,sku.unit,sku.video_url,sku.evaluate,sku.goods_service_ids,ptg.id,ptg.topic_id,ptg.start_time,
            ptg.end_time,ptg.topic_price,pt.topic_name,g.goods_image,g.goods_stock,g.goods_name,sku.qr_id,sku.market_price,
            g.stock_show,g.sale_show,g.market_price_show,g.barrage_show,g.label_name,pt.remark';
        }
        $alias = 'ptg';
        $join = [
            [ 'goods_sku sku', 'ptg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_topic pt', 'pt.topic_id = ptg.topic_id', 'inner' ],
        ];

        $info = model('promotion_topic_goods')->getInfo($condition, $field, $alias, $join);
        if (!empty($info)) {
            if (isset($info[ 'sale_num' ])) {
                $info[ 'sale_num' ] = numberFormat($info[ 'sale_num' ]);
            }
            if (isset($info[ 'stock' ])) {
                $info[ 'stock' ] = numberFormat($info[ 'stock' ]);
            }
            if (isset($info[ 'goods_stock' ])) {
                $info[ 'goods_stock' ] = numberFormat($info[ 'goods_stock' ]);
            }
        }
        return $this->success($info);
    }

    /**
     * 获取专题商品详情
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getTopicGoodsSkuList($condition)
    {
        $field = 'sku.sku_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.stock,sku.sku_image,sku.sku_images,sku.goods_spec_format,ptg.id,ptg.topic_id,ptg.start_time,ptg.end_time,ptg.topic_price,pt.topic_name,g.goods_image';
        $alias = 'ptg';
        $join = [
            [ 'goods_sku sku', 'ptg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_topic pt', 'pt.topic_id = ptg.topic_id', 'inner' ],
        ];

        $list = model('promotion_topic_goods')->getList($condition, $field, 'ptg.id asc', $alias, $join);
        foreach ($list as $k => $v) {
            $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
        }
        return $this->success($list);
    }

    /**
     * 获取专题商品列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return mixed
     */
    public function getTopicGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '')
    {
        $alias = 'nptg';
        if (empty($field)) {
            $field = 'ngs.sku_id, ngs.sku_name, ngs.sku_no, ngs.sku_spec_format, ngs.price, ngs.market_price,
            ngs.cost_price, ngs.discount_price, ngs.promotion_type, ngs.stock,
            ngs.weight, ngs.volume, ngs.click_num, (g.sale_num + g.virtual_sale) as sale_num, ngs.collect_num, ngs.sku_image,
            ngs.sku_images, ngs.goods_id, ngs.goods_class, ngs.goods_class_name, ngs.goods_attr_class,
            ngs.goods_attr_name, ngs.goods_name,ngs.goods_state,
            ngs.is_virtual, ngs.virtual_indate, ngs.is_free_shipping, ngs.shipping_template, ngs.goods_spec_format,
            ngs.goods_attr_format, ngs.is_delete, ngs.introduction, ngs.keywords, ngs.unit, ngs.sort,npt.topic_name,
            npt.topic_adv, npt.status, nptg.id,nptg.start_time, nptg.end_time, nptg.topic_price, npt.topic_id, g.stock_show,g.sale_show,g.market_price_show';
        }
        $join = [
            [ 'goods g', 'nptg.goods_id = g.goods_id', 'inner' ],
            [ 'goods_sku ngs', 'nptg.sku_id = ngs.sku_id', 'inner' ],
            [ 'promotion_topic npt', 'nptg.topic_id = npt.topic_id', 'inner' ],
        ];
        $res = model('promotion_topic_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($res[ 'list' ] as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $res[ 'list' ][ $k ][ 'stock' ] = numberFormat($res[ 'list' ][ $k ][ 'stock' ]);
            }
            if (isset($v[ 'sale_num' ])) {
                $res[ 'list' ][ $k ][ 'sale_num' ] = numberFormat($res[ 'list' ][ $k ][ 'sale_num' ]);
            }
        }
        return $this->success($res);
    }

    /**
     * 判断规格值是否禁用
     * @param $topic_id
     * @param $site_id
     * @param string $goods_spec_format
     * @param int $sku_id
     * @return int|mixed
     */
    public function getGoodsSpecFormat($topic_id, $site_id, $goods_spec_format = '')
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_topic_goods')->getColumn([ [ 'topic_id', '=', $topic_id ], [ 'site_id', '=', $site_id ] ], 'sku_id');
        $goods_model = new Goods();
        $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        return $res;
    }

}