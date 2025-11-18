<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\form\event;

use addon\form\model\Form;

/**
 * 订单待付款
 */
class OrderPayment
{
    /**
     * @param $data
     * @return array
     */
    public function handle($data)
    {
        /** @var \app\model\order\OrderCreate $order_object */
        $order_object = $data['order_object'];
        $site_id = $order_object->site_id;
        $form_model = new Form();
        $info = $form_model->getFormInfo([ [ 'site_id', '=', $site_id], [ 'is_use', '=', 1 ], [ 'form_type', '=', 'order' ] ], 'id,json_data,form_name')[ 'data' ] ?? [];
        if (!empty($info)) {
            $info[ 'json_data' ] = json_decode($info[ 'json_data' ], true);
            //todo  查询用法
            $order_object->system_form = $info;
        }
        $form_ids = array_filter(array_unique(array_column($order_object->goods_list, 'form_id')));
        if($form_ids){
            $form_list = $form_model->getFormList([ [ 'site_id', '=', $site_id], [ 'is_use', '=', 1 ] , ['id', 'in', $form_ids]], '', 'id, json_data')['data'] ?? [];
            $form_array = array_column($form_list, null, 'id');
            foreach($order_object->goods_list as &$goods_item){
                $item_form = $form_array[$goods_item['form_id'] ?? 0] ?? [];
                if($item_form){
                    $item_form['json_data'] = json_decode($item_form[ 'json_data' ], true);
                    $goods_item[ 'goods_form' ] = $item_form;
                }
            }
        }

        return true;
    }
}