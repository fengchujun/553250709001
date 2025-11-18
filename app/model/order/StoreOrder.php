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

use app\dict\order\OrderGoodsDict;
use app\dict\order_refund\OrderRefundDict;
use app\model\message\Message;
use app\model\verify\Verify;
use think\db\exception\DbException;
use think\facade\Queue;

/**
 * 门店自提订单
 *
 * @author Administrator
 *
 */
class StoreOrder extends OrderCommon
{

    /************************************************************* 订单状态 ***********************************************/
    // 订单创建
    public const ORDER_CREATE = 0;

    // 订单已支付
    public const ORDER_PAY = 1;

    // 订单待提货
    public const ORDER_PENDING_DELIVERY = 2;

    // 订单已发货（配货）
    public const ORDER_DELIVERY = 3;

    // 订单已收货
    public const ORDER_TAKE_DELIVERY = 4;

    // 订单已结算完成
    public const ORDER_COMPLETE = 10;

    // 订单已关闭
    public const ORDER_CLOSE = -1;

    // 订单类型
    public $order_type = 2;

    // 订单状态
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
                    'action' => 'orderAdjustMoney',
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
        self::ORDER_PENDING_DELIVERY => [
            'status' => self::ORDER_PENDING_DELIVERY,
            'name' => '待提货',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [
            ],
            'member_action' => [

            ],
            'color' => ''
        ],
        self::ORDER_TAKE_DELIVERY => [
            'status' => self::ORDER_TAKE_DELIVERY,
            'name' => '已提货',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-received.png',
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
     * 门店订单
     * @param $data
     * @return array
     * @throws DbException
     */
    public function orderPay($data)
    {
        $order_info = $data['order_info'];
        $member_info = $data['member_info'];
        $pay_type = $data['pay_type'];
        $member_id = $order_info['member_id'] ?? 0;
        $log_data = $data['log_data'] ?? [];
        $condition = [
            ['order_id', '=', $order_info['order_id']],
            ['order_status', '=', self::ORDER_CREATE],
        ];
        $verify = new Verify();
        $order_goods_list = model('order_goods')->getList([['order_id', '=', $order_info['order_id']]], 'sku_image,sku_name,price,num,order_goods_id,goods_id,sku_id');
        $item_array = [];
        foreach ($order_goods_list as $k => $v) {
            $item_array[] = [
                'img' => $v['sku_image'],
                'name' => $v['sku_name'],
                'price' => $v['price'],
                'num' => numberFormat($v['num']),
                'order_goods_id' => $v['order_goods_id'],
                'remark_array' => [

                ]
            ];
            // 增加门店商品销量
            model('store_goods')->setInc([['goods_id', '=', $v['goods_id']], ['store_id', '=', $order_info['delivery_store_id']]], 'sale_num', $v['num']);
            model('store_goods_sku')->setInc([['sku_id', '=', $v['sku_id']], ['store_id', '=', $order_info['delivery_store_id']]], 'sale_num', $v['num']);
        }
        $pay_time = time();
        $remark_array = [
            ['title' => '订单金额', 'value' => $order_info['order_money']],
            ['title' => '订单编号', 'value' => $order_info['order_no']],
            ['title' => '创建时间', 'value' => time_to_date($order_info['create_time'])],
            ['title' => '付款时间', 'value' => time_to_date($pay_time)],
            ['title' => '收货地址', 'value' => $order_info['full_address']],
            ['title' => '选择门店', 'value' => $order_info['delivery_store_name']],
        ];
        $verify_content_json = $verify->getVerifyJson($item_array, $remark_array);

        $code = $verify->addVerify('pickup', $order_info['site_id'], $order_info['site_name'], $verify_content_json, 0, 1, $order_info['delivery_store_id'], $member_id);
        $pay_type_list = $this->getPayType();
        $data = [
            'order_status' => self::ORDER_PENDING_DELIVERY,
            'order_status_name' => $this->order_status[self::ORDER_PENDING_DELIVERY]['name'],
            'pay_status' => 1,
            'order_status_action' => json_encode($this->order_status[self::ORDER_PENDING_DELIVERY], JSON_UNESCAPED_UNICODE),
            'delivery_code' => $code['data']['verify_code'],
            'pay_time' => $pay_time,
            'is_enable_refund' => 1,
            'pay_type' => $pay_type,
            'pay_type_name' => $pay_type_list[$pay_type]
        ];

        //记录订单日志 start
        $action = '商家对订单进行了线下支付';
        //获取用户信息
        if (empty($log_data)) {
            $log_data = [
                'uid' => $order_info['member_id'],
                'nick_name' => $order_info['name'],
                'action_way' => 1
            ];
            $action = '买家支付了订单';
        }

        $log_data = array_merge($log_data, [
            'order_id' => $order_info['order_id'],
            'action' => $action,
            'order_status' => self::ORDER_PENDING_DELIVERY,
            'order_status_name' => $this->order_status[self::ORDER_PENDING_DELIVERY]['name']
        ]);

        OrderLog::addOrderLog($log_data, $this);
        //记录订单日志 end

        model('order')->update($data, $condition);

        $order_goods_data = [
            'delivery_status_name' => '待提货'
        ];

        $res = model('order_goods')->update($order_goods_data, [['order_id', '=', $order_info['order_id']]]);
        $verify->qrcode($code['data']['verify_code'], 'all', 'pickup', $order_info['site_id']);

        // 订单发货完成，小程序发货信息录入，视频号
        Queue::push('app\job\order\OrderDeliveryAfter', ['order_id' => $order_info['order_id'], 'site_id' => $order_info['site_id']]);

        return $this->success($res);
    }

    /**
     * 主动提货
     * @param $delivery_code
     * @return array
     */
    public function verify($delivery_code)
    {
        $order_info = model('order')->getInfo([['delivery_code', '=', $delivery_code]], 'order_id, order_type, sign_time, order_status, delivery_code,site_id');
        if (empty($order_info))
            return $this->error([], 'ORDER_EMPTY');

        $result = $this->activeTakeDelivery($order_info['order_id']);
        if ($result['code'] < 0) {
            return $result;
        }
        //核销发送通知
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'VERIFY', 'order_id' => $order_info['order_id'], 'site_id' => $order_info['site_id']]);
        return $result;
    }

    /**
     * 主动提货
     * @param $order_id
     * @return array
     */
    public function activeTakeDelivery($order_id)
    {
        $order_condition = [
            ['order_id', '=', $order_id],
            ['order_type', '=', 2]
        ];
        $order_info = model('order')->getInfo($order_condition, 'delivery_code, order_status, site_id');

        //应该在这儿主动调用核销的方法函数
        if (empty($order_info))
            return $this->error([], '订单不存在！');

        if ($order_info['order_status'] != self::ORDER_PENDING_DELIVERY)
            return $this->error([], '只有待提货状态的订单才可以提货！');

        $result = $this->orderCommonTakeDelivery($order_id);
        if ($result['code'] < 0) {
            return $result;
        }
        //核销发送通知
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'VERIFY', 'order_id' => $order_id, 'site_id' => $order_info['site_id']]);
        return $result;
    }

    /**
     * 订单提货
     * @param $order_id
     * @return array
     */
    public function orderTakeDelivery($order_id)
    {
        $res = model('order_goods')->update(['delivery_status' => OrderGoodsDict::delivery, 'delivery_status_name' => '已提货'], [['order_id', '=', $order_id], ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]]);

        $order_goods_list = model('order_goods')->getList([['order_id', '=', $order_id]]);
        foreach ($order_goods_list as &$v) {
            $v['num'] = numberFormat($v['num']);
        }
        //todo 默认先将提货的发货和收货一体化,将扣除库存统一放在这
        $order_stock_model = new OrderStock();

        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], 'store_id,site_id');
        $stock_result = $order_stock_model->decOrderStock([
            'store_id' => $order_info['store_id'],
            'site_id' => $order_info['site_id'],
            'goods_sku_list' => $order_goods_list
        ]);
        if ($stock_result['code'] < 0) {
            return $stock_result;
        }
        return $this->success($res);
    }

    /**
     * 退款完成操作
     * @param $order_goods_info
     */
    public function refund($order_goods_info)
    {
        //是否入库
        $order_stock_model = new OrderStock();
        if ($order_goods_info['is_refund_stock'] == 1) {
            $order_stock_model->incOrderStock($order_goods_info);
        }else if($order_goods_info['delivery_status'] == 0){
            $order_stock_model->incOrderSaleStock([
                'store_id' => $order_goods_info['store_id'],
                'goods_sku_data' => [
                    [
                        'sku_id' => $order_goods_info['sku_id'],
                        'num' => $order_goods_info['num'],
                    ],
                ],
            ]);
        }
    }

    /**
     * 订单详情
     * @param $order_info
     * @return array
     */
    public function orderDetail($order_info)
    {
        return [];
    }
}