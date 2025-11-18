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
 * 商品分类ids
 */
class GoodsListCategoryIds
{
    public function handle($param)
    {
        if (empty($param['promotion']) || $param['promotion'] != 'groupbuy') return [];
        $condition[] = [
            ['pg.site_id', '=', $param['site_id']],
            ['pg.status', '=', 2],
            ['g.goods_state','=',1],
            ['g.is_delete','=',0]
        ];

        $groupbuy_model = new Groupbuy();
        $res = $groupbuy_model->getGoodsCategoryIds($condition);
        return $res;
    }
}