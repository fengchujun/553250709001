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
 * 商品分类
 */
class GoodsListCategoryIds
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

        $model = new Bargain();
        $res = $model->getGoodsCategoryIds($condition);
        return $res;
    }
}