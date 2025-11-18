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
class CouponDict
{
    const normal = 1;
    const used = 2;
    const expire = 3;
    const close = 4;

    /**
     * 优惠券状态
     * @param $status
     * @return string|string[]
     */
    public static function getStatus($status = ''){
        $list = [
            self::normal => '待使用',
            self::used => '已使用',
            self::expire => '已过期',
            self::close => '已关闭',
        ];

        if($status) return $list[$status] ?? '';
        return $list;
    }

    const all = 1;
    const selected = 2;
    const selected_out = 3;
    const category_selected = 4;
    const category_selected_out = 5;
    public static function getGoodsType($type = ''){
        $list = [
            self::all => '全部商品参与',
            self::selected => '指定商品参与',
            self::selected_out => '指定商品不参与',
            self::category_selected => '指定分类参与',
            self::category_selected_out => '指定分类不参与',
        ];

        if($type) return $list[$type] ?? '';
        return $list;
    }

    const channel_all = 'all';
    const channel_online = 'online';
    const channel_offline = 'offline';

    /**
     * @param $type
     * @return string|string[]
     */
    public static function getUseChannelType($type = ''){
        $list = [
            self::channel_all => '线上线下使用',
            self::channel_online => '线上使用',
            self::channel_offline => '线下使用'
        ];

        if($type) return $list[$type] ?? '';
        return $list;
    }

    const store_all = 'all';
    const store_selected = '';

    public static function getUseStoreType($type = ''){
        $list = [
            self::store_all => '全部门店',
            self::store_selected => '部分门店',
        ];

        if($type) return $list[$type] ?? '';
        return $list;
    }


}
