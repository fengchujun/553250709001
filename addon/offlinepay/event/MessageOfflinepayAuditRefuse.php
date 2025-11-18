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

class MessageOfflinepayAuditRefuse
{
    public function handle($param)
    {
        if ($param["keywords"] == "OFFLINEPAY_AUDIT_REFUSE") {
            $pay_model = new PayModel();
            return $pay_model->messageAuditRefuse($param);
        }
    }
}