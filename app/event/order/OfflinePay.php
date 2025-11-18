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

use app\model\order\OrderCommon;

/**
 * 订单支付成功同步事件
 */
class OfflinePay
{
    /**
     * 线下支付提交后记录支付方式
     * @param $pay_info
     * @return array|void
     */
    public function handle($pay_info)
    {
        if($pay_info['event'] == 'OrderPayNotify'){
            $order_model = new OrderCommon();
            $pay_type_list = $order_model->getPayType();
            $res = $order_model->orderUpdate([
                'pay_type' => $pay_info['pay_type'],
                'pay_type_name' => $pay_type_list[$pay_info['pay_type']] ?? '',
            ], [['out_trade_no', '=', $pay_info['out_trade_no']]]);
            return $res;
        }

    }

}