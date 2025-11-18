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

use app\dict\order_refund\OrderRefundDict;
use app\model\express\LocalPackage;
use app\model\message\Message;
use app\model\system\Pay as PayModel;
use Exception;
use think\facade\Db;
use think\facade\Queue;

/**
 * 外卖订单
 *
 * @author Administrator
 *
 */
class LocalOrder extends OrderCommon
{
    /*****************************************************************************************订单基础状态（其他使用）********************************/
    // 订单待付款
    public const ORDER_CREATE = 0;

    // 订单已支付(待发货)
    public const ORDER_PAY = 1;

    // 订单备货中
    public const ORDER_PENDING_DELIVERY = 2;

    // 订单已发货（配货）
    public const ORDER_DELIVERY = 3;

    // 订单已收货
    public const ORDER_TAKE_DELIVERY = 4;

    // 订单已结算完成
    public const ORDER_COMPLETE = 10;

    // 订单已关闭
    public const ORDER_CLOSE = -1;

    /***********************************************************************************订单项  配送状态**************************************************/
    // 待发货
    public const DELIVERY_WAIT = 0;

    // 已发货
    public const DELIVERY_DOING = 1;

    // 已收货
    public const DELIVERY_FINISH = 2;

    // 订单类型
    public $order_type = 3;

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
                [
                    'action' => 'orderLocalDelivery',
                    'title' => '发货',
                    'color' => ''
                ],
                [
                    'action' => 'orderAddressUpdate',
                    'title' => '修改地址',
                    'color' => ''
                ],
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
                [
                    'action' => 'memberTakeDelivery',
                    'title' => '确认收货',
                    'color' => ''
                ],

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

    // 配送状态
    public $delivery_status = [
        self::DELIVERY_WAIT => [
            'status' => self::DELIVERY_WAIT,
            'name' => '待发货',
            'color' => ''
        ],
        self::DELIVERY_DOING => [
            'status' => self::DELIVERY_DOING,
            'name' => '已发货',
            'color' => ''
        ],
        self::DELIVERY_FINISH => [
            'status' => self::DELIVERY_FINISH,
            'name' => '已收货',
            'color' => ''
        ]
    ];

    /**
     * 订单支付
     * @param $data
     * @return array
     */
    public function orderPay($data)
    {
        $order_info = $data['order_info'];
        $pay_type = $data['pay_type'];
        $log_data = $data['log_data'] ?? [];
        $member_info = $data['member_info'];
        if ($order_info['order_status'] != 0) {
            return $this->error();
        }
        $condition = [
            ['order_id', '=', $order_info['order_id']],
            ['order_status', '=', self::ORDER_CREATE],
        ];
        $pay_type_list = $this->getPayType();
        $data = [
            'order_status' => self::ORDER_PAY,
            'order_status_name' => $this->order_status[self::ORDER_PAY]['name'],
            'pay_status' => 1,
            'order_status_action' => json_encode($this->order_status[self::ORDER_PAY], JSON_UNESCAPED_UNICODE),
            'pay_time' => time(),
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
                'nick_name' => $member_info['nickname'],
                'action_way' => 1
            ];
            $action = '买家支付了订单';
        }

        $log_data = array_merge($log_data, [
            'order_id' => $order_info['order_id'],
            'action' => $action,
            'order_status' => self::ORDER_PAY,
            'order_status_name' => $this->order_status[self::ORDER_PAY]['name']
        ]);

        OrderLog::addOrderLog($log_data, $this);
        //记录订单日志 end

        $result = model('order')->update($data, $condition);
        return $this->success($result);
    }

    /**
     * 订单项发货（物流）
     * @param $param
     * @return array
     */
    public function orderGoodsDelivery($param)
    {

        $delivery_no = $param['delivery_no'] ?? '';//物流单号
        $delivery_type = $param['delivery_type'] ?? 'default';
        $order_id = $param['order_id'] ?? 0;
        $site_id = $param['site_id'];
        $store_id = $param['store_id'] ?? 0;

        $condition = [
            ['site_id', '=', $site_id],
            ['order_id', '=', $order_id],
            ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]
        ];
        if ($store_id) $condition[] = ['store_id', '=', $store_id];

        $order_goods_list = model('order_goods')->getList($condition, '*');
        if (empty($order_goods_list)) {
            return $this->error('', '发货货物不可为空！');
        }

        $condition_refund[] = ['', 'exp', Db::raw('(refund_status=' . OrderRefundDict::REFUND_APPLY . ' or  refund_status=' . OrderRefundDict::REFUND_CONFIRM . ') and site_id=' . $site_id . ' and order_id=' . $order_id)];
        $order_refund_goods_list = model('order_goods')->getList($condition_refund, 'order_goods_id');
        // 已退款的订单项不可发货
        if ($order_refund_goods_list) {
            return $this->error([], 'ORDER_GOODS_IN_REFUND');
        }

        model('order_goods')->startTrans();
        try {

            $order_goods_id_array = [];
            $goods_id_array = [];
            $member_id = 0;
            foreach ($order_goods_list as $order_goods_info) {
                $order_goods_id_array[] = $order_goods_info['order_goods_id'];
                $order_id = $order_goods_info['order_id'];
                $member_id = $order_goods_info['member_id'];
                $goods_id_array[] = $order_goods_info['sku_id'] . ':' . number_format($order_goods_info['num']) . ':' . $order_goods_info['sku_name'] . ':' . $order_goods_info['sku_image'];
                $data = ['delivery_status' => self::DELIVERY_DOING, 'delivery_status_name' => $this->delivery_status[self::DELIVERY_DOING]['name']];
                if (!empty($delivery_no)) {
                    $data['delivery_no'] = $delivery_no;
                }
                model('order_goods')->update($data, [
                    ['order_goods_id', '=', $order_goods_info['order_goods_id']],
                    ['delivery_status', '=', self::DELIVERY_WAIT]
                ]);
            }

            $order_stock_model = new OrderStock();
            $order_info = model('order')->getInfo([['order_id', '=', $order_id]]);
            $stock_result = $order_stock_model->decOrderStock([
                'store_id' => $order_info['store_id'],
                'site_id' => $order_info['site_id'],
                'goods_sku_list' => $order_goods_list,
                'user_info' => $param['user_info'] ?? []
            ]);
            if ($stock_result['code'] < 0) {
                model('order_goods')->rollback();
                return $stock_result;
            }
            // 创建包裹
            $order_common_model = new OrderCommon();
            $lock_result = $order_common_model->verifyOrderLock($order_id);
            if ($lock_result['code'] < 0) {
                model('order_goods')->rollback();
                return $lock_result;
            }

            $local_delivery_model = new LocalPackage();
            $delivery_data = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array,
                'goods_id_array' => $goods_id_array,
                'goods_array' => $goods_id_array,
                'site_id' => $site_id,
                'delivery_no' => $delivery_no,
                'member_id' => $member_id,
                'delivery_type' => $delivery_type,
                'deliverer' => $param['deliverer'],
                'deliverer_mobile' => $param['deliverer_mobile'],
            ];
            $local_delivery_model->delivery($delivery_data);
            //检测整体, 订单中订单项是否全部发放完毕
            $res = $this->orderCommonDelivery($order_id);

            //发送消息 发给配送员
            $message_model = new Message();
            $message_model->sendMessage(['keywords' => 'MESSAGE_LOCAL_WAIT_DELIVERY', 'param' => $param, 'site_id' => $param['site_id']]);


            model('order_goods')->commit();
            return $this->success($res);
        } catch (Exception $e) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单发货
     * @param $order_id
     * @return array
     */
    public function orderDelivery($order_id)
    {
        $order_info = model('order')->getInfo([['order_id', '=', $order_id], ['order_status', '=', self::ORDER_PAY]], '*');
        if (empty($order_info)) return $this->error([], '订单不存在或已发货！');
        //统计订单项目
        $count = model('order_goods')->getCount([['order_id', '=', $order_id], ['delivery_status', '=', self::DELIVERY_WAIT], ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');
//        $delivery_count = model('order_goods')->getCount([['order_id', '=', $order_id], ['delivery_status', '=', self::DELIVERY_DOING], ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');
//        if ($count == 0 && $delivery_count > 0) {
        if ($count == 0) {
            $site_id = $order_info['site_id'];
            model('order')->startTrans();
            try {
                //修改订单项的配送状态
                $order_data = [
                    'order_status' => self::ORDER_DELIVERY,
                    'order_status_name' => $this->order_status[self::ORDER_DELIVERY]['name'],
                    'delivery_status' => self::DELIVERY_FINISH,
                    'delivery_status_name' => $this->delivery_status[self::DELIVERY_FINISH]['name'],
                    'order_status_action' => json_encode($this->order_status[self::ORDER_DELIVERY], JSON_UNESCAPED_UNICODE),
                    'delivery_time' => time()
                ];
                model('order')->update($order_data, [['order_id', '=', $order_id]]);
                model('order')->commit();
                //订单自动收货
                OrderCron::takeDelivery(['order_id' => $order_id, 'site_id' => $site_id]);
                // 订单发货完成，小程序发货信息录入，视频号，同城配送不需要验证
                Queue::push('app\job\order\OrderDeliveryAfter', ['order_id' => $order_id, 'site_id' => $site_id]);
                //订单发货消息
                $message_model = new Message();
                $message_model->sendMessage(['keywords' => 'ORDER_DELIVERY', 'order_id' => $order_id, 'site_id' => $site_id]);
                return $this->success();
            } catch (Exception $e) {
                model('order')->rollback();
                return $this->error('', $e->getMessage());
            }
        } else {
            return $this->error();
        }

    }

    /**
     * 订单收货
     * @param $order_id
     * @return array
     */
    public function orderTakeDelivery($order_id)
    {
        return $this->success();
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
        } else if ($order_goods_info['delivery_status'] == 0) {
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
        $local_package_model = new LocalPackage();
        $package_info = $local_package_model->package(['order_id' => $order_info['order_id']])['data'] ?? [];
        $data['package_list'] = $package_info;
        return $data;
    }
}