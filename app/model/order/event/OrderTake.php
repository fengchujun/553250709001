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

use app\dict\order\OrderDict;
use app\model\BaseModel;
use app\model\order\OrderCommon;
use app\model\order\OrderCron;
use app\model\order\OrderLog;
use app\model\order\VirtualOrder;
use think\db\exception\DbException;

/**
 * 订单交易设置
 */
class OrderTake extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array|void
     * @throws DbException
     */
    public function check($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], '*');
        if (empty($order_info))
            return $this->error([], 'ORDER_EMPTY');
        $order_common_model = new OrderCommon();
        $order_info['goods_num'] = numberFormat($order_info['goods_num']);
        $local_result = $this->verifyOrderLock($order_id);
        if ($local_result['code'] < 0)
            return $local_result;

        $order_status = $order_info['order_status'];
        $virtual_order_model = new VirtualOrder();
        if ($order_status == $order_common_model::ORDER_TAKE_DELIVERY || $order_status == $virtual_order_model::ORDER_VERIFYED) {
            // 虚拟商品无需确认收货
            return $this->error('', '该订单已收货！');
        }
        return $this->success();
    }


    public function event($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];

        $order_status = $data['order_status'];
        //改变订单状态
        $virtual_order_model = new VirtualOrder();
        $order_common_model = new OrderCommon();
        //todo  如果是虚拟商品并且有虚拟码的话, 订单状态应该为已使用
        $order_model = $order_common_model->getOrderModel($order_info);
        if ($order_status == $virtual_order_model::ORDER_WAIT_VERIFY) {
            $order_action_array = $order_common_model->getOrderCommonAction($order_model, $virtual_order_model::ORDER_VERIFYED);
        } else {
            $order_action_array = $order_common_model->getOrderCommonAction($order_model, $order_model::ORDER_TAKE_DELIVERY);
        }

        $order_data = array(
            'order_status' => $order_action_array['order_status'],
            'order_status_name' => $order_action_array['order_status_name'],
            'order_status_action' => $order_action_array['order_status_action'],
            'is_evaluate' => 1,
            'evaluate_status' => OrderDict::evaluate_wait,
            'evaluate_status_name' => OrderDict::getEvaluateStatus(OrderDict::evaluate_wait),
            'sign_time' => time()
        );
        model('order')->update($order_data, [['order_id', '=', $order_id]]);
        OrderCron::complete(['order_id' => $order_id, 'site_id' => $order_info['site_id']]);
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

        $order_action_array = $data['order_action_array'];
        $log_data = $data['data'];
        $order_common_model = new OrderCommon();
        //记录订单日志 start
        if ($log_data) {
            $action = '商家对订单进行了确认收货';
            if ($log_data['action_way'] == 1) {
                $member_info = model('member')->getInfo(['member_id' => $log_data['uid']], 'nickname');
                $buyer_name = empty($member_info['nickname']) ? '' : '【' . $member_info['nickname'] . '】';
                $log_data['nick_name'] = $buyer_name;
                $action = '买家确认收到货物';
            }
            $log_data = array_merge($log_data, [
                'order_id' => $order_info['order_id'],
                'action' => $action,
                'order_status' => $order_action_array['order_status'],
                'order_status_name' => $order_action_array['order_status_name'],
            ]);
            OrderLog::addOrderLog($log_data, $order_common_model);
        }
        return $this->success();
    }
}