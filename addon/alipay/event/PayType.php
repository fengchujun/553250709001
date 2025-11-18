<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\alipay\event;

use addon\alipay\model\Config;

/**
 * 支付方式  (后台调用)
 */
class PayType
{
    /**
     * 支付方式及配置
     */
    public function handle($param)
    {
        $config_model = new Config();
        $config_result = $config_model->getPayConfig($param[ 'site_id' ] ?? 1);
        $config = $config_result[ "data" ][ "value" ] ?? [];
        $pay_status = $config[ "pay_status" ] ?? 0;

        $app_type = $param['app_type'] ?? '';
        if (!empty($app_type)) {
            if (!in_array($app_type, [ "h5", "app", "pc", "aliapp", 'wechat' ])) {
                return '';
            }
            if ($app_type != 'aliapp' && $pay_status == 0) {
                return '';
            }
        }

        $info = array (
            "pay_type" => "alipay",
            "pay_type_name" => "支付宝支付",
            "edit_url" => "alipay://shop/pay/config",
            "shop_url" => "alipay://shop/pay/config",
            "logo" => "addon/alipay/icon.png",
            "desc" => "支付宝网站(www.alipay.com) 是国内先进的网上支付平台。",
            "pay_status" => $pay_status,
        );
        return $info;
    }
}