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

use addon\cardservice\model\MemberCard;
use addon\coupon\model\Coupon;
use app\dict\goods\GoodsDict;
use app\dict\order\OrderDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\member\MemberAccount;
use app\model\message\Message;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use app\model\order\OrderStock;
use app\model\system\Pay;
use think\db\exception\DbException;

/**
 * 订单交易设置
 */
class OrderClose extends BaseModel
{
    /**
     * 校验
     * @param $data
     * @return array
     * @throws DbException
     */
    public function check($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];
        //尝试关闭支付
        if ($order_info['pay_status'] == 0) {
            $pay_model = new Pay();
            $result = $pay_model->closePay($order_info['out_trade_no']);//关闭旧支付单据
            if ($result['code'] < 0) {
                return $result;
            }
        }
        //订单关闭校验
        event('OrderCloseCheck', []);

        return $this->success();
    }


    public function event($data)
    {
        $order_info = $data['order_info'];
        $order_id = $order_info['order_id'];
//        $is_exist_refund = $data['is_exist_refund'];
//        $refund_goods_card = $data['refund_goods_card'];
//        $refund_point = $data['refund_point'];


        /******************************************************* 库存相关 **********************************************************/
        //库存处理
        $condition = array(
            ['order_id', '=', $order_id]
        );
        //循环订单项 依次返还库存 并修改状态
        $order_goods_list = model('order_goods')->getList($condition, '*');
        $order_refund_model = new OrderRefund();
        $goods_model = new Goods();

        $is_exist_refund = false;//是否存在退款
        $refund_goods_card = [];
        $refund_point = 0;
        $order_stock = new OrderStock();
        //用于存放退款库存的数组
        $stock_goods_sku_data = [];
        foreach ($order_goods_list as $k => $v) {
            //如果是已维权完毕的订单项, 库存不必再次返还(todo 收银台订单创建不再扣除库存了)
            if ($v['refund_status'] != OrderRefundDict::REFUND_COMPLETE && $order_info['order_type'] != OrderDict::cashier) {
                $goods_class = $v['goods_class'] ?? 0;

                if (in_array($goods_class, [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                    $stock_goods_sku_data[] = [
                        'sku_id' => $v['sku_id'],
                        'num' => $v['num']
                    ];
                }
                //返还积分
                $refund_point += $v['use_point'];
                // 是否有使用次卡
                if ($v['card_item_id']) {
                    $refund_goods_card[] = ['type' => 'order', 'relation_id' => $v['order_goods_id']];
                }
            }
            if ($v['refund_status'] == OrderRefundDict::REFUND_COMPLETE) {
                $is_exist_refund = true;
            }
            //减少商品销量(必须支付过)
            if ($order_info['pay_status'] > 0) {
                $goods_model->decGoodsSaleNum($v['sku_id'], $v['num'], $order_info['store_id']);
            }
        }
        /******************************************************* 返还库存 **********************************************************/
        if($stock_goods_sku_data){
            //返还销售库存
            $order_stock->incOrderSaleStock([
                'goods_sku_data' => $stock_goods_sku_data,
                'store_id' => $order_info['store_id']
            ]);
        }



        /******************************************************* 优惠券相关 **********************************************************/
        //返还店铺优惠券
        $coupon_id = $order_info['coupon_id'];
        if ($coupon_id > 0) {
            $coupon_model = new Coupon();
            $coupon_model->refundCoupon($coupon_id, $order_info['member_id']);
        }
        //平台优惠券
        /******************************************************* 退还余额相关 **********************************************************/
        //平台余额  退还余额
        if (!$is_exist_refund) {//因为订单完成后  只有全部退款完毕订单才会关闭
            $member_account_model = new MemberAccount();
            if ($order_info['balance_money'] > 0) {
                $result = $member_account_model->addMemberAccount($order_info['site_id'], $order_info['member_id'], 'balance', $order_info['balance_money'], 'refund', $order_id, '订单关闭返还');
            }
            // 订单关闭返还积分
            if ($refund_point > 0) {
                $result = $member_account_model->addMemberAccount($order_info['site_id'], $order_info['member_id'], 'point', $refund_point, 'refund', $order_id, '订单关闭返还');
            }
        }
        /******************************************************* 关闭后各插件相关 **********************************************************/
        //订单关闭后操作
        $close_result = event('OrderClose', $order_info);
        if (empty($close_result)) {
            foreach ($close_result as $k => $v) {
                if (!empty($v) && $v['code'] < 0) {
                    return $v;
                }
            }
        }

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
        $log_data = $data['log_data'];
        $close_cause = $data['close_cause'] ?? '';
        $order_common_model = new OrderCommon();
        /******************************************************* 会员相关 **********************************************************/

        /******************************************************* 日志相关 **********************************************************/
        //记录订单日志 start
        $close_status = OrderCommon::ORDER_CLOSE;
        if (!empty($log_data)) {
            if ($log_data['action_way'] == 1) {
                $member_info = model('member')->getInfo(['member_id' => $log_data['uid']], 'nickname');
                $buyer_name = empty($member_info['nickname']) ? '' : '【' . $member_info['nickname'] . '】';
                $log_data['nick_name'] = $buyer_name;
                $action = '买家关闭了订单';
            } else {
                $action = '商家关闭了订单';
            }
        } else {
            $action = !empty($close_cause) ? $close_cause : '系统自动关闭了订单(长时间未支付)';
            $log_data = [
                'uid' => 0,
                'nick_name' => '系统',
                'action_way' => 2
            ];

        }
        $log_data = array_merge($log_data, [
            'order_id' => $order_info['order_id'],
            'action' => $action,
            'order_status' => $close_status,
            'order_status_name' => $order_common_model->order_status[$close_status]['name']
        ]);
        OrderLog::addOrderLog($log_data, $order_common_model);
        //记录订单日志 end
        /******************************************************* 发送消息 **********************************************************/
        //订单关闭消息
        $message_model = new Message();
        $res = $message_model->sendMessage(['keywords' => 'ORDER_CLOSE', 'order_id' => $order_info['order_id'], 'site_id' => $order_info['site_id']]);


        return $this->success();
    }
}