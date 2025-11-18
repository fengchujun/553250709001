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

use addon\weapp\model\Weapp;
use addon\wechatpay\model\Config as WechatPayModel;
use app\dict\order\OrderDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\express\ExpressPackage;
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon as OrderCommonModel;
use app\model\order\Config as ConfigModel;
use app\model\order\VirtualOrder;
use think\facade\Db;

class Order extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_id = $this->params['order_id'] ?? 0;
        $merchant_trade_no = $this->params['merchant_trade_no'] ?? '';

        if (empty($order_id) && empty($merchant_trade_no)) {
            return $this->response($this->error('', '缺少参数order_id|merchant_trade_no'));
        }

        $order_common_model = new OrderCommonModel();
        $result = $order_common_model->getMemberOrderDetail($order_id, $this->member_id, $this->site_id, $merchant_trade_no);

        //获取未付款订单自动关闭时间 字段'auto_close'
        $config_model = new ConfigModel();
        $order_event_time_config = $config_model->getOrderEventTimeConfig($this->site_id, 'shop');
        $auto_close = $order_event_time_config[ 'data' ][ 'value' ][ 'auto_close' ] * 60 ?? [];
        $result[ 'data' ][ 'auto_close' ] = $auto_close;

        $result[ 'data' ][ 'pay_config' ] = ( new WechatPayModel() )->getPayConfig($this->site_id, $this->app_module, true)[ 'data' ][ 'value' ];

        // 检测微信小程序是否已开通发货信息管理服务
        $weapp_model = new Weapp($this->site_id);
        $result[ 'data' ][ 'is_trade_managed' ] = $weapp_model->orderShippingIsTradeManaged()[ 'data' ];

        return $this->response($result);
    }

    /**
     * 列表信息
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $search_text = $this->params['searchText'] ?? '';
        $order_status = $this->params['order_status'] ?? 'all';
        $page_index = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $order_id = $this->params['order_id'] ?? 0;

        $condition = [
            ['o.member_id', '=', $this->member_id ],
            ['o.site_id', '=', $this->site_id ],
            ['o.is_delete', '=', 0 ]
        ];

        switch ( $order_status ) {
            case 'waitpay'://待付款
                $condition[] = ['o.order_status', '=', 0 ];
                $condition[] = [ 'o.order_scene', '=', 'online' ];
                break;
            case 'waitsend'://待发货
                $condition[] = ['o.order_status', '=', 1 ];
                break;
            case 'waitconfirm'://待收货
                $condition[] = ['o.order_status', 'in', [ 2, 3 ] ];
                $condition[] = ['o.order_type', '<>', 4 ];
                break;
            //todo  这儿改了之后要考虑旧数据的问题
            case 'wait_use'://待使用
                $condition[] = ['o.order_status', 'in', [ 3, 11 ] ];
                $condition[] = ['o.order_type', '=', 4 ];
                break;
            case 'waitrate'://待评价
                $condition[] = ['o.order_status', 'in', [ 4, 10 ] ];
                $condition[] = ['o.is_evaluate', '=', 1 ];
                $condition[] = ['o.evaluate_status', '=', OrderDict::evaluate_wait ];
                break;
            default:
                $condition[] = [ '', 'exp', Db::raw("o.order_scene = 'online' OR (o.order_scene = 'cashier' AND o.pay_status = 1)") ];
        }
//		if (c !== "all") {
//			$condition[] = [ "order_status", "=", $order_status ];
//		}

        //获取未付款订单自动关闭时间 字段'auto_close'
        $config_model = new ConfigModel();
        $order_event_time_config = $config_model->getOrderEventTimeConfig($this->site_id, 'shop');

        if ($order_id) {
            $condition[] = ['o.order_id', '=', $order_id ];
        }
        $join = [];
        $alias = 'o';
        if ($search_text) {
            $condition[] = [ 'o.order_name|o.order_no', 'like', '%' . $search_text . '%' ];
//            $join = [
//                [ 'order_goods og', 'og.order_id = o.order_id', 'left' ]
//            ];
        }

        $order_common_model = new OrderCommonModel();
        $res = $order_common_model->getMemberOrderPageList($condition, $page_index, $page_size, 'o.create_time desc', '*', $alias, $join);

        $auto_close = $order_event_time_config[ 'data' ][ 'value' ][ 'auto_close' ] * 60 ?? [];
        $res[ 'data' ][ 'auto_close' ] = $auto_close;
        $res[ 'data' ][ 'pay_config' ] = ( new WechatPayModel() )->getPayConfig($this->site_id, $this->app_module, true)[ 'data' ][ 'value' ];

        // 检测微信小程序是否已开通发货信息管理服务
        $weapp_model = new Weapp($this->site_id);
        $res[ 'data' ][ 'is_trade_managed' ] = $weapp_model->orderShippingIsTradeManaged()[ 'data' ];
        return $this->response($res);
    }

    /**
     * 订单评价基础信息
     */
    public function evluateinfo()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_id = $this->params['order_id'] ?? 0;
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ORDER_ID'));
        }

        $order_common_model = new OrderCommonModel();
        $order_info = $order_common_model->getOrderInfo([
            [ 'order_id', '=', $order_id ],
            [ 'member_id', '=', $token[ 'data' ][ 'member_id' ] ],
            [ 'order_status', 'in', ( '4,10' ) ],
            [ 'is_evaluate', '=', 1 ],
        ], 'evaluate_status,evaluate_status_name');

        $res = $order_info[ 'data' ];
        if (!empty($res)) {
            if ($res[ 'evaluate_status' ] == OrderDict::evaluate_again) {
                return $this->response($this->error('', '该订单已评价'));
            } else {
                $condition = [
                    [ 'order_id', '=', $order_id ],
                    [ 'member_id', '=', $token[ 'data' ][ 'member_id' ] ],
                    [ 'refund_status', '<>', OrderRefundDict::REFUND_COMPLETE ],
                ];
                $res[ 'list' ] = $order_common_model->getOrderGoodsList($condition, 'order_goods_id,order_id,order_no,site_id,member_id,goods_id,sku_id,sku_name,sku_image,price,num')[ 'data' ];
                return $this->response($this->success($res));
            }
        } else {
            return $this->response($this->error('', '没有找到该订单'));
        }

    }

    /**
     * 订单收货(收到所有货物)
     */
    public function takeDelivery()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_id = $this->params['order_id'] ?? 0;
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ORDER_ID'));
        }
        $order_model = new OrderCommonModel();
        $log_data = [
            'uid' => $this->member_id,
            'action_way' => 1
        ];
        $result = $order_model->orderCommonTakeDelivery($order_id, $log_data);
        return $this->response($result);
    }

    /**
     * 关闭订单
     */
    public function close()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_id = $this->params['order_id'] ?? 0;
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ORDER_ID'));
        }

        $order_model = new OrderModel();

        //关闭检测
        $check_res = $order_model->activeOrderCloseCheck($order_id);
        if($check_res['code'] < 0) $this->response($check_res);

        $log_data = [
            'uid' => $this->member_id,
            'action_way' => 1
        ];

        $result = $order_model->orderClose($order_id, $log_data);
        return $this->response($result);
    }

    /**
     * 获取订单数量
     */
    public function num()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_common_model = new OrderCommonModel();
        $data = $order_common_model->getMemberOrderNum($this->member_id);
        return $this->response($data);
    }

    /**
     * 订单包裹信息
     */
    public function package()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_id = $this->params['order_id'] ?? '';//订单id
        $express_package_model = new ExpressPackage();
        $condition = [
            ['member_id', '=', $this->member_id ],
            ['order_id', '=', $order_id ],
        ];

        $order_common_model = new OrderCommonModel();
        $order_detail = $order_common_model->getOrderInfo([ [ 'member_id', '=', $this->member_id ], [ 'order_id', '=', $order_id ], [ 'site_id', '=', $this->site_id ] ]);
        $save_trace = $order_detail['data']['order_status'] == OrderCommonModel::ORDER_TAKE_DELIVERY;
        $result = $express_package_model->package($condition, $order_detail[ 'data' ][ 'mobile' ], $save_trace);
        if (!empty($result)) {
            foreach ($result as $kk => $vv) {
                if (!empty($vv[ 'trace' ][ 'list' ])) {
                    $result[ $kk ][ 'trace' ][ 'list' ] = array_reverse($vv[ 'trace' ][ 'list' ]);
                }
            }

        }
        if ($result) return $this->response($this->success($result));
        else return $this->response($this->error());
    }

    /**
     * 订单支付
     * @return string
     */
    public function pay()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_ids = $this->params['order_ids'] ?? '';//订单id
        if (empty($order_ids)) return $this->response($this->error('', '订单数据为空'));
        $order_common_model = new OrderCommonModel();
        $result = $order_common_model->splitOrderPay($order_ids);
        return $this->response($result);
    }

    /**
     * 交易协议
     * @return false|string
     */
    public function transactionAgreement()
    {
        $config_model = new ConfigModel();
        $document_info = $config_model->getTransactionDocument($this->site_id, $this->app_module);
        return $this->response($document_info);
    }

    /**
     * 虚拟订单收货
     */
    public function memberVirtualTakeDelivery()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_id = $this->params[ 'order_id' ] ?? 0;//订单id
        if (empty($order_id)) return $this->response($this->error('', '订单数据为空'));
        $virtual_order_model = new VirtualOrder();
        $params = [
            'order_id' => $order_id,
            'site_id' => $this->site_id,
            'member_id' => $this->member_id
        ];
        $log_data = [
            'uid' => $this->member_id,
            'action_way' => 1
        ];
        $result = $virtual_order_model->virtualTakeDelivery($params, $log_data);
        return $this->response($result);
    }

    /**
     * 删除订单
     */
    public function delete()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_id = $this->params['order_id'] ?? 0;
        $order_model = new OrderModel();
        $result = $order_model->deleteOrder([['order_id', '=', $order_id], ['member_id', '=', $this->member_id], ['order_status', '=', OrderModel::ORDER_CLOSE]]);
        return $this->response($result);
    }
}