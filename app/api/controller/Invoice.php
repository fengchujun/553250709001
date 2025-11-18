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

class Invoice extends BaseOrderCreateApi
{

    /**
     * 订单申请开票
     */
    public function applyInvoice()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = array_merge(
            [
                'order_id' => $this->params['order_id']
            ],
            $this->getInvoiceParam()
        );
        $result = $order_create->initInvoice($data);
        if ($result['code'] < 0) {
            return $this->response($result);
        }
        $order_create->calculateInvoice();
        if ($order_create->error) {
            return $this->response($this->error($order_create->error_msg));
        }
        $res = $order_create->saveInvoice();
        return $this->response($res);
    }
}