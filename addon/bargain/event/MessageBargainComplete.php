<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\event;

use addon\bargain\model\Bargain;

class MessageBargainComplete
{
    public function handle($param)
    {
        //发送消息
        if ($param[ "keywords" ] == "BARGAIN_COMPLETE") {
            $model = new Bargain();
            $result = $model->bargainCompleteMessage($param);
            return $result;
        }
    }
}