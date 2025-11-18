<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\order;



use app\dict\pay\PayDict;

/**
 * 订单项公共属性
 */
class OrderGoodsDict
{
    const wait_delivery = 0;//待发货

    const delivery = 1;//已发货

    const delivery_finish = 2;

    /**
     * 作用于订单项上的配送状态
     * @param $status
     * @return string|string[]
     */
    public static function getDeliveryStatus($status = ''){
        $list = array(
            self::wait_delivery => '待发货',
            self::delivery => '已发货',
            self::delivery_finish => '已收货'
        );
        if($status !== '') return $list[$status] ?? '';
        return $list;
    }
}
