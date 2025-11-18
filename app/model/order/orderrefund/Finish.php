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

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 订单退款完成
 */
class Finish extends BaseModel
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
        $user_info = $data['user_info'] ?? [];
        $order_goods_info = $data['order_goods_info'] ?? [];
        $order_goods_id = $order_goods_info['order_goods_id'];
        $order_info = $data['order_info'] ?? [];
        $order_refund_model = new OrderRefund();
        //订单项退款后各种订单操作
        $order_refund_model->orderGoodsRefund($order_goods_info);
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
        $order_goods_id = $order_goods_info['order_goods_id'];
        $order_info = $data['order_info'];
        $member_id = $order_info['member_id'];

        $refund_real_money = $order_goods_info['refund_real_money'];
        $order_refund_model = new OrderRefund();
        //累加会员销售额
        model('member')->setDec(
            [['member_id', '=', $member_id]],
            'order_money',
            $refund_real_money
        );
        //退货日志
        $order_refund_model->addOrderRefundLog(
            $order_goods_id,
            OrderRefundDict::REFUND_COMPLETE,
            '维权完成',
            2,
            $user_info['uid'],
            $user_info['username'],
            '维权完成，退款金额：¥' . $refund_real_money
        );

        //后续事件
        event('OrderRefundFinishAfter', $data);
        return true;
    }
}