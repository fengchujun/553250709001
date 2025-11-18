<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\orderrefund;

use app\model\BaseModel;
use app\model\message\Message;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 买家退货
 */
class Delivery extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array|true
     * @throws DbException
     */
    public static function check($data)
    {
        $order_info = $data['order_info'];
        $member_info = $data['member_info'];
        $order_goods_info = $data['order_goods_info'];


        event('OrderRefundDeliveryCheck', []);
        return true;
    }

    /**
     * 退款执行事件
     * @param $data
     * @return true
     * @throws DbException
     */
    public static function event($data)
    {
        $order_info = $data['order_info'] ?? [];
        $member_info = $data['member_info'] ?? [];
        $order_goods_info = $data['order_goods_info'] ?? [];
//        $order_refund_model = new OrderRefund();

        event('orderRefundDelivery', $data);
        return true;
    }

    /**
     * 后续事件
     * @param $data
     * @return array|true
     */
    public static function after($data)
    {
//        $order_info = $data['order_info'];
        $member_info = $data['member_info'];
        $order_goods_info = $data['order_goods_info'];
//        $log_data = $data['log_data'] ?? [];
        $order_refund_model = new OrderRefund();

        $order_refund_model->addOrderRefundLog(
            $order_goods_info['order_goods_id'],
            $data['refund_status'],
            $data['refund_delivery_name'] . ':' . $data['refund_delivery_no'],
            1,
            $member_info['member_id'],
            $member_info['nickname']);
        //买家已退货提醒
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'BUYER_DELIVERY_REFUND', 'order_goods_info' => $order_goods_info, 'site_id' => $order_goods_info['site_id']]);
        return true;
    }
}