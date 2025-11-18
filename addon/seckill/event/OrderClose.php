<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\event;

use addon\seckill\model\SeckillOrder;

/**
 * 订单营销活动类型
 */
class OrderClose
{

    /**
     * 订单关闭
     * @param $params
     * @return array
     */
    public function handle($params)
    {
        $seckill = new SeckillOrder();
        $res = $seckill->orderClose($params[ 'order_id' ]);
        return $res;
    }

}