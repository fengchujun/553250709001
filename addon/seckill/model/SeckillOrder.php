<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\model;

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\Order;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use app\model\order\OrderRefund;
use app\model\system\Pay;
use extend\exception\OrderException;
use think\facade\Cache;

/**
 * 订单创建(秒杀)
 *
 * @author Administrator
 *
 */
class SeckillOrder extends BaseModel
{

    /**
     * 订单关闭
     * @param $order_id
     */
    public function orderClose($order_id)
    {
        $order_info = model('order')->getInfo([['order_id', '=', $order_id], ['promotion_type', '=', 'seckill'], ['order_status', '=', -1]], 'promotion_id');
        if (!empty($order_info)) {
            $condition = [
                ['order_id', '=', $order_id]
            ];
            $order_goods_list = model('order_goods')->getList($condition, 'order_goods_id,sku_id,num,refund_status,use_point');
            foreach ($order_goods_list as $k => $v) {
                // 返还库存
                model('promotion_seckill')->setInc([['id', '=', $order_info['promotion_id']]], 'goods_stock', $v['num']);
                model('promotion_seckill_goods')->setInc([['sku_id', '=', $v['sku_id']], ['seckill_id', '=', $order_info['promotion_id']]], 'stock', $v['num']);
                // 减少销量
                model('promotion_seckill')->setDec([['id', '=', $order_info['promotion_id']]], 'sale_num', $v['num']);
            }
        }
        return $this->success();
    }


    /**
     * 获取商品已秒杀数
     * @param $goods_id
     * @param $member_id
     * @return float
     */
    public function getGoodsSeckillNum($seckill_id)
    {
        $join = [
            ['order o', 'o.order_id = og.order_id', 'left']
        ];
        return model('order_goods')->getSum([
            ['o.order_status', '<>', Order::ORDER_CLOSE],
            ['o.promotion_type', '=', 'seckill'],
            ['o.promotion_id', '=', $seckill_id],
            ['og.refund_status', '<>', OrderRefundDict::REFUND_COMPLETE],
        ], 'og.num', 'og', $join);
    }
}