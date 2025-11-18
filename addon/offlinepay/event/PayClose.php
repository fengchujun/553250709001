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
class PayClose
{
    /**
     * 关闭支付
     * @param $params
     * @return array
     */
    public function handle($params)
    {
        $mch_info = json_decode($params['mch_info'], true);
        $pay_type = $mch_info['pay_type'] ?? '';
        if($pay_type == PayModel::PAY_TYPE){
            $pay_model = new PayModel();
            $result = $pay_model->close([['out_trade_no', '=', $params['out_trade_no']]]);
            return $result;
        }
    }
}