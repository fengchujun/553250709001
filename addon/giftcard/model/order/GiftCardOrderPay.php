<?php


namespace addon\giftcard\model\order;

use app\model\BaseModel;
use app\model\system\Pay;

class GiftCardOrderPay extends BaseModel
{

    public function resetPay($params)
    {
        $out_trade_no = $params[ 'out_trade_no' ];
        $order_condition = array (
            [ 'pay_status', '=', 0 ],
            [ 'out_trade_no', '=', $out_trade_no ]
        );
        $order_condition[] = [ 'out_trade_no', '=', $out_trade_no ];
        $giftcard_order_model = new GiftCardOrder();
        $order_info = $giftcard_order_model->getOrderInfo($order_condition, 'pay_money,site_id,member_id');
        if (empty($order_info))
            return $this->error([], '没有可支付订单！');

        $pay_money = $order_info[ 'pay_money' ];
        $site_id = $order_info[ 'site_id' ];

        $pay_model = new Pay();
        $result = $pay_model->closePay($out_trade_no);//关闭旧支付单据
        if ($result[ 'code' ] < 0) {
            return $result;
        }
        $member_id = $order_info[ 'member_id' ];
        $new_out_trade_no = $pay_model->createOutTradeNo($member_id ?? 0);
        $update_data = array (
            'out_trade_no' => $new_out_trade_no
        );
        model('giftcard_order')->update($update_data, $order_condition);
        $result = $pay_model->addPay($site_id, $new_out_trade_no, '', $params[ 'pay_body' ], $params[ 'pay_detail' ], $pay_money, '', 'GiftCardOrderPayNotify', '', $order_info['order_id'], $order_info['member_id']);
        return $this->success($new_out_trade_no);
    }
}
