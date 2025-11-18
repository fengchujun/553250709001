<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\storegoods;

use app\model\BaseModel;
use app\model\stock\StockData;
use app\model\store\Store;


/**
 * 商品
 */
class StoreGoods extends BaseModel
{

    /**
     * 门店商品信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getStoreGoodsInfo($condition, $field = '*')
    {
        $info = model('store_goods')->getInfo($condition, $field);
        if (!empty($info)) {
            if (isset($info['stock'])) {
                $info['stock'] = numberFormat($info['stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }

        return $this->success($info);
    }


    /**
     * 门店sku信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getStoreGoodsSkuInfo($condition, $field = '*')
    {
        $info = model('store_goods_sku')->getInfo($condition, $field);
        if (!empty($info)) {
            if (isset($info['stock'])) {
                $info['stock'] = numberFormat($info['stock']);
            }
            if (isset($info['sale_num'])) {
                $info['sale_num'] = numberFormat($info['sale_num']);
            }
            if (isset($info['real_stock'])) {
                $info['real_stock'] = numberFormat($info['real_stock']);
            }
        }
        return $this->success($info);
    }

    /**
     * 查询门店sku列表
     * @param $condition
     * @param $field
     * @return array
     */
    public function getStoreGoodsSkuList($condition, $field = '*', $order = '', $alisd = 'a', $join = [])
    {
        $list = model('store_goods_sku')->getList($condition, $field, $order, $alisd, $join);

        if (!empty($list)) {
            foreach ($list as &$v) {
                if (isset($v['stock'])) $v['stock'] = numberFormat($v['stock']);
                if (isset($v['sale_num'])) $v['sale_num'] = numberFormat($v['sale_num']);
                if (isset($v['real_stock'])) $v['real_stock'] = numberFormat($v['real_stock']);
            }
        }
        return $this->success($list);
    }

    /**
     * 校验门店商品是否存在(todo  优化ed)
     * @param $goods_ids
     * @param $site_id
     * @param $store_id
     * @return array
     */
    public function checkStoreGoods($goods_ids, $site_id, $store_id)
    {
        $sku_ids = model('goods_sku')->getList([['goods_id', 'in', (string)$goods_ids], ['site_id', '=', $site_id]], 'sku_id');
        $param = [
            'goods_sku_list' => $sku_ids,
            'store_id' => $store_id
        ];
        (new StockData())->getStoreSkuAndCreateIfNotExists($param);
        return $this->success(1);
    }

    /**
     * 门店修改商品状态
     * @param $goods_ids
     * @param $goods_state
     * @param $site_id
     * @param int $store_id
     * @return array
     */
    public function modifyGoodsState($goods_ids, $goods_state, $site_id, $store_id = 0)
    {
        if ($store_id == 0) {
            $store_model = new Store();
            $store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
            $store_id = $store_info['store_id'];
        }
        $this->checkStoreGoods($goods_ids, $site_id, $store_id);

        model('store_goods')->update(['status' => $goods_state], [['goods_id', 'in', (string)$goods_ids], ['store_id', '=', $store_id]]);
        model('store_goods_sku')->update(['status' => $goods_state], [['goods_id', 'in', (string)$goods_ids], ['store_id', '=', $store_id]]);
        return $this->success(1);
    }

    /**
     * 修改所有门店的上下架状态
     * @param $goods_ids
     * @param $goods_state
     * @return array
     */
    public function modifyStoreGoodsState($goods_ids, $goods_state)
    {
        model('store_goods')->update(['status' => $goods_state], [['goods_id', 'in', (string)$goods_ids]]);
        model('store_goods_sku')->update(['status' => $goods_state], [['goods_id', 'in', (string)$goods_ids]]);
        return $this->success();
    }

    /**
     * 修改价格库存
     * @param $goods_sku_array
     * @param $site_id
     * @param $store_id
     * @param $uid
     * @return array
     */
    public function editStoreGoods($goods_sku_array, $site_id, $store_id, $uid)
    {
        $store_stock_model = new \app\model\stock\GoodsStock();
        $store_model = new Store();

        $store_info = $store_model->getStoreInfo([['store_id', '=', $store_id]])['data'] ?? [];

        $default_store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
        $default_store_id = $default_store_info['store_id'];
        $is_default_store = 0;
        if ($default_store_id == $store_id) {
            $is_default_store = 1;
        }
        model('goods')->startTrans();
        try {
            //校验门店商品,不存在就创建
            $result = (new StockData())->getStoreSkuAndCreateIfNotExists(['goods_sku_list' => $goods_sku_array, 'store_id' => $store_id]);
            $goods_list_column = $result['goods_list_column'];
            $stock_goods_sku_list = [];
            foreach ($goods_sku_array as $k => $v) {
                $item_sku_id = $v['sku_id'];
                $item_temp_info = $goods_list_column[$item_sku_id];
                $item_goods_id = $item_temp_info['goods_id'];

                $save_data = [];
                if (!$item_temp_info['is_unify_price']) {
                    $save_data['price'] = $v['price'];
                }
                if ($k == 0 && !empty($save_data)) {
                    model('store_goods')->update($save_data, [['goods_id', '=', $item_goods_id], ['store_id', '=', $store_id]]);
                }
                if (!empty($save_data)) model('store_goods_sku')->update($save_data, [['sku_id', '=', $item_sku_id], ['store_id', '=', $store_id]]);
                $temp_goods_sku_data = [
                    'sku_id' => $item_sku_id,
                    'goods_id' => $item_goods_id,
                    'goods_class' => $item_temp_info['goods_class']
                ];
                if (isset($v['stock']) && $store_info && $store_info['stock_type'] == 'store') {
                    $temp_goods_sku_data['stock'] = $v['stock'];
                }
                $stock_goods_sku_list[] = $temp_goods_sku_data;
                //如果是默认门店,也会同步修改平台价
                if (!empty($save_data['price'])) {
                    if ($is_default_store) {
                        if ($k == 0 && !empty($save_data)) {
                            model('goods')->update(['price' => $save_data['price']], [['goods_id', '=', $item_goods_id]]);
                        }
                        model('goods_sku')->update(['price' => $save_data['price']], [['sku_id', '=', $item_sku_id]]);
                    }
                }


            }
            if (isset($v['stock']) && $store_info && $store_info['stock_type'] == 'store') {
                $store_stock_model->changeGoodsStock([
                    'store_id' => $store_id,
                    'site_id' => $site_id,
                    'uid' => $uid,
                    'goods_sku_list' => $stock_goods_sku_list,
                ]);
            }
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 设置门店商品成本价
     * @param $params
     * @return array
     */
    public function setSkuPrice($params)
    {
        $goods_id = $params['goods_id'];
        $site_id = $params['site_id'];
        $store_id = $params['store_id'] ?? 0;
        if ($store_id == 0) {
            $store_model = new Store();
            $store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
            $store_id = $store_info['store_id'];
        }

        $result = $this->checkStoreGoods($goods_id, $site_id, $store_id);
        $goods_condition = array(
            ['goods_id', '=', $goods_id]
        );
        $goods_info = model('goods')->getInfo($goods_condition, 'cost_price,price');
        $sku_list = model('goods_sku')->getList($goods_condition, 'sku_id,cost_price,price');
        $store_goods_condition = array(
            ['goods_id', '=', $goods_id],
            ['store_id', '=', $store_id]
        );
        model('store_goods')->update(['cost_price' => $goods_info['cost_price'], 'price' => $goods_info['price']], $store_goods_condition);
        foreach ($sku_list as $k => $v) {
            $store_goods_sku_condition = array(
                ['sku_id', '=', $v['sku_id']],
                ['store_id', '=', $store_id]
            );
            $item_data = array(
                'cost_price' => $v['cost_price'],
                'price' => $v['price']
            );
            model('store_goods_sku')->update($item_data, $store_goods_sku_condition);
        }
        return $this->success();
    }

    /**
     * 同步数据
     * @param $params
     * @return array
     */
    public function syncGoodsData($params)
    {
        $update_data = $params['update_data'];
        $condition = $params['condition'];
        $site_id = $params['site_id'];
        $store_id = $params['store_id'] ?? 0;
        if ($store_id == 0) {
            $store_model = new Store();
            $store_info = $store_model->getDefaultStore()['data'] ?? [];
            $store_id = $store_info['store_id'];
        }

        $goods_list = model('goods')->getList($condition, 'goods_id, price, cost_price');
        $goods_ids = array_column($goods_list, 'goods_id');
        //检验商品对否存在
        $result = $this->checkStoreGoods(implode(',', $goods_ids), $site_id, $store_id);

        $store_condition = array(['store_id', '=', $store_id]);
        foreach ($goods_list as $k => $v) {
            $item_update_data = array(
                'price' => $v['price'],
                'cost_price' => $v['cost_price']
            );
            $item_store_condition = $store_condition;
            $item_store_condition[] = ['goods_id', '=', $v['goods_id']];
            model('store_goods')->update($item_update_data, $item_store_condition);
        }
        $goods_sku_list = model('goods_sku')->getList($condition, 'sku_id, price, cost_price');
        foreach ($goods_sku_list as $k => $v) {
            $item_update_data = array(
                'price' => $v['price'],
                'cost_price' => $v['cost_price']
            );
            $item_store_condition = $store_condition;
            $item_store_condition[] = ['sku_id', '=', $v['sku_id']];
            model('store_goods_sku')->update($item_update_data, $item_store_condition);
        }
        return $this->success();
    }

}