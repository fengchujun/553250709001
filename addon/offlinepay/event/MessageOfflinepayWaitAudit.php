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

class MessageOfflinepayWaitAudit
{
    public function handle($param)
    {
        if ($param["keywords"] == "OFFLINEPAY_WAIT_AUDIT") {
            $pay_model = new PayModel();
            return $pay_model->messageWaitAudit($param);
        }
    }
}