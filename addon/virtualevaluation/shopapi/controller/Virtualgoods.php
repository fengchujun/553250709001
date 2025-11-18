<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\virtualcard\shopapi\controller;

use addon\virtualcard\model\VirtualGoods as VirtualGoodsModel;
use app\shopapi\controller\BaseApi;

/**
 * 卡密商品
 * Class Virtualgoods
 * @package app\shop\controller
 */
class Virtualgoods extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function addGoods()
    {
        $data = [
            'goods_name' => $this->params['goods_name'] ?? '',// 商品名称,
            'goods_attr_class' => $this->params['goods_attr_class'] ?? '',// 商品类型id,
            'goods_attr_name' => $this->params['goods_attr_name'] ?? '',// 商品类型名称,
            'site_id' => $this->site_id,
            'category_id' => $this->params[ 'category_id' ] ?? '',
            'category_json' => $this->params[ 'category_json' ] ?? '',
            'goods_image' => $this->params['goods_image'] ?? '',// 商品主图路径
            'goods_content' => $this->params['goods_content'] ?? '',// 商品详情
            'goods_state' => $this->params['goods_state'] ?? '',// 商品状态（1.正常0下架）
            'price' => $this->params['price'] ?? 0,// 商品价格（取第一个sku）
            'market_price' => $this->params['market_price'] ?? '',// 市场价格（取第一个sku）
            'cost_price' => $this->params['cost_price'] ?? 0,// 成本价（取第一个sku）
            'sku_no' => $this->params['sku_no'] ?? '',// 商品sku编码
            'weight' => $this->params['weight'] ?? '',// 重量
            'volume' => $this->params['volume'] ?? '',// 体积
            'goods_stock' => $this->params['goods_stock'] ?? 0,// 商品库存（总和）

            'goods_stock_alarm' => $this->params['goods_stock_alarm'] ?? 0,// 库存预警
            'is_free_shipping' => $this->params['is_free_shipping'] ?? 1,// 是否免邮
            'shipping_template' => $this->params['shipping_template'] ?? 0,// 指定运费模板
            'goods_spec_format' => $this->params['goods_spec_format'] ?? '',// 商品规格格式
            'goods_attr_format' => $this->params['goods_attr_format'] ?? '',// 商品参数格式
            'introduction' => $this->params['introduction'] ?? '',// 促销语
            'keywords' => $this->params['keywords'] ?? '',// 关键词
            'unit' => $this->params['unit'] ?? '',// 单位
            'sort' => $this->params['sort'] ?? 0,// 排序,
            'video_url' => $this->params['video_url'] ?? '',// 视频
            'goods_sku_data' => $this->params['goods_sku_data'] ?? '',// SKU商品数据
            'label_id' => $this->params['label_id'] ?? '',// 商品分组id
            'max_buy' => $this->params['max_buy'] ?? '',// 限购
            'min_buy' => $this->params['min_buy'] ?? '',// 起售
            'timer_on' => isset($this->params[ 'timer_on' ]) ? strtotime($this->params[ 'timer_on' ]) : 0,//定时上架
            'timer_off' => isset($this->params[ 'timer_off' ]) ? strtotime($this->params[ 'timer_off' ]) : 0,//定时下架

            'site_name' => $this->shop_info[ 'site_name' ],//店铺名
            'virtual_sale' => $this->params[ 'virtual_sale' ] ?? 0,// 虚拟销量
            'is_consume_discount' => $this->params[ 'is_consume_discount' ] ?? 0, //是否参与会员折扣
            'goods_service_ids' => $this->params[ 'goods_service_ids' ] ?? '',// 商品服务id集合
            'recommend_way' => $this->params[ 'recommend_way' ] ?? 0, // 推荐方式，1：新品，2：精品，3；推荐
            'is_limit' => $this->params[ 'is_limit' ] ?? 0,// 商品是否限购,
            'limit_type' => $this->params[ 'limit_type' ] ?? 1 // 商品限购类型,
        ];

        $virtual_goods_model = new VirtualGoodsModel();
        $res = $virtual_goods_model->addGoods($data);
        return $this->response($res);
    }

    /**
     * 编辑商品
     * @return mixed
     */
    public function editGoods()
    {
        $virtual_goods_model = new VirtualGoodsModel();

        $data = [
            'goods_id' => $this->params['goods_id'] ?? 0,// 商品id
            'goods_name' => $this->params['goods_name'] ?? '',// 商品名称,
            'goods_attr_class' => $this->params['goods_attr_class'] ?? '',// 商品类型id,
            'goods_attr_name' => $this->params['goods_attr_name'] ?? '',// 商品类型名称,
            'site_id' => $this->site_id,
            'category_id' => $this->params[ 'category_id' ] ?? '',
            'category_json' => $this->params[ 'category_json' ] ?? '',

            'goods_image' => $this->params['goods_image'] ?? '',// 商品主图路径
            'goods_content' => $this->params['goods_content'] ?? '',// 商品详情
            'goods_state' => $this->params['goods_state'] ?? '',// 商品状态（1.正常0下架）
            'price' => $this->params['price'] ?? 0,// 商品价格（取第一个sku）
            'market_price' => $this->params['market_price'] ?? '',// 市场价格（取第一个sku）
            'cost_price' => $this->params['cost_price'] ?? 0,// 成本价（取第一个sku）
            'sku_no' => $this->params['sku_no'] ?? '',// 商品sku编码
            'weight' => $this->params['weight'] ?? '',// 重量
            'volume' => $this->params['volume'] ?? '',// 体积
            'goods_stock' => $this->params['goods_stock'] ?? 0,// 商品库存（总和）

            'goods_stock_alarm' => $this->params['goods_stock_alarm'] ?? 0,// 库存预警
            'is_free_shipping' => $this->params['is_free_shipping'] ?? 1,// 是否免邮
            'shipping_template' => $this->params['shipping_template'] ?? 0,// 指定运费模板
            'goods_spec_format' => $this->params['goods_spec_format'] ?? '',// 商品规格格式
            'goods_attr_format' => $this->params['goods_attr_format'] ?? '',// 商品参数格式
            'introduction' => $this->params['introduction'] ?? '',// 促销语
            'keywords' => $this->params['keywords'] ?? '',// 关键词
            'unit' => $this->params['unit'] ?? '',// 单位
            'sort' => $this->params['sort'] ?? 0,// 排序,
            'video_url' => $this->params['video_url'] ?? '',// 视频
            'goods_sku_data' => $this->params['goods_sku_data'] ?? '',// SKU商品数据
            'label_id' => $this->params['label_id'] ?? '',// 商品分组id
            'max_buy' => $this->params['max_buy'] ?? '',// 限购
            'min_buy' => $this->params['min_buy'] ?? '',// 起售
            'timer_on' => isset($this->params[ 'timer_on' ]) ? strtotime($this->params[ 'timer_on' ]) : 0,//定时上架
            'timer_off' => isset($this->params[ 'timer_off' ]) ? strtotime($this->params[ 'timer_off' ]) : 0,//定时下架
            'spec_type_status' => isset($this->params[ 'spec_type_status' ]) ? strtotime($this->params[ 'spec_type_status' ]) : 0,

            'site_name' => $this->shop_info[ 'site_name' ] ?? '',//店铺名
            'virtual_sale' => $this->params[ 'virtual_sale' ] ?? 0,// 虚拟销量
            'is_consume_discount' => $this->params[ 'is_consume_discount' ] ?? 0, //是否参与会员折扣
            'goods_service_ids' => $this->params[ 'goods_service_ids' ] ?? '',// 商品服务id集合
            'recommend_way' => $this->params[ 'recommend_way' ] ?? 0, // 推荐方式，1：新品，2：精品，3；推荐
            'is_limit' => $this->params[ 'is_limit' ] ?? 0,// 商品是否限购,
            'limit_type' => $this->params[ 'limit_type' ] ?? 1 // 商品限购类型,
        ];

        $res = $virtual_goods_model->editGoods($data);
        return $this->response($res);

    }

}