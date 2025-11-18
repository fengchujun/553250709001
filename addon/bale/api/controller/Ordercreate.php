<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bale\api\controller;

use addon\bale\model\BaleOrderCreate as OrderCreateModel;
use app\api\controller\BaseOrderCreateApi;

/**
 * 订单创建
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
            'bale_id' => $this->params[ 'bale_id' ] ?? '',
            'sku_list_json' => $this->params[ 'sku_list_json' ] ?? 1,//打包一口价商品信息
        ];
        if (empty($data[ 'bale_id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam()))->orderPayment();
        return $this->response($this->success($res));
    }
}