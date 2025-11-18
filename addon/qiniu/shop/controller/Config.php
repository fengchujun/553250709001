<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\qiniu\shop\controller;

use addon\qiniu\model\Config as ConfigModel;
use app\shop\controller\BaseShop;

/**
 * 七牛云上传管理
 */
class Config extends BaseShop
{

    /**
     * 云上传配置
     * @return mixed
     */
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $bucket     = input('bucket', '');
            $access_key = input('access_key', '');
            $secret_key = input('secret_key', '');
            $domain     = input('domain', '');
            $status     = input('status', 0);
            $data       = array(
                'bucket' => $bucket,
                'access_key' => $access_key,
                'secret_key' => $secret_key,
                'domain' => $domain,
            );

            $result = $config_model->setQiniuConfig($data, $status, $this->site_id, $this->app_module);
            return $result;
        } else {
            $info_result = $config_model->getQiniuConfig($this->site_id, $this->app_module);
            $info        = $info_result['data'];
            $this->assign('info', $info);
            return $this->fetch('config/config');
        }
    }
}