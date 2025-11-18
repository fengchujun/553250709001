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

use app\dict\order\OrderDict;
use app\model\BaseModel;
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 订单取消退款
 */
class Cancel extends BaseModel
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
        $order_info = $data['order_info'];
        $order_refund_model = new OrderRefund();
        //订单锁定
        $order_refund_model->verifyOrderLock($order_info['order_id']);
        // 维权拒绝 评价锁定放开
        if ($order_info['evaluate_status'] != OrderDict::evaluate_again) {
            model('order')->update(['is_evaluate' => 1], [['order_id', '=', $order_info['order_id']], ['order_status', 'in', [OrderModel::ORDER_TAKE_DELIVERY, OrderModel::ORDER_COMPLETE]]]);
        }
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
        $order_id = $order_info['order_id'];
        $order_goods_info = $data['order_goods_info'];
        $log_data = $data['log_data'] ?? [];
        $member_info = $data['member_info'];
        $order_refund_model = new OrderRefund();
        //记录订单日志 start
        if ($log_data) {
            $order_common_model = new OrderCommon();
//            $order_info = model('order')->getInfo(['order_id' => $order_id], 'order_status,order_status_name,member_id,site_id,is_video_number');
            $log_data['action'] = '商品【'.$order_goods_info['sku_name'].'】'.$log_data['action'];
            $log_data = array_merge($log_data, [
                'order_id' => $order_id,
                'order_status' => $order_info['order_status'],
                'order_status_name' => $order_info['order_status_name']
            ]);
            OrderLog::addOrderLog($log_data, $order_common_model);
        }
        //记录退款日志 end
        $order_refund_model->addOrderRefundLog($order_goods_info['order_goods_id'], 0, '买家撤销退款申请', 1, $member_info['member_id'], $member_info['nickname']);
        event('MemberCancelRefund', $data);//传入订单类型以及订单项id
        return true;
    }
}