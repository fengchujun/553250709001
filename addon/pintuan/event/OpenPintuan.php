<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\pintuan\event;

use addon\pintuan\model\Pintuan;

/**
 * 启动活动
 */
class OpenPintuan
{

    public function handle($params)
    {
        $pintuan = new Pintuan();
        $res     = $pintuan->cronOpenPintuan($params['relate_id']);
        return $res;
    }
}