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
use think\facade\Log;

/**
 * 支付回调
 */
class PayNotify
{
    /**
     * 支付方式及配置
     */
    public function handle($param)
    {
        $reqData = empty($GLOBALS[ 'HTTP_RAW_POST_DATA' ]) ? file_get_contents('php://input') : $GLOBALS[ 'HTTP_RAW_POST_DATA' ];
        Log::write('微信支付回调数据');
        Log::write($reqData);
        return ( new PayModel() )->payNotify();
    }
}