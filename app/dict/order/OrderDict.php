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


/**
 * 订单公共属性
 */
class OrderDict
{
    //普通订单
    const express = 1;
    const store = 2;
    const local = 3;
    const virtual = 4;

    const cashier = 5;

    /**
     * 订单类型
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = [
            self::express => '物流订单',
            self::store => '自提订单',
            self::local => '外卖订单',
            self::virtual => '虚拟订单',
            self::cashier => '收银订单',
        ];
        $temp_list = array_filter(event('GetOrderType'));
        if(!empty($temp_list)){
            foreach($temp_list as $k => $v){
                $list = array_merge($list, $v);
            }
        }
        if($type) return $list[$type] ?? '';
        return $list;
    }

    const scene_online = 'online';
    const scene_cashier = 'cashier';

    /**
     * 订单创建场景
     * @param $type
     * @return string|string[]
     */
    public static function getOrderScene($type = ''){
        $list = [
            self::scene_online => '线上订单',
            self::scene_cashier => '自收银台订单',
        ];
        if($type) return $list[$type] ?? '';
        return $list;
    }

    const evaluate_wait = 0;
    const evaluated = 1;
    const evaluate_again = 2;
    /**
     * 订单评价状态
     * @param $status
     * @return string|string[]
     */
    public static function getEvaluateStatus($status = ''){
        $list = [
            self::evaluate_wait => '待评价',
            self::evaluated => '已评价',
            self::evaluate_again => '已追评',
        ];
        if($status !== '') return $list[$status] ?? '';
        return $list;
    }
}
