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

use addon\alipay\model\Config as ConfigModel;
use addon\alipay\model\Pay as PayModel;
use addon\wechat\model\Config as WechatConfig;
use addon\wechatpay\model\Config;
use addon\wechatpay\model\V2;
use app\model\system\Pay;

/**
 * 查询支付结果
 */
class PayOrderQuery
{
    public function handle(array $params)
    {
        try {
            $res = success();
            $pay_info = ( new Pay() )->getInfo([ [ 'id', '=', $params[ 'relate_id' ] ] ])[ 'data' ];
            if (!empty($pay_info) && $pay_info['is_delete'] == 0) {
                $mch_info = json_decode($pay_info['mch_info'], true);
                $pay_type = $mch_info['pay_type'] ?? 'alipay';
                if($pay_type == 'alipay'){
                    $config_model = new ConfigModel();
                    $pay_config = $config_model->getPayConfig($pay_info[ 'site_id' ])[ 'data' ][ 'value' ];
                    if (!empty($pay_config) && $pay_config[ 'pay_status' ] != 2) {
                        $pay_common = new PayModel($pay_info[ 'site_id' ]);
                        $res = $pay_common->orderQuery($pay_info);
                    }
                }
            }
            return $res;
        }catch (\Throwable $e) {
            return error(-1, $e->getMessage());
        }
    }
}
