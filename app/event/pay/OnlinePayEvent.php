<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\event\pay;

use app\model\system\Pay;

/**
 * 支付回调事件
 * @author Administrator
 *
 */
class OnlinePayEvent
{
    public function handle($param)
    {
        return (new Pay)->onlinePayEvent($param['relate_id'], [], 4);
    }
}
