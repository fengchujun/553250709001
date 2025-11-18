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


namespace addon\jielong\event;

use addon\jielong\model\Cart;
use app\model\order\OrderCommon;

/**
 * 关闭活动
 */
class OrderCreateAfter
{

    public function handle($params)
    {
        //订单支付后
        $order_object = $params['order_object'];
        if ($order_object->jielong_id > 0) {

            $cart_ids = $order_object->cart_ids;
            $member_id = $order_object->member_id;
            $jielong_cart = new Cart();
            //删除购物车
            $data_cart = [
                'cart_id' => $cart_ids,
                'member_id' => $member_id
            ];
            $jielong_cart->deleteCart($data_cart);


            //配送数据
            $express_type_list = $order_object->config('delivery_type');
            $delivery_type_name = $express_type_list[$order_object->delivery['delivery_type']] ?? '';
            model('promotion_jielong_order')->add([
                'order_no' => $order_object->order_no,
                'site_id' => $order_object->site_id,
                'site_name' => $order_object->site_name,

                'order_from' => $order_object->param['order_from'],
                'order_from_name' => $order_object->param['order_from_name'],

                'order_type' => $order_object->order_type['order_type_id'],
                'order_type_name' => $order_object->order_type['order_type_name'],
                'order_status_name' => $order_object->order_type['order_status']['name'],
                'order_status_action' => json_encode($order_object->order_type['order_status'], JSON_UNESCAPED_UNICODE),


                'member_id' => $order_object->member_id,
                'name' => $order_object->delivery['member_address']['name'] ?? '',
                'mobile' => $order_object->delivery['member_address']['mobile'] ?? '',
                'telephone' => $order_object->delivery['member_address']['telephone'] ?? '',
                'province_id' => $order_object->delivery['member_address']['province_id'] ?? '',
                'city_id' => $order_object->delivery['member_address']['city_id'] ?? '',
                'district_id' => $order_object->delivery['member_address']['district_id'] ?? '',
                'community_id' => $order_object->delivery['member_address']['community_id'] ?? '',
                'address' => $order_object->delivery['member_address']['address'] ?? '',
                'full_address' => $order_object->delivery['member_address']['full_address'] ?? '',
                'longitude' => $order_object->delivery['member_address']['longitude'] ?? '',
                'latitude' => $order_object->delivery['member_address']['latitude'] ?? '',
                'buyer_ip' => request()->ip(),
                'buyer_message' => $order_object->param['buyer_message'] ?? '',
                'num' => $order_object->goods_num,
                'goods_money' => $order_object->goods_money,
                'delivery_money' => $order_object->delivery_money,
                'promotion_money' => $order_object->promotion_money,
                'coupon_id' => $order_object->coupon_id ?? 0,
                'coupon_money' => $order_object->coupon_money ?? 0,
                'order_money' => $order_object->order_money,
                'delivery_type' => $order_object->delivery['delivery_type'],
                'delivery_type_name' => $delivery_type_name,
                'create_time' => time(),
                'relate_order_id' => $order_object->order_id,
                'jielong_id' => $order_object->jielong_id,
            ]);
        }
        return true;
    }
}