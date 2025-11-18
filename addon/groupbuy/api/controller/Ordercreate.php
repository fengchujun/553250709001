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

namespace addon\groupbuy\api\controller;

use addon\groupbuy\model\GroupbuyOrderCreate as OrderCreateModel;
use app\api\controller\BaseOrderCreateApi;

/**
 * 订单创建
 * @author Administrator
 *
 */
class Ordercreate extends BaseOrderCreateApi
{
    /**
     * 创建订单
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params['order_key'] ?? '',
            'is_balance' => $this->params['is_balance'] ?? 0,//是否使用余额
        ];
        $res = $order_create->setParam(array_merge($data, $this->getInputParam(), $this->getCommonParam(), $this->getDeliveryParam(), $this->getInvoiceParam()))->create();
        return $this->response($res);
    }


    /**
     * 计算信息
     */
    public function calculate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params['order_key'] ?? '',//是否使用余额
            'is_balance' => $this->params['is_balance'] ?? 0,//是否使用余额
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
            'groupbuy_id' => $this->params[ 'groupbuy_id' ] ?? '',//团购id
            'num' => $this->params[ 'num' ] ?? 1,//商品数量(买几套)
            'sku_id' => $this->params[ 'sku_id' ] ?? 0,//sku_id
        ];
        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam()))->orderPayment();
        return $this->response($this->success($res));
    }

}