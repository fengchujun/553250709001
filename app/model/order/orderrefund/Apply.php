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
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use extend\exception\RefundException;
use think\db\exception\DbException;

/**
 * 订单申请退款
 */
class Apply extends BaseModel
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

        //判断是否允许申请退款
        if ($order_info['is_enable_refund'] == 0) {
            if ($order_info['promotion_type'] == 'pinfan' || $order_info['promotion_type'] == 'pintuan') throw new RefundException('拼团活动正在进行中,拼团成功后可再次发起退款！');

            throw new RefundException('当前订单不支持退款！');
        }
        //订单申请退款校验
        event('OrderRefundApplyCheck', []);
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
        //校验订单是否需要锁定
        $order_refund_model->verifyOrderLock($order_info['order_id']);
        event('orderRefundApply', $data);//传入订单类型以及订单项id
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
        $log_data = $data['log_data'] ?? [];
        $refund_log_data = $data['refund_log_data'] ?? [];
        $order_refund_model = new OrderRefund();
        $order_refund_model->addOrderRefundLog(...$refund_log_data);

        // 发起维权 关闭订单评价
        model('order')->update(['is_evaluate' => 0], ['order_id' => $order_goods_info['order_id']]);
        //记录订单日志 start
        if ($log_data) {
            $order_common_model = new OrderCommon();
            $log_data['action'] = '商品【'.$order_goods_info['sku_name'].'】'.$log_data['action'];
            $log_data = array_merge($log_data, [
                'order_id' => $order_goods_info['order_id'],
                'order_status' => $order_info['order_status'],
                'order_status_name' => $order_info['order_status_name']
            ]);
            OrderLog::addOrderLog($log_data, $order_common_model);
        }
        //订单会员申请退款消息
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'BUYER_REFUND', 'order_goods_id' => $order_goods_info['order_goods_id'], 'site_id' => $order_goods_info['site_id']]);
        return true;
    }
}