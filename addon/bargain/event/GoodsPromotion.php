<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\event;

use addon\bargain\model\Bargain;
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
                if (!empty($promotion_addon[ 'bargin' ])) {
                    return [
                        'promotion_type' => 'bargin',
                        'promotion_name' => '砍价',
                        'bargain_id' => $promotion_addon[ 'bargain' ]
                    ];
                }
            }

        } else {
            if (empty($param[ 'goods_id' ])) return [];
            $goods_model = new GoodsModel();
            $goods_info = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $param[ 'goods_id' ] ] ], 'promotion_addon')[ 'data' ];
            if (!empty($goods_info[ 'promotion_addon' ])) {
                $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
                if (!empty($promotion_addon[ 'bargain' ])) {
                    $bargain_model = new Bargain();
                    $condition = [
                        [ 'pbg.bargain_id', '=', $promotion_addon[ 'bargain' ] ],
                        [ 'pb.status', '=', 1 ],
                        [ 'g.goods_state', '=', 1 ],
                        [ 'g.is_delete', '=', 0 ]
                    ];
                    $field = 'pbg.id,pbg.bargain_id,pbg.goods_id,pbg.sku_id,pbg.floor_price,pb.bargain_name';
                    $goods_detail = $bargain_model->getBargainGoodsDetail($condition, $field)[ 'data' ];
                    if (!empty($goods_detail)) {
                        $goods_detail[ 'promotion_type' ] = 'bargain';
                        $goods_detail[ 'promotion_name' ] = '砍价';
                        return $goods_detail;
                    }
                }
            }
            return [];
        }

    }
}