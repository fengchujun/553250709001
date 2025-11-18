<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\pointexchange\event;

use addon\pointexchange\model\Order;

/**
 * 订单关闭
 */
class OrderClose
{

    /**
     * 订单关闭
     * @param $param
     * @return array|int|mixed|void
     */
    public function handle($param)
    {
        $order_model = new Order();
        $order_info_result = $order_model->getOrderInfo([ [ 'relate_order_id', '=', $param[ 'order_id' ] ] ]);

        if ($order_info_result[ 'code' ] < 0 || empty($order_info_result[ 'data' ])) {
            return $order_info_result;
        }
        if (empty($order_info_result[ 'data' ])) {
            $order_info_result[ 'data' ][ 'order_id' ] = $param[ 'order_id' ];
        }
        $res = $order_model->closeOrder($order_info_result[ 'data' ][ 'order_id' ]);
        return $res;
    }
}