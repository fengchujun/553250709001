<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\alipay\model;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 支付宝支付配置
 */
class Config extends BaseModel
{

    private $encrypt = '******';

    /**
     * 设置支付配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setPayConfig($data, $site_id = 0, $app_module = 'shop')
    {
        $config = new ConfigModel();

        // 未加密前的数据
        $original_config = $this->getPayConfig($site_id)[ 'data' ][ 'value' ];

        // 检测数据是否发生变化，如果没有变化，则保持未加密前的数据
        if (!empty($data[ 'private_key' ]) && $data[ 'private_key' ] == $this->encrypt) {
            $data[ 'private_key' ] = $original_config[ 'private_key' ]; // 应用私钥
        }
        if (!empty($data[ 'public_key' ]) && $data[ 'public_key' ] == $this->encrypt) {
            $data[ 'public_key' ] = $original_config[ 'public_key' ]; // 应用公钥
        }
        if (!empty($data[ 'alipay_public_key' ]) && $data[ 'alipay_public_key' ] == $this->encrypt) {
            $data[ 'alipay_public_key' ] = $original_config[ 'alipay_public_key' ]; // 支付宝公钥
        }
        if (!empty($data[ 'public_key_crt' ]) && $data[ 'public_key_crt' ] == $this->encrypt) {
            $data[ 'public_key_crt' ] = $original_config[ 'public_key_crt' ]; // 应用公钥证书
        }
        if (!empty($data[ 'alipay_public_key_crt' ]) && $data[ 'alipay_public_key_crt' ] == $this->encrypt) {
            $data[ 'alipay_public_key_crt' ] = $original_config[ 'alipay_public_key_crt' ]; // 支付宝公钥证书
        }
        if (!empty($data[ 'alipay_with_crt' ]) && $data[ 'alipay_with_crt' ] == $this->encrypt) {
            $data[ 'alipay_with_crt' ] = $original_config[ 'alipay_with_crt' ]; // 支付宝根证书
        }

        $res = $config->setConfig($data, '支付宝支付配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'ALI_PAY_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取支付配置
     * @param int $site_id
     * @param string $app_module
     * @param bool $need_encrypt 是否需要加密数据，true：加密、false：不加密
     * @return array
     */
    public function getPayConfig($site_id = 0, $app_module = 'shop', $need_encrypt = false)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'ALI_PAY_CONFIG' ] ]);
        if (!empty($res[ 'data' ][ 'value' ]) && $need_encrypt) {
            // 加密敏感信息
            if (!empty($res[ 'data' ][ 'value' ][ 'private_key' ])) {
                $res[ 'data' ][ 'value' ][ 'private_key' ] = $this->encrypt; // 应用私钥
            }
            if (!empty($res[ 'data' ][ 'value' ][ 'public_key' ])) {
                $res[ 'data' ][ 'value' ][ 'public_key' ] = $this->encrypt; // 应用公钥
            }
            if (!empty($res[ 'data' ][ 'value' ][ 'alipay_public_key' ])) {
                $res[ 'data' ][ 'value' ][ 'alipay_public_key' ] = $this->encrypt; // 支付宝公钥
            }
            if (!empty($res[ 'data' ][ 'value' ][ 'public_key_crt' ])) {
                $res[ 'data' ][ 'value' ][ 'public_key_crt' ] = $this->encrypt; // 应用公钥证书
            }
            if (!empty($res[ 'data' ][ 'value' ][ 'alipay_public_key_crt' ])) {
                $res[ 'data' ][ 'value' ][ 'alipay_public_key_crt' ] = $this->encrypt; // 支付宝公钥证书
            }
            if (!empty($res[ 'data' ][ 'value' ][ 'alipay_with_crt' ])) {
                $res[ 'data' ][ 'value' ][ 'alipay_with_crt' ] = $this->encrypt; // 支付宝根证书
            }
        }
        return $res;
    }
}