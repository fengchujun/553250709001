<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\alioss\event;

use addon\alioss\model\Config;

/**
 * 关闭云上传
 */
class CloseOss
{
    public function handle()
    {
        $config_model = new Config();
        $result       = $config_model->modifyConfigIsUse(0);
        return $result;
    }
}