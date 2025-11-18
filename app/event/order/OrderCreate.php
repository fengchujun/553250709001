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

use app\model\goods\Cart;
use app\model\system\Stat;

/**
 * 订单支付后店铺点单计算
 */
class OrderCreate
{
    /**
     * 订单创建后事件
     * @param unknown $data
     */
    public function handle($data)
    {
        /** @var \app\model\order\OrderCreate $order_object */
        $order_data = $data['create_data'];

        $site_id = $order_data['site_id'];
        $order_id = $order_data['order_id'];
        $member_id = $order_data['member_id'];
        //添加统计
        $stat = new Stat();
        return $stat->switchStat([ 'type' => 'order_create', 'data' => [
            'site_id' => $site_id,
            'order_id' => $order_id,
            'member_id' => $member_id,
            'order_data' => $order_data
        ] ]);
    }

}