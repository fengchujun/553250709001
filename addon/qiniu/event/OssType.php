<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\qiniu\event;

/**
 * 云上传方式
 */
class OssType
{
    /**
     * 短信发送方式方式及配置
     */
    public function handle()
    {
        $info = array(
            "sms_type"      => "qiniu",
            "sms_type_name" => "七牛云上传",
            "edit_url"      => "qiniu://shop/config/config",
            "shop_url"      => "qiniu://shop/config/config",
            "desc"          => "七牛云上传,七牛提供10GB免费存储空间"
        );
        return $info;
    }
}