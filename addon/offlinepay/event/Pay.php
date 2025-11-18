<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\offlinepay\event;

use app\model\system\Pay as PayModel;

/**
 * 生成支付
 */
class Pay
{
    /**
     * 支付
     */
    public function handle($params)
    {
        if ($params[ "pay_type" ] == "offlinepay") {
            $pay_model = new PayModel();
            $clear_res = $pay_model->clearMchPay($params[ "out_trade_no" ], 'offlinepay');
            if($clear_res['code'] < 0) return $clear_res;
            return success();
        }
    }
}