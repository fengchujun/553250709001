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

use addon\alipay\model\Pay;
use addon\alipay\model\Config;

class PayTransfer
{
    public function handle(array $params)
    {
        if ($params[ 'transfer_type' ] == 'alipay') {
            $pay = new Pay($params[ 'site_id' ]);

            $config_model = new Config();
            $config_result = $config_model->getPayConfig($params[ 'site_id' ]);
            $config = $config_result[ "data" ];
            if (!empty($config[ 'value' ])) {
                $config_info = $config[ "value" ];
                $countersign_type = $config_info['countersign_type'] ?? 0;
                if ($countersign_type == 0) {
                    $res = $pay->payTransfer($params);
                    return $res;
                } else {
                    $res = $pay->payNewTransfer($params);
                    return $res;
                }
            } else {
                $res = $pay->payTransfer($params);
                return $res;
            }
        }
    }
}