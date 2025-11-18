<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\event;

use app\model\BaseModel;

/**
 * 订单发货设置
 */
class OrderDelivery extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array
     */
    public function check($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];

        return $this->success();
    }


    public function event($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];


        return $this->success();
    }

    /**
     * 后续事件
     * @param $data
     * @return array
     */
    public function after($data)
    {
        $order_info = $data['order_info'];

        return $this->success();
    }
}