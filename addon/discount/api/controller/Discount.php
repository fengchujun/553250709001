<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\discount\api\controller;

use app\api\controller\BaseApi;
use addon\discount\model\Discount as DiscountModel;

/**
 * 限时折扣
 */
class Discount extends BaseApi
{
    public function lists()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;

        $dicount_model = new DiscountModel();

        $join = [
            [ 'promotion_discount pd', 'pd.discount_id = pdg.discount_id', 'left' ],
            [ 'goods_sku sku', 'sku.sku_id=pdg.sku_id', 'inner' ]
        ];
        $condition = [
            ['pd.status', '=', 1]
        ];

        $dicount_goods = $dicount_model->getDiscountGoodsPage($condition, $page, $page_size, $join);
        return $this->response($dicount_goods);
    }
}