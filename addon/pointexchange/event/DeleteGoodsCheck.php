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


namespace addon\pointexchange\event;

class DeleteGoodsCheck
{
	public function handle($param)
	{
        if(in_array($param['field'], ['goods_id', 'sku_id'])){
            $alias = 'peg';
            $join = [
                ['promotion_exchange pe', 'peg.id = pe.exchange_goods_id', 'inner'],
            ];
            $condition = [
                ['peg.type', '=', 1],
                ['peg.state', '=', 1],
            ];
            if($param['field'] == 'goods_id'){
                $condition[] = ['peg.type_id', 'in', $param['ids']];
                $query_field = 'peg.type_id';
            }else{
                $condition[] = ['pe.type_id', 'in', $param['ids']];
                $query_field = 'pe.type_id';
            }
            $field = 'type_id';
            $cannot_delete_goods_list = model('promotion_exchange_goods')->getList($condition, $query_field, '', $alias, $join);
            $cannot_delete_ids = array_unique(array_column($cannot_delete_goods_list, $field));
            return [
                'reason' => '存在积分兑换活动',
                'cannot_delete_ids' => $cannot_delete_ids,
            ];
        }
	}
}