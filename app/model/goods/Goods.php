<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\goods;

use addon\bundling\model\Bundling;
use addon\discount\model\Discount;
use app\dict\goods\GoodsDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\Order;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund;
use app\model\store\Store;
use app\model\storegoods\StoreGoods;
use app\model\storegoods\StoreSale;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use app\model\system\Stat;
use Exception;
use think\db\exception\DbException;
use think\facade\Cache;
use think\facade\Db;

/**
 * 商品
 */
class Goods extends GoodsCommon
{

    private $goods_class = array('id' => 1, 'name' => '实物商品');

    private $goods_state = array(
        1 => '销售中',
        0 => '仓库中'
    );

    public function getGoodsState()
    {
        return $this->goods_state;
    }

    public function getGoodsClass()
    {
        return $this->goods_class;
    }

    /**
     * 商品添加
     * @param $data
     * @return array
     */
    public function addGoods($data)
    {
        model('goods')->startTrans();

        try {
            $site_id = $data['site_id'];
            if (!empty($data['goods_attr_format'])) {

                $goods_attr_format = json_decode($data['goods_attr_format'], true);
                $keys = array_column($goods_attr_format, 'sort');
                if (!empty($keys)) {
                    array_multisort($keys, SORT_ASC, SORT_NUMERIC, $goods_attr_format);
                    $data['goods_attr_format'] = json_encode($goods_attr_format);
                }
            }

            $goods_image = $data['goods_image'];
            $first_image = explode(",", $goods_image)[0];

            //SKU商品数据
            if (!empty($data['goods_sku_data'])) {
                $data['goods_sku_data'] = json_decode($data['goods_sku_data'], true);
//                if (empty($goods_image)) {
//                    $goods_image = $data[ 'goods_sku_data' ][ 0 ][ 'sku_image' ];
//                }
            }

            //商品编码检测
            $sku_no_check = $this->checkSkuNoRepeat(['sku_list' => $data['goods_sku_data'], 'site_id' => $data['site_id'], 'goods_id' => $data['goods_id'] ?? 0]);
            if ($sku_no_check['code'] < 0) {
                model('goods')->rollback();
                return $sku_no_check;
            }

            if (isset($data['support_trade_type']) && strpos($data['support_trade_type'], 'express') !== false && $data['is_free_shipping'] == 0 && empty($data['shipping_template'])) {
                return $this->error('', '运费模板不能为空');
            }

            //获取标签名称
            $label_name = '';
            if ($data['label_id']) {
                $label_info = model('goods_label')->getInfo([['id', '=', $data['label_id']]], 'label_name');
                $label_name = $label_info['label_name'] ?? '';
            }
            $brand_name = '';
            if ($data['brand_id']) {
                $brand_info = model('goods_brand')->getInfo([['brand_id', '=', $data['brand_id']]], 'brand_name');
                $brand_name = $brand_info['brand_name'] ?? '';
            }

            $goods_data = array(
                'goods_image' => $goods_image,
                'price' => !empty($data['goods_sku_data'][0]['price']) ? $data['goods_sku_data'][0]['price'] : '',
                'market_price' => !empty($data['goods_sku_data'][0]['market_price']) ? $data['goods_sku_data'][0]['market_price'] : '',
                'cost_price' => !empty($data['goods_sku_data'][0]['cost_price']) ? $data['goods_sku_data'][0]['cost_price'] : '',
                'goods_spec_format' => $data['goods_spec_format'],
                'category_id' => $data['category_id'],
                'category_json' => $data['category_json'],
                'label_id' => $data['label_id'],
                'label_name' => $label_name,
                'timer_on' => $data['timer_on'],
                'timer_off' => $data['timer_off'],
                'is_consume_discount' => $data['is_consume_discount'],
                'sale_show' => $data['sale_show'] ?? 1,
                'stock_show' => $data['stock_show'] ?? 1,
                'market_price_show' => $data['market_price_show'] ?? 1,
                'barrage_show' => $data['barrage_show'] ?? 1,
            );

            $common_data = array(
                'goods_name' => $data['goods_name'],
                'goods_class' => $this->goods_class['id'],
                'goods_class_name' => $this->goods_class['name'],
                'goods_attr_class' => $data['goods_attr_class'],
                'goods_attr_name' => $data['goods_attr_name'],
                'is_limit' => $data['is_limit'] ?? 0,
                'limit_type' => $data['limit_type'] ?? 1,
                'site_id' => $data['site_id'],
                'goods_content' => $data['goods_content'],
                'goods_state' => $data['goods_state'],
                'goods_stock_alarm' => $data['goods_stock_alarm'],
                'is_free_shipping' => $data['is_free_shipping'],
                'shipping_template' => $data['shipping_template'],
                'goods_attr_format' => $data['goods_attr_format'],
                'introduction' => $data['introduction'],
                'keywords' => $data['keywords'],
                'brand_id' => $data['brand_id'],//品牌id
                'brand_name' => $brand_name,//品牌名称
                'unit' => $data['unit'],
                'video_url' => $data['video_url'],
                'sort' => $data['sort'],
                'goods_service_ids' => $data['goods_service_ids'],
                'create_time' => time(),
                'virtual_sale' => $data['virtual_sale'],
                'max_buy' => $data['max_buy'],
                'min_buy' => $data['min_buy'],
                'recommend_way' => $data['recommend_way'],
                'qr_id' => $data['qr_id'] ?? 0,
                'template_id' => $data['template_id'] ?? 0,
                'form_id' => $data['form_id'] ?? 0,
                'support_trade_type' => $data['support_trade_type'] ?? '',
                'sale_channel' => $data['sale_channel'] ?? 'all',
                'sale_store' => $data['sale_store'] ?? 'all',
                'is_unify_price' => $data['is_unify_price'] ?? 1,
                'supplier_id' => $data['supplier_id'] ?? 0
            );

            $goods_id = model('goods')->add(array_merge($goods_data, $common_data));

            $goods_stock = 0;

            $sku_arr = array();
            //添加sku商品
            $sku_stock_list = [];
            foreach ($data['goods_sku_data'] as $item) {

                $goods_stock += $item['stock'];

                $sku_data = array(
                    'sku_name' => $data['goods_name'] . ' ' . $item['spec_name'],
                    'spec_name' => $item['spec_name'],
                    'sku_no' => $item['sku_no'],
                    'sku_spec_format' => !empty($item['sku_spec_format']) ? json_encode($item['sku_spec_format']) : "",
                    'price' => $item['price'],
                    'market_price' => $item['market_price'],
                    'cost_price' => $item['cost_price'],
                    'discount_price' => $item['price'],//sku折扣价（默认等于单价）
                    'stock_alarm' => $item['stock_alarm'],
                    'weight' => $item['weight'],
                    'volume' => $item['volume'],
                    'sku_image' => !empty($item['sku_image']) ? $item['sku_image'] : $first_image,
                    'sku_images' => $item['sku_images'],
                    'goods_id' => $goods_id,
                    'is_default' => $item['is_default'] ?? 0,
                    'is_consume_discount' => $data['is_consume_discount'],
                );
                $sku_stock_list[] = ['stock' => $item['stock'], 'site_id' => $site_id, 'goods_class' => $common_data['goods_class']];
                $sku_arr[] = array_merge($sku_data, $common_data);
            }

            model('goods_sku')->addList($sku_arr);

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData(['goods_id' => $goods_id], 'sku_id', 'is_default desc,sku_id asc');
            model('goods')->update(['sku_id' => $first_info['sku_id']], [['goods_id', '=', $goods_id]]);

            $sale_store = [];
            if ($common_data['sale_store'] != 'all') {
                $sale_store = explode(',', $common_data['sale_store']);
                $sale_store = array_filter($sale_store);
            }

            $store_goods_state = $data['goods_state'];
            // 效验门店运营模式,连锁门店默认下架商品
            if(addon_is_exit('store')){
                $config_model = new \addon\store\model\Config();
                $business_config = $config_model->getStoreBusinessConfig($site_id)['data']['value'];
                if ($business_config['store_business'] == 'store') {
                    $store_goods_state = 0;
                }
            }
            //同步默认门店的上下架状态
            if ($data['goods_state'] == 1) {
                (new StoreGoods())->modifyGoodsState($goods_id, $store_goods_state, $site_id);
            } else {
                // 商品下架状态，所有门店的商品都下架
                if (!empty($sale_store)) {
                    foreach ($sale_store as $k => $v) {
                        (new StoreGoods())->modifyGoodsState($goods_id, $store_goods_state, $site_id, $v);
                    }
                } else {
                    (new StoreGoods())->modifyGoodsState($goods_id, $store_goods_state, $site_id);
                }
            }

            if (!empty($data['goods_spec_format'])) {
                // 刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($goods_id, $data['goods_spec_format']);
            }

            $cron = new Cron();
            //定时上下架
            if ($goods_data['timer_on'] > 0) {
                $cron->addCron(1, 0, "商品定时上架", "CronGoodsTimerOn", $goods_data['timer_on'], $goods_id);
            }
            if ($goods_data['timer_off'] > 0) {
                $cron->addCron(1, 0, "商品定时下架", "CronGoodsTimerOff", $goods_data['timer_off'], $goods_id);
            }

            //添加店铺添加统计
            $stat = new Stat();
            $stat->switchStat(['type' => 'add_goods', 'data' => ['add_goods_count' => 1, 'site_id' => $data['site_id']]]);
            $stat->switchStat(['type' => 'goods_on', 'data' => ['site_id' => $data['site_id']]]);
            $sku_list = model('goods_sku')->getList(['goods_id' => $goods_id], 'sku_id');

            // 商品设置库存
            $goods_stock_model = new \app\model\stock\GoodsStock();
            foreach ($sku_stock_list as $k => $v) {
                $sku_stock_list[$k]['sku_id'] = $sku_list[$k]['sku_id'];
            }
            // 同步商品成本价
            if (!empty($sale_store)) {
                foreach ($sale_store as $k => $v) {
                    (new StoreGoods())->setSkuPrice(['goods_id' => $goods_id, 'site_id' => $site_id, 'store_id' => $v]);
                }
            } else {
                (new StoreGoods())->setSkuPrice(['goods_id' => $goods_id, 'site_id' => $site_id]);
            }
            $goods_stock_model->changeGoodsStock([
                'site_id' => $data['site_id'],
                'goods_class' => $common_data['goods_class'],
                'goods_sku_list' => $sku_stock_list
            ]);

            model('goods')->commit();
            return $this->success($goods_id);
        } catch (Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 通过属性值获取属性名称
     * @param $sku_spec_format
     * @return string
     */
    public function getSpecNameBySkuSpecFormat($sku_spec_format)
    {
        if (is_string($sku_spec_format)) {
            $sku_spec_format = !empty($sku_spec_format) ? json_decode($sku_spec_format, true) : [];
        }
        $arr = [];
        foreach ($sku_spec_format as $format) {
            $arr[] = $format['spec_value_name'];
        }
        return join(' ', $arr);
    }

    /**
     * 商品编辑
     * @param $data
     * @return array
     */
    public function editGoods($data)
    {

        model('goods')->startTrans();

        try {

            $site_id = $data['site_id'];
            if (!empty($data['goods_attr_format'])) {

                $goods_attr_format = json_decode($data['goods_attr_format'], true);
                $keys = array_column($goods_attr_format, 'sort');
                if (!empty($keys)) {
                    array_multisort($keys, SORT_ASC, SORT_NUMERIC, $goods_attr_format);
                    $data['goods_attr_format'] = json_encode($goods_attr_format);
                }
            }
            $goods_id = $data['goods_id'];
            $goods_image = $data['goods_image'];
            $first_image = explode(",", $goods_image)[0];

            //SKU商品数据
            if (!empty($data['goods_sku_data'])) {
                $data['goods_sku_data'] = json_decode($data['goods_sku_data'], true);
//                if (empty($goods_image)) {
//                    $goods_image = $data[ 'goods_sku_data' ][ 0 ][ 'sku_image' ];
//                }
            }

            //商品编码检测
            $sku_no_check = $this->checkSkuNoRepeat(['sku_list' => $data['goods_sku_data'], 'site_id' => $data['site_id'], 'goods_id' => $data['goods_id'] ?? 0]);
            if ($sku_no_check['code'] < 0) {
                model('goods')->rollback();
                return $sku_no_check;
            }

            if (isset($data['support_trade_type']) && strpos($data['support_trade_type'], 'express') !== false && $data['is_free_shipping'] == 0 && empty($data['shipping_template'])) {
                return $this->error('', '运费模板不能为空');
            }

            //获取标签名称
            $label_name = '';
            if ($data['label_id']) {
                $label_info = model('goods_label')->getInfo([['id', '=', $data['label_id']]], 'label_name');
                $label_name = $label_info['label_name'] ?? '';
            }
            $brand_name = '';
            if ($data['brand_id']) {
                $brand_info = model('goods_brand')->getInfo([['brand_id', '=', $data['brand_id']]], 'brand_name');
                $brand_name = $brand_info['brand_name'] ?? '';
            }
            $goods_data = array(
                'goods_image' => $goods_image,
//                'goods_stock' => $data[ 'goods_stock' ],
                'price' => $data['goods_sku_data'][0]['price'],
                'market_price' => $data['goods_sku_data'][0]['market_price'],
                'cost_price' => $data['goods_sku_data'][0]['cost_price'],
                'goods_spec_format' => $data['goods_spec_format'],
                'category_id' => $data['category_id'],
                'category_json' => $data['category_json'],
                'label_id' => $data['label_id'],
                'label_name' => $label_name,
                'timer_on' => $data['timer_on'],
                'timer_off' => $data['timer_off'],
                'is_consume_discount' => $data['is_consume_discount'],
                'sale_show' => $data['sale_show'],
                'stock_show' => $data['stock_show'],
                'market_price_show' => $data['market_price_show'],
                'barrage_show' => $data['barrage_show'],
                'support_trade_type' => $data['support_trade_type'] ?? '',
            );

            $common_data = array(
                'goods_name' => $data['goods_name'],
                'goods_class' => $this->goods_class['id'],
                'goods_class_name' => $this->goods_class['name'],
                'goods_attr_class' => $data['goods_attr_class'],
                'goods_attr_name' => $data['goods_attr_name'],
                'site_id' => $data['site_id'],
                'goods_content' => $data['goods_content'],
                'goods_state' => $data['goods_state'],
                'goods_stock_alarm' => $data['goods_stock_alarm'],
                'is_free_shipping' => $data['is_free_shipping'],
                'shipping_template' => $data['shipping_template'],
                'goods_attr_format' => $data['goods_attr_format'],
                'introduction' => $data['introduction'],
                'keywords' => $data['keywords'],
                'unit' => $data['unit'],
                'video_url' => $data['video_url'],
                'sort' => $data['sort'],
                'goods_service_ids' => $data['goods_service_ids'],
                'modify_time' => time(),
                'virtual_sale' => $data['virtual_sale'],
                'max_buy' => $data['max_buy'],
                'min_buy' => $data['min_buy'],
                'brand_id' => $data['brand_id'],//品牌id
                'brand_name' => $brand_name,//品牌名称
                'recommend_way' => $data['recommend_way'],
                'is_consume_discount' => $data['is_consume_discount'],
                'is_limit' => $data['is_limit'],
                'limit_type' => $data['limit_type'],
                'qr_id' => $data['qr_id'] ?? 0,
                'template_id' => $data['template_id'] ?? 0,
                'form_id' => $data['form_id'] ?? 0,
                'support_trade_type' => $data['support_trade_type'] ?? '',
                'sale_channel' => $data['sale_channel'] ?? 'all',
                'sale_store' => $data['sale_store'] ?? 'all',
                'is_unify_price' => $data['is_unify_price'] ?? 1,
                'supplier_id' => $data['supplier_id'] ?? 0
            );
            model('goods')->update(array_merge($goods_data, $common_data), [['goods_id', '=', $goods_id], ['goods_class', '=', $this->goods_class['id']]]);


            $goods_stock_model = new \app\model\stock\GoodsStock();
            $sku_stock_list = [];
            $is_off_store_goods = 0; // 是否下架门店商品

            $discount_model = new Discount();
            $sku_id_arr = [];
            foreach ($data['goods_sku_data'] as $item) {
                $discount_info = [];
                if (!empty($item['sku_id'])) {
                    $discount_info_result = $discount_model->getDiscountGoodsInfo([['pdg.sku_id', '=', $item['sku_id']], ['pd.status', '=', 1]], 'id');
                    $discount_info = $discount_info_result['data'];
                }
                $sku_data = array(
                    'sku_name' => $data['goods_name'] . ' ' . $item['spec_name'],
                    'spec_name' => $item['spec_name'],
                    'sku_no' => $item['sku_no'],
                    'sku_spec_format' => !empty($item['sku_spec_format']) ? json_encode($item['sku_spec_format']) : "",
                    'goods_spec_format' => '',
                    'price' => $item['price'],
                    'market_price' => $item['market_price'],
                    'cost_price' => $item['cost_price'],
//                            'stock' => $item[ 'stock' ],
                    'stock_alarm' => $item['stock_alarm'],
                    'weight' => $item['weight'],
                    'volume' => $item['volume'],
                    'sku_image' => !empty($item['sku_image']) ? $item['sku_image'] : $first_image,
                    'sku_images' => $item['sku_images'],
                    'goods_id' => $goods_id,
                    'is_default' => $item['is_default'] ?? 0,
                    'is_consume_discount' => $data['is_consume_discount'],
                );
                if (empty($discount_info)) {
                    $sku_data['discount_price'] = $item['price'];
                }
                if (!empty($item['sku_id'])) {
                    $sku_id_arr[] = $item['sku_id'];
                    model('goods_sku')->update(array_merge($sku_data, $common_data), [['sku_id', '=', $item['sku_id']], ['goods_class', '=', $this->goods_class['id']]]);
                } else {
                    $sku_id = model('goods_sku')->add(array_merge($sku_data, $common_data));
                    $item['sku_id'] = $sku_id;
                    $sku_id_arr[] = $sku_id;
                }
                $sku_stock_list[] = ['stock' => $item['stock'], 'sku_id' => $item['sku_id'], 'site_id' => $site_id, 'goods_class' => $common_data['goods_class']];
            }

            // 移除不存在的商品SKU
            $sku_id_list = model('goods_sku')->getList([['goods_id', '=', $goods_id]], 'sku_id');
            $sku_id_list = array_column($sku_id_list, 'sku_id');
            foreach ($sku_id_list as $k => $v) {
                foreach ($sku_id_arr as $ck => $cv) {
                    if ($v == $cv) {
                        unset($sku_id_list[$k]);
                    }
                }
            }
            $sku_id_list = array_values($sku_id_list);
            if (!empty($sku_id_list)) {
                $check = $this->deleteGoodsSkuCheck($sku_id_list);
                if ($check['code'] < 0) {
                    model('goods')->rollback();
                    return $check;
                }

                $is_off_store_goods = 1;
                model('goods_sku')->delete([['sku_id', 'in', implode(",", $sku_id_list)]]);
            }

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData(['goods_id' => $goods_id], 'sku_id', 'is_default desc,sku_id asc');
            model('goods')->update(['sku_id' => $first_info['sku_id']], [['goods_id', '=', $goods_id]]);

            $sale_store = [];
            if ($common_data['sale_store'] != 'all') {
                $sale_store = explode(',', $common_data['sale_store']);
                $sale_store = array_filter($sale_store);
            }

            if (!empty($data['goods_spec_format'])) {
                //刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($goods_id, $data['goods_spec_format']);
            }

            if ($is_off_store_goods == 1) {
                (new StoreGoods())->modifyStoreGoodsState($goods_id, 0);
            }


            //同步默认门店的上下架状态
            if ($data['goods_state'] == 1) {
                $sync_goods_state = true; // 同步默认门店的上下架状态,连锁门店默认不同步
                if(addon_is_exit('store')){
                    $config_model = new \addon\store\model\Config();
                    $business_config = $config_model->getStoreBusinessConfig($site_id)['data']['value'];
                    if ($business_config['store_business'] == 'store'){
                        $store_model = new Store();
                        $store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
                        (new StoreGoods())->checkStoreGoods($goods_id, $site_id, $store_info['store_id']);
                        $sync_goods_state = false;
                    }
                }
                if($sync_goods_state){
                    (new StoreGoods())->modifyGoodsState($goods_id, $data['goods_state'], $site_id);
                }
            } else {
                // 商品下架状态，所有门店的商品都下架
                if (!empty($sale_store)) {
                    foreach ($sale_store as $k => $v) {
                        (new StoreGoods())->modifyGoodsState($goods_id, $data['goods_state'], $site_id, $v);
                    }
                } else {
                    (new StoreGoods())->modifyGoodsState($goods_id, $data['goods_state'], $site_id);
                }
            }

            $cron = new Cron();
            $cron->deleteCron([['event', '=', 'CronGoodsTimerOn'], ['relate_id', '=', $goods_id]]);
            $cron->deleteCron([['event', '=', 'CronGoodsTimerOff'], ['relate_id', '=', $goods_id]]);

            //定时上下架
            if ($goods_data['timer_on'] > 0) {
                $cron->addCron(1, 0, '商品定时上架', 'CronGoodsTimerOn', $goods_data['timer_on'], $goods_id);
            }
            if ($goods_data['timer_off'] > 0) {
                $cron->addCron(1, 0, '商品定时下架', 'CronGoodsTimerOff', $goods_data['timer_off'], $goods_id);
            }

            event('GoodsEdit', ['goods_id' => $goods_id, 'site_id' => $data['site_id']]);
            $stat = new Stat();
            $stat->switchStat(['type' => 'goods_on', 'data' => ['site_id' => $data['site_id']]]);

            //核验和校准改变的sku
            $goods_stock_model->checkExistGoodsSku(['goods_id' => $goods_id]);
            if ($common_data['is_unify_price'] == 1) {
                if (!empty($sale_store)) {
                    foreach ($sale_store as $k => $v) {
                        //同步商品成本价
                        (new StoreGoods())->setSkuPrice(['goods_id' => $goods_id, 'site_id' => $site_id, 'store_id' => $v]);
                    }
                } else {
                    //同步商品成本价
                    (new StoreGoods())->setSkuPrice(['goods_id' => $goods_id, 'site_id' => $site_id]);
                }
            }

            // 商品设置库存
            $goods_stock_model->changeGoodsStock([
                'site_id' => $data['site_id'],
                'goods_class' => $common_data['goods_class'],
                'goods_sku_list' => $sku_stock_list
            ]);

            model('goods')->commit();
            return $this->success($goods_id);
        } catch (Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 修改商品状态
     * @param $goods_ids
     * @param $goods_state
     * @param $site_id
     * @return array
     */
    public function modifyGoodsState($goods_ids, $goods_state, $site_id)
    {
        $error_num = 0;
        $error_reasons = [];
        if ($goods_state == 1) {
            $goods_list = model('goods')->getList([['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]], 'goods_id,sale_store,goods_class,support_trade_type,verify_validity_type,virtual_indate');
            $real_goods_ids = [];
            $cron = new Cron();
            foreach ($goods_list as $v) {
                if ($v['goods_class'] == GoodsDict::real && empty($v['support_trade_type'])) {
                    $error_num++;
                    $reason = '未设置配送方式';
                    if (!in_array($reason, $error_reasons)) $error_reasons[] = $reason;
                } elseif ($v['goods_class'] == GoodsDict::virtual && $v['verify_validity_type'] == 2) {
                    if ($v['virtual_indate'] < time()) {
                        $error_num++;
                        $reason = '商品核销有效期小于当前日期';
                        if (!in_array($reason, $error_reasons)) $error_reasons[] = $reason;
                    } else {
                        $cron->addCron(1, 0, "虚拟商品定时下架", "CronVirtualGoodsVerifyOff", $v['virtual_indate'], $v['goods_id']);
                    }
                } else {
                    $real_goods_ids[] = $v['goods_id'];
                }
            }
            if (count($goods_list) == $error_num) {
                return $this->error(null, '由于' . join(',', $error_reasons) . '修改失败');
            }
            $goods_ids = join(',', $real_goods_ids);
        }

        model('goods')->update(['goods_state' => $goods_state], [['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]]);
        model('goods_sku')->update(['goods_state' => $goods_state], [['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]]);

        if ($goods_state == 1) {
            if(addon_is_exit('store')){
                $config_model = new \addon\store\model\Config();
                $business_config = $config_model->getStoreBusinessConfig($site_id)['data']['value'];
                if ($business_config['store_business'] == 'shop') {
                    // 效验门店运营模式,只有连锁门店模式修改门店商品状态
                    (new StoreGoods())->modifyGoodsState($goods_ids, $goods_state, $site_id);
                }
            }else{
                (new StoreGoods())->modifyGoodsState($goods_ids, $goods_state, $site_id);
            }
        }  else {
            // 商品下架状态，所有门店的商品都下架
            $goods_list = model('goods')->getList([['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]], 'goods_id,sale_store');
            foreach ($goods_list as $k => $v) {
                $sale_store = [];
                if ($v['sale_store'] != 'all') {
                    $sale_store = explode(',', $v['sale_store']);
                    $sale_store = array_filter($sale_store);
                }

                if (!empty($sale_store)) {
                    foreach ($sale_store as $ck => $cv) {
                        (new StoreGoods())->modifyGoodsState($v['goods_id'], $goods_state, $site_id, $cv);
                    }
                } else {
                    (new StoreGoods())->modifyGoodsState($v['goods_id'], $goods_state, $site_id);
                }
            }
        }

        $stat = new Stat();
        $stat->switchStat(['type' => 'goods_on', 'data' => ['site_id' => $site_id]]);

        $res = [
            'error_num' => $error_num,
            'error_reason' => !empty($error_reasons) ? '部分商品由于' . join(',', $error_reasons) . '修改失败' : '',
        ];
        return $this->success($res);
    }

    /**
     * 商品复制
     * @param $goods_id
     * @param $site_id
     * @return array
     */
    public function copyGoods($goods_id, $site_id)
    {
        $goods_info = model('goods')->getInfo([['goods_id', '=', $goods_id], ['site_id', '=', $site_id]]);
        if (empty($goods_info)) {
            return $this->error('', '商品不存在，无法复制！');
        }

        //商品编码设置
        $config_model = new \app\model\web\Config();
        $goods_no_config = $config_model->getGoodsNo($site_id)['data']['value'];

        model('goods')->startTrans();
        try {
            unset($goods_info['goods_id']);
            $goods_info['goods_name'] .= '_副本';
            $goods_info['goods_state'] = 0;
            $goods_info['goods_stock'] = 0;
            $goods_info['real_stock'] = 0;
            $goods_info['create_time'] = time();
            $goods_info['modify_time'] = 0;
            $goods_info['sale_num'] = 0;
            $goods_info['evaluate'] = 0;
            $goods_info['evaluate_shaitu'] = 0;
            $goods_info['evaluate_shipin'] = 0;
            $goods_info['evaluate_zhuiping'] = 0;
            $goods_info['evaluate_haoping'] = 0;
            $goods_info['evaluate_zhongping'] = 0;
            $goods_info['evaluate_chaping'] = 0;
            $goods_info['is_fenxiao'] = 0;
            $goods_info['fenxiao_type'] = 1;
            $goods_info['supplier_id'] = 0;
            $goods_info['is_consume_discount'] = 0;
            $goods_info['discount_config'] = 0;
            $goods_info['discount_method'] = '';
            $goods_info['sku_id'] = 0;
            $goods_info['promotion_addon'] = '';
            $goods_info['virtual_sale'] = 0;

            $new_goods_id = model('goods')->add($goods_info);

            $goods_sku_list = model('goods_sku')->getList([['goods_id', '=', $goods_id]], '*', 'sku_id asc');

            $sku_data = array();

            $sale_store = '';
            $store_ids = [];

            foreach ($goods_sku_list as $k => $v) {
                unset($v['sku_id']);
                //编码不能重复则清空
                if ($goods_no_config['uniqueness_switch'] == 1) {
                    $v['sku_no'] = '';
                }
                $v['goods_id'] = $new_goods_id;
                $v['sku_name'] .= '_副本';
                $v['promotion_type'] = 0;
                $v['start_time'] = 0;
                $v['end_time'] = 0;
                $v['stock'] = 0;
                $v['real_stock'] = 0;
                $v['click_num'] = 0;
                $v['sale_num'] = 0;
                $v['collect_num'] = 0;
                $v['goods_name'] .= '_副本';
                $v['goods_state'] = 0;
                $v['create_time'] = time();
                $v['modify_time'] = 0;
                $v['evaluate'] = 0;
                $v['evaluate_shaitu'] = 0;
                $v['evaluate_shipin'] = 0;
                $v['evaluate_zhuiping'] = 0;
                $v['evaluate_haoping'] = 0;
                $v['evaluate_zhongping'] = 0;
                $v['evaluate_chaping'] = 0;
                $v['supplier_id'] = 0;
                $v['is_consume_discount'] = 0;
                $v['discount_config'] = 0;
                $v['discount_method'] = '';
                $v['member_price'] = '';
                $v['fenxiao_price'] = 0;
                $v['virtual_sale'] = 0;
                $sku_data[] = $v;
                $sale_store = $v['sale_store'];
            }

            model('goods_sku')->addList($sku_data);

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData(['goods_id' => $new_goods_id], 'sku_id', 'sku_id asc');
            model('goods')->update(['sku_id' => $first_info['sku_id']], [['goods_id', '=', $new_goods_id]]);

            if (!empty($goods_info['goods_spec_format'])) {
                // 刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($new_goods_id, $goods_info['goods_spec_format']);
            }

            // 卡项商品，添加卡项
            if ($goods_info['goods_class'] == GoodsDict::card) {
                $goods_card_info = model('goods_card')->getInfo([
                    ['goods_id', '=', $goods_id],
                    ['site_id', '=', $site_id]
                ]);
                if (!empty($goods_card_info)) {
                    // 添加商品卡项表
                    unset($goods_card_info['card_id']);
                    $goods_card_info['goods_id'] = $new_goods_id;
                    model('goods_card')->add($goods_card_info);

                    $goods_card_item_list = model('goods_card_item')->getList([
                        ['site_id', '=', $site_id],
                        ['card_goods_id', '=', $goods_id]
                    ]);
                    $goods_card_item = [];
                    foreach ($goods_card_item_list as $item) {
                        unset($item['id']);
                        $item['card_goods_id'] = $new_goods_id;
                        $goods_card_item[] = $item;
                    }
                    model('goods_card_item')->addList($goods_card_item);
                }

            }

            if ($sale_store != 'all') {
                $store_ids = explode(',', $sale_store);
                $store_ids = array_filter($store_ids);
            }

            // 商品下架状态，所有门店的商品都下架
            if (!empty($store_ids)) {
                foreach ($store_ids as $k => $v) {
                    (new StoreGoods())->modifyGoodsState($new_goods_id, $goods_info['goods_state'], $site_id, $v);
                }
            } else {
                (new StoreGoods())->modifyGoodsState($new_goods_id, $goods_info['goods_state'], $site_id);
            }

            model('goods')->commit();

        } catch (Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
        return $this->success($new_goods_id);
    }

    /**
     * 事件修改商品状态
     * @param $condition
     * @param $goods_state
     * @return array
     */
    public function cronModifyGoodsState($condition, $goods_state)
    {
        model('goods')->update(['goods_state' => $goods_state], $condition);
        model('goods_sku')->update(['goods_state' => $goods_state], $condition);

        if ($goods_state == 1) {
            $goods_list = model('goods')->getList($condition, 'goods_id,site_id');
            $goods_ids = array_column($goods_list, 'goods_id');
            //同步默认门店的上下架状态
            (new StoreGoods())->modifyGoodsState($goods_ids, $goods_state, $goods_list[0]['site_id']);
        }
        return $this->success(1);
    }

    /**
     * 修改排序
     * @param $sort
     * @param $goods_id
     * @param $site_id
     * @return array
     */
    public function modifyGoodsSort($sort, $goods_id, $site_id)
    {
        model('goods')->update(['sort' => $sort], [['goods_id', '=', $goods_id], ['site_id', '=', $site_id]]);
        model('goods_sku')->update(['sort' => $sort], [['goods_id', '=', $goods_id], ['site_id', '=', $site_id]]);
        return $this->success(1);
    }

    /**
     * 修改删除状态
     * @param $goods_ids
     * @param $is_delete
     * @param $site_id
     * @return array
     */
    public function modifyIsDelete($goods_ids, $is_delete, $site_id)
    {
        model('goods')->update(['is_delete' => $is_delete], [['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]]);
        model('goods_sku')->update(['is_delete' => $is_delete], [['goods_id', 'in', $goods_ids], ['site_id', '=', $site_id]]);

        //删除商品
        if ($is_delete == 1) {
            event('DeleteGoods', ['goods_id' => $goods_ids, 'site_id' => $site_id]);
        }
        return $this->success(1);
    }

    /**
     * 修改商品点击量
     * @param $sku_id
     * @param $site_id
     * @return array
     * @throws DbException
     */
    public function modifyClick($sku_id, $site_id)
    {
        model('goods_sku')->setInc([['sku_id', '=', $sku_id], ['site_id', '=', $site_id]], 'click_num', 1);
        return $this->success(1);
    }

    /**
     * 删除回收站商品
     * @param $goods_ids
     * @param $site_id
     * @return array
     */
    public function deleteRecycleGoods($goods_ids, $site_id)
    {
        if (!is_array($goods_ids)) {
            $goods_ids = explode(',', $goods_ids);
        }

        $check_res = event('DeleteGoodsCheck', ['ids' => $goods_ids, 'field' => 'goods_id']);
        $cannot_delete_goods_ids = [];
        $cannot_delete_reasons = [];
        foreach ($check_res as $val) {
            if (!empty($val['cannot_delete_ids'])) {
                $cannot_delete_goods_ids = array_merge($cannot_delete_goods_ids, $val['cannot_delete_ids']);
                $cannot_delete_reasons[] = $val['reason'];
            }
        }
        $cannot_delete_goods_ids = array_unique($cannot_delete_goods_ids);
        $cannot_delete_reasons = join('、', $cannot_delete_reasons);

        //全部不可删除
        if (count($cannot_delete_goods_ids) == count($goods_ids)) {
            return $this->error(null, "由于{$cannot_delete_reasons}不可删除");
        }

        $can_delete_goods_ids = array_diff($goods_ids, $cannot_delete_goods_ids);
        model('goods')->delete([['goods_id', 'in', $can_delete_goods_ids], ['site_id', '=', $site_id]]);
        model('goods_sku')->delete([['goods_id', 'in', $can_delete_goods_ids], ['site_id', '=', $site_id]]);

        $res = ['cannot_delete_reasons' => '', 'cannot_delete_goods_ids' => []];
        if (!empty($cannot_delete_goods_ids)) {
            $res = [
                'cannot_delete_reasons' => "部分商品由于{$cannot_delete_reasons}未删除",
                'cannot_delete_goods_ids' => $cannot_delete_goods_ids,
            ];
        }
        return $this->success($res);
    }

    /**
     * 获取商品信息
     * @param array $condition
     * @param string $field
     */
    public function getGoodsInfo($condition, $field = '*', $alias = 'a', $join = [])
    {
        $info = model('goods')->getInfo($condition, $field, $alias, $join);
        if (!empty($info)) {
            if (isset($info['goods_stock'])) {
                $info['goods_stock'] = numberFormat($info['goods_stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['virtual_sale'])) {
                $info['virtual_sale'] = numberFormat($info['virtual_sale']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }
        return $this->success($info);
    }

    /**
     * 获取商品信息
     * @param array $condition
     * @param string $field
     */
    public function editGetGoodsInfo($condition, $field = '*')
    {
        $info = model('goods')->getInfo($condition, $field);
        if (!empty($info)) {
            if (isset($info['category_json'])) {
                $category_json = json_decode($info['category_json']);
                $goods_category = [];
                $goods_category_data = [];
                foreach ($category_json as $k => $v) {
                    if (!empty($v)) {
                        $category_list = model('goods_category')->getList([['category_id', 'in', $v]], 'category_id,category_name,level', 'level asc');
                        $category_name = array_column($category_list, 'category_name');
                        $category_name = implode('/', $category_name);
                        $goods_category[$k] = [
                            'id' => $v,
                            'category_name' => $category_name
                        ];
                        $goods_category_data[] = $category_list;
                    }
                }
                //商家手机端使用
                $info['goods_category'] = $goods_category;
                //pc后台使用
                $info['goods_category_data'] = $goods_category_data;
            }

            if (isset($info['goods_stock'])) {
                $info['goods_stock'] = numberFormat($info['goods_stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['virtual_sale'])) {
                $info['virtual_sale'] = numberFormat($info['virtual_sale']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
            return $this->success($info);
        }
        return $this->error();
    }

    /**
     * 获取商品详情
     * @param $goods_id
     * @return array
     */
    public function getGoodsDetail($goods_id)
    {
        $info = model('goods')->getInfo([['goods_id', '=', $goods_id]], "*");
        if (!empty($info)) {
            if (isset($info['goods_stock'])) {
                $info['goods_stock'] = numberFormat($info['goods_stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['virtual_sale'])) {
                $info['virtual_sale'] = numberFormat($info['virtual_sale']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }

        $field = 'sku_id, sku_name,goods_name,spec_name, sku_no, sku_spec_format, price, market_price, cost_price, discount_price, stock,
                  weight, volume,  sku_image, sku_images, sort,member_price,fenxiao_price';
        $sku_data = model('goods_sku')->getList([['goods_id', '=', $goods_id]], $field);

        if (!empty($sku_data)) {
            foreach ($sku_data as $k => $v) {
                $sku_data[$k]['member_price'] = $v['member_price'] == '' ? '' : json_decode($v['member_price'], true);
                $sku_data[$k]['stock'] = numberFormat($sku_data[$k]['stock']);
            }
            $info['sku_data'] = $sku_data;
        }
        return $this->success($info);
    }

    /**
     * 商品sku 基础信息
     * @param $condition
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getGoodsSkuInfo($condition, $field = "sku_id,sku_name,sku_spec_format,price,market_price,discount_price,promotion_type,start_time,end_time,stock,click_num,sale_num,collect_num,sku_image,sku_images,goods_id,site_id,goods_content,goods_state,is_virtual,is_free_shipping,goods_spec_format,goods_attr_format,introduction,unit,video_url,sku_no,goods_name,goods_class,goods_class_name,cost_price", $alias = 'a', $join = null)
    {
        $info = model('goods_sku')->getInfo($condition, $field, $alias, $join);
        if (!empty($info)) {
            if (isset($info['stock'])) {
                $info['stock'] = numberFormat($info['stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['virtual_sale'])) {
                $info['virtual_sale'] = numberFormat($info['virtual_sale']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }
        return $this->success($info);
    }

    /**
     * 商品SKU 详情
     * @param $sku_id
     * @param $site_id
     * @param string $field
     * @return array
     */
    public function getGoodsSkuDetail($sku_id, $site_id, $field = '')
    {
        $condition = [['gs.sku_id', '=', $sku_id], ['gs.site_id', '=', $site_id], ['gs.is_delete', '=', 0]];

        if (empty($field)) {
            $field = 'gs.goods_id,gs.sku_id,gs.qr_id,gs.goods_name,gs.sku_name,gs.sku_spec_format,gs.price,gs.market_price,gs.discount_price,gs.promotion_type,gs.start_time
            ,gs.end_time,gs.stock,gs.click_num,(g.sale_num + g.virtual_sale) as sale_num,gs.collect_num,gs.sku_image,gs.sku_images
            ,gs.goods_content,gs.goods_state,gs.is_free_shipping,gs.goods_spec_format,gs.goods_attr_format,gs.introduction,gs.unit,gs.video_url
            ,gs.is_virtual,gs.goods_service_ids,gs.max_buy,gs.min_buy,gs.is_limit,gs.limit_type,gs.support_trade_type,g.goods_image,g.keywords,g.stock_show,g.sale_show,g.market_price_show,g.barrage_show,g.evaluate,g.goods_class';
        }
        $join = [
            ['goods g', 'g.goods_id = gs.goods_id', 'inner'],
        ];

        $info = model('goods_sku')->getInfo($condition, $field, 'gs', $join);
        if (!empty($info)) {
            if (isset($info['stock'])) {
                $info['stock'] = numberFormat($info['stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['virtual_sale'])) {
                $info['virtual_sale'] = numberFormat($info['virtual_sale']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }
        return $this->success($info);
    }

    /**
     * 获取商品SKU集合
     * @param $goods_id
     * @param $site_id
     * @param string $field
     * @return array
     */
    public function getGoodsSku($goods_id, $site_id, $field = '')
    {
        $condition = [
            ['gs.goods_id', '=', $goods_id],
            ['gs.site_id', '=', $site_id],
            ['gs.is_delete', '=', 0],
        ];

        if (empty($field)) {
            $field = 'gs.sku_id,g.goods_image,gs.sku_name,gs.sku_spec_format,gs.price,gs.discount_price,gs.promotion_type,gs.end_time,gs.stock,gs.sku_image,gs.sku_images,gs.goods_spec_format,gs.is_limit,gs.limit_type,gs.market_price,g.goods_state';
        }
        $join = [
            ['goods g', 'g.goods_id = gs.goods_id', 'inner'],
        ];

        $list = model('goods_sku')->getList($condition, $field, 'gs.sku_id asc', 'gs', $join);
        foreach ($list as $k => $v) {
            if (isset($v['goods_stock'])) {
                $list[$k]['goods_stock'] = numberFormat($list[$k]['goods_stock']);
            }
            if (isset($v['stock'])) {
                $list[$k]['stock'] = numberFormat($list[$k]['stock']);
            }
            if (isset($v['sale_num'])) {
                $list[$k]['sale_num'] = numberFormat($list[$k]['sale_num']);
            }
            if (isset($v['virtual_sale'])) {
                $list[$k]['virtual_sale'] = numberFormat($list[$k]['virtual_sale']);
            }

            if (isset($v['real_stock'])) {
                $list[$k]['real_stock'] = numberFormat($list[$k]['real_stock']);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取商品列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGoodsList($condition = [], $field = 'goods_id,goods_class,goods_class_name,goods_attr_name,goods_name,site_id,sort,goods_image,goods_content,goods_state,price,market_price,cost_price,goods_stock,goods_stock_alarm,is_virtual,is_free_shipping,shipping_template,goods_spec_format,goods_attr_format,create_time', $order = 'create_time desc', $limit = null, $alias = '', $join = [])
    {
        $list = model('goods')->getList($condition, $field, $order, $alias, $join, '', $limit);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (isset($v['goods_stock'])) {
                    $list[$k]['goods_stock'] = numberFormat($list[$k]['goods_stock']);
                }
                if (isset($v['sale_num'])) {
                    $list[$k]['sale_num'] = numberFormat($list[$k]['sale_num']);
                }
                if (isset($v['virtual_sale'])) {
                    $list[$k]['virtual_sale'] = numberFormat($list[$k]['virtual_sale']);
                }
                if (isset($v['real_stock'])) {
                    $list[$k]['real_stock'] = numberFormat($list[$k]['real_stock']);
                }
            }
        }
        return $this->success($list);
    }

    /**
     * 获取商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'a.create_time desc', $field = 'a.goods_id,a.goods_name,a.site_id,a.site_name,a.goods_image,a.goods_state,a.price,a.goods_stock,a.goods_stock_alarm,a.create_time,a.sale_num,a.is_virtual,a.goods_class,a.goods_class_name,a.is_fenxiao,a.fenxiao_type,a.promotion_addon,a.sku_id,a.is_consume_discount,a.discount_config,a.discount_method,a.sort,a.label_id,a.is_delete', $alias = 'a', $join = [], $group = null)
    {
        $res = model('goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        foreach ($res['list'] as $k => $v) {
            if (isset($v['goods_stock'])) {
                $res['list'][$k]['goods_stock'] = numberFormat($res['list'][$k]['goods_stock']);
            }
            if (isset($v['stock'])) {
                $res['list'][$k]['stock'] = numberFormat($res['list'][$k]['stock']);
            }
            if (isset($v['sale_num'])) {
                $res['list'][$k]['sale_num'] = numberFormat($res['list'][$k]['sale_num']);
            }
            if (isset($v['virtual_sale'])) {
                $res['list'][$k]['virtual_sale'] = numberFormat($res['list'][$k]['virtual_sale']);
            }
            if (isset($v['real_stock'])) {
                $res['list'][$k]['real_stock'] = numberFormat($res['list'][$k]['real_stock']);
            }
        }
        return $this->success($res);
    }

    /**
     * 编辑商品库存价格等信息
     * @param $goods_sku_array
     * @param $site_id
     * @return array
     */
    public function editGoodsStock($goods_sku_array, $site_id)
    {
        $goods_sku_model = new GoodsStock();
        $store_stock_model = new \app\model\stock\GoodsStock();
        model('goods')->startTrans();
        try {
            $sku_stock_list = [];
            $goods_class = '';
            $sku_id_array = array_column($goods_sku_array, 'sku_id');
            $sku_ids = implode(",", $sku_id_array);
            $sku_list = model('goods_sku')->getList([['site_id', '=', $site_id], ['sku_id', 'in', $sku_ids]], "sku_id, goods_id,stock, goods_class");
            $discount_model = new Discount();
            $discount_list = $discount_model->getDiscountGoodsListInGoodsEdit([['pdg.sku_id', 'in', $sku_ids], ['pd.status', '=', 1]], 'id');
            $sku_list = array_column($sku_list, null, 'sku_id');
            $discount_list = array_column($discount_list, null, 'sku_id');
            $goods_ids = [];
            foreach ($goods_sku_array as $k => $v) {
                $sku_info = $sku_list[$v['sku_id']];
                $sku_info['stock'] = numberFormat($sku_info['stock']);

                $goods_class = $sku_info['goods_class'];
                //验证当前规格是否参加的活动

                $discount_info = [];
                if (!empty($v['sku_id'])) {
                    $discount_info = $discount_list['data'][$v['sku_id']] ?? [];
                }
                if (!empty($discount_info)) {
                    $v['discount_price'] = $discount_info['price'];
                } else {
                    $v['discount_price'] = $v['price'];
                }

                if ($k == 0) {//修改商品中的价格等信息
                    $goods_data = [
                        'price' => $v['price'],
                        'market_price' => $v['market_price'],
                        'cost_price' => $v['cost_price']
                    ];
                    model('goods')->update($goods_data, [['goods_id', '=', $sku_info['goods_id']]]);
                }
                $stock = $v['stock'];
                unset($v['stock']);
                unset($v['is_delivery_restrictions']);
                model('goods_sku')->update($v, [['sku_id', '=', $v['sku_id']]]);
                if ($sku_info['goods_class'] != GoodsDict::virtualcard) {
                    $sku_stock_list[] = ['stock' => $stock, 'sku_id' => $v['sku_id'], 'is_delivery_restrictions' => $v['is_delivery_restrictions'] ?? 1, 'goods_class' => $goods_class];
                }
                $goods_ids[$sku_info['goods_id']] = $sku_info['goods_id'];
            }
            if (!empty($sku_stock_list)) {
                $res = $store_stock_model->changeGoodsStock([
                    'site_id' => $site_id,
                    'goods_sku_list' => $sku_stock_list,
                    'goods_class' => $goods_class
                ]);
                if ($res['code'] < 0) {
                    model('goods')->rollback();
                    return $res;
                }
            }
            //同步默认门店价格
            $store_goods_model = new StoreGoods();
            foreach ($goods_ids as $goods_id) {
                $store_goods_info = $store_goods_model->getStoreGoodsInfo([['goods_id', '=', $goods_id]])['data'];
                if (!empty($store_goods_info)) {
                    $store_goods_model->setSkuPrice([
                        'goods_id' => $goods_id,
                        'site_id' => $site_id,
                    ]);
                }
            }
            model('goods')->commit();
            return $this->success();
        } catch (Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品sku列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getGoodsSkuList($condition = [], $field = 'sku_id,sku_name,price,stock,sale_num,sku_image,goods_id,goods_name,site_id,spec_name', $order = 'price asc', $limit = null, $alias = '', $join = [])
    {
        $list = model('goods_sku')->getList($condition, $field, $order, $alias, $join, '', $limit);
        //获取属性映射数据
        if (isset($list[0]['sku_id']) && isset($list[0]['sku_spec_format'])) {
            $spec_name_data = [];
            foreach ($list as $sku_info) {
                $spec_name_data[$sku_info['sku_id']] = $this->getSpecNameBySkuSpecFormat($sku_info['sku_spec_format']);
            }
        }

        foreach ($list as $k => $v) {
            if (isset($v['stock'])) {
                $list[$k]['stock'] = numberFormat($list[$k]['stock']);
            }
            if (isset($v['sale_num'])) {
                $list[$k]['sale_num'] = numberFormat($list[$k]['sale_num']);
            }
            if (isset($v['virtual_sale'])) {
                $list[$k]['virtual_sale'] = numberFormat($list[$k]['virtual_sale']);
            }
            if (isset($v['real_stock'])) {
                $list[$k]['real_stock'] = numberFormat($list[$k]['real_stock']);
            }
            if (!empty($v['stock_transform_auto_sku'])) {
                $list[$k]['stock_transform_auto_sku_name'] = $spec_name_data[$v['stock_transform_auto_sku']] ?? '';
            }
            //is_delivery_restrictions   是门店数据  非关联需要重新
            if (isset($v['is_delivery_restrictions']) && empty($join) && isset($v['sku_id'])) {
                $store_model = new Store();
                $default_store_info = $store_model->getDefaultStore(1)['data'] ?? [];
                $store_id = $default_store_info['store_id'];
                $store_goods_sku_info = model('store_goods_sku')->getInfo([['sku_id', '=', $v['sku_id']], ['store_id', '=', $store_id]], 'is_delivery_restrictions');
                if (!empty($store_goods_sku_info)) $list[$k]['is_delivery_restrictions'] = $store_goods_sku_info['is_delivery_restrictions'];
            }

        }
        return $this->success($list);
    }

    /**
     * 获取商品sku分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param string $join
     * @param null $group
     * @return array
     */
    public function getGoodsSkuPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = '', $group = null)
    {
        $res = model('goods_sku')->Lists($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        foreach ($res['list'] as $k => $v) {
            if (isset($v['stock'])) {
                $res['list'][$k]['stock'] = numberFormat($res['list'][$k]['stock']);
            }
            if (isset($v['real_stock'])) {
                $res['list'][$k]['real_stock'] = numberFormat($res['list'][$k]['real_stock']);
            }
            if (isset($v['virtual_sale'])) {
                $res['list'][$k]['virtual_sale'] = numberFormat($res['list'][$k]['virtual_sale']);
            }
            if (isset($v['sale_num'])) {
                $res['list'][$k]['sale_num'] = numberFormat($res['list'][$k]['sale_num']);
            }
            if (isset($v['unit']) && empty($v['unit'])) {
                $res['list'][$k]['unit'] = '件';
            }
        }
        return $this->success($res);
    }

    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys 要排序的键字段
     * @param string $sort 排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    function arraySort($array, $keys, $sort = SORT_DESC)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * 商品推广二维码
     * @param $goods_id
     * @param $goods_name
     * @param $site_id
     * @param string $type
     * @return array
     */
    public function qrcode($goods_id, $goods_name, $site_id, $type = "create")
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                "goods_id" => $goods_id
            ],
            'page' => '/pages/goods/detail',
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => "goods_qrcode_" . $goods_id
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ($k) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[$k]['status'] = 1;
                    $path[$k]['url'] = $wap_domain . $data['page'] . '?goods_id=' . $goods_id;
                    $path[$k]['img'] = "upload/qrcode/goods/goods_qrcode_" . $goods_id . "_" . $k . ".png";
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WEAPP_CONFIG']]);
                    if (!empty($res['data'])) {
                        if (empty($res['data']['value']['qrcode'])) {
                            $path[$k]['status'] = 2;
                            $path[$k]['message'] = '未配置微信小程序';
                        } else {
                            $path[$k]['status'] = 1;
                            $path[$k]['img'] = $res['data']['value']['qrcode'];
                        }

                    } else {
                        $path[$k]['status'] = 2;
                        $path[$k]['message'] = '未配置微信小程序';
                    }
                    break;
                case 'wechat' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WECHAT_CONFIG']]);
                    if (!empty($res['data'])) {
                        if (empty($res['data']['value']['qrcode'])) {
                            $path[$k]['status'] = 2;
                            $path[$k]['message'] = '未配置微信公众号';
                        } else {
                            $path[$k]['status'] = 1;
                            $path[$k]['img'] = $res['data']['value']['qrcode'];
                        }
                    } else {
                        $path[$k]['status'] = 2;
                        $path[$k]['message'] = '未配置微信公众号';
                    }
                    break;
            }

        }

        $return = [
            'path' => $path,
            'goods_name' => $goods_name,
        ];

        return $this->success($return);
    }

    /**
     * 增加商品销量
     * @param $sku_id
     * @param $num
     * @param int $store_id
     * @return array
     * @throws DbException
     */
    public function incGoodsSaleNum($sku_id, $num, $store_id = 0)
    {
        $condition = array(
            ['sku_id', '=', $sku_id]
        );
        //增加sku销量
        $res = model('goods_sku')->setInc($condition, 'sale_num', $num);
        if ($res !== false) {
            $sku_info = model('goods_sku')->getInfo($condition, 'goods_id');
            $res = model('goods')->setInc([['goods_id', '=', $sku_info['goods_id']]], 'sale_num', $num);
            if ($store_id > 0) {
                $store_sale_model = new StoreSale();
                $store_sale_model->incStoreGoodsSaleNum(['sku_id' => $sku_id, 'num' => $num, 'store_id' => $store_id, 'goods_id' => $sku_info['goods_id']]);
            }
            return $this->success($res);
        }

        return $this->error($res);
    }

    /**
     * 减少商品销量
     * @param $sku_id
     * @param $num
     * @param int $store_id
     * @return array
     * @throws DbException
     */
    public function decGoodsSaleNum($sku_id, $num, $store_id = 0)
    {
        $condition = array(
            ['sku_id', '=', $sku_id]
        );
        //增加sku销量
        $res = model('goods_sku')->setDec($condition, 'sale_num', $num);
        if ($res !== false) {
            $sku_info = model('goods_sku')->getInfo($condition, 'goods_id');
            if (!empty($sku_info)) {
                $res = model('goods')->setDec([['goods_id', '=', $sku_info['goods_id']]], 'sale_num', $num);
                if ($store_id > 0) {
                    $store_sale_model = new StoreSale();
                    $store_sale_model->decStoreGoodsSaleNum(['sku_id' => $sku_id, 'num' => $num, 'store_id' => $store_id, 'goods_id' => $sku_info['goods_id']]);
                }
            }

            return $this->success($res);
        }
        return $this->error($res);
    }

    /**
     * 修改商品分组
     * @param $label_id
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsLabel($label_id, $site_id, $goods_ids)
    {
        //获取标签名称
        $label_info = model('goods_label')->getInfo([['id', '=', $label_id]], 'label_name');
        if (empty($label_info)) {
            return $this->error(null, '标签数据有误');
        }

        $result = model('goods')->update([
            'label_id' => $label_id,
            'label_name' => $label_info['label_name'],
        ], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids]
        ]);
        return $this->success($result);
    }

    /**
     * 修改商品表单
     * @param $form_id
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsForm($form_id, $site_id, $goods_ids)
    {
        $result = model('goods')->update([
            'form_id' => $form_id,
        ], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids]
        ]);
        $result = model('goods_sku')->update([
            'form_id' => $form_id,
        ], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids]
        ]);
        return $this->success($result);
    }

    /**
     * 修改商品分类Id
     * @param $category_id
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsCategoryId($category_id, $site_id, $goods_ids)
    {
        $category_json = json_encode($category_id);//分类字符串;
        $category_id = ',' . implode(',', $category_id) . ',';
        model('goods')->update(['category_id' => $category_id, 'category_json' => $category_json], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 修改商品推荐方式
     * @param $recommend_way
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsShopIntor($recommend_way, $site_id, $goods_ids)
    {

        model('goods')->update(['recommend_way' => $recommend_way], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        model('goods_sku')->update(['recommend_way' => $recommend_way], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 批量设置参与会员优惠
     * @param $is_consume_discount
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsConsumeDiscount($is_consume_discount, $site_id, $goods_ids)
    {
        model('goods')->update(['is_consume_discount' => $is_consume_discount], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        model('goods_sku')->update(['is_consume_discount' => $is_consume_discount], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 修改商品服务
     * @param $service_ids
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsService($service_ids, $site_id, $goods_ids)
    {
        model('goods')->update(['goods_service_ids' => $service_ids], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        model('goods_sku')->update(['goods_service_ids' => $service_ids], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 修改商品品牌
     * @param $brand_id
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsBrand($brand_id, $site_id, $goods_ids)
    {
        model('goods')->update(['brand_id' => $brand_id], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 修改商品虚拟销量
     * @param $sale
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsVirtualSale($sale, $site_id, $goods_ids)
    {
        model('goods')->update(['virtual_sale' => $sale], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        model('goods_sku')->update(['virtual_sale' => $sale], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 修改商品限购
     * @param $max_buy
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsPurchaseLimit($max_buy, $site_id, $goods_ids)
    {
        model('goods')->update(['max_buy' => $max_buy], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        model('goods_sku')->update(['max_buy' => $max_buy], [['site_id', '=', $site_id], ['goods_id', 'in', $goods_ids]]);
        return $this->success();
    }

    /**
     * 设置商品是否包邮
     * @param $is_free_shipping
     * @param $shipping_template
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsShippingTemplate($is_free_shipping, $shipping_template, $site_id, $goods_ids)
    {
        model('goods')->update(['is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids],
            ['goods_class', '=', GoodsDict::real]
        ]);
        model('goods_sku')->update(['is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids],
            ['goods_class', '=', GoodsDict::real]
        ]);
        return $this->success();
    }


    /**
     * 设置配送方式
     * @param $support_trade_type
     * @param $is_free_shipping
     * @param $shipping_template
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsDelivery($support_trade_type, $is_free_shipping, $shipping_template, $site_id, $goods_ids)
    {
        model('goods')->update(['support_trade_type' => $support_trade_type, 'is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids],
            ['goods_class', 'in', [GoodsDict::real, GoodsDict::weigh]]
        ]);
        model('goods_sku')->update(['support_trade_type' => $support_trade_type, 'is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template], [
            ['site_id', '=', $site_id],
            ['goods_id', 'in', $goods_ids],
            ['goods_class', 'in', [GoodsDict::real, GoodsDict::weigh]]
        ]);
        return $this->success();
    }

    /**
     * 获取商品总数
     * @param array $condition
     * @return array
     */
    public function getGoodsTotalCount($condition = [])
    {
        $res = model('goods')->getCount($condition);
        return $this->success($res);
    }

    /**
     * 获取商品会员价
     * @param $sku_id
     * @param $member_id
     * @param int $store_id
     * @return array
     */
    public function getGoodsPrice($sku_id, $member_id, $store_id = 0)
    {
        $res = [
            'discount_price' => 0, // 折扣价（默认等于单价）
            'member_price' => 0, // 会员价
            'price' => 0 // 最低价格
        ];
        $condition = [
            ['gs.sku_id', '=', $sku_id]
        ];

        $field = 'gs.is_consume_discount,gs.discount_config,gs.discount_method,gs.price,gs.member_price,gs.discount_price';
        $join = [
            ['goods g', 'g.goods_id = gs.goods_id', 'inner'],
        ];
        if ($store_id) {
            $join[] = ['store_goods_sku sgs', 'gs.sku_id = sgs.sku_id and sgs.store_id=' . $store_id, 'left'];
            $field = str_replace('gs.price', 'IFNULL(IF(g.is_unify_price = 1,gs.price,sgs.price), gs.price) as price', $field);
            $field = str_replace('gs.discount_price', 'IFNULL(IF(g.is_unify_price = 1,gs.discount_price,sgs.price), gs.discount_price) as discount_price', $field);
        }
        $goods_sku_info = model('goods_sku')->getInfo($condition, $field, 'gs', $join);

        if (empty($goods_sku_info)) return $this->success($res);

        $res['discount_price'] = $goods_sku_info['discount_price'];
        $res['price'] = $goods_sku_info['discount_price'];

        if (!addon_is_exit("memberprice")) return $this->success($res);

        if ($goods_sku_info['is_consume_discount']) {
            $alias = 'm';
            $join = [
                ['member_level ml', 'ml.level_id = m.member_level', 'inner'],
            ];
            $member_info = model("member")->getInfo([['member_id', '=', $member_id]], 'm.member_level,ml.consume_discount', $alias, $join);
            if (!empty($member_info)) {
                if ($goods_sku_info['discount_config'] == 1) {
                    // 自定义优惠
                    $goods_sku_info['member_price'] = json_decode($goods_sku_info['member_price'], true);
                    $value = $goods_sku_info['member_price'][$goods_sku_info['discount_method']][$member_info['member_level']] ?? 0;
                    switch ($goods_sku_info['discount_method']) {
                        case "discount":
                            // 打折
                            if ($value == 0) {
                                $res['member_price'] = $goods_sku_info['price'];
                            } else
                                $res['member_price'] = number_format($goods_sku_info['price'] * $value / 10, 2, '.', '');
                            break;
                        case "manjian":
                            if ($value == 0) {
                                $res['member_price'] = $goods_sku_info['price'];
                            } else
                                // 满减
                                $res['member_price'] = number_format($goods_sku_info['price'] - $value, 2, '.', '');
                            break;
                        case "fixed_price":
                            if ($value == 0) {
                                $res['member_price'] = $goods_sku_info['price'];
                            } else
                                // 指定价格
                                $res['member_price'] = number_format($value, 2, '.', '');
                            break;
                    }
                } else {
                    // 默认按会员享受折扣计算
                    $res['member_price'] = number_format($goods_sku_info['price'] * $member_info['consume_discount'] / 100, 2, '.', '');
                }
                if ($res['member_price'] < $res['price']) {
                    $res['price'] = $res['member_price'];
                }
            }

        }
        return $this->success($res);
    }

    /**
     * 获取商品会员价（列表）
     * @param array $goods_list
     * @param $member_id
     * @return array
     */
    public function getGoodsListMemberPrice(array $goods_list, $member_id)
    {
        $alias = 'm';
        $join = [
            ['member_level ml', 'ml.level_id = m.member_level', 'inner'],
        ];
        $member_info = model("member")->getInfo([['member_id', '=', $member_id]], 'm.member_level,ml.consume_discount', $alias, $join);
        if (empty($member_info)) return $goods_list;

        if (!addon_is_exit("memberprice")) return $goods_list;

        //预售数据
        $is_join_presale = event('IsJoinPresale', ['sku_ids' => array_column($goods_list, 'sku_id')], true);
        $presale_list = [];
        if (!empty($is_join_presale) && $is_join_presale['code'] == 0) {
            $presale_list = array_column($is_join_presale['data'], 'presale_id', 'sku_id');
        }

        foreach ($goods_list as $key => &$goods_item) {
            //预售不计算会员价
            if (isset($presale_list[$goods_item['sku_id']])) continue;
            if ($goods_item['is_consume_discount']) {
                // 自定义优惠
                if ($goods_item['discount_config'] == 1) {
                    $member_price_config = json_decode($goods_item['member_price'], true);
                    $value = $member_price_config[$goods_item['discount_method']][$member_info['member_level']] ?? 0;
                    switch ($goods_item['discount_method']) {
                        case "discount":
                            // 打折
                            if ($value == 0) {
                                $goods_item['member_price'] = $goods_item['price'];
                            } else
                                $goods_item['member_price'] = number_format($goods_item['price'] * $value / 10, 2, '.', '');
                            break;
                        case "manjian":
                            if ($value == 0) {
                                $goods_item['member_price'] = $goods_item['price'];
                            } else
                                // 满减
                                $goods_item['member_price'] = number_format($goods_item['price'] - $value, 2, '.', '');
                            break;
                        case "fixed_price":
                            if ($value == 0) {
                                $goods_item['member_price'] = $goods_item['price'];
                            } else
                                // 指定价格
                                $goods_item['member_price'] = number_format($value, 2, '.', '');
                            break;
                    }
                } else {
                    // 默认按会员享受折扣计算
                    $goods_item['member_price'] = number_format($goods_item['price'] * $member_info['consume_discount'] / 100, 2, '.', '');
                }
            } else {
                unset($goods_list[$key]['member_price']);
            }
        }
        return $goods_list;
    }

    /**
     * 获取会员卡商品价格
     * @param $sku_id
     * @param $level_id
     * @return array
     */
    public function getMemberCardGoodsPrice($sku_id, $level_id)
    {
        $res = [
            'discount_price' => 0, // 折扣价（默认等于单价）
            'member_price' => 0, // 会员价
            'price' => 0 // 最低价格
        ];

        $goods_sku_info = model('goods_sku')->getInfo([['sku_id', '=', $sku_id]], 'is_consume_discount,discount_config,discount_method,price,member_price,discount_price');
        if (empty($goods_sku_info)) return $this->success($res);

        $res['discount_price'] = $goods_sku_info['discount_price'];
        $res['price'] = $goods_sku_info['discount_price'];

        $level_info = model('member_level')->getInfo([['level_id', '=', $level_id]], 'consume_discount');

        if (!addon_is_exit('memberprice') || empty($level_info)) return $this->success($res);
        if ($goods_sku_info['is_consume_discount']) {
            if ($goods_sku_info['discount_config'] == 1) {
                // 自定义优惠
                $goods_sku_info['member_price'] = json_decode($goods_sku_info['member_price'], true);
                $value = $goods_sku_info['member_price'][$goods_sku_info['discount_method']][$level_id] ?? 0;

                switch ($goods_sku_info['discount_method']) {
                    case 'discount':
                        // 打折
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            $res['member_price'] = number_format($goods_sku_info['price'] * $value / 10, 2, '.', '');
                        }
                        break;
                    case 'manjian':
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            // 满减
                            $res['member_price'] = number_format($goods_sku_info['price'] - $value, 2, '.', '');
                        }
                        break;
                    case 'fixed_price':
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            // 指定价格
                            $res['member_price'] = number_format($value, 2, '.', '');
                        }
                        break;
                }
            } else {
                // 默认按会员享受折扣计算
                $res['member_price'] = number_format($goods_sku_info['price'] * $level_info['consume_discount'] / 100, 2, '.', '');
            }
            if ($res['member_price'] < $res['price']) {
                $res['price'] = $res['member_price'];
            }
        }
        return $this->success($res);
    }

    public function getSkuMemberPrice($sku_list, $site_id)
    {
        $member_level_list = model('member_level')->getList([['site_id', '=', $site_id]], 'level_name,level_id,consume_discount', 'level_type asc,growth asc');
        foreach ($sku_list as $k => $sku_item) {
            $member_level = [];
            if ($sku_item['is_consume_discount']) {
                foreach ($member_level_list as $level_item) {
                    // 自定义优惠
                    if ($sku_item['discount_config'] == 1) {
                        $member_price = json_decode($sku_item['member_price'], true);
                        $value = $member_price[$sku_item['discount_method']][$level_item['level_id']] ?? 0;
                        switch ($sku_item['discount_method']) {
                            case "discount":
                                // 打折
                                if ($value == 0) {
                                    $level_item['member_price'] = $sku_item['price'];
                                } else
                                    $level_item['member_price'] = number_format($sku_item['price'] * $value / 10, 2, '.', '');
                                break;
                            case "manjian":
                                if ($value == 0) {
                                    $level_item['member_price'] = $sku_item['price'];
                                } else
                                    // 满减
                                    $level_item['member_price'] = number_format($sku_item['price'] - $value, 2, '.', '');
                                break;
                            case "fixed_price":
                                if ($value == 0) {
                                    $level_item['member_price'] = $sku_item['price'];
                                } else
                                    // 指定价格
                                    $level_item['member_price'] = number_format($value, 2, '.', '');
                                break;
                        }
                    } else {
                        $level_item['member_price'] = number_format($sku_item['price'] * $level_item['consume_discount'] / 100, 2, '.', '');
                    }
                    $member_level[] = $level_item;
                }
            }
            $sku_list[$k]['member_price_list'] = $member_level;
        }
        return $sku_list;
    }

    /**
     * 修改当前商品参与的营销活动标识，逗号分隔（限时折扣、团购、拼团、秒杀、专题活动）
     * @param $goods_id
     * @param array $promotion 营销活动标识，【promotion:value】
     * @param bool $is_delete 是否删除
     * @return array
     */
    public function modifyPromotionAddon($goods_id, $promotion = [], $is_delete = false)
    {
        $goods_info = model('goods')->getInfo([['goods_id', '=', $goods_id]], 'promotion_addon');
        $promotion_addon = [];
        if (!empty($goods_info['promotion_addon'])) {
            $promotion_addon = json_decode($goods_info['promotion_addon'], true);
        }
        $promotion_addon = array_merge($promotion_addon, $promotion);
        if ($is_delete) {
            foreach ($promotion as $k => $v) {
                unset($promotion_addon[$k]);
            }
        }
        if (!empty($promotion_addon)) {
            $promotion_addon = json_encode($promotion_addon);
        } else {
            $promotion_addon = '';
        }
        $res = model('goods')->update(['promotion_addon' => $promotion_addon], [['goods_id', '=', $goods_id]]);
        return $this->success($res);
    }

    /**
     * 获取会员已购该商品数
     * @param $goods_id
     * @param $member_id
     * @return float
     */
    public function getGoodsPurchasedNum($goods_id, $member_id)
    {
        $join = [
            ['order o', 'o.order_id = og.order_id', 'left']
        ];
        return model('order_goods')->getSum([
            ['og.member_id', '=', $member_id],
            ['og.goods_id', '=', $goods_id],
            ['o.order_status', '<>', Order::ORDER_CLOSE],
            ['og.refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]
        ], 'og.num', 'og', $join);
    }

    /**
     * 判断规格值是否禁用
     * @param $sku_ids
     * @param $goods_spec_format
     * @return mixed
     */
    public function getGoodsSpecFormat($sku_ids, $goods_spec_format)
    {
        if (!empty($goods_spec_format) && !empty($sku_ids)) {

            $sku_spec_format = model('goods_sku')->getColumn([['sku_id', 'in', $sku_ids]], 'sku_spec_format');

            $sku_spec_format_arr = [];
            foreach ($sku_spec_format as $sku_spec) {
                $format = json_decode($sku_spec, true);
                if (is_array($format)) {
                    foreach ($format as $format_v) {
                        if (empty($sku_spec_format_arr[$format_v['spec_id']])) {
                            $sku_spec_format_arr[$format_v['spec_id']] = [];
                        }
                        $sku_spec_format_arr[$format_v['spec_id']][] = $format_v['spec_value_id'];
                    }
                }
            }

            $goods_spec_format = json_decode($goods_spec_format, true);
            $count = count($goods_spec_format);
            foreach ($goods_spec_format as $k => $v) {
                foreach ($v['value'] as $key => $item) {
                    if (!in_array($item['spec_value_id'], $sku_spec_format_arr[$item['spec_id']])) {
                        $v['value'][$key]['disabled'] = true;
                    }
                }
                if ($k > 0 || $count == 1) {
                    foreach ($v['value'] as $key => $item) {
                        if (!in_array($item['sku_id'] ?? '', $sku_ids)) {
                            $v['value'][$key]['disabled'] = true;
                        }
                    }
                }
                $goods_spec_format[$k]['value'] = $v['value'];
            }
            return $goods_spec_format;
        }
    }

    /**
     * 库存预警数量
     * @param $site_id
     * @return array
     */
    public function getGoodsStockAlarm($site_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $sql = 'select goods_id from ' . $prefix . 'goods_sku where stock_alarm >= stock and stock_alarm > 0 and is_delete = 0 and goods_state = 1 and site_id = ' . $site_id . ' group by goods_id';
        $data = model('goods')->query($sql);
        if (!empty($data)) {
            $data = array_column($data, 'goods_id');
        }
        return $this->success($data);
    }

    /**
     * 商品导入
     * @param $goods_data
     * @param $site_id
     * @return array
     */
    public function importGoods($goods_data, $site_id)
    {

        try {
            if (empty($goods_data['goods_name'])) return $this->error('', '商品名称不能为空');
            if (empty($goods_data['goods_image'])) return $this->error('', '商品主图不能为空');
            if (empty($goods_data['category_1']) && empty($goods_data['category_2']) && empty($goods_data['category_3'])) return $this->error('', '商品分类不能为空');

            // 处理商品分类
            $category_id = '';
            $category_json = [];
            if (!empty($goods_data['category_3'])) {
                $category_info = model('goods_category')->getInfo([['level', '=', 3], ['site_id', '=', $site_id], ['category_full_name', '=', "{$goods_data['category_1']}/{$goods_data['category_2']}/{$goods_data['category_3']}"]], 'category_id_1,category_id_2,category_id_3');
                if (!empty($category_info)) {
                    $category_id = "{$category_info['category_id_1']},{$category_info['category_id_2']},{$category_info['category_id_3']}";
                }
            }
            if (!empty($goods_data['category_2']) && empty($category_id)) {
                $category_info = model('goods_category')->getInfo([['level', '=', 2], ['site_id', '=', $site_id], ['category_full_name', '=', "{$goods_data['category_1']}/{$goods_data['category_2']}"]], 'category_id_1,category_id_2');
                if (!empty($category_info)) {
                    $category_id = "{$category_info['category_id_1']},{$category_info['category_id_2']}";
                }
            }
            if (!empty($goods_data['category_1']) && empty($category_id)) {
                $category_info = model('goods_category')->getInfo([['level', '=', 1], ['site_id', '=', $site_id], ['category_name', '=', "{$goods_data['category_1']}"]], 'category_id_1');
                if (!empty($category_info)) {
                    $category_id = "{$category_info['category_id_1']}";
                }
            }
            if (empty($category_id)) return $this->error('', '未找到所填商品分类');
            $category_json = [$category_id];

            $sku_data = [];
            $goods_spec_format = [];
            $tag = 0;
            // 处理sku数据
            if (isset($goods_data['sku'])) {
                foreach ($goods_data['sku'] as $sku_item) {
                    if (empty($sku_item['sku_data'])) return $this->error('', '规格数据不能为空');

                    $spec_name = '';
                    $spec_data = explode(';', $sku_item['sku_data']);

                    $sku_spec_format = [];
                    foreach ($spec_data as $item) {
                        $spec_item = explode(':', $item);
                        $spec_name .= ' ' . $spec_item[1];

                        // 规格项
                        $spec_index = array_search($spec_item[0], array_column($goods_spec_format, 'spec_name'));
                        if (empty($goods_spec_format) || $spec_index === false) {
                            $spec = [
                                'spec_id' => -($tag + getMillisecond()),
                                'spec_name' => $spec_item[0],
                                'value' => []
                            ];
                            $goods_spec_format[] = $spec;
                            $tag++;
                        } else {
                            $spec = $goods_spec_format[$spec_index];
                        }
                        // 规格值
                        $spec_index = array_search($spec_item[0], array_column($goods_spec_format, 'spec_name'));
                        $spec_value_index = array_search($spec_item[1], array_column($spec['value'], 'spec_value_name'));
                        if (empty($spec['value']) || $spec_value_index === false) {
                            $spec_value = [
                                'spec_id' => $spec['spec_id'],
                                'spec_name' => $spec['spec_name'],
                                'spec_value_id' => -($tag + getMillisecond()),
                                'spec_value_name' => $spec_item[1],
                                'image' => '',
                            ];
                            $goods_spec_format[$spec_index]['value'][] = $spec_value;
                            $tag++;
                        } else {
                            $spec_value = $spec['value'][$spec_value_index];
                        }

                        $sku_spec_format[] = [
                            'spec_id' => $spec['spec_id'],
                            'spec_name' => $spec['spec_name'],
                            'spec_value_id' => $spec_value['spec_value_id'],
                            'spec_value_name' => $spec_value['spec_value_name'],
                            'image' => '',
                        ];
                    }

                    $sku_images_arr = explode(',', $sku_item['sku_image']);

                    $sku_temp = [
                        'spec_name' => trim($spec_name),
                        'sku_no' => $sku_item['sku_code'],
                        'sku_spec_format' => $sku_spec_format,
                        'price' => $sku_item['price'],
                        'market_price' => $sku_item['market_price'],
                        'cost_price' => $sku_item['cost_price'],
                        'stock' => $sku_item['stock'],
                        'stock_alarm' => $sku_item['stock_alarm'],
                        'weight' => $sku_item['weight'],
                        'volume' => $sku_item['volume'],
                        'sku_image' => empty($sku_item['sku_image']) ? '' : $sku_images_arr[0],
                        'sku_images' => empty($sku_item['sku_image']) ? '' : $sku_item['sku_image'],
                        'sku_images_arr' => empty($sku_item['sku_image']) ? [] : $sku_images_arr,
                        'is_default' => 0
                    ];

                    $sku_data[] = $sku_temp;
                }
            } else {
                $goods_img = explode(',', $goods_data['goods_image']);
                $sku_data = [
                    [
                        'sku_id' => 0,
                        'sku_name' => $goods_data['goods_name'],
                        'spec_name' => '',
                        'sku_spec_format' => '',
                        'price' => empty($goods_data['price']) ? 0 : $goods_data['price'],
                        'market_price' => empty($goods_data['market_price']) ? 0 : $goods_data['market_price'],
                        'cost_price' => empty($goods_data['cost_price']) ? 0 : $goods_data['cost_price'],
                        'sku_no' => $goods_data['goods_code'],
                        'weight' => empty($goods_data['weight']) ? 0 : $goods_data['weight'],
                        'volume' => empty($goods_data['volume']) ? 0 : $goods_data['volume'],
                        'stock' => empty($goods_data['stock']) ? 0 : $goods_data['stock'],
                        'stock_alarm' => empty($goods_data['stock_alarm']) ? 0 : $goods_data['stock_alarm'],
                        'sku_image' => $goods_img[0],
                        'sku_images' => $goods_data['goods_image']
                    ]
                ];
            }

            if (count($goods_spec_format) > 4) return $this->error('', '最多支持四种规格项');

            $shipping_template = 0;
            $is_free_shipping = $goods_data['is_free_shipping'] == 1 || $goods_data['is_free_shipping'] == '是' ? 1 : 0;// 是否免邮
            if ($is_free_shipping == 0 && $goods_data['template_name'] == "") return $this->error('', '运费模板不能为空');

            if ($is_free_shipping == 0 && $goods_data['template_name']) {
                $shipping = model("express_template")->getInfo([['template_name', '=', $goods_data['template_name']]]);
                if (empty($shipping)) {
                    return $this->error('', '未找到该运费模板');
                }
                $shipping_template = $shipping['template_id'];
            }

            $data = [
                'goods_name' => $goods_data['goods_name'],// 商品名称,
                'goods_attr_class' => '',// 商品类型id,
                'goods_attr_name' => '',// 商品类型名称,
                'site_id' => $site_id,
                'category_id' => ',' . $category_id . ',',
                'category_json' => json_encode($category_json),
                'goods_image' => $goods_data['goods_image'],// 商品主图路径
                'goods_content' => '',// 商品详情
                'goods_state' => 0, //$goods_data['goods_state'] == 1 || $goods_data['goods_state'] == '是' ? 1 : 0,// 商品状态（1.正常0下架）
                'price' => empty($goods_data['price']) ? 0 : $goods_data['price'],// 商品价格（取第一个sku）
                'market_price' => empty($goods_data['market_price']) ? 0 : $goods_data['market_price'],// 市场价格（取第一个sku）
                'qr_id' => empty($goods_data['qr_id']) ? 0 : $goods_data['qr_id'],// 社群二维码id
                'template_id' => empty($goods_data['template_id']) ? 0 : $goods_data['template_id'],// 海报id
                'is_limit' => empty($goods_data['is_limit']) ? 0 : $goods_data['is_limit'],// 是否限购
                'limit_type' => empty($goods_data['limit_type']) ? 0 : $goods_data['limit_type'],// 限购类型
                'cost_price' => empty($goods_data['cost_price']) ? 0 : $goods_data['cost_price'],// 成本价（取第一个sku）
                'sku_no' => $goods_data['goods_code'],// 商品sku编码
                'weight' => empty($goods_data['weight']) ? 0 : $goods_data['weight'],// 重量
                'volume' => empty($goods_data['volume']) ? 0 : $goods_data['volume'],// 体积
                'goods_stock' => empty($goods_data['goods_stock']) ? 0 : $goods_data['goods_stock'],// 商品库存（总和）
                'goods_stock_alarm' => empty($goods_data['goods_stock_alarm']) ? 0 : $goods_data['goods_stock_alarm'],// 库存预警
                'is_free_shipping' => $is_free_shipping,
                'shipping_template' => $shipping_template,// 指定运费模板
                'goods_spec_format' => empty($goods_spec_format) ? '' : json_encode($goods_spec_format, JSON_UNESCAPED_UNICODE),// 商品规格格式
                'goods_attr_format' => '',// 商品参数格式
                'introduction' => $goods_data['introduction'],// 促销语
                'keywords' => $goods_data['keywords'],// 关键词
                'unit' => $goods_data['unit'],// 单位
                'sort' => '',// 排序,
                'video_url' => '',// 视频
                'goods_sku_data' => json_encode($sku_data, JSON_UNESCAPED_UNICODE),// SKU商品数据
                'goods_service_ids' => '',// 商品服务id集合
                'label_id' => '',// 商品分组id
                'virtual_sale' => 0,// 虚拟销量
                'max_buy' => 0,// 限购
                'min_buy' => 0,// 起售
                'recommend_way' => 0, // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => 0,//定时上架
                'timer_off' => 0,//定时下架
                'brand_id' => 0,
                'is_consume_discount' => $goods_data['is_consume_discount'] == 1 || $goods_data['is_consume_discount'] == '是' ? 1 : 0, //是否参与会员折扣
                'support_trade_type' => 'express,store,local'
            ];
            return $this->addGoods($data);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 商品用到的分类
     * @param $condition
     * @return array
     */
    public function getGoodsCategoryIds($condition)
    {
        $cache_name = "shop_goods_category_" . md5(json_encode($condition));
        $cache_time = 60;
        $cache_res = Cache::get($cache_name);
        if (empty($cache_res) || time() - $cache_res['time'] > $cache_time) {
            $list = Db::name('goods')
                ->where($condition)
                ->group('category_id')
                ->column('category_id');
            $category_ids = trim(join('0', $list), ',');
            $category_id_arr = array_unique(explode(',', $category_ids));
            Cache::set($cache_name, ['time' => time(), 'data' => $category_id_arr]);
        } else {
            $category_id_arr = $cache_res['data'];
        }
        return $this->success($category_id_arr);
    }

    public function urlQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => $promotion_type,
            'app_type' => $app_type,
            'h5_path' => $page . '?goods_id=' . $qrcode_param['goods_id'],
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => 'goods_qrcode_' . $promotion_type . $qrcode_param['goods_id'] . '_' . $site_id
        ];

        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }

    /**
     * 批量修改商品库存
     * @param $goods_sku_array
     * @param $stock
     * @param $type
     * @param int $site_id
     * @return array|int
     */
    public function editGoodsSkuStock($goods_sku_array, $stock, $type, $site_id = 0)
    {
        model('goods')->startTrans();
        try {
            $stock_list = [];
            foreach ($goods_sku_array as $k => $v) {

                if ($type == 'inc') {
                    $item_stock = $v['stock'] + $stock;
                } else {
                    $item_stock = $v['stock'] - $stock;
                    $item_stock = max($item_stock, 0);
                }
                $stock_list[] = ['sku_id' => $v['sku_id'], 'stock' => $item_stock, 'goods_class' => $v['goods_class']];
            }

            $goods_stock_model = new \app\model\stock\GoodsStock();
            $result = $goods_stock_model->changeGoodsStock([
                'site_id' => $site_id,
                'goods_sku_list' => $stock_list
            ]);
            if ($result['code'] < 0) {
                model('goods')->rollback();
                return $result;
            }
            model('goods')->commit();
            return $this->success();
        } catch (Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 获取商品图片大小
     */
    public function getGoodsImage($goods_images, $site_id)
    {
        $list = model('album_pic')->getList([['pic_path', 'in', $goods_images], ['site_id', '=', $site_id]], 'pic_path,pic_spec');
        return $this->success($list);
    }

    /**
     * 验证商品编码是否重复
     * @param $params
     * @return array
     */
    public function verifySkuNo($params)
    {
        if (empty($params['sku_no'])) {
            return $this->error(0, "缺少参数sku_no");
        }

        $config_model = new \app\model\web\Config();
        $info = $config_model->getGoodsNo($params['site_id'])['data']['value'];
        if ($info['uniqueness_switch'] == 0) {
            return $this->success(0);
        }

        //编码可以是多个，逗号分割，例如119,120,121
        $sku_no_arr = explode(',', $params['sku_no']);
        $sql_arr = [];
        foreach ($sku_no_arr as $sku_no) {
            $sql_arr[] = "FIND_IN_SET('{$sku_no}', sku_no)";
        }
        $condition = [
            ['site_id', '=', $params['site_id']],
            ['', 'exp', Db::raw(join(' or ', $sql_arr))],
            ['is_delete', '=', 0]
        ];

        if (!empty($params['goods_id'])) {
            $condition[] = ['goods_id', '<>', $params['goods_id']];
        }

        $info = model('goods_sku')->getInfo($condition, 'sku_no');
        if (!empty($info)) {
            $exist_sku_no_arr = array_intersect($sku_no_arr, explode(',', $info['sku_no']));
            $exist_sku_no_arr = array_values($exist_sku_no_arr);
            return $this->error(1, "条码[{$exist_sku_no_arr[0]}]已存在");
        }
        return $this->success(0);
    }

    /**
     * 获取库存转换数据
     * @param $goods_sku_list
     * @param $store_id
     * @param $store_business
     * @return mixed
     */
    public function goodsStockTransform($goods_sku_list, $store_id, $store_business)
    {
        try {
            if (addon_is_exit('stock')) {
                $transform_model = new \addon\stock\model\stock\Transform();
                $transform_stock_data = $transform_model->getGoodsStockTransformData([
                    'sku_ids' => array_column($goods_sku_list, 'sku_id'),
                    'store_id' => $store_id,
                    'store_business' => $store_business,
                ]);
                foreach ($goods_sku_list as &$sku_info) {
                    if (isset($transform_stock_data[$sku_info['sku_id']]['transform_stock'])) {
                        $sku_info['stock'] = $transform_stock_data[$sku_info['sku_id']]['transform_stock'];
                    }
                    //商品库存处理，前端是否显示售罄根据goods_stock判断
                    if (isset($sku_info['goods_stock']) && $sku_info['goods_stock'] == 0) {
                        $sku_info['goods_stock'] = $sku_info['stock'];
                    }
                }
            }
        } catch (\Exception $e) {
            \think\facade\Log::write('库存转换捕获错误');
            \think\facade\Log::write(exceptionData($e));
        }
        return $goods_sku_list;
    }


    /**
     * 补充组合套餐信息
     * @return array
     */
    public function bundlingGoods(array $list,$site_id=1): array
    {
        if(empty($list)){
            return $list;
        }
        $sku_id_arr = array_column($list, 'sku_id');
        $bundling_model = new Bundling();
        $condition = [
            ['pdg.sku_id', 'in', $sku_id_arr],
            ['pb.status', '=', 1],
            ['pb.site_id', '=', $site_id]
        ];
        $alias = 'pb';
        $join = [
            ['promotion_bundling_goods pdg', 'pdg.bl_id = pb.bl_id','left'],
        ];
        $field = 'pb.*,pdg.sku_id';
        $result = $bundling_model->getBundlingGoodsList($condition,$field,'pb.bl_id asc',$alias,$join);
        if(empty($result['data'])){
            return $list;
        }
        foreach ($list as $key=>$item){
            foreach ($result['data'] as $v){
                if($item['sku_id'] == $v['sku_id']){
                    $list[$key]['bundling_list'][] = $v;
                }
            }
        }
        return $list;
    }
}