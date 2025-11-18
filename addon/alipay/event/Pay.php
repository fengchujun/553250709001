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

use addon\alipay\model\Pay as PayModel;

/**
 * 生成支付
 */
class Pay
{
    /**
     * 支付方式及配置
     */
    public function handle($param)
    {
        if ($param[ "pay_type" ] == "alipay") {
            if (in_array($param[ "app_type" ], [ "h5", "app", "pc", "aliapp", 'wechat' ])) {
                $pay_model = new PayModel($param[ 'site_id' ], $param[ "app_type" ] == 'aliapp');
                $res = $pay_model->pay($param);
                return $res;
            }
        }
    }
}