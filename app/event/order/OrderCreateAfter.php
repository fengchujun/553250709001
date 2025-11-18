<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\order;

use app\model\goods\Cart;
use app\model\message\Message;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\system\Stat;

/**
 * 订单创建后事件
 */
class OrderCreateAfter
{
    /**
     * 传入订单信息
     * @param unknown $data
     */
    public function handle($data)
    {
        //添加自动关闭事件
        /** @var \app\model\order\OrderCreate $order_object */
        $order_object = $data['order_object'];
        //清理订单缓存
        $order_object->deleteOrderCache($order_object->order_key);
        $order_data = $data['create_data'];
        $site_id = $order_data['site_id'];
        $order_id = $order_data['order_id'];
        $member_id = $order_data['member_id'];
//        $site_id = $order_object->site_id;
//        $order_id = $order_object->order_id;
//        $member_id = $order_object->member_id;
        //添加自动关闭事件
        $order_object->addOrderCronClose();
        //写日志
        $log_data = $order_data['log'] ?? [];
        if(!$log_data){
            //记录订单日志 start
            //获取用户信息
            $member_info = $order_object->member_account;

            $buyer_name = !empty($member_info['nickname']) ? '【' . $member_info['nickname'] . '】' : '';
            $log_data = [
                'order_id' => $order_id,
                'action' => '买家下单了',
                'uid' => $member_id,
                'nick_name' => $member_info['nickname'],
                'action_way' => 1,
                'order_status' => 0,
                'order_status_name' => $order_object->order_type['order_status']['name']
            ];
        }
        $order_common_model = new OrderCommon();
        OrderLog::addOrderLog($log_data, $order_common_model);
        //记录订单日志 end


        //清除购物车数据
        $cart_ids = $order_object->cart_ids;
        if (!empty($cart_ids)) {
            $cart = new Cart();
            $data_cart = [
                'cart_id' => $cart_ids,
                'member_id' => $member_id
            ];
            if ($order_object->jielong_id == 0) {
                $cart->deleteCart($data_cart);
            }
        }
        //发送消息
        if($member_id > 0){
            //订单创建发消息
            $message_model = new Message();
            $message_model->sendMessage(['keywords' => 'ORDER_CREATE', 'order_id' => $order_id, 'site_id' => $site_id]);
        }

        //添加统计
        $stat = new Stat();
        $stat->switchStat([ 'type' => 'order_create', 'data' => [
            'site_id' => $site_id,
            'order_id' => $order_id,
            'member_id' => $member_id,
            'order_data' => $order_data
        ] ]);
        return success();
    }

}