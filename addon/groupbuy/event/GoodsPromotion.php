<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\event;

use addon\groupbuy\model\Groupbuy;
use app\model\goods\Goods as GoodsModel;

/**
 * 商品营销活动信息
 */
class GoodsPromotion
{

    /**
     * 商品营销活动信息
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if (isset($param[ 'goods_sku_detail' ])) {
            $goods_info = $param[ 'goods_sku_detail' ];
            if (!empty($goods_info[ 'promotion_addon' ])) {
                $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
                if (!empty($promotion_addon[ 'groupbuy' ])) {
                    return [
                        'promotion_type' => 'groupbuy',
                        'promotion_name' => '团购',
                        'groupbuy_id' => $promotion_addon[ 'groupbuy' ]
                    ];
                }
            }

        } else {
            if (empty($param[ 'goods_id' ])) return [];
            $goods_model = new GoodsModel();
            $goods_info = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $param[ 'goods_id' ] ] ], 'promotion_addon')[ 'data' ];
            if (!empty($goods_info[ 'promotion_addon' ])) {
                $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
                if (!empty($promotion_addon[ 'groupbuy' ])) {
                    $groupbuy_model = new Groupbuy();
                    $condition = [
                        [ 'pg.groupbuy_id', '=', $promotion_addon[ 'groupbuy' ] ],
                        [ 'pg.goods_id', '=', $param[ 'goods_id' ] ],
                        [ 'pg.status', '=', 2 ],
                        [ 'g.goods_state', '=', 1 ],
                        [ 'g.is_delete', '=', 0 ]
                    ];
                    $goods_detail = $groupbuy_model->getGroupbuyInfo($condition, 'pg.groupbuy_id,pg.site_id,pg.goods_id,pg.goods_price,pg.groupbuy_price,g.sku_id')[ 'data' ];
                    if (!empty($goods_detail)) {
                        $goods_detail[ 'promotion_type' ] = 'groupbuy';
                        $goods_detail[ 'promotion_name' ] = '团购';
                        return $goods_detail;
                    }
                }
            }
            return [];
        }
    }
}