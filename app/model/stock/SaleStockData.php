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
use think\facade\Db;

/**
 * 销售库存
 * @author Administrator
 *
 */
class SaleStockData extends BaseModel
{
    use StockTool;

    /**
     * 减少库存(存在已经)
     * @param $params
     * @return array
     * @throws DbException
     */
    public function dec($params)
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
        $params['stock_action_type'] = 'dec';
        $result_data = $this->getCheckGoodsSkuData($params);
        $this->setSaleStock(array_merge($params, $result_data));
        //触发库存变动
        $params['fun'] = __METHOD__;
        event('GoodsStockChange', $params);
        return $this->success();
    }


    /**
     * 返还销售库存
     * @param $params
     * @return array
     * @throws DbException
     */
    public function inc($params)
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
        $this->setSaleStock(array_merge($params, $result_data));
        //触发库存变动
        $params['fun'] = __METHOD__;
        event('GoodsStockChange', $params);
        return $this->success();
    }
}
