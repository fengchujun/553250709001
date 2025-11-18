<?php
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
use GuzzleHttp\Exception\GuzzleException;

class MessageOrderRefundApply
{
    /**
     * @param $param
     * @return void|null
     * @throws GuzzleException
     */
    public function handle($param)
    {
        // 发送订单消息
        if ($param["keywords"] == "BUYER_REFUND") {
            $model = new OrderMessage();
            return $model->messageOrderRefundApply($param);
        }
    }

}