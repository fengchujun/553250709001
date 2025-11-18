<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.niushop.com.cn
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace addon\presale\api\controller;

use addon\presale\model\PresaleOrderCreate as OrderCreateModel;
use app\api\controller\BaseOrderCreateApi;

/**
 * 订单创建
 * @author Administrator
 *
 */
class Ordercreate extends BaseOrderCreateApi
{

    /**
     * 定金创建订单
     */
    public function depositCreate()
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
     * 定金计算信息
     */
    public function depositCalculate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '', //订单缓存key
            'is_balance' => $this->params[ 'is_balance' ] ?? 0,//是否使用余额
        ];

        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam(), $this->getInvoiceParam()))->confirm();
        return $this->response($this->success($res));
    }

    /**
     * 待支付订单(定金)
     * @return string
     */
    public function depositPayment()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'presale_id' => $this->params[ 'presale_id' ] ?? '', //预售id
            'sku_id' => $this->params[ 'sku_id' ] ?? '',
            'num' => $this->params[ 'num' ] ?? '',
        ];
        if (empty($data[ 'presale_id' ]) && empty($data[ 'sku_id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->setParam(array_merge($data, $this->getCommonParam(), $this->getDeliveryParam()))->depositOrderPayment();
        return $this->response($this->success($res));
    }

    /**
     * 待支付订单(尾款)
     * @return string
     */
//    public function finalPayment()
//    {
//        $token = $this->checkToken();
//        if ($token[ 'code' ] < 0) return $this->response($token);
//        $order_create = new OrderCreateModel();
//        $data = [
//            'id' => $this->params[ 'id' ] ?? '', //预售订单id
//            'site_id' => $this->site_id,//站点id
//            'member_id' => $this->member_id,
//            'is_balance' => $this->params[ 'is_balance' ] ?? 0,//是否使用余额
//            'pay_password' => isset($this->params[ 'pay_password' ]) ? $this->params[ 'pay_password' ] : '',//支付密码
//        ];
//        if (empty($data[ 'id' ])) {
//            return $this->response($this->error('', '缺少必填参数订单数据'));
//        }
//        $res = $order_create->finalCalculate($data);
//        return $this->response($this->success($res));
//    }

    /**
     *  尾款订单
     */
    public function finalCreate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_create = new OrderCreateModel();
        $data = [
            'id' => $this->params[ 'id' ] ?? '', //预售订单id
            'site_id' => $this->site_id,//站点id
            'member_id' => $this->member_id,
            'is_balance' => $this->params[ 'is_balance' ] ?? 0,//是否使用余额
            'pay_password' => $this->params[ 'pay_password' ] ?? '',//支付密码
        ];
        if (empty($data[ 'id' ])) {
            return $this->response($this->error('', '缺少必填参数订单数据'));
        }
        $res = $order_create->setParam($data)->payfinalMoneyPresaleOrder($data);
        return $this->response($res);
    }

}