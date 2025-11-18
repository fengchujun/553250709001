<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\offlinepay\model;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 线下支付配置
 */
class Config extends BaseModel
{
    /**
     * 设置支付配置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setPayConfig($data, $site_id = 0, $app_module = 'shop')
    {
        $data = $this->handleConfigData($data);
        $config = new ConfigModel();
        $res = $config->setConfig($data, '线下支付配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'OFFLINE_PAY_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取支付配置
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getPayConfig($site_id = 0, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'OFFLINE_PAY_CONFIG' ] ]);
        $res['data']['value'] = $this->handleConfigData($res['data']['value']);
        return $res;
    }

    /**
     * 处理配置数据
     * @param $data
     * @return mixed
     */
    protected function handleConfigData($data)
    {
        $default_config = [
            'pay_status' => 0,//支付状态
            'bank' => [
                'status' => 0,//是否开启
                'bank_name' => '',//银行名称
                'account_name' => '',//账户名称
                'account_number' => '',//账号
                'branch_name' => '',//支行名称
            ],
            'wechat' => [
                'status' => 0,//是否开启
                'account_name' => '',//账户名称
                'payment_code' => '',//收款码
            ],
            'alipay' => [
                'status' => 0,//是否开启
                'account_name' => '',//账户名称
                'payment_code' => '',//收款码
            ],
        ];
        return assignData($data, $default_config);
    }
}