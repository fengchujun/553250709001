<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy riht 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use addon\form\model\Form;
use addon\postertemplate\model\PosterTemplate as PosterTemplateModel;
use addon\supply\model\Supplier as SupplierModel;
use app\dict\goods\GoodsDict;
use app\model\express\Config as ExpressConfig;
use app\model\express\ExpressTemplate as ExpressTemplateModel;
use app\model\goods\Batch;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsAttribute as GoodsAttributeModel;
use app\model\goods\GoodsBrand as GoodsBrandModel;
use app\model\goods\GoodsBrowse;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\goods\GoodsCollect;
use app\model\goods\GoodsEvaluate as GoodsEvaluateModel;
use app\model\goods\GoodsExport;
use app\model\goods\GoodsImport;
use app\model\goods\GoodsLabel as GoodsLabelModel;
use app\model\goods\GoodsPoster;
use app\model\goods\GoodsService as GoodsServiceModel;
use app\model\goods\ServiceCategory;
use app\model\store\Store as StoreModel;
use app\model\web\Config;
use app\model\web\Config as ConfigModel;
use think\App;

/**
 * 实物商品
 * Class Goods
 * @package app\shop\controller
 */
class Goods extends BaseShop
{

    /**
     * 商品列表
     * @return mixed
     */
    public function lists()
    {
        $stockalarm = input('stockalarm', 0);
        $goods_model = new GoodsModel();
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_index = intval($page_index);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $goods_state = input('goods_state', '');
            $start_sale = input('start_sale', 0);
            $end_sale = input('end_sale', 0);
            $start_price = input('start_price', 0);
            $end_price = input('end_price', 0);
            $goods_class = input('goods_class', '');
            $label_id = input('label_id', '');
            $brand_id = input('brand_id', '');
            $order = input('order', '');
            $sort = input('sort', 'asc');
            $sku_no = input('sku_no', '');
            $promotion_type = input('promotion_type', '');
            $category_id = input('category_id', '');
            $supplier_id = input('supplier_id', '');

            $alias = 'g';
            $join = [
                ['goods_sku gs', 'g.goods_id = gs.goods_id', 'inner'],
            ];

            $condition = [
                ['g.is_delete', '=', 0],
                ['g.site_id', '=', $this->site_id],
            ];

            if (!empty($search_text)) {
                $condition[] = ['gs.sku_name', 'like', '%' . $search_text . '%'];
            }

            if (!empty($category_id)) {
                $condition[] = ['g.category_id', 'like', '%,' . $category_id . ',%'];
            }

            if ($goods_class !== '') {
                $condition[] = ['g.goods_class', '=', $goods_class];
            }

            if (!empty($label_id)) {
                $condition[] = ['g.label_id', '=', $label_id];
            }
            if (!empty($brand_id)) {
                $condition[] = ['g.brand_id', '=', $brand_id];
            }

            if (!empty($promotion_type)) {
                $condition[] = ['g.promotion_addon', 'like', "%{$promotion_type}%"];
            }

            // 上架状态
            if ($goods_state !== '') {
                $condition[] = ['g.goods_state', '=', $goods_state];
            }
            if (!empty($start_sale)) $condition[] = ['g.sale_num', '>=', $start_sale];
            if (!empty($end_sale)) $condition[] = ['g.sale_num', '<=', $end_sale];
            if (!empty($start_price)) $condition[] = ['gs.price', '>=', $start_price];
            if (!empty($end_price)) $condition[] = ['gs.price', '<=', $end_price];

            // 查询库存预警的商品
            if ($stockalarm) {
                $stock_alarm = $goods_model->getGoodsStockAlarm($this->site_id);
                if (empty($stock_alarm['data'])) $stock_alarm['data'] = [0];
                $condition[] = ['g.goods_id', 'in', $stock_alarm['data']];
            }
            if (!empty($sku_no)) {
                $condition[] = ['gs.sku_no', 'like', '%' . $sku_no . '%'];
            }
            if ($supplier_id) {
                $condition[] = ['g.supplier_id', '=', $supplier_id];
            }

            $order_by = 'g.create_time desc';
            if ($order != '') {
                if ($order == 'sort') {
                    $order_by = 'g.' . $order . ' ' . $sort . ',create_time desc';
                } else {
                    $order_by = 'g.' . $order . ' ' . $sort;
                }
            }

            $field = 'goods_id,goods_name,site_id,site_name,goods_image,goods_state,price,goods_stock,goods_stock_alarm,create_time,sale_num,is_virtual,goods_class,goods_class_name,is_fenxiao,fenxiao_type,promotion_addon,sku_id,is_consume_discount,discount_config,discount_method,sort,label_id,is_delete,label_name,virtual_deliver_type,supplier_id';
            $field = preg_replace('/\s+/', '', $field);
            $field_arr = explode(',', $field);
            foreach ($field_arr as $key => $val) {
                $field_arr[$key] = 'g.' . $val;
            }
            $field = join(',', $field_arr);

            $group = 'g.goods_id';

            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size, $order_by, $field, $alias, $join, $group);

            $goods_promotion_type = event('GoodsPromotionType');
            if (!empty($res['data']['list'])) {
                foreach ($res['data']['list'] as $k => &$v) {
                    if (!empty($v['promotion_addon'])) {
                        $v['promotion_addon'] = json_decode($v['promotion_addon'], true);
                        foreach ($v['promotion_addon'] as $ck => $cv) {
                            foreach ($goods_promotion_type as $gk => $gv) {
                                if ($gv['type'] == $ck) {
                                    $res['data']['list'][$k]['promotion_addon_list'][] = $gv;
                                    break;
                                }
                            }
                        }
                    }
                }
                //获取供应商名称
                $is_install_supply = addon_is_exit('supply');
                if ($is_install_supply) {
                    $supplier_ids = array_unique(array_column($res['data']['list'], 'supplier_id'));
                    $supplier_model = new SupplierModel();
                    $supplier_list = $supplier_model->getSupplyList([['supplier_site_id', '=', $this->site_id], ['supplier_id', 'in', $supplier_ids]], 'supplier_id,title', 'supplier_id desc')['data'];
                    $supplier_list = array_column($supplier_list, null, 'supplier_id');
                    foreach ($res['data']['list'] as &$goods_info) {
                        $goods_info['supplier_name'] = $supplier_list[$goods_info['supplier_id']]['title'] ?? '';
                    }
                }
            }
            return $res;
        } else {
            $goods_state = input('state', 1);
            $this->assign('goods_state', $goods_state);
            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                ['pid', '=', 0],
                ['site_id', '=', $this->site_id]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate')['data'];
            $this->assign('goods_category_list', $goods_category_list);

            // 商品分组
            $goods_label_model = new GoodsLabelModel();
            $label_list = $goods_label_model->getLabelList([['site_id', '=', $this->site_id]], 'id,label_name', 'sort asc')['data'];
            $this->assign('label_list', $label_list);

            // 商品品牌
            $goods_brand_model = new GoodsBrandModel();
            $brand_list = $goods_brand_model->getBrandList([['site_id', '=', $this->site_id]], 'brand_id,brand_name', 'sort asc')['data'];
            $this->assign('brand_list', $brand_list);

            // 商品服务
            $goods_service_model = new GoodsServiceModel();
            $service_list = $goods_service_model->getServiceList([['site_id', '=', $this->site_id]], 'id,service_name,icon')['data'];
            $this->assign('service_list', $service_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([['site_id', '=', $this->site_id]], 'template_id,template_name', 'is_default desc')['data'];
            $this->assign('express_template_list', $express_template_list);

            //判断会员价插件
            $memberprice_is_exit = addon_is_exit('memberprice', $this->site_id);
            $this->assign('memberprice_is_exit', $memberprice_is_exit);

            // 获取商品排序
            $confif_model = new ConfigModel();
            $goods_sort = $confif_model->getGoodsSort($this->site_id);
            $this->assign('goods_sort', $goods_sort['data']['value']['type']);

            // 营销活动
            $goods_promotion_type = event('GoodsPromotionType');
            $this->assign('promotion_type', $goods_promotion_type);

            $this->assign('virtualcard_exit', addon_is_exit('virtualcard', $this->site_id));

            $this->assign('stockalarm', $stockalarm);

            $this->assign('pc_domain', '');
            if (addon_is_exit('pc')) {
                $config_model = new Config();
                $config = $config_model->getPcDomainName($this->site_id);
                $this->assign('pc_domain', $config['data']['value']['domain_name_pc'] ?? '');
            }

            $this->assign('goods_class', array_column(event('GoodsClass'), null, 'goods_class'));

            $cardservice_is_exit = addon_is_exit('cardservice', $this->site_id);
            $this->assign('cardservice_is_exit', $cardservice_is_exit);

            $form_is_exit = addon_is_exit('form', $this->site_id);
            if ($form_is_exit) {
                $form_list = (new Form())->getFormList([['site_id', '=', $this->site_id], ['form_type', '=', 'goods'], ['is_use', '=', 1]], 'id desc', 'id, form_name')['data'];
                $this->assign('form_list', $form_list);
            }
            $this->assign('form_is_exit', $form_is_exit);

            $stock_config = [];
            if (addon_is_exit('stock', $this->site_id)) {
                $stock_model = new \addon\stock\model\stock\Stock();
                $stock_config = $stock_model->getStockConfig($this->site_id)['data']['value'];
            }
            $this->assign('stock_config', $stock_config);

            $express_type = (new ExpressConfig())->getEnabledExpressType($this->site_id);
            $this->assign('express_type', $express_type);

            $is_install_supply = addon_is_exit('supply');
            if ($is_install_supply) {
                $supplier_model = new SupplierModel();
                $supplier_list = $supplier_model->getSupplyList([['supplier_site_id', '=', $this->site_id]], 'supplier_id,title', 'supplier_id desc')['data'];
                $this->assign('supplier_list', $supplier_list);
            }
            $this->assign('is_install_supply', $is_install_supply);

            return $this->fetch('goods/lists');
        }
    }

    /**
     * 修改商品排序
     */
    public function modifySort()
    {
        if (request()->isJson()) {
            $goods_model = new GoodsModel();
            $goods_id = input('goods_id', 0);
            $sort = input('sort', 0);
            return $goods_model->modifyGoodsSort($sort, $goods_id, $this->site_id);
        }
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function addGoods()
    {
        if (request()->isJson()) {

            $category_id = input('category_id', 0);// 分类id
            $category_json = json_encode($category_id);//分类字符串
            $category_id = ',' . implode(',', $category_id) . ',';
            $data = [
                'goods_name' => input('goods_name', ''),// 商品名称,
                'goods_attr_class' => input('goods_attr_class', ''),// 商品类型id,
                'goods_attr_name' => input('goods_attr_name', ''),// 商品类型名称,

                'is_limit' => input('is_limit', '0'),// 商品是否限购,
                'limit_type' => input('limit_type', '1'),// 商品限购类型,

                'site_id' => $this->site_id,
                'category_id' => $category_id,
                'category_json' => $category_json,
                'goods_image' => input('goods_image', ''),// 商品主图路径
                'goods_content' => input('goods_content', ''),// 商品详情
                'goods_state' => input('goods_state', ''),// 商品状态（1.正常0下架）
                'price' => input('price', 0),// 商品价格（取第一个sku）
                'market_price' => input('market_price', 0),// 市场价格（取第一个sku）
                'cost_price' => input('cost_price', 0),// 成本价（取第一个sku）
                'sku_no' => input('sku_no', ''),// 商品sku编码
                'weight' => input('weight', ''),// 重量
                'volume' => input('volume', ''),// 体积
                'goods_stock' => input('goods_stock', 0),// 商品库存（总和）
                'goods_stock_alarm' => input('goods_stock_alarm', 0),// 库存预警
                'is_free_shipping' => input('is_free_shipping', 1),// 是否免邮
                'shipping_template' => input('shipping_template', 0),// 指定运费模板
                'goods_spec_format' => input('goods_spec_format', ''),// 商品规格格式
                'goods_attr_format' => input('goods_attr_format', ''),// 商品参数格式
                'introduction' => input('introduction', ''),// 促销语
                'keywords' => input('keywords', ''),// 关键词
                'unit' => input('unit', ''),// 单位
                'sort' => input('sort', 0),// 排序,
                'video_url' => input('video_url', ''),// 视频
                'goods_sku_data' => input('goods_sku_data', ''),// SKU商品数据
                'goods_service_ids' => input('goods_service_ids', ''),// 商品服务id集合
                'label_id' => input('label_id', ''),// 商品分组id
                'brand_id' => input('brand_id', 0),//品牌id
                'virtual_sale' => input('virtual_sale', 0),// 虚拟销量
                'max_buy' => input('max_buy', 0),// 限购
                'min_buy' => input('min_buy', 0),// 起售
                'recommend_way' => input('recommend_way', 0), // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => strtotime(input('timer_on', 0)),//定时上架
                'timer_off' => strtotime(input('timer_off', 0)),//定时下架
                'is_consume_discount' => input('is_consume_discount', 0),//是否参与会员折扣
                'qr_id' => input('qr_id', 0),//社群二维码id
                'sale_show' => input('sale_show', 0),//
                'stock_show' => input('stock_show', 0),//
                'market_price_show' => input('market_price_show', 0),//
                'barrage_show' => input('barrage_show', 0),//
                'template_id' => input('template_id', 0),//商品海报id
                'form_id' => input('form_id', 0),
                'support_trade_type' => input('support_trade_type', ''),
                'sale_channel' => input('sale_channel', 'all'),
                'sale_store' => input('sale_store', 'all'),
                'is_unify_price' => input('is_unify_price', '1'),
                'supplier_id' => input('supplier_id', 0),
            ];
            $goods_model = new GoodsModel();
            $res = $goods_model->addGoods($data);
            return $res;
        } else {

            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                ['pid', '=', 0],
                ['site_id', '=', $this->site_id]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate')['data'];
            $this->assign('goods_category_list', $goods_category_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([['site_id', '=', $this->site_id]], 'template_id,template_name', 'is_default desc')['data'];
            $this->assign('express_template_list', $express_template_list);

            //获取商品类型
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_list = $goods_attr_model->getAttrClassList([['site_id', '=', $this->site_id]], 'class_id,class_name')['data'];
            $this->assign('attr_class_list', $attr_class_list);

            // 商品服务
            $goods_service_model = new GoodsServiceModel();
            $service_list = $goods_service_model->getServiceList([['site_id', '=', $this->site_id]], 'id,service_name,icon')['data'];
            $this->assign('service_list', $service_list);

            // 商品分组
            $goods_label_model = new GoodsLabelModel();
            $label_list = $goods_label_model->getLabelList([['site_id', '=', $this->site_id]], 'id,label_name', 'sort asc')['data'];
            $this->assign('label_list', $label_list);

            //商品默认排序值
            $config_model = new ConfigModel();
            $sort_config = $config_model->getGoodsSort($this->site_id, $this->app_module)['data']['value'];
            $this->assign('sort_config', $sort_config);

            //获取品牌
            $goods_brand_model = new GoodsBrandModel();
            $brand_list = $goods_brand_model->getBrandList([['site_id', '=', $this->site_id]], 'brand_id, brand_name')['data'];
            $this->assign('brand_list', $brand_list);

            //获取商品海报
            $poster_template_model = new PosterTemplateModel();
            $poster_list = $poster_template_model->getPosterTemplateList([['site_id', '=', $this->site_id], ['template_status', '=', 1]], 'template_id,poster_name,site_id');
            $this->assign('poster_list', $poster_list['data']);
            $this->assign('virtualcard_exit', addon_is_exit('virtualcard', $this->site_id));

            $form_is_exit = addon_is_exit('form', $this->site_id);
            if ($form_is_exit) {
                $form_list = (new Form())->getFormList([['site_id', '=', $this->site_id], ['form_type', '=', 'goods'], ['is_use', '=', 1]], 'id desc', 'id, form_name')['data'];
                $this->assign('form_list', $form_list);
            }
            $this->assign('form_is_exit', $form_is_exit);

            $express_type = (new ExpressConfig())->getEnabledExpressType($this->site_id);
            $this->assign('express_type', $express_type);

            $this->assign('all_goodsclass', event('GoodsClass'));
            $this->assign('goods_class', (new GoodsModel())->getGoodsClass());

            $this->assign('store_is_exit', addon_is_exit('store', $this->site_id));

            $is_install_supply = addon_is_exit('supply');
            if ($is_install_supply) {
                $supplier_model = new SupplierModel();
                $supplier_list = $supplier_model->getSupplyList([['supplier_site_id', '=', $this->site_id]], 'supplier_id,title', 'supplier_id desc')['data'];
                $this->assign('supplier_list', $supplier_list);
            }
            $this->assign('is_install_supply', $is_install_supply);

            return $this->fetch('goods/add_goods');
        }
    }

    /**
     * 编辑商品
     * @return mixed
     */
    public function editGoods()
    {
        $goods_model = new GoodsModel();
        if (request()->isJson()) {

            $category_id = input('category_id', 0);// 分类id
            $category_json = json_encode($category_id);//分类字符串
            $category_id = ',' . implode(',', $category_id) . ',';

            $data = [
                'goods_id' => input('goods_id', 0),// 商品id
                'goods_name' => input('goods_name', ''),// 商品名称,
                'goods_attr_class' => input('goods_attr_class', ''),// 商品类型id,
                'goods_attr_name' => input('goods_attr_name', ''),// 商品类型名称,
                'is_limit' => input('is_limit', '0'),// 商品是否限购,
                'limit_type' => input('limit_type', '1'),// 商品限购类型,
                'site_id' => $this->site_id,
                'category_id' => $category_id,
                'category_json' => $category_json,
                'goods_image' => input('goods_image', ''),// 商品主图路径
                'goods_content' => input('goods_content', ''),// 商品详情
                'goods_state' => input('goods_state', ''),// 商品状态（1.正常0下架）
                'price' => input('price', 0),// 商品价格（取第一个sku）
                'market_price' => input('market_price', 0),// 市场价格（取第一个sku）
                'cost_price' => input('cost_price', 0),// 成本价（取第一个sku）
                'sku_no' => input('sku_no', ''),// 商品sku编码
                'weight' => input('weight', ''),// 重量
                'volume' => input('volume', ''),// 体积
                'goods_stock' => input('goods_stock', 0),// 商品库存（总和）
                'goods_stock_alarm' => input('goods_stock_alarm', 0),// 库存预警
                'is_free_shipping' => input('is_free_shipping', 1),// 是否免邮
                'shipping_template' => input('shipping_template', 0),// 指定运费模板
                'goods_spec_format' => input('goods_spec_format', ''),// 商品规格格式
                'goods_attr_format' => input('goods_attr_format', ''),// 商品参数格式
                'introduction' => input('introduction', ''),// 促销语
                'keywords' => input('keywords', ''),// 关键词
                'unit' => input('unit', ''),// 单位
                'sort' => input('sort', 0),// 排序,
                'video_url' => input('video_url', ''),// 视频
                'goods_sku_data' => input('goods_sku_data', ''),// SKU商品数据
                'goods_service_ids' => input('goods_service_ids', ''),// 商品服务id集合
                'label_id' => input('label_id', ''),// 商品分组id
                'brand_id' => input('brand_id', 0),//品牌id
                'virtual_sale' => input('virtual_sale', 0),// 虚拟销量
                'max_buy' => input('max_buy', 0),// 限购
                'min_buy' => input('min_buy', 0),// 起售
                'recommend_way' => input('recommend_way', 0), // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => strtotime(input('timer_on', 0)),//定时上架
                'timer_off' => strtotime(input('timer_off', 0)),//定时下架
                'spec_type_status' => input('spec_type_status', 0),
                'is_consume_discount' => input('is_consume_discount', 0),//是否参与会员折扣
                'qr_id' => input('qr_id', 0),//社群二维码id
                'sale_show' => input('sale_show', 0),//
                'stock_show' => input('stock_show', 0),//
                'market_price_show' => input('market_price_show', 0),//
                'barrage_show' => input('barrage_show', 0),//
                'template_id' => input('template_id', 0),//商品海报id
                'form_id' => input('form_id', 0),
                'support_trade_type' => input('support_trade_type', ''),
                'sale_channel' => input('sale_channel', 'all'),
                'sale_store' => input('sale_store', 'all'),
                'is_unify_price' => input('is_unify_price', '1'),
                'supplier_id' => input('supplier_id', 0),
            ];

            $res = $goods_model->editGoods($data);
            return $res;
        } else {

            $goods_id = input('goods_id', 0);
            $goods_info = $goods_model->editGetGoodsInfo([['goods_id', '=', $goods_id], ['site_id', '=', $this->site_id]])['data'];
            if (empty($goods_info)) $this->error('未获取到商品数据', href_url('shop/goods/lists'));

            $goods_sku_list = $goods_model->getGoodsSkuList([['goods_id', '=', $goods_id], ['site_id', '=', $this->site_id]], 'sku_id,sku_name,sku_no,sku_spec_format,price,market_price,cost_price,stock,weight,volume,sku_image,sku_images,goods_spec_format,spec_name,stock_alarm,is_default', '')['data'];
            $goods_info['sku_list'] = $goods_sku_list;
            $this->assign('goods_info', $goods_info);

            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                ['pid', '=', 0],
                ['site_id', '=', $this->site_id]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate')['data'];
            $this->assign('goods_category_list', $goods_category_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([['site_id', '=', $this->site_id]], 'template_id,template_name', 'is_default desc')['data'];
            $this->assign('express_template_list', $express_template_list);

            //获取商品类型
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_list = $goods_attr_model->getAttrClassList([['site_id', '=', $this->site_id]], 'class_id,class_name')['data'];
            $this->assign('attr_class_list', $attr_class_list);

            //获取品牌
            $goods_brand_model = new GoodsBrandModel();
            $brand_list = $goods_brand_model->getBrandList([['site_id', '=', $this->site_id]], 'brand_id, brand_name')['data'];
            $this->assign('brand_list', $brand_list);

            // 商品服务
            $goods_service_model = new GoodsServiceModel();
            $service_list = $goods_service_model->getServiceList([['site_id', '=', $this->site_id]], 'id,service_name,icon')['data'];
            $this->assign('service_list', $service_list);

            // 商品分组
            $goods_label_model = new GoodsLabelModel();
            $label_list = $goods_label_model->getLabelList([['site_id', '=', $this->site_id]], 'id,label_name', 'sort asc')['data'];
            $this->assign('label_list', $label_list);

            //获取商品海报
            $poster_template_model = new PosterTemplateModel();
            $poster_list = $poster_template_model->getPosterTemplateList([['site_id', '=', $this->site_id], ['template_status', '=', 1], ['template_type', '=', 'goods']], 'template_id,poster_name,site_id')['data'];
            $this->assign('poster_list', $poster_list);

            $form_is_exit = addon_is_exit('form', $this->site_id);
            if ($form_is_exit) {
                $form_list = (new Form())->getFormList([['site_id', '=', $this->site_id], ['form_type', '=', 'goods'], ['is_use', '=', 1]], 'id desc', 'id, form_name')['data'];
                $this->assign('form_list', $form_list);
            }
            $this->assign('form_is_exit', $form_is_exit);

            $express_type = (new ExpressConfig())->getEnabledExpressType($this->site_id);
            $this->assign('express_type', $express_type);

            $store_is_exit = addon_is_exit('store', $this->site_id);
            if ($store_is_exit && $goods_info['sale_store'] != 'all') {
                $store_list = (new StoreModel())->getStoreList([['site_id', '=', $this->site_id], ['store_id', 'in', $goods_info['sale_store']]], 'store_id,store_name,status,address,full_address,is_frozen');
                $this->assign('store_list', $store_list['data']);
            }
            $this->assign('store_is_exit', $store_is_exit);

            $is_install_supply = addon_is_exit('supply');
            if ($is_install_supply) {
                $supplier_model = new SupplierModel();
                $supplier_list = $supplier_model->getSupplyList([['supplier_site_id', '=', $this->site_id]], 'supplier_id,title', 'supplier_id desc')['data'];
                $this->assign('supplier_list', $supplier_list);
            }
            $this->assign('is_install_supply', $is_install_supply);

            $stock_config = [];
            if (addon_is_exit('stock')) {
                // 检查库存是否需要调整
                $stock_model = new \addon\stock\model\stock\Stock();
                $has_stock_records = $stock_model->getGoodsIsHasStockRecords($goods_id, $this->site_id);
                $this->assign('has_stock_records', $has_stock_records['code']);

                $stock_config = $stock_model->getStockConfig($this->site_id)['data']['value'];
            }

            $this->assign('stock_config', $stock_config);

            return $this->fetch('goods/edit_goods');
        }
    }

    /**
     * 删除商品
     */
    public function deleteGoods()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyIsDelete($goods_ids, 1, $this->site_id);
            return $res;
        }

    }

    /**
     * 商品回收站
     */
    public function recycle()
    {
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_keys = input('search_text', '');
            $goods_class = input('goods_class', '');
            $condition = [['is_delete', '=', 1], ['site_id', '=', $this->site_id]];
            if (!empty($search_keys)) {
                $condition[] = ['goods_name', 'like', '%' . $search_keys . '%'];
            }
            $category_id = input('category_id', '');
            if (!empty($category_id)) {
                $condition[] = ['category_id', 'like', '%,' . $category_id . ',%'];
            }

            if ($goods_class !== '') {
                $condition[] = ['goods_class', '=', $goods_class];
            }
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size);
            return $res;
        } else {
            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                ['pid', '=', 0],
                ['site_id', '=', $this->site_id]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate')['data'];
            $this->assign('goods_category_list', $goods_category_list);
            $this->assign('virtualcard_exit', addon_is_exit('virtualcard', $this->site_id));
            return $this->fetch('goods/recycle');
        }
    }

    /**
     * 商品回收站商品删除
     */
    public function deleteRecycleGoods()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->deleteRecycleGoods($goods_ids, $this->site_id);
            return $res;
        }
    }

    /**
     * 商品回收站商品恢复
     */
    public function recoveryRecycle()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyIsDelete($goods_ids, 0, $this->site_id);
            return $res;
        }

    }

    /**
     * 商品下架
     */
    public function offGoods()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', 0);
            $goods_state = input('goods_state', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyGoodsState($goods_ids, $goods_state, $this->site_id);
            return $res;
        }

    }

    /**
     * 商品上架
     */
    public function onGoods()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', 0);
            $goods_state = input('goods_state', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyGoodsState($goods_ids, $goods_state, $this->site_id);
            return $res;
        }
    }

    /**
     * 编辑商品库存
     * @return array|\multitype
     */
    public function editGoodsStock()
    {
        if (request()->isJson()) {
            $sku_list = input('sku_list', '');
            $model = new GoodsModel();
            $res = $model->editGoodsStock($sku_list, $this->site_id);
            $model = new \app\model\goods\GoodsLocalRestrictions();
            $model->setRestrictions($sku_list, $this->site_id, 0);
            return $res;
        }
    }

    /**
     * 获取商品分类列表
     * @return \multitype
     */
    public function getCategoryList()
    {
        if (request()->isJson()) {
            $category_id = input('category_id', 0);
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                ['pid', '=', $category_id],
                ['site_id', '=', $this->site_id]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate');
            return $goods_category_list;
        }
    }

    /**
     * 获取商品参数列表
     * @return \multitype
     */
    public function getAttributeList()
    {

        if (request()->isJson()) {
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_id = input('attr_class_id', 0);// 商品类型id
            $attribute_list = $goods_attr_model->getAttributeList([['attr_class_id', '=', $attr_class_id], ['is_spec', '=', 0], ['site_id', '=', $this->site_id]], 'attr_id,attr_name,attr_class_id,attr_class_name,attr_type,attr_value_format', 'sort asc');
            if (!empty($attribute_list['data'])) {
                foreach ($attribute_list['data'] as $k => $v) {
                    if (!empty($v['attr_value_format'])) {
                        $attribute_list['data'][$k]['attr_value_format'] = json_decode($v['attr_value_format'], true);
                    }
                }
            }

            return $attribute_list;
        }
    }

    /**
     * 获取SKU商品列表
     * @return array
     */
    public function getGoodsSkuList()
    {
        if (request()->isJson()) {
            $goods_id = input('goods_id', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsSkuList([['goods_id', '=', $goods_id], ['site_id', '=', $this->site_id]], '1 as is_delivery_restrictions,goods_class,sku_id,sku_name,price,market_price,cost_price,stock,weight,volume,sku_no,sale_num,sku_image,spec_name,goods_id,stock_alarm,is_consume_discount,member_price,discount_method,discount_config,verify_num,sku_spec_format');
            if (!empty($res['data'])) {
                $res['data'] = $goods_model->getSkuMemberPrice($res['data'], $this->site_id);
            }
            return $res;
        }
    }

    /**
     * 商品选择组件
     * @return \multitype
     */
    public function goodsSelect()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $goods_ids = input('goods_ids', '');
            $is_virtual = input('is_virtual', '');// 是否虚拟类商品（0实物1.虚拟）
            $min_price = input('min_price', 0);
            $max_price = input('max_price', 0);
            $goods_class = input('goods_class', '');// 商品类型，实物、虚拟，多个逗号隔开
            $category_id = input('category_id', '');// 商品分类id
            $promotion = input('promotion', '');//营销活动标识：pintuan、groupbuy、fenxiao、bargain
            $promotion_type = input('promotion_type', '');
            $label_id = input('label_id', '');
            $select_type = input('select_type', 'all');
            $sale_channel = input('sale_channel', ''); // 销售渠道 all 线上线下销售 online 线上销售  offline线下销售

            if (!empty($promotion) && addon_is_exit($promotion)) {
                $promotion_name = input('promotion_name', '');// 营销活动
                $goods_list = event('GoodsListPromotion', [
                    'page' => $page,
                    'page_size' => $page_size,
                    'site_id' => $this->site_id,
                    'promotion' => $promotion,
                    'promotion_name' => $promotion_name,
                    //筛选参数
                    'category_id' => $category_id,
                    'select_type' => $select_type,
                    'goods_ids' => $goods_ids,
                    'label_id' => $label_id,
                    'goods_class' => $goods_class,
                    'goods_name' => $search_text,
                ], true);
            } else {
                $goods_model = new GoodsModel();

                $condition = [
                    ['is_delete', '=', 0],
                    ['goods_state', '=', 1],
                    ['goods_stock', '>', 0],
                    ['site_id', '=', $this->site_id]
                ];

                if (!empty($search_text)) {
                    $search_text = paramFilter($search_text);
                    $goods_sku_list = $goods_model->getGoodsSkuList([['sku_no', 'like', '%' . $search_text . '%']], 'goods_id')['data'];
                    $goods_id_arr = array_unique(array_column($goods_sku_list, 'goods_id'));
                    if (!empty($goods_id_arr)) {
                        $goods_ids = join(',', $goods_id_arr);
                        $condition[] = ['', 'exp', \think\facade\Db::raw("goods_name like '%{$search_text}%' or goods_id in ({$goods_ids})")];
                    } else {
                        $condition[] = ['goods_name', 'like', "%{$search_text}%"];
                    }
                }
                if ($is_virtual !== '') {
                    $condition[] = ['is_virtual', '=', $is_virtual];
                }
                if ($select_type == 'selected') {
                    $condition[] = ['goods_id', 'in', $goods_ids];
                }

                if (!empty($category_id)) {
                    if (!empty($goods_class) && $goods_class == 4) {
                        $condition[] = ['service_category', 'like', '%,' . $category_id . ',%'];
                    } else {
                        $condition[] = ['category_id', 'like', '%,' . $category_id . ',%'];
                    }
                }

                if (!empty($sale_channel)) {
                    $condition[] = ['sale_channel', 'in', $sale_channel];
                }

                if (!empty($promotion_type)) {
                    $condition[] = ['promotion_addon', 'like', "%{$promotion_type}%"];
                }

                if (!empty($label_id)) {
                    $condition[] = ['label_id', '=', $label_id];
                }

                if ($goods_class !== '') {
                    $condition[] = ['goods_class', 'in', $goods_class];
                }

                if ($min_price != '' && $max_price != '') {
                    $condition[] = ['price', 'between', [$min_price, $max_price]];
                } elseif ($min_price != '') {
                    $condition[] = ['price', '<=', $min_price];
                } elseif ($max_price != '') {
                    $condition[] = ['price', '>=', $max_price];
                }

                $config_model = new ConfigModel();
                $sort_config = $config_model->getGoodsSort($this->site_id)['data']['value'];

                $order = 'sort ' . $sort_config['type'] . ',create_time desc';

                $field = 'goods_id,goods_name,goods_class_name,goods_image,price,goods_stock,is_virtual';
                $goods_list = $goods_model->getGoodsPageList($condition, $page, $page_size, $order, $field);
                if (!empty($goods_list['data']['list'])) {
                    foreach ($goods_list['data']['list'] as $k => $v) {
                        $goods_list['data']['list'][$k]['sku_list'] = $goods_model->getGoodsSkuList([['goods_id', '=', $v['goods_id']], ['site_id', '=', $this->site_id]], 'sku_id,sku_name,price,stock,sku_image,goods_id,goods_class_name', 'price asc')['data'];
                    }
                }
            }
            return $goods_list;
        } else {

            $mode = input('mode', 'spu');
            $max_num = input('max_num', 0);
            $min_num = input('min_num', 0);
            $is_virtual = input('is_virtual', '');
            $disabled = input('disabled', 0);
            $promotion = input('promotion', ''); // 营销活动标识：pintuan、groupbuy、seckill、fenxiao
            $is_disabled_goods_class = input('is_disabled_goods_class', 0); // 是否禁用商品类型筛选 0开启 1关闭
            $is_weigh = input('is_weigh', 0); // 是否支持称重
            $sale_channel = input('sale_channel', ''); // 销售渠道

            $this->assign('is_disabled_goods_class', $is_disabled_goods_class);
            $this->assign('mode', $mode);
            $this->assign('max_num', $max_num);
            $this->assign('min_num', $min_num);
            $this->assign('is_virtual', $is_virtual);
            $this->assign('disabled', $disabled);
            $this->assign('promotion', $promotion);
            $this->assign('sale_channel', $sale_channel);

            $goods_class = input('goods_class', ''); //查找商品类型
            $this->assign('goods_class', $goods_class);

            $goods_class_arr = event('GoodsClass');
            $goods_class_arr = array_column($goods_class_arr, null, 'goods_class');

            if (!empty($goods_class)) {
                $goods_class = explode(',', $goods_class);
                foreach ($goods_class_arr as $k => $v) {
                    if (!in_array($k, $goods_class)) {
                        unset($goods_class_arr[$k]);
                    }
                }
            }
            $this->assign('goods_class_arr', $goods_class_arr);
            $this->assign('is_weigh', $is_weigh);

            // 营销活动
            $goods_promotion_type = event('GoodsPromotionType');
            $this->assign('promotion_type', $goods_promotion_type);

            // 商品分组
            $goods_label_model = new GoodsLabelModel();
            $label_list = $goods_label_model->getLabelList([['site_id', '=', $this->site_id]], 'id,label_name', 'sort asc')['data'];
            $this->assign('label_list', $label_list);

            // 分类过滤
            if (!empty($promotion) && addon_is_exit($promotion)) {
                $category_id_arr = event('GoodsListCategoryIds', [
                        'promotion' => $promotion,
                        'site_id' => $this->site_id,
                    ], true)['data'] ?? [];
            } else {
                $goods_model = new GoodsModel();
                $category_id_arr = $goods_model->getGoodsCategoryIds([
                    ['is_delete', '=', 0],
                    ['goods_state', '=', 1],
                    ['goods_stock', '>', 0],
                    ['site_id', '=', $this->site_id],
                ])['data'];
            }

            if (!empty($goods_class) && in_array(4, $goods_class)) {
                $service_category_model = new ServiceCategory();
                $service_category_list = $service_category_model->getCategoryList([
                    ['site_id', '=', $this->site_id]
                ], 'category_id,category_name as title,pid')['data'];
                $tree = list_to_tree($service_category_list, 'category_id', 'pid', 'children', 0);
                $this->assign("category_list", $tree);
            } else {
                $goods_category_model = new GoodsCategoryModel();
                $field = 'category_id,category_name as title,pid';
                $list = $goods_category_model->getCategoryList([
                    ['site_id', '=', $this->site_id],
                    ['category_id', 'in', $category_id_arr]
                ], $field)['data'];
                $tree = list_to_tree($list, 'category_id', 'pid', 'children', 0);
                $this->assign("category_list", $tree);
            }

            return $this->fetch('goods/goods_select');
        }
    }

    /**
     * 检测商品是否存在，移除已删除的商品，返回有效商品id
     * @return array
     */
    public function checkGoods()
    {
        if (request()->isJson()) {
            $goods_ids = input('goods_ids', '');
            $mode = input('mode', 'spu');
            $list = [];
            if (!empty($goods_ids)) {
                $goods_model = new GoodsModel();
                if ($mode == 'spu') {

                    $field = 'goods_id,goods_name,goods_class_name,goods_image,price,goods_stock,is_virtual';

                    $config_model = new ConfigModel();
                    $sort_config = $config_model->getGoodsSort($this->site_id)['data']['value'];

                    $order = 'sort ' . $sort_config['type'] . ',create_time desc';

                    $list = $goods_model->getGoodsList([
                        ['site_id', '=', $this->site_id],
                        ['goods_id', 'in', $goods_ids],
                        ['is_delete', '=', 0],
                        ['goods_state', '=', 1],
                        ['goods_stock', '>', 0],
                    ], $field, $order)['data'];

                    if (!empty($list)) {
                        foreach ($list as $k => $v) {
                            $list[$k]['sku_list'] = $goods_model->getGoodsSkuList([['goods_id', '=', $v['goods_id']], ['site_id', '=', $this->site_id]], 'sku_id,sku_name,price,stock,sku_image,goods_id,goods_class_name', 'price asc')['data'];
                        }
                    }

                } elseif ($mode = 'sku') {

                    $field = 'g.goods_id,g.goods_name,g.goods_class_name,g.goods_image,g.price as goods_price,g.goods_stock,g.is_virtual,
                    gs.sku_id,gs.sku_name,gs.price,gs.stock,gs.sku_image';

                    $join = [
                        ['goods g', 'gs.goods_id = g.goods_id', 'left']
                    ];
                    $list = $goods_model->getGoodsSkuList([
                        ['gs.site_id', '=', $this->site_id],
                        ['gs.sku_id', 'in', $goods_ids],
                        ['gs.is_delete', '=', 0],
                        ['g.goods_state', '=', 1],
                        ['g.goods_stock', '>', 0],
                    ], $field, 'gs.price asc', '', 'gs', $join)['data'];
                }
            }
            return success('', '', $list);
        }
    }

    /***********************************************************商品评价**************************************************/

    /**
     * 商品评价
     */
    public function evaluate()
    {
        $goods_evaluate = new GoodsEvaluateModel();

        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $explain_type = input('explain_type', ''); //1好评2中评3差评
            $is_show = input('is_show', ''); //1显示 0隐藏
            $search_text = input('search_text', ''); //搜索值
            $search_type = input('search_type', ''); //搜索类型
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $goods_id = input('goods_id', '');
            $is_audit = input('is_audit', '');

            if (!empty($is_audit)) {
                if ($is_audit == 1) {
                    $condition[] =
                        ['is_audit', '=', 0];
                } else if ($is_audit == 2) {
                    $condition[] =
                        ['is_audit', '=', 1];
                } else if ($is_audit == 3) {
                    $condition[] =
                        ['is_audit', '=', 2];
                }
            }
            $condition[] =
                ['site_id', '=', $this->site_id];
            //评分类型
            if ($explain_type != '') {
                $condition[] = ['explain_type', '=', $explain_type];
            }
            if ($is_show != '') {
                $condition[] = ['is_show', '=', $is_show];
            }
            if ($search_text != '') {
                $condition[] = [$search_type, 'like', '%' . $search_text . '%'];
            }
            if ($goods_id != '') {
                $condition[] = ['goods_id', '=', $goods_id];
            }
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = ['create_time', '>=', date_to_time($start_time)];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = ['create_time', '<=', date_to_time($end_time)];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = ['create_time', 'between', [date_to_time($start_time), date_to_time($end_time)]];
            }
            return $goods_evaluate->getEvaluatePageList($condition, $page_index, $page_size, 'is_audit asc, create_time desc');
        } else {
            $goods_id = input('goods_id', '');
            $this->assign('goods_id', $goods_id);
            return $this->fetch('goods/evaluate');
        }

    }

    /**
     * 商品评价删除
     */
    public function deleteEvaluate()
    {

        if (request()->isJson()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_ids = input('evaluate_ids', 0);
            return $goods_evaluate->deleteEvaluate($evaluate_ids);
        }
    }

    /**
     * 修改商品评价审核状态
     */
    public function modifyAuditEvaluate()
    {
        if (request()->isJson()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_ids = input('evaluate_ids', '');
            $is_audit = input('is_audit', 0);
            $data = [
                'is_audit' => $is_audit
            ];
            $condition = [
                ['evaluate_id', 'in', $evaluate_ids],
                ['is_audit', '=', 0],
                ['site_id', '=', $this->site_id],
            ];
            $res = $goods_evaluate->modifyAuditEvaluate($data, $condition);
            return $res;
        }
    }

    /**
     * 修改商品追评审核状态
     */
    public function modifyAgainAuditEvaluate()
    {
        if (request()->isJson()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_ids = input('evaluate_ids', '');
            $again_is_audit = input('again_is_audit', 0);
            $data = [
                'again_is_audit' => $again_is_audit
            ];
            $condition = [
                ['evaluate_id', 'in', $evaluate_ids],
                ['again_is_audit', '=', 0],
                ['site_id', '=', $this->site_id],
            ];
            $res = $goods_evaluate->modifyAgainAuditEvaluate($data, $condition);
            return $res;
        }
    }

    /**
     * 商品推广
     * return
     */
    public function goodsUrl()
    {
        $goods_id = input('goods_id', '');
        $app_type = input('app_type', 'all');
        $goods_model = new GoodsModel();
        $goods_sku_info = $goods_model->getGoodsSkuInfo([['goods_id', '=', $goods_id]], 'sku_id,goods_name,site_id')['data'];
        if (!empty($goods_sku_info)) {
//        $res = $goods_model->qrcode($goods_sku_info[ 'sku_id' ], $goods_sku_info[ 'goods_name' ], $goods_sku_info[ 'site_id' ]);
            $res = $goods_model->urlQrcode('/pages/goods/detail', ['goods_id' => $goods_id], 'goods', $app_type, $this->site_id);
            return $res;
        }
    }

    /**
     * 商品评价回复
     */
    public function evaluateApply()
    {
        if (request()->isJson()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_id = input('evaluate_id', 0);
            $explain = input('explain', 0);
            $is_first_explain = input('is_first_explain', 0);// 是否第一次回复
            $data = [
                'evaluate_id' => $evaluate_id
            ];
            if ($is_first_explain == 0) {
                $data['explain_first'] = $explain;
            } elseif ($is_first_explain == 1) {
                $data['again_explain'] = $explain;
            }

            return $goods_evaluate->evaluateApply($data);
        }
    }

    /**
     * 删除商品评价回复
     */
    public function deleteContent()
    {
        if (request()->isJson()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_id = input('evaluate_id', 0);
            $is_first_explain = input('is_first', 0);// 0 第一次回复，1 追评回复
            $data = [];
            if ($is_first_explain == 0) {
                $data['explain_first'] = '';
            } elseif ($is_first_explain == 1) {
                $data['again_explain'] = '';
            }
            $condition = [
                ['evaluate_id', '=', $evaluate_id],
                ['site_id', '=', $this->site_id],
            ];
            return $goods_evaluate->editEvaluate($data, $condition);
        }
    }


    /**
     * 商品批量设置
     */
    public function batchSet()
    {
        if (request()->isJson()) {
            $type = input('type', '');
            $goods_ids = input('goods_ids', '');
            $field = input('field', '');
            $data = !empty($field) ? json_decode($field, true) : [];
            $goods_model = new GoodsModel();

            $result = error(-1, '操作失败');
            try {
                if (!empty($goods_ids)) {
                    switch ($type) {
                        case 'group':
                            $result = $goods_model->modifyGoodsLabel($data['group'], $this->site_id, $goods_ids);
                            break;
                        case 'service':
                            $result = $goods_model->modifyGoodsService($data['server_ids'], $this->site_id, $goods_ids);
                            break;
                        case 'sale':
                            $result = $goods_model->modifyGoodsVirtualSale($data['sale'], $this->site_id, $goods_ids);
                            break;
                        case 'purchase_limit':
                            $result = $goods_model->modifyGoodsPurchaseLimit($data['max_buy'], $this->site_id, $goods_ids);
                            break;
                        case 'shipping':
                            $result = $goods_model->modifyGoodsDelivery($data['support_trade_type'], $data['is_free_shipping'], $data['shipping_template'], $this->site_id, $goods_ids);
                            break;
                        case 'category':
                            $result = $goods_model->modifyGoodsCategoryId($data['category_id'], $this->site_id, $goods_ids);
                            //$result = ',' . implode(',', $data[ 'category_id' ]) . ',';
                            break;
                        case 'shop_intor':
                            $result = $goods_model->modifyGoodsShopIntor($data['recom_way'], $this->site_id, $goods_ids);
                            break;
                        case 'member_price':
                            $result = $goods_model->modifyGoodsConsumeDiscount($data['is_consume_discount'], $this->site_id, $goods_ids);
                            break;
                        case 'stock':
                            $sku_list = $goods_model->getGoodsSkuList([['goods_id', 'in', $goods_ids], ['goods_class', 'in', [
                                    GoodsDict::real,
                                    GoodsDict::virtual,
                                    GoodsDict::service,
                                    GoodsDict::card,
                                    GoodsDict::weigh
                                ]]], 'sku_id,goods_id,stock,goods_class')['data'] ?? [];
                            // 实物,虚拟,卡项,服务，称重
                            $result = $goods_model->editGoodsSkuStock($sku_list, $data['stock'], $data['stock_type']);
                            break;
                        case 'price':
                            $batch_model = new Batch();
                            $params = array(
                                'site_id' => $this->site_id,
                                'type' => $type,
                                'goods_ids' => $goods_ids,
                            );
                            $result = $batch_model->setPrice(array_merge($params, $data));
                            break;
                        case 'goods_form':
                            $result = $goods_model->modifyGoodsForm($data['form_id'], $this->site_id, $goods_ids);
                            break;
                        case 'goods_brand':
                            $result = $goods_model->modifyGoodsBrand($data['brand_id'], $this->site_id, $goods_ids);
                            break;
                    }
                }
            } catch (\Exception $e) {
                $result = error(-1, $e->getMessage());
            }
            return $result;
        }
    }

    /**
     * 热门搜索关键词
     * @return mixed
     */
    public function hotSearchWords()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $words = input('words', []);
            $data = [
                'words' => implode(',', $words)
            ];
            $res = $config_model->setHotSearchWords($data, $this->site_id, $this->app_module);
            return $res;
        } else {

            $hot_search_words = $config_model->getHotSearchWords($this->site_id, $this->app_module)['data']['value'];

            $words_array = [];
            if (!empty($hot_search_words['words'])) {
                $words_array = explode(',', $hot_search_words['words']);
            }
            $hot_search_words['words_array'] = $words_array;
            $this->assign('hot_search_words', $hot_search_words);
            return $this->fetch('goods/hot_search_words');
        }
    }

    /**
     * 猜你喜欢
     * @return mixed
     */
    public function guessYouLike()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $value = input('value', '');
            if (!empty($value)) {
                $value = json_decode($value, true);
                $res = $config_model->setGuessYouLike($value, $this->site_id, $this->app_module);
                return $res;
            }
        } else {
            $config = $config_model->getGuessYouLike($this->site_id, $this->app_module)['data']['value'];
            $this->assign('config', $config);

            $this->assign('store_is_exit', addon_is_exit('store', $this->site_id));

            return $this->fetch('goods/guess_you_like');
        }
    }

    /**
     * 商品列表配置
     * @return mixed
     */
    public function goodsListConfig()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $value = input('value', '');
            if (!empty($value)) {
                $value = json_decode($value, true);
                $res = $config_model->setGoodsListConfig($value, $this->site_id, $this->app_module);
                return $res;
            }
        } else {
            $config = $config_model->getGoodsListConfig($this->site_id, $this->app_module)['data']['value'];
            $this->assign('config', $config);

            $this->assign('store_is_exit', addon_is_exit('store', $this->site_id));

            return $this->fetch('goods/goods_list_config');
        }
    }

    /**
     * 默认搜索关键词
     * @return mixed
     */
    public function defaultSearchWords()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $default_data = [
                'words' => input('default_words', '')
            ];
            $config_model->setDefaultSearchWords($default_data, $this->site_id, $this->app_module);
            $words = input('words', []);
            $data = [
                'words' => implode(',', $words)
            ];
            $res = $config_model->setHotSearchWords($data, $this->site_id, $this->app_module);

            $split_word_data = [
                'is_open'=>input('is_open',0),
                'apiKey' =>input('apiKey',''),
                'secretKey' =>input('secretKey','')
            ];

            $default_search_words = $config_model->getSplitWordConfig($this->site_id, $this->app_module)['data']['value'];
            foreach ($split_word_data as $key=>$value){
                if(strstr($value,'******')){
                    $split_word_data[$key] = $default_search_words[$key];
                }
            }

            $res = $config_model->setSplitWordConfig($split_word_data,$this->site_id, $this->app_module);

            return $res;
        } else {

            $default_search_words = $config_model->getDefaultSearchWords($this->site_id, $this->app_module)['data']['value'];
            $this->assign('default_search_words', $default_search_words);

            $hot_search_words = $config_model->getHotSearchWords($this->site_id, $this->app_module)['data']['value'];

            $words_array = [];
            if (!empty($hot_search_words['words'])) {
                $words_array = explode(',', $hot_search_words['words']);
            }
            $hot_search_words['words_array'] = $words_array;
            $this->assign('hot_search_words', $hot_search_words);

            $split_word = $config_model->getSplitWordConfig($this->site_id, $this->app_module);
            $this->assign('split_word', $split_word['data']['value']);
            return $this->fetch('goods/default_search_words');
        }
    }

    /**
     * 复制商品
     * @return array
     */
    public function copyGoods()
    {
        if (request()->isJson()) {
            $goods_id = input('goods_id', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->copyGoods($goods_id, $this->site_id);
            return $res;
        }
    }

    /**
     * 会员商品收藏
     */
    public function memberGoodsCollect()
    {
        $goods_collect_model = new GoodsCollect();
        $member_id = input('member_id', 0);
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [];
            $condition[] = ['gc.site_id', '=', $this->site_id];
            $condition[] = ['gc.member_id', '=', $member_id];
            $order = 'gc.create_time desc';
            $field = 'gc.collect_id,gc.create_time,gc.member_id, gc.goods_id, gc.sku_id,gc.sku_name, gc.sku_price, gc.sku_image,g.goods_name,g.is_free_shipping,sku.promotion_type,sku.member_price,sku.discount_price,g.sale_num,g.price,g.market_price,g.is_virtual,sku.collect_num,g.goods_state';
            return $goods_collect_model->getCollectPageList($condition, $page, $page_size, $order, $field);
        }
    }

    /**
     * 会员浏览记录
     */
    public function memberGoodsBrowse()
    {
        $member_id = input('member_id', 0);
        $goods_browse_model = new GoodsBrowse();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search = input('search', '');
            $condition = [];
            $condition[] = ['gb.site_id', '=', $this->site_id];
            $condition[] = ['gb.member_id', '=', $member_id];
            if (!empty($search)) {
                $condition[] = ['gs.sku_name', 'like', '%' . $search . '%'];
            }

            $order = 'browse_time desc';
            $field = 'gb.*,gs.sku_name,gs.sku_image,gs.price,gs.goods_state,gs.stock,gs.click_num';
            $alias = 'gb';
            $join = [
                ['goods_sku gs', 'gs.sku_id = gb.sku_id', 'right']
            ];
            return $goods_browse_model->getBrowsePageList($condition, $page, $page_size, $order, $field, $alias, $join);
        }
    }

    /**
     * 商品浏览记录
     */
    public function goodsBrowse()
    {
        $goods_id = input('goods_id', 0);
        $goods_browse_model = new GoodsBrowse();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search = input('search', '');
            $condition = [];
            $condition[] = ['gb.site_id', '=', $this->site_id];
            if ($goods_id > 0) {
                $condition[] = ['gb.goods_id', '=', $goods_id];
            }
            if (!empty($search))
                $condition[] = ['gs.sku_name', 'like', '%' . $search . '%'];

            $order = 'browse_time desc';
            $field = 'gb.*,gs.sku_name,gs.sku_image,gs.price,gs.goods_state,gs.stock,gs.click_num,m.nickname,m.headimg';
            $alias = 'gb';
            $join = [
                ['goods_sku gs', 'gs.sku_id = gb.sku_id', 'left'],
                ['member m', 'm.member_id = gb.member_id', 'left']
            ];
            $res = $goods_browse_model->getBrowsePageList($condition, $page, $page_size, $order, $field, $alias, $join);
            foreach ($res['data']['list'] as $k => $v) {
                $res['data']['list'][$k]['stock'] = numberFormat($res['data']['list'][$k]['stock']);
            }
            return $res;
        } else {
            $this->assign('goods_id', $goods_id);
            return $this->fetch('goods/goods_browse');
        }
    }

    /**
     * 商品排序
     */
    public function goodssort()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $type = input('type', 'asc');
            $default_value = input('default_value', '0');
            $data = [
                'type' => trim($type),
                'default_value' => trim($default_value),
            ];
            $res = $config_model->setGoodsSort($data, $this->site_id, $this->app_module);
            return $res;
        } else {

            $goods_sort_config = $config_model->getGoodsSort($this->site_id, $this->app_module)['data']['value'];

            $this->assign('goods_sort_config', $goods_sort_config);
            return $this->fetch('goods/goods_sort');
        }
    }

    /**
     * 商品导入
     */
    public function import()
    {
        $import_model = new GoodsImport();
        $type = input('type');
        if (request()->isJson()) {
            $file = request()->file('xlsx');
            if (empty($file)) {
                return $import_model->error();
            }
            $tmp_name = $file->getPathname();//获取上传缓存文件
            $data = (new GoodsImport())->readGoodsExcel($tmp_name);
            if ($data['code'] < 0) {
                return $data;
            }
            $result = (new GoodsImport())->importGoods($data['data'], $this->site_id, $type);
            return $result;
        } else {
            return $this->fetch('goods/import');
        }
    }

    public function importRecordList()
    {
        $page_index = input('page', 1);
        $page_size = input('page_size', PAGE_LIST_ROWS);
        $goodsimport_model = new GoodsImport();
        if (request()->isJson()) {
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $condition = [['site_id', '=', $this->site_id]];
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = ['import_time', '>=', date_to_time($start_time)];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = ['import_time', '<=', date_to_time($end_time)];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = ['import_time', 'between', [date_to_time($start_time), date_to_time($end_time)]];
            }
            $res = $goodsimport_model->getImportPageList($condition, $page_index, $page_size);

            if (!empty($res['data']['list'])) {
                foreach ($res['data']['list'] as $k => &$v) {
                    $v['import_time'] = time_to_date($v['import_time']);
                }
            }
            return $res;
        } else {
            return $this->fetch('goods/import_record_list');
        }
    }

    public function download()
    {
        $id = input('id', '0');
        (new GoodsImport())->downloadFailData($id, $this->site_id);
    }

    /**
     * 商品海报
     */
    public function poster()
    {
        $goods_poster_model = new GoodsPoster();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $keywords = input('keywords', '');
            if (!empty($keywords)) {
                $condition[] = ['poster_name', 'like', '%' . $keywords . '%'];
            }
            $condition[] = ['site_id', '=', $this->site_id];
            $result = $goods_poster_model->getPosterPageList($condition, $page, $page_size);
            return $result;
        } else {
            return $this->fetch('goods/goods_poster_list');
        }
    }

    /**
     * 商品海报添加
     */
    public function editPoster()
    {
        $goods_poster_model = new GoodsPoster();
        if (request()->isJson()) {
            $poster_name = input('poster_name', '');
            $json_data = input('json_data', '');
            $poster_id = input('poster_id', '');
            if (empty($poster_id)) {
                $data['create_time'] = time();
            } else {
                $data['modify_time'] = time();
            }
            $data['poster_id'] = $poster_id;
            $data['poster_name'] = $poster_name;
            $data['json_data'] = $json_data;
            $data['poster_type'] = 1;
            $data['site_id'] = $this->site_id;

            $result = $goods_poster_model->addPoster($data);
            return $result;
        } else {
            $poster_id = input('poster_id', '');
            if (!empty($poster_id)) {
                $goods_poster_data = $goods_poster_model->getPosterInfo($poster_id, $this->site_id);
                $this->assign('poster_data', $goods_poster_data['data']);
            }

            return $this->fetch('goods/goods_edit_poster');
        }
    }

    /**
     * 修改海报启用状态
     */
    public function editStatus()
    {
        $goods_poster_model = new GoodsPoster();

        $poster_id = input('poster_id', 0);
        $status = input('status', '');

        $data = [
            'poster_id' => $poster_id,
            'status' => $status,
            'site_id' => $this->site_id,
            'modify_time' => time()
        ];
        return $goods_poster_model->addPoster($data);
    }

    /**
     * 删除海报
     */
    public function deletePoster()
    {
        $goods_poster_model = new GoodsPoster();

        $poster_id = input('poster_id', 0);

        return $goods_poster_model->deletePoster($poster_id, $this->site_id);
    }

    public function verify()
    {
        $goods_id = input('goods_id', '0');
        $virtual_goods_model = new \app\model\goods\VirtualGoods();
        if (request()->isJson()) {
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $nickname = input('nickname', '');
            $is_verify = input('is_verify', 'all');
            $verify_code = input('verify_code', '');

            $field = 'gv.*, m.nickname,m.headimg';

            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $alias = 'gv';
            $condition = [
                ['gv.site_id', '=', $this->site_id],
                ['gv.goods_id', '=', $goods_id],
            ];
            if ($nickname) $condition[] = ['m.nickname', 'like', '%' . $nickname . '%'];
            if ($verify_code) $condition[] = ['gv.code', '=', $verify_code];
            $order = 'gv.id desc';
            $join = [
                [
                    'member m',
                    'm.member_id = gv.member_id',
                    'left'
                ]
            ];

            if ($is_verify != 'all') {
                $condition[] = ['gv.is_veirfy', '=', $is_verify];
            }

            $list = $virtual_goods_model->getVirtualGoodsPageList($condition, $page_index, $page_size, $order, $field, $alias, $join);
            return $list;
        }
        $this->assign('goods_id', $goods_id);
        $goods_model = new GoodsModel();
        $goods_info = $goods_model->getGoodsInfo([['goods_id', '=', $goods_id], ['site_id', '=', $this->site_id]], 'goods_id, goods_name,goods_image,price,goods_state,goods_stock,sale_num');
        $this->assign('goods_info', $goods_info['data']);
        $verify_count = $virtual_goods_model->getVirtualGoodsInfo([['goods_id', '=', $goods_id], ['site_id', '=', $this->site_id]], 'count(id) as total_count, sum(verify_use_num) as verify_use_num')['data'] ?? [];
        $this->assign('total_count', $verify_count['total_count'] ?? 0);
        $this->assign('verify_use_num', $verify_count['verify_use_num'] ?? 0);

        return $this->fetch('goods/verify');
    }

    /**
     * 商品详情配置
     * @return mixed
     */
    public function goodsDetailConfig()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $data = [
                'nav_bar_switch' => input('nav_bar_switch', 0),
                'introduction_color' => input('introduction_color', ''),
            ];
            $res = $config_model->setGoodsDetailConfig($data, $this->site_id, $this->app_module);
            return $res;
        } else {
            $config = $config_model->getGoodsDetailConfig($this->site_id, $this->app_module)['data']['value'];
            $this->assign('config', $config);
            return $this->fetch('goods/goods_detail_config');
        }
    }

    /**
     * 获取运费模板
     * @return array
     */
    public function getExpressTemplateList()
    {
        if (request()->isJson()) {
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([['site_id', '=', $this->site_id]], 'template_id,template_name', 'is_default desc') ?? [];
//            foreach ($express_template_list[ 'data' ] as $k => $v) {
//                $template_item_condition = array (
//                    [ 'template_id', '=', $v[ 'template_id' ] ],
//                );
//                $template_item_list = $express_template_model->getExpressTemplateItemList($template_item_condition)[ 'data' ] ?? [];
//                $express_template_list[ 'data' ][ $k ][ 'template_item_list' ] = $template_item_list;
//            }
            return $express_template_list;
        }
    }

    /**
     * 验证商品编码是否重复
     * @return array
     */
    public function verifySkuNo()
    {
        if (request()->isJson()) {
            $sku_no = input('sku_no', '');
            $goods_id = input('goods_id', 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->verifySkuNo([
                'site_id' => $this->site_id,
                'sku_no' => $sku_no,
                'goods_id' => $goods_id
            ]);
            return $res;
        }
    }

    /**
     * 商品导出
     */
    public function exportGoods()
    {
        if (request()->isJson()) {
            $input = input();
            $goods_export = new GoodsExport();
            return $goods_export->export($input, [
                'site_id' => $this->site_id,
            ]);
        }
    }

    /**
     * 导出记录
     * @return array|mixed
     */
    public function export()
    {
        $param = [
            'from_type_list' => [
                ['id' => 'goods', 'name' => '商品'],
            ],
            'lists_url' => 'shop/goods/export',
            'delete_url' => 'shop/goods/deleteExport',
        ];
        $export_controller = new \app\shop\controller\Export();
        return $export_controller->lists($param);
    }

    /**
     * 删除导出记录
     * @return array
     */
    public function deleteExport()
    {
        $export_controller = new \app\shop\controller\Export();
        return $export_controller->delete('goods');
    }

    /**
     * 商品编码
     */
    public function goodsNo()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $uniqueness_switch = input('uniqueness_switch', 0);
            $data = [
                'uniqueness_switch' => $uniqueness_switch,
            ];
            $res = $config_model->setGoodsNo($data, $this->site_id, $this->app_module);
            return $res;
        } else {

            $info = $config_model->getGoodsNo($this->site_id, $this->app_module)['data']['value'];

            $this->assign('info', $info);
            return $this->fetch('goods/goods_no');
        }
    }

}