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
class OrderCreateAfter
{
    /**
     * @return multitype:number unknown
     */
    public function handle($data)
    {
        /** @var \app\model\order\OrderCreate $order_object */
        $order_object = $data['order_object'];
        $form_data = $order_object->param['form_data'] ?? [];
        if ($form_data) {
            $site_id = $order_object->site_id;
            $order_id = $order_object->order_id;
            $member_id = $order_object->member_id;
            // 添加订单表单
            $form = new Form();
            if (isset($form_data[ 'form_data' ])) {
                $res = $form->addFormData([
                    'site_id' => $site_id,
                    'form_id' => $form_data[ 'form_id' ],
                    'member_id' => $member_id,
                    'relation_id' => $order_id,
                    'form_data' => $form_data[ 'form_data' ],
                    'scene' => 'order'
                ]);
                if ($res[ 'code' ] != 0) return $res;
            }
            // 添加商品表单
            $goods_form = $form_data[ 'goods_form' ] ?? [];
            if ($goods_form) {
                //查询订单项
//                $goods_list = array_column($order_object->goods_list, null, 'sku_id');
                $order_goods_list = array_column($order_object->getOrderGoodsList(), null, 'sku_id');
                foreach ($goods_form as $sku_id => $form_item) {
//                    $goods_item = $goods_list[ $sku_id ] ?? [];
                    $order_goods_item = $order_goods_list[$sku_id] ?? [];
                    $res = $form->addFormData([
                        'site_id' => $site_id,
                        'form_id' => $form_item[ 'form_id' ],
                        'member_id' => $member_id,
                        'relation_id' => $order_goods_item[ 'order_goods_id' ] ?? 0,
                        'form_data' => $form_item[ 'form_data' ],
                        'scene' => 'goods'
                    ]);
                    if ($res[ 'code' ] != 0) return $res;
                }
            }
        }
        return success();
    }
}