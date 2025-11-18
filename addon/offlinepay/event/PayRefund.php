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

use addon\offlinepay\model\Pay as PayModel;

/**
 * 关闭支付
 */
class PayRefund
{
    /**
     * 关闭支付
     * @param $params
     * @return array
     */
    public function handle($params)
    {
        if ($params[ "pay_info" ][ "pay_type" ] == PayModel::PAY_TYPE) {
            $pay_model = new PayModel();
            return $pay_model->refund($params['pay_info']['out_trade_no'], $params['refund_fee']);
        }
    }
}