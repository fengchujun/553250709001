<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace app\api\controller;

use app\model\order\OrderCreate as OrderCreateModel;

/**
 * 订单创建
 */
class Ordercreate extends BaseOrderCreateApi
{
    /**
     * 创建
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '',
            'is_balance' => $this->params[ 'is_balance' ] ?? 0,//是否使用余额
            'is_point' => $this->params[ 'is_point' ] ?? 1,//是否使用积分
            'coupon' => isset($this->params[ 'coupon' ]) && !empty($this->params[ 'coupon' ]) ? json_decode($this->params[ 'coupon' ], true) : [],
            //会员卡项
            'member_card_unit' => $this->params[ 'member_card_unit' ] ?? '',

            //门店专属
            'store_id' => $this->params[ 'store_id' ] ?? 0,
        ];
        $res = $order_create->setParam(array_merge($data, $this->getInputParam(), $this->getCommonParam(), $this->getDeliveryParam(), $this->getInvoiceParam()))->create();
        return $this->response($res);
    }

    /**
     * 计算
     */
    public function calculate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '',//是否使用余额
            'is_balance' => $this->params[ 'is_balance' ] ?? 0,//是否使用余额
            'is_point' => $this->params[ 'is_point' ] ?? 1,//是否使用积分
            'coupon' => isset($this->params[ 'coupon' ]) && !empty($this->params[ 'coupon' ]) ? json_decode($this->params[ 'coupon' ], true) : [],
            //会员卡项
            'member_card_unit' => $this->params[ 'member_card_unit' ] ?? '',

            //门店专属
            'store_id' => $this->params[ 'store_id' ] ?? 0,
        ];

        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam(), $this->getInvoiceParam()))->confirm();
        return $this->response($this->success($res));
    }

    /**
     * 待支付订单 数据初始化
     * @return string
     */
    public function payment()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            //商品项
            'cart_ids' => $this->params[ 'cart_ids' ] ?? '',
            'sku_id' => $this->params[ 'sku_id' ] ?? '',
            'num' => $this->params[ 'num' ] ?? '',
            //会员卡项
            'member_goods_card' => isset($this->params[ 'member_goods_card' ]) && !empty($this->params[ 'member_goods_card' ]) ? json_decode($this->params[ 'member_goods_card' ], true) : [],
            //接龙活动id
            'jielong_id' => $this->params[ 'jielong_id' ] ?? '',

            //会员卡项
            'is_open_card' => $this->params[ 'is_open_card' ] ?? 0,
            'member_card_unit' => $this->params[ 'member_card_unit' ] ?? '',

            //门店专属
            'store_id' => $this->params[ 'store_id' ] ?? 0,
        ];
        if (!$data[ 'cart_ids' ] && !$data[ 'sku_id' ]) return $this->response($this->error('', '缺少必填参数商品数据'));

        $res = $order_create->setParam(array_merge($data, $this->getInputParam(), $this->getCommonParam(), $this->getDeliveryParam()))->orderPayment();
        return $this->response($this->success($res));
    }

    /**
     * 查询订单可用的优惠券
     * @return false|string
     */
    public function getCouponList(){
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '',//是否使用余额
            'store_id' => $this->params[ 'store_id' ] ?? 0,//可能没有门店id，要做默认处理
            'delivery' => $this->params[ 'delivery' ],
        ];
        $res = $order_create->setParam(array_merge($data, $this->getCommonParam()))->getOrderCouponList();
        return $this->response($this->success($res));
    }
}