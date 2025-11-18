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
use app\model\message\Message;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;

/**
 * 订单完成设置
 */
class OrderComplete extends BaseModel
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
        /******************************************************* 校验订单锁定相关 **********************************************************/
        $lock_result = (new OrderCommon())->verifyOrderLock($order_id);
        if ($lock_result['code'] < 0)
            return $lock_result;

        return $this->success();
    }


    public function event($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];
        /******************************************************* 会员账户相关 **********************************************************/
        //修改用户表order_complete_money和order_complete_num
        model('member')->setInc([['member_id', '=', $order_info['member_id']]], 'order_complete_money', $order_info['order_money'] - $order_info['refund_money']);
        model('member')->setInc([['member_id', '=', $order_info['member_id']]], 'order_complete_num');

        /******************************************************* 插件相关 **********************************************************/
        event('OrderComplete', ['order_id' => $order_id]);
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
        $order_id = $order_info['order_id'];
        $site_id = $order_info['site_id'];

        /******************************************************* 日志相关 **********************************************************/
        $log_data = array(
            'order_id' => $order_id,
            'action' => 'complete',
            'site_id' => $site_id,
            'is_auto' => 1,// todo 当前业务默认是系统任务完成订单
        );
        (new OrderLog())->addLog($log_data);
        /******************************************************* 消息相关 **********************************************************/
        $message_model = new Message();
        //订单完成
        $message_model->sendMessage(['keywords' => 'ORDER_COMPLETE', 'order_id' => $order_id, 'site_id' => $site_id]);
        // 买家订单完成通知商家
        $message_model->sendMessage(['keywords' => 'BUYER_ORDER_COMPLETE', 'order_id' => $order_id, 'site_id' => $site_id]);

        return $this->success();
    }
}