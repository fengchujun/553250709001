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

use app\model\system\Stat;

/**
 * 订单支付成功异步事件
 */
class OrderPayAfter
{
    /**
     * 传入订单信息
     * @param unknown $data
     */
    public function handle($data)
    {
        //添加统计
        $stat = new Stat();
        return $stat->switchStat([ 'type' => 'order_pay', 'data' => $data ]);
    }

}