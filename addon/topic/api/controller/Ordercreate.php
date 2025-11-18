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

namespace addon\topic\api\controller;

use addon\topic\model\TopicOrderCreate as OrderCreateModel;
use app\api\controller\BaseOrderCreateApi;

/**
 * 订单创建
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
            'order_key' => $this->params['order_key'] ?? '',//订单缓存
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
            'id' => $this->params[ 'topic_goods_id' ] ?? '',//专题商品id
            'num' => $this->params[ 'num' ] ?? 1,//专题商品数量(买几套)
        ];
        if (empty($data[ 'id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        if ($data[ 'num' ] < 1) {
            return $this->response($this->error('', '购买数量不能小于1'));
        }
        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam()))->orderPayment();
        return $this->response($this->success($res));
    }

}