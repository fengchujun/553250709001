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
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 订单关闭退款
 */
class Close extends BaseModel
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
        $order_goods_info = $data['order_goods_info'];
        $order_refund_model = new OrderRefund();
        $order_refund_model->verifyOrderLock($order_goods_info['order_id']);
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
        $user_info = $data['user_info'];
        $order_goods_info = $data['order_goods_info'];
        $order_common_model = new OrderCommon();
        //记录订单日志 start
        $log_data = [
            'uid' => $user_info['uid'],
            'nick_name' => $user_info['username'],
            'action' => '商家关闭了维权',
            'action_way' => 2,
            'order_id' => $order_goods_info['order_id'],
            'order_status' => $order_info['order_status'],
            'order_status_name' => $order_info['order_status_name']
        ];
        OrderLog::addOrderLog($log_data, $order_common_model);
        //记录订单日志 end
        //退款日志
        $order_refund_model = new OrderRefund();
        $order_refund_model->addOrderRefundLog(
            $order_goods_info['order_goods_id'],
            OrderRefundDict::REFUND_NOT_APPLY,
            '卖家关闭本次维权',
            2,
            $user_info['uid'],
            $user_info['username']
        );
        event('MemberCancelRefund', $data);//传入订单类型以及订单项id
        return true;
    }
}