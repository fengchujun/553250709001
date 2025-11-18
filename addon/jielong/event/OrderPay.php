<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace addon\jielong\event;

use addon\jielong\model\Jielong;
use app\model\order\OrderCommon;

/**
 * 订单支付后同步状态
 */
class OrderPay
{

    public function handle($params)
    {
        $order_id = $params['order_id'];
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]]);
        //操作接龙订单start
        $where = array(
            ['relate_order_id', '=', $params['order_id']],
            ['order_status', '=', OrderCommon::ORDER_CREATE],
        );
        model('promotion_jielong_order')->update([
            'order_status' => $order_info['order_status'],
            'order_status_name' => $order_info['order_status_name'],
            'order_status_action' => $order_info['order_status_action'],
            'pay_time' => time(),
            'pay_type' => $order_info['pay_type'],
            'pay_type_name' => $order_info['pay_type_name']
        ], $where);
        //操作接龙订单end
        return true;
    }
}