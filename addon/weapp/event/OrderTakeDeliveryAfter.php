<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\event;


use addon\weapp\model\Weapp;
use addon\wechatpay\model\Config as WechatPayModel;
use think\facade\Log;

/**
 * 订单收货，小程序确认收货提醒
 */
class OrderTakeDeliveryAfter
{
    public function handle($data)
    {
        //通知应该是在用户实际收货后去提醒，正确的做法是通过物流信息获取到用户已收货，然后发出提醒。但是目前做不到这一点，只能是后台商家点击收货，系统自动收货和用户自己点击了收货后发出提醒。
        try {
            $weapp_model = new Weapp($data[ 'site_id' ]);

            // 检测微信小程序是否已开通发货信息管理服务
            $is_trade_managed = $weapp_model->orderShippingIsTradeManaged()['data'];
            if (!$is_trade_managed) return $weapp_model->success();

            $filed = 'o.order_id,o.site_id,o.order_type,o.out_trade_no,o.pay_type,o.mobile,m.weapp_openid';
            $join = [
                [ 'member m', 'o.member_id=m.member_id', 'left' ]
            ];
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $data[ 'order_id' ] ] ], $filed, 'o', $join);
            if (empty($order_info)) {
                return $weapp_model->error('', '订单不存在');
            }
            if ($order_info[ 'pay_type' ] != 'wechatpay') {
                return $weapp_model->success('', '订单未使用微信支付');
            }
            if ($order_info[ 'order_type' ] != 1) {
                return $weapp_model->success('', '只有物流订单才能进行提醒');
            }

            $param = [
                'site_id' => $order_info['site_id'],
                'out_trade_no' => $order_info['out_trade_no'],
            ];
            $res = $weapp_model->orderShippingNotifyConfirmReceive($param);
            //dd($param,$res);
            return $res;
        }catch(\Exception $e){
            return error(-1, '小程序发送确认发货信息提醒错误，'.$e->getMessage());
        }
    }
}