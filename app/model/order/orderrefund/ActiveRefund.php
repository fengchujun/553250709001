<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\orderrefund;

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\OrderCommon;
use app\model\order\OrderLog;
use app\model\order\OrderRefund;
use think\db\exception\DbException;

/**
 * 商家主动退款
 */
class ActiveRefund extends BaseModel
{
    /**
     * 校验
     * @param $param
     * @return mixed
     */
    public static function check($param)
    {
        $instance = new self;
        $order_goods_id = $param[ 'order_goods_id' ];
        $shop_active_refund_money_type = $param[ 'shop_active_refund_money_type' ];
        $shop_active_refund_money = $param[ 'shop_active_refund_money' ];

        //退款方式
        $refund_money_type_arr = OrderRefundDict::getRefundMoneyType();
        if(!isset($refund_money_type_arr[$shop_active_refund_money_type])){
            return $instance->error(null, '退款方式有误！');
        }

        //订单项检测
        $order_goods_info = model('order_goods')->getInfo([ 'order_goods_id' => $order_goods_id ]);
        if (empty($order_goods_info)) return $instance->error(null, '订单项不存在！');
        if($order_goods_info['shop_active_refund'] == 1) return $instance->error(null, '已操作过主动退款');
        if ($order_goods_info[ 'refund_status' ] != OrderRefundDict::REFUND_NOT_APPLY &&
            $order_goods_info[ 'refund_status' ] != OrderRefundDict::REFUND_DIEAGREE) {
            return $instance->error(null, '存在进行中的退款！');
        }

        //订单检测
        $order_id = $order_goods_info[ 'order_id' ];
        $order_info = model('order')->getInfo([ 'order_id' => $order_id ]);
        if (empty($order_info)) return $instance->error([], '订单不存在！');
        if ($order_info[ 'is_enable_refund' ] == 0) {
            if ($order_info[ 'promotion_type' ] == 'pinfan') {
                return $instance->error(null, '拼团活动正在进行中,待拼成功后可发起退款！');
            }
            return $instance->error(null, '当前订单不允许退款！');
        }

        //退款金额检测
        $order_refund_model = new OrderRefund();
        $refund_apply_money_array = $order_refund_model->getOrderRefundMoney($order_goods_id);//可退款金额 通过计算获得
        $refund_apply_money = $refund_apply_money_array[ 'refund_money' ];
        if($shop_active_refund_money < 0){
            return $instance->error(null, '主动退款金额不可小于0！');
        }
        if ($shop_active_refund_money > $refund_apply_money){
            return $instance->error(null, '主动退款金额不能大于可退款总额！');
        }
        if($refund_apply_money > 0 && $shop_active_refund_money == 0){
            return $instance->error(null, '主动退款金额不可为0！');
        }

        //退货数量检测
        $param['is_refund_stock']  = $param['is_refund_stock'] ?? 0;
        $param['refund_stock_num'] = $param['refund_stock_num'] ?? $order_goods_info['num'];
        if($param['is_refund_stock'] == 1){
            if($param['refund_stock_num'] > $order_goods_info['num']){
                return $instance->error(null, '主动退货数量不可大于订单数量！');
            }
            $order_goods_info['refund_stock_num'] = $param['refund_stock_num'];
        }

        return $instance->success([
            'order_goods_info' => $order_goods_info,
            'order_info' => $order_info,
            'refund_apply_money' => $refund_apply_money,
        ]);
    }

    /**
     * 执行事件
     * @param $param
     * @return true
     * @throws DbException
     */
    public static function event($param)
    {
        $order_goods_info = $param['order_goods_info'];
//        if($order_goods_info['refund_status'] == OrderRefundDict::REFUND_COMPLETE){
//            $order_refund_model = new OrderRefund();
//            $order_refund_model->orderGoodsRefund($order_goods_info);
//        }

        //返还库存
        $param['is_refund_stock'] = $param['is_refund_stock'] ?? 0;
        $param['refund_stock_num'] = isset($param['refund_stock_num']) && $param['refund_stock_num']>0 ? $param['refund_stock_num'] : $order_goods_info['num'];
        $order_refund_model = new OrderRefund();
        $order_goods_info['num'] = $param['refund_stock_num'] ?:$order_goods_info['num'];
        $order_refund_model->orderGoodsRefund($order_goods_info);


        //更新退款金额
        $order_info = $param['order_info'];
        $order_id = $order_info['order_id'];
        $refund_total_real_money = model('order_goods')->getSum([ [ 'order_id', '=', $order_id ], [ 'refund_status', '=', OrderRefundDict::REFUND_COMPLETE ] ], 'refund_real_money');
        $refund_total_real_money += model('order_goods')->getSum([ [ 'order_id', '=', $order_id ], [ 'shop_active_refund', '=', 1 ] ], 'shop_active_refund_money');
        model('order')->update(['refund_money' => $refund_total_real_money], [['order_id', '=', $order_id]]);
    }

    /**
     * 后续事件
     * @param $param
     * @return array|true
     */
    public static function after($param)
    {
        $shop_active_refund_money = $param[ 'shop_active_refund_money' ];
        $shop_active_refund_remark = $param[ 'shop_active_refund_remark' ];
        $user_info = $param['user_info'];
        $order_goods_info = $param['order_goods_info'];
        $order_info = $param['order_info'];

        //记录订单日志
        $log_data = [
            'uid' => $user_info['uid'],
            'nick_name' => $user_info['username'],
            'action' => '商品【'.$order_goods_info['sku_name'].'】商家主动退款，退款金额：'.$shop_active_refund_money.'元，退款说明：'.$shop_active_refund_remark,
            'action_way' => 2,
            'order_id' => $order_goods_info['order_id'],
            'order_status' => $order_info['order_status'],
            'order_status_name' => $order_info['order_status_name']
        ];
        $order_common_model = new OrderCommon();
        OrderLog::addOrderLog($log_data, $order_common_model);

        //记录退款日志
        $order_refund_model = new OrderRefund();
        $order_refund_model->addOrderRefundLog(
            $order_goods_info['order_goods_id'],
            $order_goods_info['refund_status'],
            '商家主动退款',
            2,
            $user_info['uid'],
            $user_info['username'],
            '退款金额：'.$shop_active_refund_money.'元，退款说明：'.$shop_active_refund_remark,
        );
        return true;
    }
}