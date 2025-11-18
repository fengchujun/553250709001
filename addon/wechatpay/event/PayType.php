<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\event;

use addon\wechatpay\model\Config;

/**
 * 支付方式  (前后台调用)
 */
class PayType
{
    /**
     * 支付方式及配置
     */
    public function handle($params)
    {
        $config_model = new Config();
        $config_result = $config_model->getPayConfig($params[ 'site_id' ] ?? 1);
        $config = $config_result[ "data" ][ "value" ] ?? [];
        $pay_status = $config[ "pay_status" ] ?? 0;

        $app_type = $params['app_type'] ?? '';
        if (!empty($app_type)) {
            $app_type_array = [ 'h5', 'wechat', 'weapp', 'pc' ];
            if (!in_array($app_type, $app_type_array)) {
                return '';
            }
            if ($pay_status == 0) {
                return '';
            }
        }
        $info = array (
            "pay_type" => "wechatpay",
            "pay_type_name" => "微信支付",
            "edit_url" => "wechatpay://shop/pay/config",
            "shop_url" => "wechatpay://shop/pay/config",
            "logo" => "addon/wechatpay/icon.png",
            "desc" => "微信支付，用户通过扫描二维码、微信内打开商品页面购买等多种方式调起微信支付模块完成支付。",
            "pay_status" => $pay_status,
        );
        return $info;

    }
}