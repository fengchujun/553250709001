<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\message;

use app\model\order\OrderMessage;


/**
 * 外卖订单 指定配送员后 同步短信推送
 */
class MessageLocalWaitDelivery
{

    public function handle($param)
    {
        //发送订单消息
        if ($param["keywords"] == "MESSAGE_LOCAL_WAIT_DELIVERY") {
            $model = new OrderMessage();
            return $model->messageLocalWaitDelivery($param);
        }

    }

}