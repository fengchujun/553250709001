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

use app\model\order\OrderCommon;

/**
 * 订单支付后同步状态
 */
class OrderClose
{

    public function handle($params)
    {
        $order_common_model = new OrderCommon();
        $close_status = OrderCommon::ORDER_CLOSE;
        /******************************************************* 接龙订单相关,建议移动到插件 **********************************************************/
        //更改接龙订单状态
        model('promotion_jielong_order')->update([
            'order_status' => $close_status,
            'order_status_name' => $order_common_model->order_status[$close_status]['name'],
            'order_status_action' => json_encode($order_common_model->order_status[$close_status], JSON_UNESCAPED_UNICODE),
            'close_time' => time(),
        ], [['relate_order_id', '=', $params['order_id']]]);
        return true;
    }
}