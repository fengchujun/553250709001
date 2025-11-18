<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\pay;

/**
 * 支付公共属性
 */
class PayDict
{
    //普通订单
    const wechatpay = 'wechatpay';
    const alipay = 'alipay';

    /**
     * 支付方式
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = array(
            self::wechatpay => '微信支付',
            self::alipay => '支付宝支付',
        );
        if($type) return $list[$type] ?? '';
        return $list;
    }
}
