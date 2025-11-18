<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\goods;

use app\model\goods\Goods;

/**
 * 商品自动上架(计划任务)
 * @author Administrator
 *
 */
class CronGoodsTimerOn
{
    public function handle($param)
    {

        $goods_model = new Goods();
        $condition = [
            [ 'goods_id', '=', $param[ 'relate_id' ] ]
        ];
        $res = $goods_model->cronModifyGoodsState($condition, 1);
        return $res;
    }
}
