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

use app\model\BaseModel;
use app\model\stock\StockData;
use app\model\store\Store;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

/**
 * 商品同城起送配置  门店业务
 */
class GoodsLocalRestrictions extends BaseModel
{


    /**
     * 设置不同门店 sku  同城配送模式 非起送 商品业务
     * @param $goods_sku_array
     * @param $site_id
     * @param $store_id  restrictions
     * @param $uid
     * @return array
     */
    public function setRestrictions($goods_sku_array, $site_id, $store_id)
    {
        $store_model = new Store();
        $store_info = $store_model->getStoreInfo([['store_id', '=', $store_id]])['data'] ?? [];
        if (empty($store_info)) {
            $default_store_info = $store_model->getDefaultStore($site_id)['data'] ?? [];
            $store_id = $default_store_info['store_id'];
        } else {
            $store_id = $store_id;
        }
        model('goods')->startTrans();
        try {
            //校验门店商品,不存在就创建
            $result = (new StockData())->getStoreSkuAndCreateIfNotExists(['goods_sku_list' => $goods_sku_array, 'store_id' => $store_id]);
            foreach ($goods_sku_array as $k => $v) {
                $item_sku_id = $v['sku_id'];
                //是否限制送货  1是限制  0 不限制
                if (isset($v['is_delivery_restrictions'])) {
                    $save_data['is_delivery_restrictions'] = $v['is_delivery_restrictions'] ?? 1;
                }
                if (!empty($save_data)) {
                    model('store_goods_sku')->update($save_data, [['sku_id', '=', $item_sku_id], ['store_id', '=', $store_id]]);
                }
            }
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }

    }
}