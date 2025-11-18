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

/**
 * 商品营销活动信息
 */
class GoodsListPromotion
{

    /**
     * 商品营销活动信息
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if (empty($param[ 'promotion' ]) || $param[ 'promotion' ] != 'groupbuy') return [];
        $condition[] = [
            [ 'pg.site_id', '=', $param[ 'site_id' ] ],
            [ 'pg.status', '=', 2 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
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

        $groupbuy_model = new Groupbuy();
        $list = $groupbuy_model->getGroupbuyGoodsPageList($condition, $param[ 'page' ], $param[ 'page_size' ], 'pg.groupbuy_id desc');
        $list[ 'condition' ] = $condition;
        return $list;
    }
}