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

use addon\wechatpay\model\Pay as PayModel;

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
        $is_weapp = $mch_info['is_weapp'] ?? 0;
        if($pay_type == 'wechatpay'){
            try {
                $pay_model = new PayModel($is_weapp, $params[ 'site_id' ]);
                $result = $pay_model->close($params);
                return $result;
            } catch (\Exception $e) {
                return error(-1, $e->getMessage());
            } catch (\Throwable $e) {
                return error(-1, $e->getMessage());
            }
        }
    }
}