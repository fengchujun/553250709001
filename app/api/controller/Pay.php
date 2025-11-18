<?php
/**
 * Pay.php
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

use app\model\order\Order as OrderModel;
use app\model\system\Pay as PayModel;
use app\model\order\Config;
use app\model\system\PayBalance;

/**
 * 支付控制器
 */
class Pay extends BaseApi
{
    /**
     * 支付信息
     */
    public function info()
    {
        $out_trade_no = $this->params[ 'out_trade_no' ];
        $pay = new PayModel();
        $info = $pay->getPayInfo($out_trade_no)[ 'data' ] ?? [];

        if (!empty($info)) {
            if (in_array($info[ 'event' ], [ 'OrderPayNotify', 'CashierOrderPayNotify' ])) {
                $order_model = new OrderModel();
                $order_info = $order_model->getOrderInfo([ [ 'out_trade_no', '=', $out_trade_no ] ], 'order_id,order_type,create_time')[ 'data' ];
                if (!empty($order_info)) {
                    $info[ 'order_id' ] = $order_info[ 'order_id' ];
                    $info[ 'order_type' ] = $order_info[ 'order_type' ];
                    //获取未付款订单自动关闭时间
                    $config_model = new Config();
                    $order_event_time_config = $config_model->getOrderEventTimeConfig($this->site_id, 'shop');
                    $info['auto_close_time'] = $order_event_time_config[ 'data' ][ 'value' ][ 'auto_close' ] * 60 + $order_info['create_time'];
                }
            }
        }
        return $this->response($this->success($info));
    }

    /**
     * 支付调用
     */
    public function pay()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $pay_type = $this->params[ 'pay_type' ];
        $out_trade_no = $this->params[ 'out_trade_no' ];
        $app_type = $this->params[ 'app_type' ];
        $return_url = isset($this->params[ 'return_url' ]) && !empty($this->params[ 'return_url' ]) ? urldecode($this->params[ 'return_url' ]) : null;
        $scene = $this->params[ 'scene' ] ?? 0;
        $is_balance = $this->params[ 'is_balance' ] ?? 0;
        $pay = new PayModel();
        $info = $pay->pay($pay_type, $out_trade_no, $app_type, $this->member_id, $return_url, $is_balance, $scene);
        return $this->response($info);
    }

    /**
     * 支付方式
     */
    public function type()
    {
        $pay = new PayModel();
        $info = $pay->getPayType($this->params);
        $temp = empty($info) ? [] : $info;
        $type = [];
        foreach ($temp[ 'data' ] as $k => $v) {
            $type[] = $v['pay_type'];
        }
        $type = implode(',', $type);
        return $this->response(success(0, '', [ 'pay_type' => $type ]));
    }

    /**
     * 获取订单支付状态
     */
    public function status()
    {
        $pay = new PayModel();
        $out_trade_no = $this->params[ 'out_trade_no' ];
        $res = $pay->getPayInfo($out_trade_no);
        if(empty($res['data'])) $res = $pay->error();
        return $this->response($res);
    }

    /**
     * 获取余额支付配置
     */
    public function getBalanceConfig()
    {
        $config_model = new Config();
        $res = $order_evaluate_config = $config_model->getBalanceConfig($this->site_id);
        return $this->response($this->success($res[ 'data' ][ 'value' ]));
    }

    /**
     * 重置支付
     */
    public function resetPay()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $out_trade_no = $this->params[ 'out_trade_no' ];
        $pay = new PayModel();
        $result = $pay->resetPay([ 'out_trade_no' => $out_trade_no ]);
        return $this->response($result);
    }

    /**
     * 会员付款码
     */
    public function memberPayCode()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $data = ( new PayBalance() )->create([ 'member_id' => $this->member_id, 'site_id' => $this->site_id ]);
        return $this->response($data);
    }

    /**
     * 查询会员付款码信息
     * @return false|string
     */
    public function memberPayInfo()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $auth_code = $this->params[ 'auth_code' ] ?? '';

        $data = ( new PayBalance() )->getInfo([ [ 'member_id', '=', $this->member_id ], [ 'site_id', '=', $this->site_id ], [ 'auth_code', '=', $auth_code ] ], 'status,out_trade_no');
        return $this->response($data);
    }

    /**
     * 通过外部交易号获取订单详情路径
     */
    public function outTradeNoToOrderDetailPath()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $out_trade_no = $this->params[ 'out_trade_no' ] ?? '';

        $pay_model = new PayModel();
        $pay_info = $pay_model->getPayInfo($out_trade_no)[ 'data' ];
        if(empty($pay_info)){
            return $this->response($this->error(null, '交易信息有误'));
        }
        if($this->app_type == 'pc'){
            $event = 'PcOrderDetailPathByPayInfo';
        }else{
            $event = 'WapOrderDetailPathByPayInfo';
        }
        $order_detail_path = event($event, $pay_info, true);
        if(empty($order_detail_path)){
            return $this->response($this->error(null, '未获取到订单详情路径'));
        }
        return $this->response($this->success($order_detail_path));
    }
}