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

use addon\cardservice\model\MemberCard;
use app\dict\goods\GoodsDict;
use app\dict\member_account\AccountDict;
use app\dict\order\OrderDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\order\Order;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use app\model\verify\Verify as VerifyModel;
use extend\exception\RefundException;
use think\db\exception\DbException;

/**
 * 订单退款完成
 */
class FinishAction extends BaseModel
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
        $order_goods_id = $order_goods_info['order_goods_id'];
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];
        $order_refund_model = new OrderRefund();
        $is_all_refund = $data['is_all_refund'];
        $refund_total_real_money = $data['refund_total_real_money'];
        $member_id = $order_info['member_id'];
        $site_id = $order_info['site_id'];
        //更新订单锁定状态
        $order_refund_model->verifyOrderLock($order_id);
        if ($order_goods_info['refund_mode'] == OrderRefundDict::refund) {
            //虚拟商品 退款 修改核销码状态
            if ($order_goods_info['goods_class'] == GoodsDict::virtual) {
                $verify_goods_condition = [
                    ['order_no', '=', $order_info['order_no']],
                    ['site_id', '=', $site_id]
                ];
                model('goods_virtual')->update(['is_veirfy' => VerifyModel::STATUS_REFUNDED], $verify_goods_condition);

                $verify_model = new VerifyModel();
                $verify_condition = [
                    ['verify_code', '=', $order_info['virtual_code']],
                    ['site_id', '=', $site_id]
                ];
                $verify_model->editVerify(['is_verify' => VerifyModel::STATUS_REFUNDED], $verify_condition);
            }
            // 退还积分 只有退款时返还 售后不返还
            if ($order_goods_info['use_point'] > 0) {
                $member_account_model = new MemberAccount();
                $point_result = $member_account_model->addMemberAccount(
                    $site_id,
                    $member_id,
                    AccountDict::point,
                    $order_goods_info['use_point'],
                    'refund',
                    $order_id,
                    '订单退款返还！'
                );
                if ($point_result['code'] < 0) {
                    throw new RefundException($point_result['message']);
                }
            }
        }

        //订单修改
        $order_update_data = [
            'refund_money' => $refund_total_real_money,
        ];
        // 如果售后完成关闭订单评价
        if ($is_all_refund && $order_info['order_status'] == Order::ORDER_COMPLETE) {
            $order_update_data['is_evaluate'] = 0;
        } else if ($order_info['order_status'] == Order::ORDER_COMPLETE || $order_info['order_status'] == Order::ORDER_TAKE_DELIVERY) {
            if ($order_info['evaluate_status'] != OrderDict::evaluate_again) {
                $order_update_data['is_evaluate'] = 1;
            }
        }
        model('order')->update($order_update_data, [['order_id', '=', $order_id]]);
        //统一写入退款日志
        if (!empty($log_data)) {
            $order_common_model = new OrderCommon();
            $log_data = array_merge($log_data, [
                'order_id' => $order_id,
                'order_status' => $order_info['order_status'],
                'order_status_name' => $order_info['order_status_name']
            ]);
            OrderLog::addOrderLog($log_data, $order_common_model);
        }
        //订单退款完成操作(满减送奖励收回)
        event('OrderRefundFinish', ['order_goods_info' => $order_goods_info, 'order_info' => $order_info, 'is_all_refund' => $is_all_refund]);
        return true;
    }

    /**
     * 后续事件
     * @param $data
     * @return array|true
     */
    public static function after($data)
    {
        $order_goods_info = $data['order_goods_info'] ?? [];
        $order_info = $data['order_info'] ?? [];

        $order_refund_model = new OrderRefund();
        $order_id = $order_info['order_id'] ?? 0;
        $member_id = $order_info['member_id'] ?? 0;
        $site_id = $order_info['site_id'] ?? 0;
        return true;
    }


}