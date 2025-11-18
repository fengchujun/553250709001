<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\stock;


use app\model\BaseModel;
use think\db\exception\DbException;

/**
 * 库存model  (公共的库存相关改动和查询)
 *
 * @author Administrator
 *
 */
class StockData extends BaseModel
{
    use StockTool;


    public function setGoodsStock($params)
    {
        //触发库存变动前操作
        $params['fun'] = __METHOD__;
        event('GoodsStockChangePre', $params);

        //门店id
        $store_id = $this->getDefaultStore($params['store_id'] ?? 0);
        $params['store_id'] = $store_id;
        //获取商品列表
        $goods_sku_list = $this->getFormatSkuList($params);

        $params['goods_sku_list'] = $goods_sku_list;
        $params['stock_action_type'] = 'set';
        $result_data = $this->getCheckGoodsSkuData($params);
        $this->setStock(array_merge($params, $result_data));
        //触发库存变动
        $params['fun'] = __METHOD__;
        event('GoodsStockChange', $params);
        return $this->success();
    }

    /**
     * 减少库存(存在已经)
     * @param $params
     * @return array
     * @throws DbException
     */
    public function decGoodsStock($params)
    {
        //触发库存变动前操作
        $params['fun'] = __METHOD__;
        event('GoodsStockChangePre', $params);
        //门店id
        $store_id = $this->getDefaultStore($params['store_id'] ?? 0);
        $params['store_id'] = $store_id;
        $is_out_stock = $params['is_out_stock'] ?? 0;//是否扣除销售库存(销售状态下一般销售库存已经被扣除了)
        //获取商品列表
        $goods_sku_list = $this->getFormatSkuList($params);

        $params['goods_sku_list'] = $goods_sku_list;
        $params['stock_action_type'] = 'dec';
        $result_data = $this->getCheckGoodsSkuData($params);
        $this->setStock(array_merge($params, $result_data));

        //触发库存变动
        $params['fun'] = __METHOD__;
        event('GoodsStockChange', $params);
        return $this->success();
    }


    /**
     * 增加库存
     * @param $params
     * @return array
     * @throws DbException
     */
    public function incGoodsStock($params)
    {
        //触发库存变动前操作
        $params['fun'] = __METHOD__;
        event('GoodsStockChangePre', $params);

        //门店id
        $store_id = $this->getDefaultStore($params['store_id'] ?? 0);
        $params['store_id'] = $store_id;
        //获取商品列表
        $goods_sku_list = $this->getFormatSkuList($params);

        $params['goods_sku_list'] = $goods_sku_list;
        $params['stock_action_type'] = 'inc';
        $result_data = $this->getCheckGoodsSkuData($params);
        $this->setStock(array_merge($params, $result_data));
        //触发库存变动
        $params['fun'] = __METHOD__;
        event('GoodsStockChange', $params);
        return $this->success();
    }

}
