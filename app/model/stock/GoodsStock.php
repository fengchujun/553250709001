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


use addon\stock\model\stock\Stock;
use app\dict\goods\GoodsDict;
use app\model\BaseModel;
use app\model\store\Store;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * 库存model  (公共的库存相关改动和查询)
 *
 * @author Administrator
 *
 */
class GoodsStock extends BaseModel
{

    /**
     * 商品直接设置库存权重最高(只允许商品数据发生变动时调用)
     * @param $params
     * @return array|int
     */
    public function changeGoodsStock($params)
    {
        $store_id = $params['store_id'] ?? 0;
        $site_id = $params['site_id'] ?? 1;

        $goods_sku_list = $params['goods_sku_list'] ?? [];

        //没有传递仓库id的话,就选取默认
        if ($store_id == 0 || !addon_is_exit('store')) {
            $store_model = new Store();
            $store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
            $store_id = $store_info['store_id'];
            $params['store_id'] = $store_id;
        }

        //矫正商品库存
        $this->correctGoodsStock($params);

        $is_exist = addon_is_exit('stock');
        if ($is_exist) {
            $stock_model = new Stock();
        }

        if (!empty($goods_sku_list)) {
            $goods_sku_list_1 = [];
            $goods_sku_list_2 = [];
            $goods_sku_list_3 = [];
            foreach ($goods_sku_list as $k => $v) {
                if ($is_exist && $v['goods_class'] == GoodsDict::real) {
                    $goods_sku_list_1[] = $v;
                } else {
                    if (in_array($v['goods_class'], [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card])) {
                        $goods_sku_list_2[] = $v;
                    } elseif ($v['goods_class'] == GoodsDict::weigh) {
                        $goods_sku_list_3[] = $v;
                    }
                }
            }
            if (!empty($goods_sku_list_1)) {
                $params['goods_sku_list'] = $goods_sku_list_1;
                $result = $stock_model->setGoodsStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
            if (!empty($goods_sku_list_2)) {
                $params['goods_sku_list'] = $goods_sku_list_2;
                $result = $this->setGoodsStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
            if (!empty($goods_sku_list_3)) {
                $params['goods_sku_list'] = $goods_sku_list_3;
                $result = $this->setGoodsStock($params);
                if ($result['code'] < 0) {
                    return $result;
                }
            }
        } else {
            $goods_class = $params['goods_class'];
            //如果存在进销存的话生成入库单据
            if ($is_exist && $goods_class == GoodsDict::real) {
                $result = $stock_model->setGoodsStock($params);
            } else {//没有的话直接生成支付单据
                $result = $this->setGoodsStock($params);
            }
        }

        return $result ?? $this->success();
    }

    /**
     * 商品库存设置(主体永远是sku)
     * @param $params
     * @return array
     * @throws DbException
     */
    public function setGoodsStock($params)
    {
        $params['field'] = 'stock';
        return (new StockData())->setGoodsStock($params);
    }

    /**
     * 减少库存(存在已经)
     * @param $params
     * @return array
     * @throws DbException
     */
    public function decGoodsStock($params)
    {
        return (new StockData())->decGoodsStock($params);
    }

    /**
     * 增加库存
     * @param $params
     * @return array
     */
    public function incGoodsStock($params)
    {
        return (new StockData())->incGoodsStock($params);
    }

    /**
     * 核验可能不存在的sku门店数据,并校正数据(单个商品解决方案)
     * @param $params
     * @return array
     */
    public function checkExistGoodsSku($params)
    {
        $goods_id = $params['goods_id'];
        $goods_condition = [
            ['goods_id', '=', $goods_id]
        ];
        $sku_ids = model('goods_sku')->getColumn($goods_condition, 'sku_id');
        $store_sku_condition = [
            ['goods_id', '=', $goods_id],
            ['sku_id', 'not in', $sku_ids]
        ];
        //被废弃的门店sku
        $store_sku_list = model('store_goods_sku')->getList($store_sku_condition, 'store_id, sum(stock) as stock, sum(real_stock) as real_stock', '', '', [], 'store_id');
        if (empty($store_sku_list))
            return $this->success();

        $store_goods_list = model('store_goods')->getColumn([['goods_id', '=', $goods_id]], 'store_id, stock, real_stock', 'store_id');
        $stock = 0;
        $real_stock = 0;
        foreach ($store_sku_list as $k => $v) {
            $store_id = $v['store_id'];
            $item_stock = numberFormat($v['stock']);
            $item_real_stock = numberFormat($v['real_stock']);
            $item_store_goods_condition = $goods_condition;
            $item_store_goods_condition[] = ['store_id', '=', $store_id];
//            $item_store_goods_info = model('store_goods')->getInfo($item_store_goods_condition, 'stock, real_stock');
            $item_store_goods_info = $store_goods_list[$store_id] ?? [];
            $new_item_stock = $item_store_goods_info['stock'] - $item_stock;
            $new_item_real_stock = $item_store_goods_info['real_stock'] - $item_real_stock;

            model('store_goods')->update([
                'stock' => max($new_item_stock, 0),
                'real_stock' => max($new_item_real_stock, 0)
            ], $item_store_goods_condition);
            $stock += $item_stock;
            $real_stock += $item_real_stock;
        }
        //删除已经不存在的商品sku
        model('store_goods_sku')->delete($store_sku_condition);

        $goods_info = model('goods')->getInfo($goods_condition, 'goods_stock, real_stock');
        $goods_stock = $goods_info['goods_stock'] - $stock;
        $goods_real_stock = $goods_info['real_stock'] - $real_stock;
        model('goods')->update([
            'goods_stock' => max($goods_stock, 0),
            'real_stock' => max($goods_real_stock, 0)
        ], $goods_condition);
        return $this->success();
    }

    /**
     * 矫正商品库存
     * @param $params
     * @throws DbException
     */
    public function correctGoodsStock($params)
    {
        $store_id = $params['store_id'] ?? 0;
        $site_id = $params['site_id'] ?? 1;
        $goods_sku_list = $params['goods_sku_list'] ?? [];

        $store_model = new Store();
        $store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
        $is_default_store = $store_id == $store_info['store_id'];

        $goods_ids = model('goods_sku')->getColumn([['sku_id', 'in', array_column($goods_sku_list, 'sku_id')]], 'goods_id');
        $goods_ids = array_unique($goods_ids);

        //门店商品库存=门店商品规格库存累加
        $store_goods_sku_table = Db::name('store_goods_sku')
            ->field('goods_id,sum(stock) as stock_sum,sum(real_stock) as real_stock_sum')
            ->where([['goods_id', 'in', $goods_ids], ['store_id', '=', $store_id]])
            ->group('goods_id')
            ->buildSql();
        Db::name('store_goods')
            ->alias('sg')
            ->join("{$store_goods_sku_table} as sgs", 'sg.goods_id = sgs.goods_id', 'inner')
            ->where([['sg.goods_id', 'in', $goods_ids], ['store_id', 'in', $store_id]])
            ->update([
                'sg.stock' => Db::raw('sgs.stock_sum'),
                'sg.real_stock' => Db::raw('sgs.real_stock_sum'),
            ]);
        if($is_default_store){
            //商家规格库存与门店同步
            Db::name('goods_sku')
                ->alias('gs')
                ->join('store_goods_sku sgs', 'gs.sku_id = sgs.sku_id and sgs.store_id = '.$store_id, 'inner')
                ->where([['gs.goods_id', 'in', $goods_ids]])
                ->update([
                    'gs.stock' => Db::raw('sgs.stock'),
                    'gs.real_stock' => Db::raw('sgs.real_stock'),
                ]);
            //商家商品库存与门店同步
            Db::name('goods')
                ->alias('g')
                ->join('store_goods sg', 'g.goods_id = sg.goods_id and sg.store_id = '.$store_id, 'inner')
                ->where([['g.goods_id', 'in', $goods_ids]])
                ->update([
                    'g.goods_stock' => Db::raw('sg.stock'),
                    'g.real_stock' => Db::raw('sg.real_stock'),
                ]);
        }
    }
}
