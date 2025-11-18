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

/**
 * 商品营销活动信息
 */
class GoodsListPromotion
{

    public function handle($param)
    {
        if (empty($param[ 'promotion' ]) || $param[ 'promotion' ] != 'bargain') return [];

        $condition = [
            [ 'pb.site_id', '=', $param[ 'site_id' ] ],
            [ 'pb.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];

        if (!empty($param[ 'goods_name' ])) {
            $condition[] = [ 'g.goods_name', 'like', '%' . $param[ 'goods_name' ] . '%' ];
        }

        if (!empty($param[ 'select_type' ]) && $param[ 'select_type' ] == 'selected' && isset($param[ 'goods_ids' ])) {
            $condition[] = [ 'g.goods_id', 'in', $param[ 'goods_ids' ] ];
        }
        if (!empty($param[ 'category_id' ])) {
            $condition[] = [ 'g.category_id', 'like', '%,' . $param[ 'category_id' ] . ',%' ];
        }
        if (!empty($param[ 'label_id' ])) {
            $condition[] = [ 'g.label_id', '=', $param[ 'label_id' ] ];
        }
        if (!empty($param[ 'goods_class' ])) {
            $condition[] = [ 'g.goods_class', '=', $param[ 'goods_class' ] ];
        }

        $field = 'pb.*,g.goods_name,g.goods_image,g.price,g.recommend_way,sku.sku_id,sku.price,sku.sku_name,sku.sku_image,sku.stock as goods_stock,g.label_name,g.goods_class_name';

        $model = new Bargain();

        $list = $model->getBargainPageList($condition, $param[ 'page' ], $param[ 'page_size' ], 'g.create_time desc', $field);

        return $list;
    }
}