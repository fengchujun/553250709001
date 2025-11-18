<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use addon\coupon\model\Coupon;
use addon\presale\model\PresaleOrder;
use app\dict\member_account\AccountDict;
use app\dict\order\OrderDict;
use app\dict\order\OrderGoodsDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\member\Member;
use app\model\member\MemberAccount;
use app\model\order\Order as OrderModel;
use app\model\order\orderrefund\Apply;
use app\model\order\orderrefund\Cancel;
use app\model\order\orderrefund\Close;
use app\model\order\orderrefund\Confirm;
use app\model\order\orderrefund\Delivery;
use app\model\order\orderrefund\Finish;
use app\model\order\orderrefund\FinishAction;
use app\model\order\orderrefund\Refuse;
use app\model\order\orderrefund\TakeDelivery;
use app\model\order\orderrefund\ActiveRefund;
use app\model\shop\Shop;
use app\model\shop\SiteAddress;
use app\model\system\Pay;
use app\model\system\Stat;
use Exception;
use think\facade\Log;
use think\facade\Queue;

/**
 * 订单退款
 *
 * @author Administrator
 *
 */
class OrderRefund extends BaseModel
{
    /*********************************************************************************订单退款属性*****************************************************/


    //已完成
    public const REFUND_COMPLETE = 3;

    /**
     * 根据配送状态获取退款方式
     * @param $order_id
     * @return array
     */
    public function getRefundOrderType($order_id)
    {
        $status = model('order')->getInfo([['order_id', '=', $order_id]], 'delivery_status');
        if ($status['delivery_status'] == OrderGoodsDict::wait_delivery) {
            return [OrderRefundDict::ONLY_REFUNDS];
        } else {
            return [OrderRefundDict::ONLY_REFUNDS, OrderRefundDict::A_REFUND_RETURN];
        }
    }


    /**
     * 根据配送状态获取退款方式
     * @param $order_goods_info
     * @return array
     */
    public function getRefundType($order_goods_info)
    {
        if ($order_goods_info['is_virtual'] == 1) {
            return [OrderRefundDict::ONLY_REFUNDS];
        } else {
            if ($order_goods_info['delivery_status'] == OrderGoodsDict::wait_delivery) {
                return [OrderRefundDict::ONLY_REFUNDS];
            } else {
                return [OrderRefundDict::ONLY_REFUNDS, OrderRefundDict::A_REFUND_RETURN];
            }
        }

    }

    /**
     * 获取退款金额
     * @param $order_goods_ids
     * @return array
     */
    public function getOrderRefundMoney($order_goods_ids)
    {
        //订单商品项
        $order_goods_ids = (string)$order_goods_ids;
        $order_goods_lists = model('order_goods')->getList([[
            'order_goods_id', 'in', is_array($order_goods_ids) ? $order_goods_ids : (string)$order_goods_ids
        ]]);
        if (empty($order_goods_lists)) return $this->error(null, '未查询到订单商品！');
        $order_id = $order_goods_lists[0]['order_id'];

        //退款状态检测 只有未申请的可以发起退款
        foreach ($order_goods_lists as $val) {
            if (!in_array($val['refund_status'], [OrderRefundDict::REFUND_NOT_APPLY, OrderRefundDict::REFUND_DIEAGREE, OrderRefundDict::PARTIAL_REFUND])) {
                return $this->error(null, '订单商品退款状态有误！');
            }
        }

        //剩余未申请退款的订单商品统计
        $not_apply_count = model('order_goods')->getCount([
            ['order_id', '=', $order_id],
            ['order_goods_id', 'not in', $order_goods_ids],
            ['refund_status', 'in', [OrderRefundDict::REFUND_NOT_APPLY, OrderRefundDict::REFUND_DIEAGREE]],
        ], 'order_goods_id');
        //有退过运费的订单商品统计
        $refund_delivery_count = model('order_goods')->getCount([
            ['order_id', '=', $order_id],
            ['order_goods_id', 'not in', $order_goods_ids],
            ['refund_delivery_money', '>', 0],
        ], 'order_goods_id');

        //如果还有未申请退款的商品就不退运费 发票 和发票运费
        if ($not_apply_count > 0) {
            $delivery_money = 0;
            $invoice_delivery_money = 0;
            $invoice_money = 0;
        } else {
            $order_info = model('order')->getInfo([
                ['order_id', '=', $order_id],
            ], '*');
            if ($refund_delivery_count == 0) {
                $delivery_money = $order_info['delivery_money'];
            } else {
                $delivery_money = 0;
            }
            $invoice_delivery_money = $order_info['invoice_delivery_money'];
            $invoice_money = $order_info['invoice_money'];
        }

        //计算实际退款金额，商家主动退款金额部分不可再退
        $refund_money = 0;
        foreach ($order_goods_lists as $item) {
            $refund_money += $item['real_goods_money'] - $item['shop_active_refund_money'];
        }
        $refund_money += $delivery_money + $invoice_delivery_money + $invoice_money;
        return [
            'refund_money' => round($refund_money, 2),
            'refund_delivery_money' => round($delivery_money, 2)
        ];
    }
    /************************************************************************* 操作事件 ***********************************************************/

    /**
     * 退款操作检测
     * @param $status
     * @param $role
     * @param $event
     * @return bool
     */
    public function refundActionCheck($status, $role, $event)
    {
        $action_config = [
            'shop' => 'action',
            'buyer' => 'member_action',
        ];
        $action_key = $action_config[$role] ?? '';
        $refund_status_data = OrderRefundDict::getStatus($status);
        $action_list = $refund_status_data[$action_key] ?? [];
        $exist = false;
        foreach ($action_list as $action_info) {
            if ($action_info['event'] == $event) {
                $exist = true;
                break;
            }
        }
        return $exist;
    }

    /**
     * 卖家确认收到退货
     * @param array $data 退货信息
     * @param $user_info
     * @return array
     */
    public function orderRefundTakeDelivery($data, $user_info)
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'shop', 'orderRefundTakeDelivery')) {
            return $this->error(null, '当前状态不可操作');
        }
        model('order_goods')->startTrans();
        try {
            $refund_status = OrderRefundDict::REFUND_TAKEDELIVERY;
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $data['refund_status'] = $refund_status;
            $data['refund_status_name'] = $refund_status_data['name'];
            $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
            model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);

            model('order_goods')->commit();
            $param = [
                'order_goods_info' => $order_goods_info,
                'refund_status' => $refund_status,
                'user_info' => $user_info
            ];
            //收货后事件
            TakeDelivery::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 会员申请退款
     * @param $data
     * @param $member_info
     * @param array $log_data
     * @return array
     */
    public function apply($data, $member_info, $log_data = [])
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id], '*');
        if (empty($order_goods_info))
            return $this->error([], '订单项不存在！');
        $event = 'orderRefundApply';
        if ($order_goods_info['refund_status'] == OrderRefundDict::REFUND_DIEAGREE) {
            $event = 'orderRefundAsk';
        }
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'buyer', $event)) {
            return $this->error([], '当前状态不可操作');
        }
        $refund_type_list = $this->getRefundType($order_goods_info);
        //防止退款方式越权
        if (!in_array($data['refund_type'], $refund_type_list))
            return $this->error([], '退款方式不符合！');

        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']]);
        if (empty($order_info)) return $this->error([], '订单不存在！');
        $param = [
            'order_info' => $order_info,
            'order_goods_info' => $order_goods_info,
            'member_info' => $member_info,
            'log_data' => $log_data
        ];
        //校验是否可以申请退款
        Apply::check($param);
        $refund_status = OrderRefundDict::REFUND_APPLY;
        $refund_status_data = OrderRefundDict::getStatus($refund_status);
        $data['refund_status'] = $refund_status;
        $data['refund_status_name'] = $refund_status_data['name'];
        $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
        $data['refund_mode'] = $order_info['order_status'] == Order::ORDER_COMPLETE ? OrderRefundDict::after_sales : OrderRefundDict::refund;

        $pay_model = new Pay();
        $data['refund_no'] = $pay_model->createRefundNo();
        $data['refund_action_time'] = time();
        $refund_apply_money_array = $this->getOrderRefundMoney($order_goods_id);//可退款金额 通过计算获得
        $refund_apply_money = $refund_apply_money_array['refund_money'];
        $refund_delivery_money = $refund_apply_money_array['refund_delivery_money'];
        $data['refund_apply_money'] = $refund_apply_money;//申请的总退款
        $data['refund_delivery_money'] = $refund_delivery_money;//退的运费
        $data['is_refund_stock'] = 0;//初始化为不退库存

        model('order_goods')->startTrans();
        try {

            $res = model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);
            //退款需要操作的事件
            Apply::event($param);
            model('order_goods')->commit();
            //退款之后的事件
            $param['refund_log_data'] = [
                $order_goods_info['order_goods_id'],
                OrderRefundDict::REFUND_APPLY,
                '买家申请退款',
                1,
                $member_info['member_id'],
                $member_info['nickname']
            ];
//            Queue::push('app\job\order_refund\OrderRefundApplyAfter', $param);
            Apply::after($param);



            return $this->success($res);
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 用户撤销退款申请
     * @param $data
     * @param $member_info
     * @param array $log_data
     * @return array
     */
    public function cancel($data, $member_info, $log_data = [])
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'buyer', 'orderRefundCancel')) {
            return $this->error([], '当前状态不可操作');
        }
        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']]);
        if (empty($order_info)) return $this->error([], '订单不存在！');
        $param = [
            'order_info' => $order_info,
            'order_goods_info' => $order_goods_info,
            'log_data' => $log_data,
            'member_info' => $member_info
        ];
        model('order_goods')->startTrans();
        try {
            //如果有主动退款撤销后是变为部分退款状态
            if ($order_goods_info['shop_active_refund'] == 1) {
                $refund_status = OrderRefundDict::PARTIAL_REFUND;
                $refund_type = OrderRefundDict::ONLY_REFUNDS;
            } else {
                $refund_status = OrderRefundDict::REFUND_NOT_APPLY;
                $refund_type = 0;
            }
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $data['refund_status'] = $refund_status;
            $data['refund_status_name'] = $refund_status_data['name'];
            $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
            $data['refund_type'] = $refund_type;
            //重置部分字段
            $data['refund_apply_money'] = 0;
            $data['refund_address'] = '';
            $data['refund_delivery_remark'] = '';
            $data['refund_remark'] = '';
            $data['refund_delivery_name'] = '';
            $data['refund_delivery_no'] = '';
            $data['refund_reason'] = '';
            model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);
            //订单项退款取消的事件
            Cancel::event($param);
            model('order_goods')->commit();
            //订单取消后后续事件
//            Queue::push('app\job\order_refund\OrderRefundCancelAfter', $param);
            Cancel::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }




    /****************************************************************************订单退款相关操作（开始）**********************************/

    /**
     * 卖家确认退款
     * @param $data
     * @param $user_info
     * @return array
     */
    public function orderRefundConfirm($data, $user_info)
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id]);
        if (empty($order_goods_info)) {
            return $this->error([], '订单项不存在！');
        }
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'shop', 'orderRefundAgree')) {
            return $this->error([], '当前状态不可操作');
        }
        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']], '*');
        if (empty($order_info)) return $this->error([], '订单不存在！');
        model('order_goods')->startTrans();
        try {
            if ($order_goods_info['refund_type'] == OrderRefundDict::ONLY_REFUNDS) {
                $data['refund_status'] = OrderRefundDict::REFUND_CONFIRM;  //确认等待转账
            } else {
                $data['refund_status'] = OrderRefundDict::REFUND_WAIT_DELIVERY;  //确认等待买家发货
            }
            $refund_status_data = OrderRefundDict::getStatus($data['refund_status']);
            $data['refund_status_name'] = $refund_status_data['name'];
            $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
            $res = model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);
            model('order_goods')->commit();
            //订单退款同意后事件
            $param = [
                'order_info' => $order_info,
                'order_goods_info' => $order_goods_info,
                'refund_status' => $data['refund_status'],
                'user_info' => $user_info
            ];
//            Queue::push('app\job\order_refund\OrderRefundConfirmAfter', $param);
            Confirm::after($param);
            return $this->success($res);
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 卖家拒绝退款
     * @param $data
     * @param $user_info
     * @param $refund_refuse_reason
     * @param array $log_data
     * @return array
     */
    public function orderRefundRefuse($data, $user_info, $refund_refuse_reason, $log_data = [])
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $order_goods_id]]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'shop', 'orderRefundRefuse')) {
            return $this->error([], '当前状态不可操作');
        }
        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']], '*');
        if (empty($order_info)) return $this->error([], '订单不存在！');

        $refund_status = OrderRefundDict::REFUND_DIEAGREE;
        $refund_status_data = OrderRefundDict::getStatus($refund_status);
        $data['refund_status'] = $refund_status;
        $data['refund_status_name'] = $refund_status_data['name'];
        $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
        $data['refund_refuse_reason'] = $refund_refuse_reason;

        $data['refund_action_time'] = time();

        $param = [
            'order_info' => $order_info,
            'order_goods_info' => $order_goods_info,
            'refund_status' => $refund_status,
            'user_info' => $user_info
        ];
        //校验定的那项是否可以退款
        Refuse::check($param);
        model('order_goods')->startTrans();
        try {

            model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);
            //拒绝的关联事件
            Refuse::event($param);

            model('order_goods')->commit();
            $param['refund_status'] = $data['refund_status'];
            $param['refund_refuse_reason'] = $refund_refuse_reason;
            $param['log_data'] = $log_data;
            //拒绝后事件
            Refuse::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 买家退货
     * @param array $data 退货信息
     * @param array $member_info 会员信息
     */
    public function orderRefundDelivery($data, $member_info)
    {
        $order_goods_id = $data['order_goods_id'] ?? 0;
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id]);
        if (empty($order_goods_info)) {
            return $this->error([], '订单项不存在！');
        }
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'buyer', 'orderRefundDelivery')) {
            return $this->error([], '当前状态不可操作');
        }
        model('order_goods')->startTrans();
        try {
            $refund_status = OrderRefundDict::REFUND_WAIT_TAKEDELIVERY;
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $data['refund_status'] = $refund_status;
            $data['refund_status_name'] = $refund_status_data['name'];
            $data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);

            $refund_address = $this->getRefundAddress($order_goods_info['site_id']);
            $data['refund_address'] = $refund_address['shop_address'];
            model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);

            model('order_goods')->commit();
            $param = [
                'order_goods_info' => $order_goods_info,
                'refund_status' => $data['refund_status'],
                'member_info' => $member_info,
                'refund_delivery_name' => $data['refund_delivery_name'],
                'refund_delivery_no' => $data['refund_delivery_no'],
            ];
            //退货后事件
            Delivery::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 查询退货地址
     * @param $site_id
     * @param int $refund_address_id
     * @return array
     */
    public function getRefundAddress($site_id, $refund_address_id = 0)
    {
        $address = [];
        $site_address_model = new SiteAddress();
        $site_address_condition = [
            ['site_id', '=', $site_id],
            ['is_return', '=', 1],
        ];
        if ($refund_address_id > 0) {
            $site_address_condition[] = ['id', '=', $refund_address_id];
        } else {
            $site_address_condition[] = ['is_return_default', '=', 1];
        }
        $site_address_info = $site_address_model->getAddressInfo($site_address_condition)['data'] ?? [];
        if (empty($site_address_info)) {
            unset($site_address_condition[2]);
            $site_address_info = $site_address_model->getAddressInfo($site_address_condition)['data'] ?? [];
        }
        if (empty($site_address_info)) {
            $shop_model = new Shop();
            $shop_info_result = $shop_model->getShopInfo([['site_id', '=', $site_id]], 'full_address,address,name,mobile');
            $shop_info = $shop_info_result['data'];
            $address['shop_contacts'] = $shop_info['name'];
            $address['shop_mobile'] = $shop_info['mobile'];
            $address['shop_address'] = $shop_info['full_address'] . $shop_info['address'];
        }
        if (!empty($site_address_info)) {
            $address['shop_contacts'] = $site_address_info['contact_name'];
            $address['shop_mobile'] = $site_address_info['mobile'];
            $address['shop_address'] = $site_address_info['full_address'];
        }
        return $address;
    }

    /**
     * 关闭退款
     * @param $order_goods_id
     * @param $site_id
     * @param $user_info
     * @return array
     */
    public function orderRefundClose($order_goods_id, $site_id, $user_info)
    {
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id, 'site_id' => $site_id]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'shop', 'orderRefundClose')) {
            return $this->error([], '当前状态不可操作');
        }
        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']], '*');
        if (empty($order_info)) return $this->error([], '订单不存在！');
        model('order_goods')->startTrans();
        try {
            //如果有主动退款撤销后是变为部分退款状态
            if ($order_goods_info['shop_active_refund'] == 1) {
                $refund_status = OrderRefundDict::PARTIAL_REFUND;
                $refund_type = OrderRefundDict::ONLY_REFUNDS;
            } else {
                $refund_status = OrderRefundDict::REFUND_NOT_APPLY;
                $refund_type = 0;
            }
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $data = [
                'order_goods_id' => $order_goods_id,
                'refund_status' => $refund_status,
                'refund_status_name' => $refund_status_data['name'],
                'refund_status_action' => json_encode($refund_status_data, JSON_UNESCAPED_UNICODE),
                'refund_apply_money' => 0,
                'refund_type' => $refund_type,
                'refund_address' => '',
                'refund_delivery_remark' => '',
                'refund_remark' => '',
                'refund_delivery_name' => '',
                'refund_delivery_no' => '',
                'refund_reason' => ''
            ];
            model('order_goods')->update($data, ['order_goods_id' => $order_goods_id]);

            //退款关闭相关事件
            $param = [
                'order_goods_info' => $order_goods_info,
                'order_info' => $order_info,
                'user_info' => $user_info
            ];
            Close::event($param);
            model('order_goods')->commit();
            //退款关闭后事件
            Close::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 移除订单项退款操作
     * @param $condition
     * @return array
     */
    public function removeOrderGoodsRefundAction($condition)
    {
        //订单项增加可退款操作
        $data = [
            'refund_status_action' => ''
        ];
        $result = model('order_goods')->update($data, $condition);
        return $this->success($result);
    }


    /************************************************************************  查询相关函数  *******************************************************************/

    /**
     * 会员维权详情
     * @param $order_goods_id
     * @param $member_id
     * @return array
     */
    public function getMemberRefundDetail($order_goods_id, $member_id)
    {
        $condition = [
            ['order_goods_id', '=', $order_goods_id]
        ];

        $condition[] = ['member_id', '=', $member_id];

        $order_common_model = new OrderCommon();
        $info = $order_common_model->getOrderGoodsInfo($condition)['data'];

        //将售后日志引入
        $refund_log_list = model('order_refund_log')->getList([['order_goods_id', '=', $order_goods_id]], '*', 'action_time desc');
        $info['refund_log_list'] = $refund_log_list;
        return $this->success($info);
    }

    /**
     * 会员维权详情
     * @param $order_goods_id
     * @param int $site_id
     * @param int $store_id
     * @return array
     */
    public function getRefundDetail($order_goods_id, $site_id = 0, $store_id = 0)
    {
        $order_common_model = new OrderCommon();

        $condition = [
            ['order_goods_id', '=', $order_goods_id]
        ];
        if ($site_id > 0) {
            $condition[] = ['site_id', '=', $site_id];
        }
        if ($store_id > 0) {
            $condition[] = ['store_id', '=', $store_id];
        }
        $info = $order_common_model->getOrderGoodsInfo($condition)['data'];
        if (empty($info)) return $this->error('', '订单项不存在！');
        $order_id = $info['order_id'];

        $order_info = $order_common_model->getOrderDetail($info['order_id'])['data'];
        $info['pay_type'] = $order_info['pay_type'];

        $coupon_info = [];
        if ($order_info['coupon_id'] > 0) {
            $order_goods_count = model('order_goods')->getCount([['order_id', '=', $order_id]], 'order_goods_id');
            $refund_count = model('order_goods')->getCount([['order_id', '=', $order_id], ['refund_status', '=', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');

            if (($order_goods_count - $refund_count) == 1) {
                //查询优惠劵信息
                $coupon_model = new Coupon();
                $coupon_info = $coupon_model->getCouponInfo([['coupon_id', '=', $order_info['coupon_id']]], 'coupon_id,coupon_name,type,at_least,money,discount,discount_limit')['data'];
            }
        }
        $info['coupon_info'] = $coupon_info;

        //添加会员昵称
        $member = new Member();
        $member_info = $member->getMemberInfo([['member_id', '=', $info['member_id']]], 'nickname')['data'] ?? [];
        $info['nickname'] = $member_info['nickname'] ?? '';

        if ($info['refund_status'] == OrderRefundDict::REFUND_NOT_APPLY) {
            $refund_apply_arr = $this->getOrderRefundMoney($order_goods_id);
            $info['refund_apply_money'] = round($refund_apply_arr['refund_money'], 2);
            $info['refund_delivery_money'] = $refund_apply_arr['refund_delivery_money'];
        }
        $refund_action = empty($info['refund_status_action']) ? [] : json_decode($info['refund_status_action'], true);
        $refund_action = $refund_action['action'] ?? [];
        $info['refund_action'] = $refund_action;
        //将售后日志引入
        $refund_log_list = model('order_refund_log')->getList([['order_goods_id', '=', $order_goods_id]], '*', 'action_time desc');
        $info['refund_log_list'] = $refund_log_list;
        $info['num'] = numberFormat($info['num']);
        return $this->success($info);
    }

    /**
     * 获取退款维权订单列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getRefundOrderGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = 'nop.*,no.order_no,no.site_id,no.site_name,no.name,m.nickname')
    {
        $join = [
            [
                'order no',
                'nop.order_id = no.order_id',
                'left'
            ],
            [
                'member m',
                'm.member_id = no.member_id',
                'left'
            ],
        ];
        $list = model('order_goods')->pageList($condition, $field, $order, $page, $page_size, 'nop', $join);
        $order_common_model = new OrderCommon();
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                $list['list'][$k] = $order_common_model->handleOrderGoodsInfo($v);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取退款维权订单数量
     * @param array $condition
     * @return array
     */
    public function getRefundOrderGoodsCount($condition = [])
    {
        $count = model('order_goods')->getCount($condition);
        return $this->success($count);
    }

    /**
     * 获取订单项退款信息
     * @param $order_goods_id
     * @param int $site_id
     * @param int $store_id
     * @return array
     */
    public function getOrderGoodsRefundInfo($order_goods_id, $site_id = 0, $store_id = 0)
    {
        $order_goods_condition = [
            ['order_goods_id', '=', $order_goods_id]
        ];
        if ($site_id > 0) {
            $order_goods_condition[] = ['site_id', '=', $site_id];
        }
        if ($store_id > 0) {
            $order_goods_condition[] = ['store_id', '=', $store_id];
        }
        $order_goods_info = model('order_goods')->getInfo($order_goods_condition);
        if (empty($order_goods_info)) {
            return $this->error('', '该订单项不存在！');
        }
        if ($order_goods_info['refund_status'] == OrderRefundDict::REFUND_COMPLETE) {
            return $this->error(null, '该订单项已维权结束！');
        }
        $order_id = $order_goods_info['order_id'];
        $order_goods_info['num'] = numberFormat($order_goods_info['num']);

        if ($order_goods_info['refund_status'] == OrderRefundDict::REFUND_NOT_APPLY) {
            $refund_apply_arr = $this->getOrderRefundMoney($order_goods_id);
            $order_goods_info['refund_apply_money'] = round($refund_apply_arr['refund_money'], 2);
            $order_goods_info['refund_delivery_money'] = $refund_apply_arr['refund_delivery_money'];
        }

        //获取订单信息
        $order_info = model('order')->getInfo([['order_id', '=', $order_goods_info['order_id']]]);

        $coupon_info = [];
        if ($order_info['coupon_id'] > 0) {
            $order_goods_count = model('order_goods')->getCount([['order_id', '=', $order_id]], 'order_goods_id');
            $refund_count = model('order_goods')->getCount([['order_id', '=', $order_id], ['refund_status', '=', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');

            if (($order_goods_count - $refund_count) == 1) {
                //查询优惠劵信息
                $coupon_model = new Coupon();
                $coupon_info = $coupon_model->getCouponInfo([['coupon_id', '=', $order_info['coupon_id']]], 'coupon_id,coupon_name,type,at_least,money,discount,discount_limit')['data'];
            }
        }

        //补充订单商品信息
        $goods_sku_info = model("goods_sku")->getInfo([['sku_id','=',$order_goods_info['sku_id']]],'pricing_type');
        if(!empty($goods_sku_info)){
            $order_goods_info = array_merge($order_goods_info, $goods_sku_info);
        }
        $data = [
            'order_goods_info' => $order_goods_info,
            'order_info' => $order_info,
            'coupon_info' => $coupon_info
        ];

        //预售订单
        if ($order_info['promotion_type'] == 'presale') {
            $presale_order_model = new PresaleOrder();
            $presale_order_info = $presale_order_model->getPresaleOrderInfo([['order_no', '=', $order_info['order_no']]], 'presale_deposit_money,final_money');
            $data['presale_order_info'] = $presale_order_info['data'];
        }

        return $this->success($data);

    }

    /**
     * 求和
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getRefundSum($where = [], $field = '', $alias = 'a', $join = null)
    {
        $data = model('order_goods')->getSum($where, $field, $alias, $join);
        return $this->success($data);
    }

    /********************************************************************** 主动退款 ********************************************************************/

    /**
     * 主动完成退款流程
     * @param $order_id
     * @param $remark
     * @param $refund_reason
     * @return array|mixed|void
     */
    public function activeRefund($order_id, $remark, $refund_reason)
    {
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], '*');
        if ($order_info['order_money'] > 0) {
            $pay_model = new Pay();
            //遍历订单项
            $order_goods_list = model('order_goods')->getList([['order_id', '=', $order_id]]);
            if (!empty($order_goods_list)) {
                $count = count($order_goods_list);
                foreach ($order_goods_list as $k => $v) {
                    $item_refund_money = $v['real_goods_money'];
                    if ($count == ($k + 1)) {
                        $item_refund_money += $order_info['delivery_money'];
                    }
                    $item_result = $this->activeOrderGoodsRefund($v['order_goods_id'], $item_refund_money, $remark, $refund_reason);
                    if ($item_result['code'] < 0) {
                        return $item_result;
                    }
                }
            }

            //订单整体退款
//            $refund_result = $pay_model->refund($refund_no, $order_info['pay_money'], $order_info['out_trade_no'], '', $order_info['pay_money'], $order_info['site_id'], 1);
        }
        return $this->success();

    }

    /**
     * 订单项主动退款
     * @param $order_goods_id
     * @param $refund_money
     * @param string $remark
     * @param string $refund_reason
     * @return array|mixed|void
     */
    public function activeOrderGoodsRefund($order_goods_id, $refund_money, $remark = '', $refund_reason = '')
    {
        //判断是否退款完毕
        $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $order_goods_id]]);
        if ($order_goods_info['refund_status'] == OrderRefundDict::REFUND_COMPLETE) return $this->error('', '订单不能重复维权！');

        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']]);
        model('order_goods')->startTrans();
        try {
            $pay_model = new Pay();
            $refund_no = $pay_model->createRefundNo();

            $update_data = [
                'refund_no' => $refund_no,
                'refund_time' => time(),
                'refund_reason' => $refund_reason,
                'refund_apply_money' => $refund_money,
                'refund_real_money' => $refund_money,
                'refund_action_time' => time()
            ];
            $refund_status = OrderRefundDict::REFUND_COMPLETE;
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $update_data['refund_status'] = $refund_status;
            $update_data['refund_status_name'] = $refund_status_data['name'];
            $update_data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
            $res = model('order_goods')->update($update_data, [['order_goods_id', '=', $order_goods_id]]);
            if ($res === false) {
                model('order_goods')->rollback();
                return $this->error();
            }

            $refund_result = $this->finishAction(array_merge($order_goods_info, $update_data), $order_info, [], 1, true);
            if ($refund_result['code'] < 0) {
                model('order_goods')->rollback();
                return $refund_result;
            }
            //退货日志
            $this->addOrderRefundLog($order_goods_id, OrderRefundDict::REFUND_COMPLETE, $remark . ',维权完成', 3, 0, '平台');

            model('order_goods')->commit();
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 商家主动退款
     * @param $param
     * @return array
     */
    public function shopActiveRefund($param)
    {
        try {
            $order_goods_id = $param['order_goods_id'];
            $shop_active_refund_money = $param['shop_active_refund_money'];
            $shop_active_refund_remark = $param['shop_active_refund_remark'];
            $shop_active_refund_money_type = $param['shop_active_refund_money_type'];
            $shop_active_refund_status = $param['refund_status'] ?? 'PARTIAL_REFUND';
            $is_refund_stock = $param['is_refund_stock'] ?? 0;

            //检测
            $check_res = ActiveRefund::check($param);
            if ($check_res['code'] < 0) return $check_res;
            $order_goods_info = $check_res['data']['order_goods_info'];
            $order_info = $check_res['data']['order_info'];
            $refund_apply_money = $check_res['data']['refund_apply_money'];

            if(empty($order_goods_info['goods_id'])){
                $is_refund_stock = 0 ; //无码商品不退库存
            }

            //退款编号
            $pay_model = new Pay();
            $shop_active_refund_no = $pay_model->createRefundNo();

            //计算退款余额和现金部分
            if ($order_info['balance_money'] > 0) {
                $balance_rate = $order_info['balance_money'] / $order_info['order_money'];
                $refund_balance_money = $shop_active_refund_money * $balance_rate;
                $refund_pay_money = $shop_active_refund_money - $refund_balance_money;
            } else {
                $refund_balance_money = 0;
                $refund_pay_money = $shop_active_refund_money;
            }

            //判断退款状态
            if ($shop_active_refund_money == $refund_apply_money) {
                $refund_status = OrderRefundDict::REFUND_COMPLETE;
            } else {
                if ($shop_active_refund_status == 'PARTIAL_REFUND') {
                    $refund_status = OrderRefundDict::PARTIAL_REFUND;
                } else {
                    $refund_status = OrderRefundDict::REFUND_COMPLETE;
                }
            }
            $refund_status_data = OrderRefundDict::getStatus($refund_status);

            //更新数据
            $update_data = [
                'shop_active_refund' => 1,
                'shop_active_refund_no' => $shop_active_refund_no,
                'shop_active_refund_remark' => $shop_active_refund_remark,
                'shop_active_refund_money' => $shop_active_refund_money,
                'shop_active_refund_money_type' => $shop_active_refund_money_type,
                'refund_time' => time(),
                'refund_action_time' => time(),
                'refund_no' => $shop_active_refund_no,
                'refund_type' => OrderRefundDict::ONLY_REFUNDS,
                'refund_status' => $refund_status,
                'refund_status_name' => $refund_status_data['name'],
                'refund_status_action' => json_encode($refund_status_data, JSON_UNESCAPED_UNICODE),
                'is_refund_stock' => $is_refund_stock,//初始化为不退库存
            ];
        } catch (Exception $e) {
            return $this->error(null, $e->getMessage());
        }

        model('order_goods')->startTrans();
        try {
            model('order_goods')->update($update_data, [['order_goods_id', '=', $order_goods_id]]);
            $order_goods_info = array_merge($order_goods_info, $update_data);

            //调用退款操作
            $action_res = $this->refundMoneyAction([
                'order_goods_id' => $order_goods_id,
                'refund_pay_money' => $refund_pay_money,
                'refund_balance_money' => $refund_balance_money,
                'refund_money_type' => $shop_active_refund_money_type,
                'refund_no' => $shop_active_refund_no,
                'order_info' => $order_info,
            ]);
            if ($action_res['code'] < 0) {
                model('order_goods')->rollback();
                return $action_res;
            }
            event('OrderRefundMoneyFinish', ['order_goods_info' => $order_goods_info, 'order_info' => $order_info, 'refund_money' => $shop_active_refund_money]);
            //如果退款完成则调用完成操作
            if ($refund_status == OrderRefundDict::REFUND_COMPLETE) {
                $action_res = $this->finishAction($order_goods_info, $order_info);
                if ($action_res['code'] < 0) {
                    model('order_goods')->rollback();
                    return $action_res;
                }
            }

            $check_res['data']['order_goods_info'] = $order_goods_info;
            ActiveRefund::event(array_merge($param, $check_res['data']));

            model('order_goods')->commit();

            ActiveRefund::after(array_merge($param, $check_res['data']));

            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error(null, $e->getMessage());
        }
    }



    /********************************************************************** 退款公共事件 ********************************************************************/

    /**
     * 添加退款操作日志
     * @param $order_goods_id
     * @param $refund_status
     * @param $action
     * @param $action_way
     * @param $action_userid
     * @param $action_username
     * @param string $desc
     * @return int|string
     */
    public function addOrderRefundLog($order_goods_id, $refund_status, $action, $action_way, $action_userid, $action_username, $desc = '')
    {
        $data = [
            'order_goods_id' => $order_goods_id,
            'refund_status' => $refund_status,
            'action' => $action,
            'action_way' => $action_way,
            'action_userid' => $action_userid,
            'username' => $action_username,
            'action_time' => time(),
            'desc' => $desc
        ];
        return model('order_refund_log')->add($data);
    }

    /**
     * 锁定订单
     * @param $order_id
     * @return int
     */
    public function verifyOrderLock($order_id)
    {
        $condition = [
            ['order_id', '=', $order_id],
            ['refund_status', 'not in', [OrderRefundDict::REFUND_NOT_APPLY, OrderRefundDict::REFUND_COMPLETE, OrderRefundDict::PARTIAL_REFUND]],
        ];
        $count = model('order_goods')->getCount($condition, 'order_goods_id');
        $order_common_model = new OrderCommon();
        if ($count > 0) {
            $res = $order_common_model->orderLock($order_id);
        } else {
            $res = $order_common_model->orderUnlock($order_id);
        }
        return $res;
    }

    /**
     * 退款完成操作
     * @param $order_goods_info
     * @param $order_info
     * @param array $log_data
     * @param int $is_deposit_back
     * @param bool $is_active_refund
     * @return array|mixed|void
     */
    public function finishAction($order_goods_info, $order_info, $log_data = [], $is_deposit_back = 1, $is_active_refund = false)
    {
        $order_goods_id = $order_goods_info['order_goods_id'];
        $order_id = $order_goods_info['order_id'];
        $order_no = $order_info['order_no'];
        //订单项总数
        $order_goods_count = model('order_goods')->getCount([['order_id', '=', $order_id]], 'order_goods_id');
        //退款订单项数
        $refund_count = model('order_goods')->getCount([['order_id', '=', $order_id], ['refund_status', '=', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');
        //是否全部退款
        $is_all_refund = false;
        $is_all_refund_money = false;
        if ($order_goods_count == $refund_count) {
            $is_all_refund = true;
            $is_all_refund_money = true;
        }
        $refund_total_real_money = model('order_goods')->getSum([['order_id', '=', $order_id], ['refund_status', '=', OrderRefundDict::REFUND_COMPLETE]], 'refund_real_money');
        $refund_total_real_money += model('order_goods')->getSum([['order_id', '=', $order_id], ['shop_active_refund', '=', 1]], 'shop_active_refund_money');

        if ($refund_total_real_money > $order_info['order_money']+$order_info['delivery_money']) {
            Log::write("订单退款异常,退款金额不能大于订单总金额:".json_encode(['refund_total_real_money'=>$refund_total_real_money,'order_info'=>$order_info]));
            return $this->error([], '退款金额不能大于订单总金额！');
        }
        $refund_pay_money_sum = model('order_goods')->getSum([['order_id', '=', $order_id], ['refund_status', '=', OrderRefundDict::REFUND_COMPLETE], ['order_goods_id', '<>', $order_goods_id]], 'refund_pay_money');
        $shop_active_refund_money_sum = model('order_goods')->getSum([['order_id', '=', $order_id], ['shop_active_refund', '=', 1], ['order_goods_id', '<>', $order_goods_id]], 'shop_active_refund_money');
        $remain_pay_money = $order_info['pay_money'] - $refund_pay_money_sum - $shop_active_refund_money_sum;
        //todo  退还创建订单时使用的次卡
        //实际执行转账 (存在余额支付的话   退款一部分余额  退还一部分实际金额)  //订单退款退回余额积分等操作
        if ($order_info['balance_money'] > 0 && $order_goods_info['refund_real_money'] > 0) {
            $balance_rate = $order_info['balance_money'] / $order_info['order_money'];
            $refund_balance_money = $order_goods_info['refund_real_money'] * $balance_rate;
            $refund_pay_money = $order_goods_info['refund_real_money'] - $refund_balance_money;
        } else {
            $refund_balance_money = 0;
            $refund_pay_money = $order_goods_info['refund_real_money'];
        }

        if ($refund_pay_money > 0 && $refund_pay_money > $remain_pay_money) {
            $refund_balance_money += $refund_pay_money - $remain_pay_money;
            $refund_pay_money = $remain_pay_money;
        }
        $param = [
            'order_info' => $order_info,
            'order_goods_info' => $order_goods_info,
            'is_all_refund' => $is_all_refund,
            'refund_total_real_money' => $refund_total_real_money,
            'log_data' => $log_data
        ];
        model('order_goods')->startTrans();
        try {
            model('order_goods')->update(['refund_pay_money' => $refund_pay_money], ['order_goods_id' => $order_goods_id]);
            $addon_result = event('AddonOrderRefund', ['order_no' => $order_no, 'promotion_type' => $order_info['promotion_type'], 'is_deposit_back' => $is_deposit_back, 'refund_money_type' => $order_goods_info['refund_money_type']], true);
            if (empty($addon_result)) {
                //根据选择的退款方式来退款
                $action_res = $this->refundMoneyAction([
                    'order_goods_id' => $order_goods_id,
                    'refund_pay_money' => $refund_pay_money,
                    'refund_balance_money' => $refund_balance_money,
                    'refund_money_type' => $order_goods_info['refund_money_type'],
                    'refund_no' => $order_goods_info['refund_no'],
                    'order_info' => $order_info,
                ]);
                if ($action_res['code'] < 0) {
                    model('order_goods')->rollback();
                    return $action_res;
                }
                event('OrderRefundMoneyFinish', ['order_goods_info' => $order_goods_info, 'order_info' => $order_info, 'refund_money' => $order_goods_info['refund_real_money']]);
            } else {
                if ($addon_result['code'] < 0) {
                    model('order_goods')->rollback();
                    return $addon_result;
                }
            }
            //退款完成操作关联事件
            FinishAction::event($param);
            //验证订单是否全部退款完毕  订单如果全部退款完毕,订单关闭
            if ($is_all_refund) {


                //将订单设置为不可退款
                $order_common_model = new OrderCommon();
                $order_common_model->orderUpdate(['is_enable_refund' => 0], [['order_id', '=', $order_id]]);
                //完成的订单不会关闭(收银台订单除外)
                if ($order_info['order_status'] != Order::ORDER_COMPLETE && $is_all_refund) {
                    $param['is_all_refund_money'] = $is_all_refund_money;
                    //订单完全退款事件(金额完全退)
                    event('OrderRefundAllFinish', $param);

                    $order_common_model = new OrderCommon();
                    //记录订单日志 start
                    if (!empty($log_data)) {
                        $log_data = array_merge($log_data, [
                            'order_id' => $order_id,
                            'order_status' => OrderCommon::ORDER_CLOSE,
                            'order_status_name' => '已关闭'
                        ]);
//                            $order_common_model->addOrderLog($log_data);
                    }
                    $close_result = $order_common_model->orderClose($order_id, $log_data ?? [], $order_goods_info['refund_reason'] ?: '退款完成,订单关闭！');
                    if ($close_result['code'] < 0) {
                        model('order_goods')->rollback();
                        return $close_result;
                    }
                }
            }
            model('order_goods')->commit();
            //退款完成操作后事件
            $param['is_all_refund_money'] = $is_all_refund_money;
            FinishAction::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 退款金额操作
     * 1、用户申请同意后转账
     * 2、商家主动退款
     * @param $param
     * @return array|mixed|void
     */
    public function refundMoneyAction($param)
    {
        $order_goods_id = $param['order_goods_id'];
        $refund_pay_money = $param['refund_pay_money'];
        $refund_balance_money = $param['refund_balance_money'];
        $refund_money_type = $param['refund_money_type'];
        $refund_no = $param['refund_no'] ?? '';
        $order_info = $param['order_info'];

        //根据选择的退款方式来退款
        $refund_stat_money = 0;
        switch ($refund_money_type) {
            case OrderRefundDict::back: //原路退回的时候退还余额 + 支付金额
                //退还直接支付的金额
                if ($refund_pay_money > 0) {
                    $pay_model = new Pay();
                    if ($refund_no == '') {
                        $refund_no = $pay_model->createRefundNo();
                    }
                    $refund_result = $pay_model->refund($refund_no, $refund_pay_money, $order_info['out_trade_no'], '', $order_info['pay_money'], $order_info['site_id'], 1, $order_goods_id, $order_info['is_video_number']);
                    if ($refund_result['code'] < 0) {
                        return $refund_result;
                    }
                }
                //余额部分
                $balance_result = $this->refundBalanceMoneyAction($param);
                if ($balance_result['code'] < 0) return $balance_result;
                $refund_stat_money = $refund_pay_money;
                break;
            case OrderRefundDict::balance://退款到余额
                $member_account_model = new MemberAccount();
                $refund_result = $member_account_model->addMemberAccount($order_info['site_id'], $order_info['member_id'], AccountDict::balance, $refund_pay_money + $refund_balance_money, 'refund', $order_info['order_id'], '订单退款返还！');
                if ($refund_result['code'] < 0) {
                    return $refund_result;
                }
                break;
            case OrderRefundDict::offline://线下退款
                //余额部分
                $balance_result = $this->refundBalanceMoneyAction($param);
                if ($balance_result['code'] < 0) return $balance_result;
                $refund_stat_money = $refund_pay_money;
                break;
        }
        //退款统计金额 原路退款和线下退款中的现金部分需要统计
        if ($refund_stat_money > 0) {
            $stat_model = new Stat();
            $stat_model->switchStat(['type' => 'order_refund', 'data' => ['order_goods_id' => $order_goods_id, 'refund_pay_money' => $refund_stat_money, 'site_id' => $order_info['site_id']]]);
        }
        return $this->success();
    }

    /**
     * 退余额操作
     * @param $param
     * @return array
     */
    public function refundBalanceMoneyAction($param)
    {
        $refund_balance_money = $param['refund_balance_money'];
        $order_info = $param['order_info'];
        //退款余额
        if ($refund_balance_money > 0) {
            $member_account_model = new MemberAccount();
            // 查询该订单使用的现金余额
            $order_use_balance_money = abs($member_account_model->getMemberAccountSum([['account_type', '=', AccountDict::balance_money], ['type_tag', '=', $order_info['order_id']], ['from_type', '=', 'order']], 'account_data')['data']);
            // 查询该订单已退回的现金余额
            $refunded_balance_money = $member_account_model->getMemberAccountSum([['account_type', '=', AccountDict::balance_money], ['type_tag', '=', $order_info['order_id']], ['from_type', '=', 'refund']], 'account_data')['data'];

            if ($order_use_balance_money > $refunded_balance_money) {
                $refundable_balance_money = $order_use_balance_money - $refunded_balance_money;
                $refundable_balance_money = min($refundable_balance_money, $refund_balance_money);
                $refund_balance_money -= $refundable_balance_money;
                $balance_result = $member_account_model->addMemberAccount($order_info['site_id'], $order_info['member_id'], AccountDict::balance_money, $refundable_balance_money, 'refund', $order_info['order_id'], '订单退款返还！');
                if ($balance_result['code'] < 0) {
                    return $balance_result;
                }
            }
            if ($refund_balance_money > 0) {
                $balance_result = $member_account_model->addMemberAccount($order_info['site_id'], $order_info['member_id'], AccountDict::balance, $refund_balance_money, 'refund', $order_info['order_id'], '订单退款返还！');
                if ($balance_result['code'] < 0) {
                    return $balance_result;
                }
            }
        }
        return $this->success();
    }

    /**
     * 退货完成
     * @param $data
     * @param $user_info
     * @param array $log_data
     * @return array
     */
    public function orderRefundFinish($data, $user_info, $log_data = [])
    {
        $order_goods_id = $data['order_goods_id'];
        $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $order_goods_id]]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');
        $order_id = $order_goods_info['order_id'];
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]]);
        if (empty($order_info)) return $this->error([], '订单不存在！');

        $refund_apply_money = $order_goods_info['refund_apply_money'];

        $update_data = [
            'refund_time' => time(),
        ];
        if (!$this->refundActionCheck($order_goods_info['refund_status'], 'shop', 'orderRefundTransfer')) {
            return $this->error(null, '当前状态不可操作');
        }


        if ($data['refund_real_money'] > $refund_apply_money) return $this->error('', '退款金额超出最大可退金额！');
        model('order_goods')->startTrans();
        try {
            $update_data['refund_apply_money'] = $refund_apply_money;
            $update_data['refund_money_type'] = $data['refund_money_type'];
            $update_data['refund_real_money'] = $data['refund_real_money'];
            $update_data['shop_refund_remark'] = $data['shop_refund_remark'];
            $update_data['refund_delivery_money'] = $data['refund_delivery_money'] ?? 0.00;

            $refund_status = OrderRefundDict::REFUND_COMPLETE;
            $refund_status_data = OrderRefundDict::getStatus($refund_status);
            $update_data['refund_status'] = $refund_status;
            $update_data['refund_status_name'] = $refund_status_data['name'];
            $update_data['refund_status_action'] = json_encode($refund_status_data, JSON_UNESCAPED_UNICODE);
            model('order_goods')->update($update_data, [['order_goods_id', '=', $order_goods_id]]);

            //订单日志
            if(!empty($log_data)){
                $order_common_model = new OrderCommon();
                $log_data['action'] = '商品【'.$order_goods_info['sku_name'].'】'.$log_data['action'];
                $log_data = array_merge($log_data, [
                    'order_id' => $order_goods_info['order_id'],
                    'order_status' => $order_info['order_status'],
                    'order_status_name' => $order_info['order_status_name']
                ]);
                OrderLog::addOrderLog($log_data, $order_common_model);
            }

            //退款操作
            $result = $this->finishAction(array_merge($order_goods_info, $update_data), $order_info, $log_data, $data['is_deposit_back'] ?? 0);
            if ($result['code'] < 0) {
                model('order_goods')->rollback();
                return $result;
            }

            $param = [
                'refund_real_money' => $data['refund_real_money'],
                'order_goods_info' => array_merge($order_goods_info, $update_data),
                'order_info' => $order_info,
                'user_info' => $user_info
            ];
            //退款后相关事件
            Finish::event($param);
            model('order_goods')->commit();
            //退款完成后事件
            Finish::after($param);
            return $this->success();
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 订单项退款
     * @param $order_goods_info
     * @return mixed|void
     */
    public function orderGoodsRefund($order_goods_info)
    {
        $order_info = model('order')->getInfo(['order_id' => $order_goods_info['order_id']]);
        if (!empty($order_info)) {
            $order_info['goods_num'] = numberFormat($order_info['goods_num']);
        }
        $order_goods_info['order_info'] = $order_info;
        $result = event('OrderGoodsRefund', $order_goods_info, true);
        if (empty($result)) {
            $order_common_model = new OrderCommon();
            $order_model = $order_common_model->getOrderModel($order_info);
            $result = $order_model->refund($order_goods_info);
        }

        return $result;
    }


    /**
     * 未发货订单项主动退款
     * @param $order_goods_id
     */
    public function autoRefundOrder($order_goods_id){

        $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $order_goods_id]]);
        if (empty($order_goods_info)) return $this->error([], '订单项不存在！');

        if($order_goods_info['delivery_status'] != OrderGoodsDict::wait_delivery){
            return $this->error([], '订单项已发货！');
        }
        return $this->activeOrderGoodsRefund($order_goods_id,$order_goods_info['refund_apply_money'],'未发货订单自动退款');
    }




}