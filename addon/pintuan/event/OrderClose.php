<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\event;

use addon\pintuan\model\PintuanOrder;

/**
 * 订单关闭衍生
 */
class OrderClose
{

    /**
     * 活动展示
     * @param $param
     * @return array|mixed
     */
    public function handle($params)
    {
        if ($params[ 'promotion_type' ] == 'pintuan') {
            $pintuan_order = new PintuanOrder();
            $condition= array(
                ['order_id', '=', $params['order_id']]
            );
            $res = $pintuan_order->pintuanOrderClose($condition);
            return $res;
        }
    }
}