<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;


use app\model\system\Pay;
use think\facade\Log;

/**
 * 订单支付相关
 *
 * @author Administrator
 *
 */
class OrderPay extends OrderCommon
{
    /**
     * 改变订单的交易流水号
     * @param $params
     * @return array
     */
    public function resetOrderTradeNo($params)
    {
        $out_trade_no = $params['out_trade_no'];
        $order_condition = array(
            ['pay_status', '=', 0]
        );
        $order_condition[] = ['out_trade_no', '=', $out_trade_no];
        $order_info = model('order')->getInfo($order_condition, 'pay_money,order_name,out_trade_no,order_id,pay_status,site_id,member_id,member_card_order');
        //判断订单数是否匹配
        if (empty($order_info))
            return $this->error([], '没有可支付订单！');

        $pay_model = new Pay();
        $result = $pay_model->closePay($out_trade_no);//关闭旧支付单据
        if ($result['code'] < 0) {
            return $result;
        }

        $member_id = $order_info['member_id'];
        $new_out_trade_no = $pay_model->createOutTradeNo($member_id ?? 0);
        $update_data = array(
            'out_trade_no' => $new_out_trade_no
        );
        model('order')->update($update_data, [['out_trade_no', '=', $out_trade_no], ['pay_status', '=', 0]]);
        model('member_level_order')->update($update_data, [['out_trade_no', '=', $out_trade_no], ['pay_status', '=', 0]]);
        Log::write('resetOrderTradeNo_old_'.$out_trade_no.'_new_'.$new_out_trade_no);

        $pay_model->addPay($order_info['site_id'], $new_out_trade_no, '', $order_info['order_name'], $order_info['order_name'], $order_info['pay_money'], '', 'OrderPayNotify', '', $order_info['order_id'], $order_info['member_id']);
        return $this->success($new_out_trade_no);
    }
}
