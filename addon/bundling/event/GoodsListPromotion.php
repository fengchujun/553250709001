<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bundling\event;

use addon\bundling\model\Bundling;

/**
 * 查找套餐商品
 */
class GoodsListPromotion
{
    /**
     *  查找套餐商品
     */
    public function handle($param)
    {

        if (empty($param[ 'promotion' ]) || $param[ 'promotion' ] != 'bundling') return [];

        $model = new Bundling();
        $condition = [];
        if(!empty($param['goods_name'])){
            $condition[] = ['bl_name','like','%'.$param['goods_name']."%"];
        }
        $res = $model->getBundlingPageList($condition,$param['page']??1,$param['page_size']?? '');
        return $res;
    }
}