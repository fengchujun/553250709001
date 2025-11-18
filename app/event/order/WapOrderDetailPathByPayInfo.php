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
 * 通过支付信息获取手机端订单详情路径
 */
class WapOrderDetailPathByPayInfo
{
    public function handle($data)
    {
        if($data['event'] == 'OrderPayNotify'){
            $order_common = new OrderCommon();
            $order_info = $order_common->getOrderInfo([['out_trade_no', '=', $data['out_trade_no']]], 'order_id')['data'];
            if(!empty($order_info)){
                return '/pages/order/detail?order_id='.$order_info['order_id'];
            }
        }
    }
}