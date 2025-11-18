<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\coupon\dict;


/**
 * 订单公共属性
 */
class CouponTypeDict
{
    const normal = 1;
    const expire = 2;
    const close = -1;
    /**
     * 优惠券状态
     * @param $status
     * @return string|string[]
     */
    public static function getStatus($status = ''){
        $list = [
            self::normal => '进行中',
            self::expire => '已结束',
            self::close => '已关闭',
        ];

        if($status) return $list[$status] ?? '';
        return $list;
    }




}
