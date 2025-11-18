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

use addon\wechat\model\Config as WechatConfig;
use addon\wechatpay\model\Config;
use addon\wechatpay\model\Pay as PayModel;
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
            $pay_info = ( new Pay() )->getInfo([ [ 'id', '=', $params[ 'relate_id' ] ]])[ 'data' ];
            if (!empty($pay_info) && $pay_info['is_delete'] == 0) {
                $mch_info = json_decode($pay_info['mch_info'], true);
                $pay_type = $mch_info['pay_type'] ?? 'wechatpay';
                if($pay_type == 'wechatpay'){
                    $pay_config = ( new Config() )->getPayConfig($pay_info[ 'site_id' ])[ 'data' ][ 'value' ];
                    $wechat_config = ( new WechatConfig() )->getWechatConfig($pay_info[ 'site_id' ])[ 'data' ][ 'value' ];
                    $pay_config[ 'appid' ] = $wechat_config[ 'appid' ] ?? '';
                    if (!empty($pay_config) && $pay_config[ 'pay_status' ] == 1) {
                        $res = ( new V2($pay_config) )->orderQuery($pay_info);
                    }
                }
            }
            return $res;
        }catch (\Throwable $e) {
            return error(-1, $e->getMessage());
        }

    }
}
