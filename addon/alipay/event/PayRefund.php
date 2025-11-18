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
 * 原路退款
 */
class PayRefund
{
    /**
     * 关闭支付
     */
    public function handle($params)
    {
        if ($params[ "pay_info" ][ "pay_type" ] == "alipay") {
            $mch_info = empty($params[ 'pay_info' ][ 'mch_info' ]) ? [] : json_decode($params[ 'pay_info' ][ 'mch_info' ], true);

            $pay_model = new PayModel($params[ 'site_id' ], $mch_info[ 'is_aliapp' ] ?? 0);
            $result = $pay_model->refund($params);
            return $result;
        }
    }
}