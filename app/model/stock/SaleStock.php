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


use think\db\exception\DbException;


/**
 * 库存model  (公共的库存相关改动和查询)
 *
 * @author Administrator
 *
 */
class SaleStock extends GoodsStock
{


    /**
     * 减少库存(存在已经)
     * @param $params
     * @return array
     * @throws DbException
     */
    public function decGoodsStock($params)
    {
        return (new SaleStockData())->dec($params);
    }

    /**
     * 增加库存
     * @param $params
     * @return array
     */
    public function incGoodsStock($params)
    {
        return (new SaleStockData())->inc($params);
    }

}
