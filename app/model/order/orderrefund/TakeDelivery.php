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
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 订单审核退款
 */
class TakeDelivery extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array|true
     * @throws DbException
     */
    public static function check($data)
    {
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

        return true;
    }

    /**
     * 后续事件
     * @param $data
     * @return array|true
     */
    public static function after($data)
    {
        $user_info = $data['user_info'];
        $order_goods_info = $data['order_goods_info'];
        $refund_status = $data['refund_status'];
        $order_refund_model = new OrderRefund();
        $order_refund_model->addOrderRefundLog(
            $order_goods_info['order_goods_id'],
            $refund_status,
            '卖家确认收到退货',
            2,
            $user_info['uid'],
            $user_info['username']);
        //后续事件
        event('OrderRefundTakeDeliveryAfter', $data);
        return true;
    }
}