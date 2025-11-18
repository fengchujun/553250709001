<?php


namespace app\event\goods;

use app\model\goods\Goods;

/**
 * 虚拟商品自动下架（计划任务）
 * @author Administrator
 *
 */

class CronVirtualGoodsVerifyOff
{
    public function handle($param)
    {
        $goods_model = new Goods();
        $condition = [
            [ 'goods_id', '=', $param[ 'relate_id' ] ]
        ];
        $res = $goods_model->getGoodsDetail($param[ 'relate_id' ])['data'];
        if($res['goods_state'] == 1){
            $res = $goods_model->cronModifyGoodsState($condition, 0);
        }
        return $res;
    }

}