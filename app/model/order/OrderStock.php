<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use addon\stock\model\stock\Stock as StockAddonModel;
use app\dict\goods\GoodsDict;
use app\model\BaseModel;
use app\model\stock\GoodsStock;
use app\model\stock\SaleStock;
use app\model\store\Store;
use app\model\storegoods\StoreGoods;
use extend\exception\StockException;
use think\facade\Cache;

/**
 * 商品库存
 */
class OrderStock extends BaseModel
{

    /**
     * 扣除订单库存
     * @param $sku_id
     * @param $num
     * @param int $store_id
     * @param array $sku_info
     * @param array $create_data
     * @return array
     */
    public function decOrderSaleStock($params)
    {
        $store_id = $params['store_id'] ?? 0;
        $goods_sku_data = $params['goods_sku_data'] ?? [];
        $sale_stock_model = new SaleStock();
        $create_order_data = $params['create_order_data'];
        if ($store_id > 0) {
            $store_info = $create_order_data['store_info'] ?? [];
            if (empty($store_info)) {
                $store_model = new Store();
                $store_condition = [
                    ['store_id', '=', $store_id]
                ];
                $store_info = $store_model->getStoreInfo($store_condition)['data'] ?? [];
            }
            $stock_type = $store_info['stock_type'];
            if ($stock_type == 'all') {//如果总部统一库存的话就扣除总店的
                $store_id = 0;
            }
        }
        $params = [
            'goods_sku_list' => $goods_sku_data,
            'store_id' => $store_id,
            'is_allow_negative' => false
        ];
        $sale_stock_result = $sale_stock_model->decGoodsStock($params);
        if ($sale_stock_result['code'] < 0) {
            return $sale_stock_result;
        }
        return $this->success();

    }

    /**
     * 返还订单库存
     * @param $sku_id
     * @param $num
     * @param int $store_id
     * @return array
     */
    public function incOrderSaleStock($params)
    {
        $store_id = $params['store_id'] ?? 0;
        $goods_sku_data = $params['goods_sku_data'] ?? [];
        $sale_stock_model = new SaleStock();
        if ($store_id > 0) {
            $store_model = new Store();
            $store_condition = [
                ['store_id', '=', $store_id]
            ];
            $store_info = $store_model->getStoreInfo($store_condition)['data'] ?? [];
            $stock_type = $store_info['stock_type'];
            if ($stock_type == 'all') {//如果总部统一库存的话就扣除总店的
                $store_id = 0;
            }
        }
        $params = [
            'goods_sku_list' => $goods_sku_data,
            'store_id' => $store_id
        ];
        $sale_stock_result = $sale_stock_model->incGoodsStock($params);
        if ($sale_stock_result['code'] < 0) {
            return $sale_stock_result;
        }
        return $this->success();

    }



    /**
     * 扣除库存(用于订单)
     * @param $params
     * @return array
     */
    public function decOrderStock($params)
    {
        $params['is_out_stock'] = $params['is_out_stock'] ?? 0;//不再改变销售库存
        $store_id = $params['store_id'] ?? 0;
        //是否允许负库存
        $is_allow_negative = $params['is_allow_negative'] ?? false;
        if ($store_id > 0) {
            $store_model = new Store();
            $store_condition = [
                ['store_id', '=', $store_id]
            ];
            $store_info = $store_model->getStoreInfo($store_condition)['data'] ?? [];
            $stock_type = $store_info['stock_type'];
            if ($stock_type == 'all') {//如果总部统一库存的话就扣除总店的
                $params['store_id'] = 0;
            }
        }
        $is_exist = addon_is_exit('stock');
        if ($is_exist) {
            $stock_model = new StockAddonModel();
        }
        $goods_sku_list = $params['goods_sku_list'] ?? [];
        if (!empty($goods_sku_list)) {
            $goods_sku_list_1 = [];
            $goods_sku_list_2 = [];

            foreach ($goods_sku_list as $k => $v) {
                if ($is_exist && $v['goods_class'] == GoodsDict::real) {
                    $goods_sku_list_1[] = $v;
                } else {
                    if (in_array($v['goods_class'], [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                        $goods_sku_list_2[] = $v;
                    }
                }
            }
            if (!empty($goods_sku_list_1)) {
                $params['goods_sku_list'] = $goods_sku_list_1;
                $params['key'] = 'SEAILCK';
                $result = $stock_model->changeStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
            if (!empty($goods_sku_list_2)) {
                $params['goods_sku_list'] = $goods_sku_list_2;
                $goods_stock_model = new GoodsStock();
                $result = $goods_stock_model->decGoodsStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
        } else {
            $goods_class = $params['goods_class'];
            $params['stock'] = $params['num'] ?? $params['stock'];
            if ($is_exist && $goods_class == GoodsDict::real) {
                $params['key'] = 'SEAILCK';
                $result = $stock_model->changeStock($params);
            } else {//没有的话直接生成支付单据
                $goods_stock_model = new GoodsStock();
                $result = $goods_stock_model->decGoodsStock($params);
            }
        }

        return $result ?? $this->success();

    }

    /**
     * 返还库存(用于订单)
     * @param $params
     * @return array
     */
    public function incOrderStock($params)
    {
        $store_id = $params['store_id'] ?? 0;
        if ($store_id > 0) {
            $store_model = new Store();
            $store_condition = [
                ['store_id', '=', $store_id]
            ];
            $store_info = $store_model->getStoreInfo($store_condition)['data'] ?? [];
            $stock_type = $store_info['stock_type'];
            if ($stock_type == 'all') {//如果总部统一库存的话就返还总店的
                $params['store_id'] = 0;
            }
        }
        $is_exist = addon_is_exit('stock');
        if ($is_exist) {
            $stock_model = new StockAddonModel();
        }
        $goods_sku_list = $params['goods_sku_list'] ?? [];
        if (!empty($goods_sku_list)) {
            $goods_sku_list_1 = [];
            $goods_sku_list_2 = [];

            foreach ($goods_sku_list as $v) {
                if ($is_exist && $v['goods_class'] == GoodsDict::real) {
                    $goods_sku_list_1[] = $v;
                } else {
                    if (in_array($v['goods_class'], [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                        $goods_sku_list_2[] = $v;
                    }
                }
            }
            if (!empty($goods_sku_list_1)) {

                $params['goods_sku_list'] = $goods_sku_list_1;
                $params['key'] = 'REFUND';
                $result = $stock_model->changeStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
            if (!empty($goods_sku_list_2)) {
                $params['goods_sku_list'] = $goods_sku_list_2;
                $goods_stock_model = new GoodsStock();
                $result = $goods_stock_model->incGoodsStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
        } else {
            $goods_class = $params['goods_class'];
            $params['stock'] = $params['num'] ?? $params['stock'];
            if ($is_exist && $goods_class == GoodsDict::real) {
                $params['key'] = 'REFUND';
                $result = $stock_model->changeStock($params);
            } else {//没有的话直接生成支付单据
                $goods_stock_model = new GoodsStock();
                $result = $goods_stock_model->incGoodsStock($params);
            }
        }
        return $result ?? $this->success();

    }


    /**
     * 校验商品库存是否足够
     * @return void
     */
    public function checkStock($params){
        $store_id = $params['store_id'] ?? 0;
        $goods_sku_data = $params['goods_sku_data'];
        $store_model = new Store();
        if ($store_id > 0) {
            $store_condition = [
                ['store_id', '=', $store_id]
            ];
            $store_info = $store_model->getStoreInfo($store_condition)['data'] ?? [];
            $stock_type = $store_info['stock_type'];
            if ($stock_type == 'all') {//如果总部统一库存的话就扣除总店的
                $store_id = 0;
            }
        }
        if($store_id == 0){
            $store_info = $store_model->getDefaultStore()['data'] ?? [];
            $store_id = $store_info['store_id'];
        }
        $goods_sku_ids = array_column($goods_sku_data, 'sku_id');
        $goods_sku_list = model('store_goods_sku')->getColumn([['sku_id', 'in', $goods_sku_ids], ['store_id', '=', $store_id]], '*', 'sku_id');
        foreach($goods_sku_data as $v){
            $item_sku_id = $v['sku_id'];
            $item_sku_name = $v['sku_name'];
            $item_goods_sku = $goods_sku_list[$item_sku_id] ?? [];

            if(!$item_goods_sku) throw new StockException('商品：【'.$item_sku_name.'】 库存不足！');

            $item_num = $v['num'];
            if($item_num > $item_goods_sku['stock']) throw new StockException('商品：【'.$item_sku_name.'】 库存不足！');
        }
        return true;
    }
}