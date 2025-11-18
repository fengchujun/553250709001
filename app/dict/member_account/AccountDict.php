<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\member_account;


/**
 * 账户公共属性
 */
class AccountDict
{
    const balance = 'balance';
    const balance_money = 'balance_money';
    const point = 'point';
    const growth = 'growth';


    /**
     * 账户类型
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = array(
            self::balance => '储值余额',
            self::balance_money => '现金余额',
            self::point => '积分',
            self::growth => '成长值'
        );
        if(!$type) return $list[$type] ?? '';
        return $list;
    }
}
