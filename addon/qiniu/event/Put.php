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

use addon\qiniu\model\Qiniu;

/**
 * 云上传方式
 */
class Put
{
    /**
     * 短信发送方式方式及配置
     */
    public function handle($param)
    {
        $qiniu_model = new Qiniu();
        $result = $qiniu_model->putFile($param);
        return $result;
    }
}