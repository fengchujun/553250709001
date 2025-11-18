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
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 订单申请退款
 */
class Refuse extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array|true
     * @throws DbException
     */
    public static function check($data)
    {
//        $order_info = $data['order_info'];
//        $user_info = $data['user_info'];
//        $order_goods_info = $data['order_goods_info'];
//
//
//        //订单申请退款校验
//        event('OrderRefundRefuseCheck', []);
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
        $order_info = $data['order_info'];
        $user_info = $data['user_info'] ?? [];
        $order_goods_info = $data['order_goods_info'];
        $order_refund_model = new OrderRefund();
        //订单锁定或解锁
        $order_refund_model->verifyOrderLock($order_goods_info['order_id']);
        // 维权拒绝 评价锁定放开
        model('order')->update(['is_evaluate' => 1], [['order_id', '=', $order_info['order_id']], ['order_status', 'in', [OrderModel::ORDER_TAKE_DELIVERY, OrderModel::ORDER_COMPLETE]]]);
        event('OrderRefundRefuse', $data);
        return true;
    }

    /**
     * 后续事件
     * @param $data
     * @return array|true
     */
    public static function after($data)
    {
        $order_info = $data['order_info'];
        $order_goods_info = $data['order_goods_info'];
        $user_info = $data['user_info'];
        $log_data = $data['log_data'] ?? [];
        $order_refund_model = new OrderRefund();
        $log_desc = empty($data['refund_refuse_reason']) ? '' : '拒绝原因：' . $data['refund_refuse_reason'];
        $order_refund_model->addOrderRefundLog($order_goods_info['order_goods_id'], $data['refund_status'], '卖家拒绝退款', 2, $user_info['uid'], $user_info['username'], $log_desc);

        if ($log_data) {
            $order_common_model = new OrderCommon();
            $log_data = array_merge($log_data, [
                'order_id' => $order_goods_info['order_id'],
                'order_status' => $order_info['order_status'],
                'order_status_name' => $order_info['order_status_name']
            ]);
            OrderLog::addOrderLog($log_data, $order_common_model);
        }

        //订单退款拒绝消息
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'ORDER_REFUND_REFUSE', 'order_id' => $order_goods_info['order_id'], 'order_goods_id' => $order_goods_info['order_goods_id'], 'site_id' => $order_goods_info['site_id']]);
        event('OrderRefundRefuseAfter', $data);
        return true;
    }
}