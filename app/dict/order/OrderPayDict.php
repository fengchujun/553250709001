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
 * 订单支付公共属性
 */
class OrderPayDict
{
    const online_pay = 'ONLINE_PAY';
    const balance = 'BALANCE';
    const offline_pay = 'offlinepay';
    const point = 'POINT';

    /**
     * 订单支付方式
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = array(
            self::online_pay => '在线支付',
            self::balance => '余额支付',
            self::offline_pay => '线下支付',
            self::point => '积分兑换',
        );
        $list = array_merge($list, PayDict::getType());
        if($type) return $list[$type] ?? '';
        return $list;
    }
}
