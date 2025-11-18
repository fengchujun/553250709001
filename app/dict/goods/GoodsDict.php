<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\goods;


/**
 * 商品公共属性
 */
class GoodsDict
{

    const real = 1;
    const virtual = 2;
    const virtualcard = 3;
    const service = 4;
    const card = 5;
    const weigh = 6;

    /**
     * 商品类型
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = [
            self::real => '实物商品',
            self::virtual => '虚拟商品',
            self::virtualcard => '电子卡密',
            self::service => '服务项目',
            self::card => '卡项套餐',
            self::weigh => '称重商品',
        ];
        //todo 插件商品类型应该用钩子获取
        $temp_list = array_filter(event('GetGoodsClass'));
        if(!empty($temp_list)){
            foreach($temp_list as $v){
                $list = array_merge($list, $v);
            }
        }
        if($type) return $list[$type] ?? '';
        return $list;
    }

    const virtual_auto_deliver = 'auto_deliver';
    const virtual_artificial_deliver = 'artificial_deliver';
    const virtual_verify = 'verify';

    /**
     * 获取虚拟商品发货方式
     * @param $type
     * @return void
     */
    public static function getVirtualDeliverType($type = ''){
        $list = [
            self::virtual_auto_deliver => '自动发货',
            self::virtual_artificial_deliver => '手动发货',
            self::virtual_verify => '到店核销',
        ];
        //todo 插件商品类型应该用钩子获取
        if($type) return $list[$type] ?? '';
        return $list;
    }

    const service_permanent = 0;
    const service_day = 1;
    const service_day_expire = 2;

    public static function getVerifyValidityType($type = ''){
        $list = [
            self::service_permanent => '永久',
            self::service_day => '指定几日内有效',
            self::service_day_expire => '指定日期过期',
        ];
        //todo 插件商品类型应该用钩子获取
        if($type) return $list[$type] ?? '';
        return $list;
    }

}
