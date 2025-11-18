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
use app\model\system\Pay as PayCommon;

/**
 * 支付回调
 */
class PayNotify
{
    /**
     * 支付方式及配置
     */
    public function handle()
    {
        if (isset($_POST[ 'out_trade_no' ])) {
            $out_trade_no = $_POST[ 'out_trade_no' ];
            $pay = new PayCommon();
            $pay_info = $pay->getPayInfo($out_trade_no)[ 'data' ];
            if (empty($pay_info)) return false;

            if ($_POST[ 'total_amount' ] != $pay_info[ 'pay_money' ]) {
                return false;
            }
            $mch_info = empty($pay_info[ 'mch_info' ]) ? [] : json_decode($pay_info[ 'mch_info' ], true);

            $pay_model = new PayModel($pay_info[ 'site_id' ], $mch_info[ 'is_aliapp' ] ?? 0);
            $pay_model->payNotify();
        }
    }
}