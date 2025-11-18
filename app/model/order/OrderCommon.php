<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use app\dict\order\OrderDict;
use app\dict\order\OrderPayDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\event\OrderClose;
use app\model\order\event\OrderComplete;
use app\model\order\OrderRefund as OrderRefundModel;
use app\model\system\Cron;
use app\model\system\Pay;
use app\model\verify\Verify;
use Exception;
use think\db\exception\DbException;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;

/**
 * 常规订单操作
 *
 * @author Administrator
 *
 */
class OrderCommon extends BaseModel
{
    /*****************************************************************************************订单基础状态（其他使用）********************************/
    // 订单待付款
    public const ORDER_CREATE = 0;

    // 订单已支付
    public const ORDER_PAY = 1;

    // 订单已发货（配货）
    public const ORDER_DELIVERY = 3;

    // 订单已收货
    public const ORDER_TAKE_DELIVERY = 4;

    // 订单已结算完成
    public const ORDER_COMPLETE = 10;

    // 订单已关闭
    public const ORDER_CLOSE = -1;

    /*********************************************************************************订单支付状态****************************************************/
    // 待支付
    public const PAY_WAIT = 0;

    // 支付中
    public const PAY_DOING = 1;

    // 已支付
    public const PAY_FINISH = 2;

    /**************************************************************************支付方式************************************************************/
    public const OFFLINEPAY = 10;


    // 订单待使用
    public const ORDER_WAIT_VERIFY = 11;

    //订单已使用
    public const ORDER_VERIFYED = 12;
    /**
     * 线上收款方式
     * @var array
     */
    public static $online_pay_type = [ 'wechatpay', 'alipay' ];
    /**
     * 基础订单状态(不同类型的订单可以不使用这些状态，但是不能冲突)
     * @var array
     */
    public $order_status = [
        self::ORDER_CREATE => [
            'status' => self::ORDER_CREATE,
            'name' => '待支付',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon.png',
            'action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'orderAddressUpdate',
                    'title' => '修改地址',
                    'color' => ''
                ],
                [
                    'action' => 'orderAdjustMoney',
                    'title' => '调整价格',
                    'color' => ''
                ],
                [
                    'action' => 'offlinePay',
                    'title' => '调整价格',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'orderPay',
                    'title' => '支付',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_PAY => [
            'status' => self::ORDER_PAY,
            'name' => '待发货',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [

            ],
            'member_action' => [

            ],
            'color' => ''
        ],
        self::ORDER_DELIVERY => [
            'status' => self::ORDER_DELIVERY,
            'name' => '已发货',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-receive.png',
            'action' => [
                [
                    'action' => 'takeDelivery',
                    'title' => '确认收货',
                    'color' => ''
                ],
            ],
            'member_action' => [
            ],
            'color' => ''
        ],
        self::ORDER_TAKE_DELIVERY => [
            'status' => self::ORDER_TAKE_DELIVERY,
            'name' => '已收货',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-received.png',
            'action' => [
            ],
            'member_action' => [
            ],
            'color' => ''
        ],
        // 新增虚拟 订单状态
        self::ORDER_WAIT_VERIFY => [
            'status' => self::ORDER_WAIT_VERIFY,
            'name' => '待使用',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-close.png',
            'action' => [

            ],
            'member_action' => [

            ],
            'color' => ''
        ],
        self::ORDER_VERIFYED => [
            'status' => self::ORDER_VERIFYED,
            'name' => '已使用',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-close.png',
            'action' => [

            ],
            'member_action' => [

            ],
            'color' => ''
        ],
        self::ORDER_COMPLETE => [
            'status' => self::ORDER_COMPLETE,
            'name' => '已完成',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-received.png',
            'action' => [
            ],
            'member_action' => [

            ],
            'color' => ''
        ],
        self::ORDER_CLOSE => [
            'status' => self::ORDER_CLOSE,
            'name' => '已关闭',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-close.png',
            'action' => [

            ],
            'member_action' => [

            ],
            'color' => ''
        ],

    ];
    /**
     * 基础支付方式(不考虑实际在线支付方式或者货到付款方式)
     * @var array
     */
    public $pay_type = [
        'ONLINE_PAY' => '在线支付',
        'BALANCE' => '余额支付',
        /*'offlinepay' => '线下支付',*/
        'POINT' => '积分兑换'
    ];
    /**
     * 订单类型
     * @var array
     */
    public $order_type = [
        1 => '物流订单',
        2 => '自提订单',
        3 => '外卖订单',
        4 => '虚拟订单',
        5 => '收银订单'
    ];

    /**
     * 获取支付方式
     * @param array $params
     * @return array
     */
    public function getPayType($params = [])
    {
        $order_type = $params[ 'order_type' ] ?? '';
        //获取订单基础的其他支付方式
        if (!empty($order_type)) {
            $order_model = $this->getOrderModel([ 'order_type' => $order_type ]);
            $pay_type = $order_model->pay_type;
        } else {
            $pay_type = $this->pay_type;
        }
        //获取当前所有在线支付方式
        $onlinepay = event('PayType', []);
        if (!empty($onlinepay)) {
            foreach ($onlinepay as $v) {
                $pay_type[ $v[ 'pay_type' ] ] = $v[ 'pay_type_name' ];
            }
        }
        return $pay_type;
    }

    /**
     * 获取订单model对象
     * @param $order_info
     * @return LocalOrder|Order|StoreOrder|VirtualOrder|mixed|void
     */
    public function getOrderModel($order_info)
    {
        $order_model = event('GetOrderModel', $order_info, true);
        if (empty($order_model)) {
            //调用各种订单
            switch ( $order_info[ 'order_type' ] ) {
                case OrderDict::express:
                    // 普通物流订单
                    $order_model = new Order();
                    break;
                case OrderDict::store:
                    // 门店自提订单
                    $order_model = new StoreOrder();
                    break;
                case OrderDict::local:
                    // 本地配送订单
                    $order_model = new LocalOrder();
                    break;
                case OrderDict::virtual:
                    // 虚拟订单
                    $order_model = new VirtualOrder();
                    break;
            }
        }

        return $order_model;
    }

    /**
     * 订单来源
     * @param array $params
     * @return array|mixed
     */
    public function getOrderFromList($params = [])
    {
        $order_from_list = config('app_type');
        $from_event_list = event('OrderFromList', $params);//没有的话就别返回值了(未来插件订单场景扩展)
        foreach ($from_event_list as $v) {
            $order_from_list = array_merge($order_from_list, $v);
        }
        return $order_from_list;
    }

    /**
     * 订单类型(根据物流配送来区分)
     * @return array
     */
    public function getOrderTypeStatusList()
    {
        $list = [];
        $all_order_list = array_column($this->order_status, 'name', 'status');
        $all_order_list[ 'refunding' ] = '退款中';
        $list[ 'all' ] = [
            'name' => '全部',
            'type' => 'all',
            'status' => $all_order_list
        ];
        if (!addon_is_exit('cashier')) {
            unset($this->order_type[ OrderDict::cashier ]);
        }
        foreach ($this->order_type as $k => $v) {
            $order_model = $this->getOrderModel([ 'order_type' => $k ]);
            $temp_order_list = array_column($order_model->order_status, 'name', 'status');
            $temp_order_list[ 'refunding' ] = '退款中';

            $item = [
                'name' => $v,
                'type' => $k,
                'status' => $temp_order_list
            ];
            $list[ $k ] = $item;
        }
        return $list;
    }



    /**********************************************************************************订单操作基础方法（订单关闭，订单完成，订单调价）开始********/



    /**
     * 订单删除
     * @param $condition
     * @return array
     */
    public function deleteOrder($condition)
    {
        $res = model('order')->update([ 'is_delete' => 1 ], $condition);
        if ($res === false) {
            return $this->error(null, '删除失败');
        } else {
            return $this->success($res);
        }
    }

    /**
     * 订单完成
     * @param $order_id
     * @return array
     * @throws DbException
     */
    public function orderComplete($order_id)
    {
        $cache_name = 'order_complete_execute_' . $order_id;
        if(Cache::get($cache_name)) return $this->success();
        Cache::set($cache_name, 1);

        try{
            $order_complete_status = self::ORDER_COMPLETE;
            $order_condition = [ [ 'order_id', '=', $order_id ] ];
            $order_info = model('order')->getInfo($order_condition, '*');
            if (empty($order_info)) {
                Cache::delete($cache_name);
                return $this->success(['message' => '订单数据缺失']);
            }
            if (!in_array($order_info['order_status'], [self::ORDER_TAKE_DELIVERY, self::ORDER_VERIFYED])) {
                Cache::delete($cache_name);
                return $this->success(['message' => '订单不是已收货状态或已核销状态']);
            }
            //校验
            $check_result = ( new OrderComplete() )->check([
                'order_info' => $order_info,
            ]);
            if ($check_result[ 'code' ] < 0){
                Cache::delete($cache_name);
                return $check_result;
            }

            $site_id = $order_info[ 'site_id' ];
            $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);
            $order_action_array = $this->getOrderCommonAction($order_info, $order_complete_status);
            $order_data = [
                'order_status' => $order_action_array[ 'order_status' ],
                'order_status_name' => $order_action_array[ 'order_status_name' ],
                'order_status_action' => $order_action_array[ 'order_status_action' ],
                'finish_time' => time()
            ];
            $order_config = Config::getOrderConfig($site_id);
            $after_sales_time = $order_config[ 'after_sales_time' ] ?? 0;
            if ($after_sales_time > 0) {
                OrderCron::afterSaleClose(['order_id' => $order_id, 'after_sales_time' => $after_sales_time]);
            } else {
                $order_data[ 'is_enable_refund' ] = 0;
            }
            $res = model('order')->update($order_data, $order_condition);
            /******************************************************* 订单退款操作相关 **********************************************************/
            //订单项移除可退款操作
            $order_refund_model = new OrderRefund();
            $order_refund_model->removeOrderGoodsRefundAction($order_condition);

            //关键业务
            ( new OrderComplete() )->event([
                'order_info' => $order_info,
            ]);
            //后续业务
            ( new OrderComplete() )->after([
                'order_info' => $order_info,
            ]);

            Cache::delete($cache_name);
            return $this->success($res);
        }catch(\Exception $e){
            Cache::delete($cache_name);
            return $this->error(['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], '订单完成捕获错误');
        }
    }


    /**********************************************************************************订单支付相关业务开始********/

    /**
     * 鉴于耦合度太高,封装一些公共操作
     * @param $temp
     * @param $action
     * @return array
     */
    public function getOrderCommonAction($temp, $action)
    {
        if (is_object($temp)) {
            $order_model = $temp;
        } else {
            $order_model = $this->getOrderModel($temp);
        }
        return [
            'order_status' => $action,
            'order_status_name' => $order_model->order_status[ $action ][ 'name' ] ?? '',
            'order_status_action' => json_encode($order_model->order_status[ $action ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * 主动关闭订单检测
     * @param $order_id
     * @return array
     */
    public function activeOrderCloseCheck($order_id)
    {
        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'order_status');
        if(empty($order_info)){
            return $this->error(null, '订单不存在');
        }
        if($order_info['order_status'] != self::ORDER_CREATE){
            return $this->error(null, '订单不是待支付状态，不可关闭');
        }
        return $this->success();
    }

    /**
     * 订单关闭
     * @param $order_id
     * @param array $log_data
     * @param string $close_cause
     * @return array
     */
    public function orderClose($order_id, $log_data = [], $close_cause = '')
    {
        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], '*');
        if ($order_info[ 'order_status' ] == self::ORDER_CLOSE) {
            return $this->success();
        }
        $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);
        $local_result = $this->verifyOrderLock($order_info);
        if ($local_result[ 'code' ] < 0) {
            return $local_result;
        }
        //校验支付
        $check_result = ( new OrderClose() )->check([ 'order_info' => $order_info ]);
        if ($check_result[ 'code' ] < 0) return $check_result;
        $order_data = [
            'order_status' => self::ORDER_CLOSE,
            'order_status_name' => $this->order_status[ self::ORDER_CLOSE ][ 'name' ],
            'order_status_action' => json_encode($this->order_status[ self::ORDER_CLOSE ], JSON_UNESCAPED_UNICODE),
            'close_time' => time(),
            'is_enable_refund' => 0,
            'is_evaluate' => 0,
            'close_cause' => $close_cause
        ];
        model('order')->startTrans();
        try {
            model('order')->update($order_data, [ [ 'order_id', '=', $order_id ] ]);
            /******************************************************* 移除可退款相关 **********************************************************/
            //订单项移除可退款操作
            $order_refund_model = new OrderRefund();
            $order_refund_model->removeOrderGoodsRefundAction([ [ 'order_id', '=', $order_id ] ]);

            //订单关闭操作
            ( new OrderClose() )->event([
                'order_info' => $order_info,
            ]);
            model('order')->commit();
            //订单关闭后操作
            ( new OrderClose() )->after([
                'order_info' => $order_info,
                'log_data' => $log_data,
                'close_cause' => $close_cause

            ]);
            return $this->success();
        } catch (Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 验证订单锁定状态
     * @param $param
     * @return array
     */
    public function verifyOrderLock($param)
    {
        if (!is_array($param)) {
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $param ] ], 'is_lock');
        } else {
            $order_info = $param;
        }
        if ($order_info[ 'is_lock' ] == 1) {//判断订单锁定状态
            return $this->error('', 'ORDER_LOCK');
        } else {
            return $this->success();
        }
    }

    /************************************************************************ 订单调价 start **************************************************************************/

    /**
     * 订单线上支付(废弃合并支付)
     * @param $data
     * @return array
     */
    public function orderOnlinePay($data)
    {
        $out_trade_no = $data[ 'out_trade_no' ];
        $pay_model = new Pay();
        $pay_info = $pay_model->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)){
            return $this->error(['PAY_RELATE_ERROR' => 1], '支付信息有误');
        }

        //初始化订单信息
        $data[ 'order_info' ] = model('order')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ] ], '*');
        if ($data[ 'order_info' ][ 'order_status' ] == self::ORDER_CLOSE) {
            return $this->error('订单已关闭！');
        }
        if ($data[ 'order_info' ][ 'pay_status' ] == 1) {
            return $this->success('订单已支付！');
        }
        $data[ 'order_info' ][ 'goods_num' ] = numberFormat($data[ 'order_info' ][ 'goods_num' ]);
        //初始化支付信息
        $data[ 'pay_info' ] = $pay_info;
        //初始化会员信息
        $data[ 'member_info' ] = model('member')->getInfo([ [ 'member_id', '=', $data[ 'order_info' ][ 'member_id' ] ] ], '*');
        $data[ 'order_id' ] = $data[ 'order_info' ][ 'order_id' ];
        $order_model = $this->getOrderModel($data[ 'order_info' ]);
        model('order')->startTrans();
        try {
            /** 各种类型订单支付 **/
            $order_model->orderPay($data);
            //订单支付关键业务
            ( new \app\model\order\event\OrderPay() )->event($data);
            model('order')->commit();
        } catch (Exception $e) {
            //todo  不应该失败
            model('order')->rollback();
            Log::write('OrderPaySuccess_' . $e->getMessage() . $e->getFile() . $e->getLine());
            return $this->error('', $e->getMessage());
        }

        //订单支付后操作
        Queue::push('app\job\order\OrderPayAfter', $data);
        return $this->success();
    }

    /**
     * 拆分订单
     * @param $order_id
     * @param string $out_trade_no
     * @return array
     * @throws DbException
     */
    public function splitOrderPay($order_id, $out_trade_no = '')
    {

        $order_condition = [
            [ 'pay_status', '=', 0 ],
            [ 'order_status', '=', 0 ]
        ];
        if (empty($order_id)) {
            $order_condition[] = [ 'out_trade_no', '=', $out_trade_no ];
        } else {
            $order_condition[] = [ 'order_id', '=', $order_id ];
        }
        $order_info = model('order')->getInfo($order_condition, 'pay_money,order_name,out_trade_no,order_id,pay_status,site_id,member_id,member_card_order');

        if (empty($order_info))
            return $this->error([], '选中订单中包含已支付或已关闭数据！');

        $out_trade_no = $order_info[ 'out_trade_no' ];
        $pay_model = new Pay();
        $pay_info = $pay_model->getPayInfo($out_trade_no)[ 'data' ];
        if (!empty($pay_info) && $pay_info['pay_status'] != Pay::PAY_STATUS_CLOSE) {
            if ($pay_info[ 'balance' ] == 0 && $pay_info[ 'balance_money' ] == 0) {
                return $this->success($out_trade_no);
            }
        }

        $result = $pay_model->closePay($out_trade_no);//关闭旧支付单据
        if ($result[ 'code' ] < 0) {
            return $result;
        }

        //生成新的支付流水号
        $out_trade_no = $pay_model->createOutTradeNo($order_info[ 'member_id' ] ?? 0);
        if (!empty($order_info[ 'member_card_order' ])) model('member_level_order')->update([ 'out_trade_no' => $out_trade_no ], [ [ 'order_id', '=', $order_info[ 'member_card_order' ] ], [ 'pay_status', '=', 0 ] ]);
        model('order')->update([ 'out_trade_no' => $out_trade_no ], [ [ 'order_id', '=', $order_info[ 'order_id' ] ], [ 'pay_status', '=', 0 ] ]);

        $result = $pay_model->addPay($order_info[ 'site_id' ], $out_trade_no, '', $order_info[ 'order_name' ], $order_info[ 'order_name' ], $order_info[ 'pay_money' ], '', 'OrderPayNotify', '', $order_info['order_id'], $order_info['member_id']);
        return $this->success($out_trade_no);
    }

    /**
     * 订单金额调整 按整单调整
     * @param $order_id
     * @param $adjust_money
     * @param $delivery_money
     * @return array
     */
    public function orderAdjustMoney($order_id, $adjust_money, $delivery_money)
    {
        model('order')->startTrans();
        try {
            //查询订单
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'site_id, out_trade_no,delivery_money, adjust_money, pay_money, order_money, promotion_money, coupon_money, goods_money, invoice_money, invoice_delivery_money, promotion_money, coupon_money, invoice_rate, invoice_delivery_money, balance_money, point_money, member_card_money');
            if (empty($order_info))
                return $this->error('', '找不到订单！');

            if ($delivery_money < 0)
                return $this->error('', '配送费用不能小于0！');

            $real_goods_money = $order_info[ 'goods_money' ] - $order_info[ 'promotion_money' ] - $order_info[ 'coupon_money' ] - $order_info[ 'point_money' ];//计算出订单真实商品金额
            $new_goods_money = $real_goods_money + $adjust_money;

            if ($new_goods_money < 0)
                return $this->error('', '真实商品金额不能小于0！');

            $invoice_money = round(floor($new_goods_money * $order_info[ 'invoice_rate' ]) / 100, 2);
            $new_order_money = $invoice_money + $new_goods_money + $delivery_money + $order_info[ 'invoice_delivery_money' ] + $order_info[ 'member_card_money' ];

            if ($new_order_money < 0)
                return $this->error('', '订单金额不能小于0！');

            $pay_money = $new_order_money - $order_info[ 'balance_money' ];
            if ($pay_money < 0)
                return $this->error('', '实际支付不能小于0！');

            $data_order = [
                'delivery_money' => $delivery_money,
                'pay_money' => $pay_money,
                'adjust_money' => $adjust_money,
                'order_money' => $new_order_money,
                'invoice_money' => $invoice_money
            ];
            model('order')->update($data_order, [ [ 'order_id', '=', $order_id ] ]);

            $order_goods_list = model('order_goods')->getList([ [ 'order_id', '=', $order_id ] ], 'order_goods_id,goods_money,adjust_money,coupon_money,promotion_money,point_money');
            //将调价摊派到所有订单项
            $real_goods_money = $order_info[ 'goods_money' ] - $order_info[ 'promotion_money' ] - $order_info[ 'coupon_money' ] - $order_info[ 'point_money' ];
            $this->distributionGoodsAdjustMoney($order_goods_list, $real_goods_money, $adjust_money);

            //关闭原支付  生成新支付
            $pay_model = new Pay();
            $pay_result = $pay_model->closePay($order_info[ 'out_trade_no' ]);//关闭旧支付单据
            if ($pay_result[ 'code' ] < 0) {
                model('order')->rollback();
                return $pay_result;
            }

            //重新生成支付单
            $res = $this->splitOrderPay($order_id);
            if($res['code'] < 0){
                model('order')->rollback();
                return $res;
            }
            $order_info['out_trade_no'] = $res['data'];

            // 调价之后支付金额为0
            if ($pay_money == 0) {
                $pay_model = new Pay();
                $res = $pay_model->onlinePay($order_info[ 'out_trade_no' ], OrderPayDict::offline_pay, '', '', []);
                if($res['code'] < 0){
                    model('order')->rollback();
                    return $res;
                }
            }

            model('order')->commit();

            return $this->success();
        } catch (Exception $e) {
            model('order')->rollback();
            return $this->error([$e->getFile(),$e->getLine(),$e->getMessage()], $e->getMessage());
        }
    }

    /************************************************************************ 订单调价 end **************************************************************************/

    /**
     * 按比例摊派订单调价
     * @param $goods_list
     * @param $goods_money
     * @param $adjust_money
     * @return array
     */
    public function distributionGoodsAdjustMoney($goods_list, $goods_money, $adjust_money)
    {
        $temp_adjust_money = $adjust_money;
        $last_key = count($goods_list) - 1;
        foreach ($goods_list as $k => $v) {
            $item_goods_money = $v[ 'goods_money' ] - $v[ 'promotion_money' ] - $v[ 'coupon_money' ] - $v[ 'point_money' ];
            if ($last_key != $k) {
                $item_adjust_money = round($item_goods_money / $goods_money * $adjust_money, 2);
            } else {
                $item_adjust_money = $temp_adjust_money;
            }
            $temp_adjust_money -= $item_adjust_money;
            $real_goods_money = $item_goods_money + $item_adjust_money;
            $real_goods_money = max($real_goods_money, 0);
            $order_goods_data = [
                'adjust_money' => $item_adjust_money,
                'real_goods_money' => $real_goods_money,
            ];
            model('order_goods')->update($order_goods_data, [ [ 'order_goods_id', '=', $v[ 'order_goods_id' ] ] ]);
        }
        return $this->success();
    }

    /**
     * 订单线下支付
     * @param $order_id
     * @param array $log_data
     * @return array|mixed|multitype|void
     */
    public function orderOfflinePay($order_id, $log_data = [])
    {
        //支付业务
        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ], [ 'order_status', '=', 0 ] ], 'out_trade_no');
        if(empty($order_info)){
            return $this->error(null, '订单信息有误');
        }
        $pay_model = new Pay();
        return $pay_model->onlinePay($order_info[ 'out_trade_no' ], OrderPayDict::offline_pay, '', '', $log_data);
    }

    /**
     * 订单删除
     * @param $order_id
     * @param array $user_info
     * @return array
     */
    public function orderDelete($order_id, $user_info = [])
    {

        model('order')->startTrans();
        try {
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'order_status,site_id,order_status_name');
            if ($order_info[ 'order_status' ] != self::ORDER_CLOSE) {
                return $this->error([], '只有已经关闭的订单才能删除！');
            }
            $order_data = [
                'is_delete' => 1
            ];

            //记录订单日志 start
            if ($user_info) {
                $log_data = [
                    'order_id' => $order_id,
                    'uid' => $user_info[ 'uid' ],
                    'nick_name' => $user_info[ 'username' ],
                    'action' => '商家删除了订单',
                    'action_way' => 2,
                    'order_status' => $order_info[ 'order_status' ],
                    'order_status_name' => $order_info[ 'order_status_name' ]
                ];
                OrderLog::addOrderLog($log_data, $this);
            }
            //记录订单日志 end
            $res = model('order')->update($order_data, [ [ 'order_id', '=', $order_id ] ]);
            model('order')->commit();
            return $this->success();
        } catch (Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 订单编辑
     * @param $data
     * @param $condition
     * @param array $log_data
     * @return array
     */
    public function orderUpdate($data, $condition, $log_data = [])
    {
        $order_model = model('order');
        $res = $order_model->update($data, $condition);
        if ($res === false) {
            return $this->error();
        } else {
            //记录订单日志 start
            if ($log_data) {
                $order_info = model('order')->getInfo([ 'order_id' => $log_data[ 'order_id' ] ], 'order_status,order_status_name');
                $log_data = array_merge($log_data, [
                    'order_status' => $order_info[ 'order_status' ],
                    'order_status_name' => $order_info[ 'order_status_name' ]
                ]);
                OrderLog::addOrderLog($log_data, $this);
            }
            //记录订单日志 end
            return $this->success($res);
        }
    }

    /**
     * 订单发货
     * @param $order_id
     * @param array $log_data
     * @return array|int
     */
    public function orderCommonDelivery($order_id, $log_data = [])
    {
        $order_common_model = new OrderCommon();
        $local_result = $order_common_model->verifyOrderLock($order_id);
        if ($local_result[ 'code' ] < 0)
            return $local_result;

        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'order_type,site_id');
        $order_model = $this->getOrderModel($order_info);
        return $order_model->orderDelivery($order_id, $log_data);
    }

    /**
     * 订单收货
     * @param $order_id
     * @param array $log_data
     * @return array
     */
    public function orderCommonTakeDelivery($order_id, $log_data = [])
    {
        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], '*');
        if (empty($order_info))
            return $this->error([], 'ORDER_EMPTY');

        $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);
        $local_result = $this->verifyOrderLock($order_id);
        if ($local_result[ 'code' ] < 0)
            return $local_result;

        $order_status = $order_info[ 'order_status' ];
        $virtual_order_model = new VirtualOrder();
        if (!in_array($order_status, [self::ORDER_DELIVERY, self::ORDER_WAIT_VERIFY, StoreOrder::ORDER_PENDING_DELIVERY])) {
            return $this->error(null, '不是已发货或待核销状态');
        }

        $order_model = $this->getOrderModel($order_info);
        model('order')->startTrans();
        try {
            $order_model->orderTakeDelivery($order_id);
            //改变订单状态

            //todo  如果是虚拟商品并且有虚拟码的话, 订单状态应该为已使用
            if ($order_status == $virtual_order_model::ORDER_WAIT_VERIFY) {
                $order_action_array = $this->getOrderCommonAction($order_model, $virtual_order_model::ORDER_VERIFYED);
            } else {
                $order_action_array = $this->getOrderCommonAction($order_model, $order_model::ORDER_TAKE_DELIVERY);
            }

            $order_data = [
                'order_status' => $order_action_array[ 'order_status' ],
                'order_status_name' => $order_action_array[ 'order_status_name' ],
                'order_status_action' => $order_action_array[ 'order_status_action' ],
                'is_evaluate' => 1,
                'evaluate_status' => OrderDict::evaluate_wait,
                'evaluate_status_name' => OrderDict::getEvaluateStatus(OrderDict::evaluate_wait),
                'sign_time' => time()
            ];
            model('order')->update($order_data, [ [ 'order_id', '=', $order_id ] ]);

            model('order')->commit();
            //自动完成事件
            OrderCron::complete(['order_id' => $order_id, 'site_id' => $order_info['site_id']]);
            // 小程序确认收货提醒、视频号接口返回信息
            Queue::push('app\job\order\OrderTakeDeliveryAfter', [ 'order_id' => $order_id, 'site_id' => $order_info[ 'site_id' ] ]);
            //记录订单日志 start
            if ($log_data) {
                if(isset($log_data['action'])){
                    $action = $log_data['action'];
                }else{
                    $action = '商家对订单进行了确认收货';
                    if ($log_data[ 'action_way' ] == 1) {
                        $member_info = model('member')->getInfo([ 'member_id' => $log_data[ 'uid' ] ], 'nickname');
                        $buyer_name = empty($member_info[ 'nickname' ]) ? '' : '【' . $member_info[ 'nickname' ] . '】';
                        $log_data[ 'nick_name' ] = $buyer_name;
                        $action = '买家确认收到货物';
                    }
                }

                $log_data = array_merge($log_data, [
                    'order_id' => $order_id,
                    'action' => $action,
                    'order_status' => $order_action_array[ 'order_status' ],
                    'order_status_name' => $order_action_array[ 'order_status_name' ],
                ]);
                OrderLog::addOrderLog($log_data, $this);
            }
            //记录订单日志 end

            //删除定时任务
            $cron = new Cron();
            $cron->deleteCron([ [ 'event', '=', 'CronOrderTakeDelivery' ], [ 'relate_id', '=', $order_id ] ]);

            return $this->success();
        } catch (Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /*************************************************订单操作基础方法（订单关闭，订单完成，订单调价）结束*****************************************/

    /******************************************************** 订单数据查询（开始）*********************************************************/



    /**
     * 订单解除锁定
     * @param $order_id
     * @return int
     */
    public function orderUnlock($order_id)
    {
        $data = [
            'is_lock' => 0
        ];
        return model('order')->update($data, [ [ 'order_id', '=', $order_id ] ]);
    }

    /**
     * 订单锁定
     * @param $order_id
     * @return int
     */
    public function orderLock($order_id)
    {
        $data = [
            'is_lock' => 1
        ];
        return model('order')->update($data, [ [ 'order_id', '=', $order_id ] ]);
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return array
     */
    public function getOrderDetail($order_id)
    {
        $order_info = model('order')->getInfo([ [ 'o.order_id', '=', $order_id ] ], 'o.*,s.store_name', 'o', [ [ 'store s', 'o.store_id = s.store_id', 'left' ] ]);
        if (empty($order_info))
            return $this->error();

        $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);

        if ($order_info[ 'member_id' ] != 0) {
            $member_info = model('member')->getInfo([ [ 'member_id', '=', $order_info[ 'member_id' ] ] ], 'nickname');

        } else {
            $member_info = [];
        }

        $order_info[ 'nickname' ] = $member_info[ 'nickname' ] ?? '';
        if (!empty($order_info[ 'delivery_code' ])) {
            $order_info[ 'verifier_name' ] = model('verify')->getValue([ [ 'verify_code', '=', $order_info[ 'delivery_code' ] ] ], 'verifier_name');
        } else {
            $order_info[ 'verifier_name' ] = '';
        }

        $order_goods_list = model('order_goods')->getList([ [ 'order_id', '=', $order_id ] ]);
        $order_goods_id_array = [];
        foreach ($order_goods_list as $k => $v) {
            $order_goods_list[ $k ][ 'num' ] = numberFormat($v[ 'num' ]);
            $order_goods_id_array[] = $v[ 'order_goods_id' ];
            $order_goods_list[$k]['spec_name'] = mb_substr($v['sku_name'], mb_strlen($v['goods_name']) + 1);
        }
        $order_goods_ids = implode(',', $order_goods_id_array);
        $form_list = model('form_data')->getList([ [ 'relation_id', 'in', $order_goods_ids ], [ 'scene', '=', 'goods' ] ], 'relation_id, form_data');
        foreach ($order_goods_list as $k => $v) {
            foreach ($form_list as $k_form => $v_form) {
                if ($v[ 'order_goods_id' ] == $v_form[ 'relation_id' ]) {
                    $order_goods_list[ $k ][ 'form' ] = json_decode($v_form[ 'form_data' ], true);
                }
            }
        }
        $order_info[ 'order_goods' ] = $order_goods_list;

        $order_model = $this->getOrderModel($order_info);
        $temp_info = $order_model->orderDetail($order_info);
        $order_info = array_merge($order_info, $temp_info);

        $form_info = model('form_data')->getInfo([ [ 'relation_id', '=', $order_id ], [ 'scene', '=', 'order' ] ], 'form_data');
        if (!empty($form_info)) {
            $order_info[ 'form' ] = json_decode($form_info[ 'form_data' ], true);
        }

        if ($order_info[ 'store_id' ]) $order_info[ 'store_name' ] = model('store')->getValue([ [ 'store_id', '=', $order_info[ 'store_id' ] ] ], 'store_name');

        $order_info[ 'order_log' ] = model('order_log')->getList([ [ 'order_id', '=', $order_id ] ], '*', 'action_time desc,id desc');

        //线下支付信息
        if(addon_is_exit('offlinepay')){
            $order_info = (new \addon\offlinepay\model\Pay())->handleAdminOrderInfo($order_info);
        }
        //实际支付金额处理
        $order_info = $this->dealWithRealPayMoney($order_info);
        return $this->success($order_info);
    }

    /**
     * 获取订单详情(为退款的订单项)
     * @param $order_id
     * @return array
     */
    public function getUnRefundOrderDetail($order_id)
    {
        $order_info = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ]);

        if (empty($order_info))
            return $this->error();

        $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);
        $member_info = model('member')->getInfo([ [ 'member_id', '=', $order_info[ 'member_id' ] ] ], 'nickname');

        $order_info[ 'nickname' ] = $member_info[ 'nickname' ];

        $order_goods_list = model('order_goods')->getList([ [ 'order_id', '=', $order_id ], [ 'refund_status', 'in', [OrderRefundDict::REFUND_NOT_APPLY,OrderRefundDict::PARTIAL_REFUND] ] ]);
        foreach ($order_goods_list as $k => $v) {
            $order_goods_list[ $k ][ 'num' ] = numberFormat($v[ 'num' ]);
        }
        $order_info[ 'order_goods' ] = $order_goods_list;
        $order_model = $this->getOrderModel($order_info);
        $temp_info = $order_model->orderDetail($order_info);
        $order_info = array_merge($order_info, $temp_info);

        return $this->success($order_info);
    }

    /**
     * 得到订单基础信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getOrderInfo($condition, $field = '*')
    {
        $info = model('order')->getInfo($condition, $field);
        if (!empty($info)) {
            if (isset($info[ 'goods_num' ])) {
                $info[ 'goods_num' ] = numberFormat($info[ 'goods_num' ]);
            }
        }
        return $this->success($info);
    }

    /**
     * 得到订单数量
     * @param $condition
     * @param string $field
     * @param string $alias
     * @param null $join
     * @param null $group
     * @return array
     */
    public function getOrderCount($condition, $field = '*', $alias = 'a', $join = null, $group = null)
    {
        $res = model('order')->getCount($condition, $field, $alias, $join, $group);
        return $this->success($res);
    }

    /**
     * 得到订单加和
     * @param $condition
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getOrderSum($condition, $field = '*', $alias = 'a', $join = null)
    {
        $res = model('order')->getSum($condition, $field, $alias, $join);
        return $this->success($res);
    }

    /**
     * 得到订单项数量
     * @param $condition
     * @param string $field
     * @param string $alias
     * @param null $join
     * @param null $group
     * @return array
     */
    public function getOrderGoodsCount($condition, $field = '*', $alias = 'a', $join = null, $group = null)
    {
        $res = model('order_goods')->getCount($condition, $field, $alias, $join, $group);
        return $this->success($res);
    }

    /**
     * 查询会员订单数量
     * @param $member_id
     * @return array
     */
    public function getMemberOrderNum($member_id)
    {
        $data = [
            'waitpay' => 0,
            'waitsend' => 0,
            'waitconfirm' => 0,
            'wait_use' => 0,
            'waitrate' => 0
        ];
        $list = model('order')->getList([ [ 'member_id', '=', $member_id ] ], 'order_id, order_status,order_type,is_evaluate,evaluate_status,order_scene');

        //计算退款中id
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                //待支付
                if ($v[ 'order_status' ] == 0 && $v['order_scene'] == 'online') {
                    $data[ 'waitpay' ] += 1;
                }

                //待发货
                if ($v[ 'order_status' ] == 1) {
                    $data[ 'waitsend' ] += 1;
                }

                //待收货
                if (( $v[ 'order_status' ] == 2 || $v[ 'order_status' ] == 3 ) && ( $v[ 'order_type' ] != 4 )) {
                    $data[ 'waitconfirm' ] += 1;
                }

                //待使用
                if (( $v[ 'order_status' ] == 3 || $v[ 'order_status' ] == 11 ) && ( $v[ 'order_type' ] == 4 )) {
                    $data[ 'wait_use' ] += 1;
                }

                //待评价
                if (( $v[ 'order_status' ] == 4 || $v[ 'order_status' ] == 10 ) && ( $v[ 'is_evaluate' ] == 1 ) && ( $v[ 'evaluate_status' ] == OrderDict::evaluate_wait )) {
                    $data[ 'waitrate' ] += 1;
                }
            }
        }
        $order_refund_model = new OrderRefundModel();
        $result = $order_refund_model->getRefundOrderGoodsCount([
            [ 'member_id', '=', $member_id ],
            [ 'refund_status', 'not in', [ OrderRefundDict::REFUND_NOT_APPLY, OrderRefundDict::REFUND_COMPLETE,OrderRefundDict::PARTIAL_REFUND ] ]
        ]);
        $data[ 'refunding' ] = $result[ 'data' ];


        return $this->success($data);
    }

    /**
     * 获取订单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @param string $group
     * @param string $alias
     * @param string $join
     * @return array
     */
    public function getOrderList($condition = [], $field = '*', $order = '', $limit = null, $group = '', $alias = '', $join = '')
    {
        $list = model('order')->getList($condition, $field, $order, $alias, $join, $group, $limit);
        foreach ($list as $k => $v) {
            if (isset($v[ 'goods_num' ])) {
                $list[ $k ][ 'goods_num' ] = numberFormat($v[ 'goods_num' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $order_list = model('order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        $check_condition = array_column($condition, 2, 0);
        //线下支付判断
        $offline_pay_exist = addon_is_exit('offlinepay');
        if($offline_pay_exist) $offline_pay_model = new \addon\offlinepay\model\Pay();
        if (!empty($order_list[ 'list' ])) {
            //组装数据
            $member_id_array = [];
            $order_id_array = [];
            foreach ($order_list[ 'list' ] as $k => &$order_v) {
                //先初始化订单商品数据，如果没有查到订单项，页面会报错
                $order_v['order_goods'] = [];
                if (isset($order_v[ 'member_id' ])) {
                    $member_id_array[] = $order_v[ 'member_id' ];
                }
                $order_id_array[] = $order_v[ 'order_id' ];
                if (!empty($check_condition[ 'a.order_status' ])) {
                    $order_v[ 'order_data_status' ] = $check_condition[ 'a.order_status' ];

                }
                if (isset($order_v[ 'goods_num' ])) {
                    $order_v[ 'goods_num' ] = numberFormat($order_v[ 'goods_num' ]);
                }
                //处理线下支付信息
                if($offline_pay_exist){
                    $order_v = $offline_pay_model->handleAdminOrderInfo($order_v);
                }
                //实际支付金额处理
                $order_v = $this->dealWithRealPayMoney($order_v);
            }

            $order_goods_list = model('order_goods')->getList([
                [ 'order_id', 'in', $order_id_array ]
            ]);

            $member_list = [];
            if (!empty($member_id_array)) {
                $member_list = model('member')->getColumn([ [ 'member_id', 'in', $member_id_array ] ], 'nickname', 'member_id');
            }
            foreach ($order_list[ 'list' ] as &$v) {
                foreach ($order_goods_list as &$cv) {
                    if ($v[ 'order_id' ] == $cv[ 'order_id' ]) {
                        $cv[ 'num' ] = numberFormat($cv[ 'num' ]);
                        $v[ 'order_goods' ][] = $cv;

                    }
                }
                $v['nickname'] = $member_list[ $v[ 'member_id' ] ?? 0 ] ?? '';
                $v['buyer_message'] = htmlspecialchars($v['buyer_message']);
            }
            //部分发货处理
            $order_model = new Order();
            foreach ($order_list[ 'list' ] as &$v) {
                if($v['order_type'] == OrderDict::express){
                    $v = array_merge($v, $order_model->orderPartDeliveryHandle($v));
                }
            }
        }
        return $this->success($order_list);
    }
    /****************************************************************************订单数据查询结束*************************************/

    /****************************************************************************会员订单订单数据查询开始*************************************/

    /**
     * 获取订单发票分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getOrderInvoicePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $order_list = model('order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($order_list);
    }

    /**
     * 获取订单项详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getOrderGoodsInfo($condition = [], $field = '*', $alias = 'og', $join = [])
    {
        $info = model('order_goods')->getInfo($condition, $field, $alias, $join);
        $info = $this->handleOrderGoodsInfo($info);
        return $this->success($info);
    }

    /**
     * 处理订单商品信息
     * @param $info
     * @return mixed
     */
    public function handleOrderGoodsInfo($info)
    {
        if(isset($info['refund_status_action'])){
            $refund_action = empty($info[ 'refund_status_action' ]) ? [] : json_decode($info[ 'refund_status_action' ], true);
            $refund_member_action = $refund_action[ 'member_action' ] ?? [];
            $info[ 'refund_action' ] = $refund_member_action;
        }
        if (isset($v['goods_num'])) {
            $info['goods_num'] = numberFormat($info['goods_num']);
        }
        if (isset($info[ 'num' ])) {
            $info[ 'num' ] = numberFormat($info[ 'num' ]);
        }
        if(isset($info['refund_type'])){
            $info['refund_type_name'] = OrderRefundDict::getRefundType($info['refund_type']);
        }
        if(isset($info['refund_money_type'])){
            $info['refund_money_type_name'] = OrderRefundDict::getRefundMoneyType($info['refund_money_type']);
        }
        if(isset($info['shop_active_refund_money_type'])){
            $info['shop_active_refund_money_type_name'] = OrderRefundDict::getRefundMoneyType($info['shop_active_refund_money_type']);
        }
        return $info;
    }

    /**
     * 获取订单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @param string $group
     * @param string $alias
     * @param string $join
     * @return array
     */
    public function getOrderGoodsList($condition = [], $field = '*', $order = '', $limit = null, $group = '', $alias = '', $join = '')
    {
        $list = model('order_goods')->getList($condition, $field, $order, $alias, $join, $group, $limit);
        foreach ($list as &$v) {
            $v = $this->handleOrderGoodsInfo($v);
        }
        return $this->success($list);
    }

    /****************************************************************************会员订单订单数据查询结束*************************************/


    /***************************************************************** 交易记录 *****************************************************************/

    /**
     * 会员订单详情
     * @param $order_id
     * @param $member_id
     * @param $site_id
     * @param string $merchant_trade_no
     * @return array
     */
    public function getMemberOrderDetail($order_id, $member_id, $site_id, $merchant_trade_no = '')
    {
        $condition = [
            [ 'member_id', '=', $member_id ],
            [ 'site_id', '=', $site_id ]
        ];
        if (!empty($order_id)) {
            $condition[] = [ 'order_id', '=', $order_id ];
        }
        if (!empty($merchant_trade_no)) {
            $condition[] = [ 'out_trade_no', '=', $merchant_trade_no ];
        }
        $order_info = model('order')->getInfo($condition);
        if (empty($order_info))
            return $this->error([], '当前订单不是本账号的订单！');

        $order_info[ 'goods_num' ] = numberFormat($order_info[ 'goods_num' ]);
        $order_goods_list = model('order_goods')->getList([ [ 'order_id', '=', $order_id ], [ 'member_id', '=', $member_id ] ]);
        foreach ($order_goods_list as $k => $v) {
            $refund_action = empty($v[ 'refund_status_action' ]) ? [] : json_decode($v[ 'refund_status_action' ], true);
            $refund_action = $refund_action[ 'member_action' ] ?? [];
            $order_goods_list[ $k ][ 'refund_action' ] = $refund_action;
            //TODO 优化
            $form_info = model('form_data')->getInfo([ [ 'relation_id', '=', $v[ 'order_goods_id' ] ], [ 'scene', '=', 'goods' ] ], 'form_data');
            if (!empty($form_info)) $order_goods_list[ $k ][ 'form' ] = json_decode($form_info[ 'form_data' ], true);
            $order_goods_list[ $k ][ 'num' ] = numberFormat($order_goods_list[ $k ][ 'num' ]);
        }
        $order_info[ 'order_goods' ] = $order_goods_list;

        $order_model = $this->getOrderModel($order_info);
        $temp_info = $order_model->orderDetail($order_info);
        $order_info = array_merge($order_info, $temp_info);

        $code_result = $this->orderQrcode($order_info);
        $order_info = array_merge($order_info, $code_result);
        $order_info[ 'code_info' ] = $code_result;

        $form_info = model('form_data')->getInfo([ [ 'relation_id', '=', $order_id ], [ 'scene', '=', 'order' ] ], 'form_data');
        if (!empty($form_info)) $order_info[ 'form' ] = json_decode($form_info[ 'form_data' ], true);

        //操作按钮处理
        $action = empty($order_info[ 'order_status_action' ]) ? [] : json_decode($order_info[ 'order_status_action' ], true);
        $member_action = $action[ 'member_action' ] ?? [];
        if (!in_array($order_info[ 'order_status' ], [ self::ORDER_CREATE, self::ORDER_COMPLETE, self::ORDER_CLOSE ]) && $order_info[ 'is_enable_refund' ]) {
            $not_apply_refund_count = model('order_goods')->getCount([
                [ 'order_id', '=', $order_id ],
                [ 'refund_status', '=', OrderRefundDict::REFUND_NOT_APPLY ],
            ], 'order_goods_id');
            if ($not_apply_refund_count > 1) {
                $member_action[] = [
                    'action' => 'memberBatchRefund',
                    'title' => '批量退款',
                    'color' => '',
                ];
            }
        }
        $order_info[ 'action' ] = $member_action;

        //线下支付处理
        if(addon_is_exit('offlinepay')){
            $order_info = (new \addon\offlinepay\model\Pay())->handleMemberOrderInfo($order_info);
        }

        //增加删除订单操作
        if(isset($order_info['order_status']) && isset($order_info['action']) && $order_info['order_status'] == self::ORDER_CLOSE){
            $order_info['action'][] = [
                'action' => 'orderDelete',
                'title' => '删除',
                'color' => '',
            ];
        }

        return $this->success($order_info);
    }

    /***************************************************************** 交易记录 *****************************************************************/

    /**
     * 订单生成码
     * @param $order_info
     * @return array
     */
    public function orderQrcode($order_info)
    {

        $app_type = 'h5';
        switch ( $order_info[ 'order_type' ] ) {
            case OrderDict::store:
                $code = $order_info[ 'delivery_code' ];
                $verify_type = 'pickup';
                break;
            case OrderDict::virtual:
                $code = $order_info[ 'virtual_code' ];
                $verify_type = 'virtualgoods';
                break;
            default:
                return [];
        }
        $verify_model = new Verify();
        $result = $verify_model->qrcode($code, $app_type, $verify_type, $order_info[ 'site_id' ], 'get');

        // 生成条形码
        $txm = getBarcode($code, 'upload/qrcode/' . $verify_type);
        $data = [];
        if (!empty($result) && $result[ 'code' ] >= 0) {
            $data[ $verify_type ] = $result[ 'data' ][ $app_type ][ 'path' ] ?? '';
        }
        $data[ $verify_type . '_barcode' ] = $txm;
        return $data;
    }

    /************************************************************************* 订单日志 start ********************************************************************/

    /**
     * 会员订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getMemberOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $order_list = model('order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        if (!empty($order_list[ 'list' ])) {
            $order_id_array = [];
            //线下支付判断
            $offline_pay_exist = addon_is_exit('offlinepay');
            if($offline_pay_exist) $offline_pay_model = new \addon\offlinepay\model\Pay();
            foreach ($order_list[ 'list' ] as &$v) {
                //需要先初始化 会有丢失订单项的情况导致前端报错
                $v['order_goods'] = [];
                $order_id_array[] = $v[ 'order_id' ];
                $action = empty($v[ 'order_status_action' ]) ? [] : json_decode($v[ 'order_status_action' ], true);
                $member_action = $action[ 'member_action' ] ?? [];
                $v[ 'action' ] = $member_action;
                //线下支付处理
                if($offline_pay_exist) $v = $offline_pay_model->handleMemberOrderInfo($v);
                if (isset($v[ 'goods_num' ])) {
                    $v[ 'goods_num' ] = numberFormat($v[ 'goods_num' ]);
                }
                //增加删除订单操作
                if(isset($v['order_status']) && isset($v['action']) && $v['order_status'] == self::ORDER_CLOSE){
                    $v['action'][] = [
                        'action' => 'orderDelete',
                        'title' => '删除',
                        'color' => '',
                    ];
                }
            }

            $order_ids = implode(',', $order_id_array);
            $order_goods_list = model('order_goods')->getList([ [ 'order_id', 'in', $order_ids ] ]);
            unset($v);
            //附件商品项目列表
            foreach ($order_list[ 'list' ] as $k => $v) {
                foreach ($order_goods_list as $cv) {
                    $cv[ 'num' ] = numberFormat($cv[ 'num' ]);
                    if ($v[ 'order_id' ] == $cv[ 'order_id' ]) {
                        $order_list[ 'list' ][ $k ][ 'order_goods' ][] = $cv;
                    }
                }
            }
            //部分发货处理
            $order_model = new Order();
            foreach ($order_list[ 'list' ] as &$v) {
                if($v['order_type'] == OrderDict::express){
                    $v = array_merge($v, $order_model->orderPartDeliveryHandle($v));
                }
            }
        }
        return $this->success($order_list);
    }

    /**
     * 获取交易记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getTradePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $res = model('order')->pageList($condition, $field, $order, $page, $page_size);
        foreach ($res[ 'list' ] as &$v) {
            if (isset($v[ 'goods_num' ])) {
                $v[ 'goods_num' ] = numberFormat($v[ 'goods_num' ]);
            }
        }
        return $this->success($res);
    }

    /**
     * @param $order_id
     * @return array
     */
    public function orderAfterSaleClose($order_id)
    {
        $res = model('order')->update([ 'is_enable_refund' => 0 ], [ [ 'order_id', '=', $order_id ] ]);
        return $this->success($res);
    }


    /**
     * 获取订单项分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getOrderGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [], $group = '')
    {
        $res = model('order_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        foreach ($res[ 'list' ] as $k => $v) {
            if (isset($v[ 'num' ])) {
                $res[ 'list' ][ $k ][ 'num' ] = numberFormat($res[ 'list' ][ $k ][ 'num' ]);
            }
        }
        return $this->success($res);
    }

    /**
     * 处理页面要显示的实际支付金额
     * @param $order_info
     * @return mixed
     */
    public function dealWithRealPayMoney($order_info)
    {
        $order_info['real_pay_money'] = $order_info['order_money'];
        if(isset($order_info['order_money']) && isset($order_info['balance_money']) && isset($order_info['order_status']) && isset($order_info['pay_status'])){
            if($order_info['order_status'] == self::ORDER_CREATE){
                $order_info['real_pay_money'] = $order_info['balance_money'];
            }
            if($order_info['order_status'] == self::ORDER_CLOSE && $order_info['pay_status'] == 0){
                $order_info['real_pay_money'] = '0.00';
            }
        }
        return $order_info;
    }
}