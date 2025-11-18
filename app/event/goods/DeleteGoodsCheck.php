<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace app\event\goods;

use app\model\order\Order;
use think\facade\Db;

class DeleteGoodsCheck
{
	public function handle($param)
	{
	    if(in_array($param['field'], ['goods_id', 'sku_id'])){
            $order_close = Order::ORDER_CLOSE;
            $order_complete = Order::ORDER_COMPLETE;
            $cannot_delete_goods_list = model('order_goods')->getList([
                ['', 'exp', Db::raw("o.order_status not in ({$order_close},{$order_complete}) or (o.order_status = {$order_complete} and o.is_enable_refund = 1)")],
                ['og.'.$param['field'], 'in', $param['ids']],
            ], $param['field'], '', 'og', [['order o', 'og.order_id = o.order_id', 'inner']]);
            $cannot_delete_ids = array_unique(array_column($cannot_delete_goods_list, $param['field']));
            return [
                'reason' => '存在进行中的订单',
                'cannot_delete_ids' => $cannot_delete_ids,
            ];
        }
	}
}