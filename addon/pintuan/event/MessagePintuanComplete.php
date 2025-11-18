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

class MessagePintuanComplete
{

    public function handle($param)
    {
        //发送消息
        if ($param[ "keywords" ] == "PINTUAN_COMPLETE") {
            $model = new Pintuan();
            $result = $model->pintuanCompleteMessage($param);
            return $result;
        }
    }
}