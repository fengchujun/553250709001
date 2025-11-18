<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointexchange\model;

use addon\coupon\model\Coupon;
use app\dict\member_account\AccountDict;
use app\dict\order\OrderGoodsDict;
use app\model\member\MemberAccount;
use app\model\BaseModel;
use app\model\order\VirtualOrder;
use app\model\system\Pay;
use app\model\order\OrderCreate;
use app\model\order\Order as CommonOrder;
use app\model\order\StoreOrder;
use app\model\order\LocalOrder;
use app\model\order\OrderCommon;
use think\facade\Log;

/**
 * 积分兑换订单
 */
class Order extends BaseModel
{

    /**
     * 支付订单
     * @param $data
     * @return array
     */
    public function orderPay($data)
    {
        $out_trade_no = $data[ 'out_trade_no' ];
        $order_info = model('promotion_exchange_order')->getInfo([ [ 'out_trade_no', '=', $out_trade_no ] ], '*');
        if (empty($order_info)) {
            return $this->error([], '找不到可支付的订单');
        }
        if ($order_info[ 'order_status' ] == 1) {
            return $this->error([], '当前订单已支付');
        }
        model('promotion_exchange_order')->startTrans();
        try {
            $order_data = array (
                'order_status' => 1,
                'pay_time' => time()
            );
            switch ( $order_info[ 'type' ] ) {
                case 1://商品
                    //添加对应商品订单
                    $order_create = new OrderCreate();
                    $order_no = $order_create->createOrderNo();

                    //查询兑换的商品信息
                    $exchange_info = model('promotion_exchange')->getInfo([ [ 'id', '=', $order_info[ 'exchange_id' ] ] ], 'price,type_id');
                    $sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $exchange_info[ 'type_id' ] ] ], 'sku_id,sku_name,sku_no,sku_image,is_virtual,goods_class,goods_class_name,cost_price,goods_id,goods_name,sku_spec_format,supplier_id');

                    //订单类型
                    $order_type = $this->orderType($order_info[ 'delivery_type' ], $sku_info[ 'is_virtual' ]);
                    $data_order = [
                        'order_no' => $order_no,
                        'site_id' => $order_info[ 'site_id' ],
                        'site_name' => '',
                        'order_from' => $order_info[ 'order_from' ],
                        'order_from_name' => $order_info[ 'order_from_name' ],
                        'order_type' => $order_type[ 'order_type_id' ],
                        'order_type_name' => $order_type[ 'order_type_name' ],
                        'order_status_name' => $order_type[ 'order_status' ][ 'name' ],
                        'order_status_action' => json_encode($order_type[ 'order_status' ], JSON_UNESCAPED_UNICODE),
                        'out_trade_no' => $out_trade_no,
                        'member_id' => $order_info[ 'member_id' ],
                        'name' => $order_info[ 'name' ],
                        'mobile' => $order_info[ 'mobile' ],
                        'telephone' => $order_info[ 'telephone' ],
                        'province_id' => $order_info[ 'province_id' ],
                        'city_id' => $order_info[ 'city_id' ],
                        'district_id' => $order_info[ 'district_id' ],
                        'community_id' => $order_info[ 'community_id' ],
                        'address' => $order_info[ 'address' ],
                        'full_address' => $order_info[ 'full_address' ],
                        'longitude' => $order_info[ 'longitude' ],
                        'latitude' => $order_info[ 'latitude' ],
                        'buyer_ip' => '',
                        'goods_money' => $order_info[ 'exchange_price' ] * $order_info[ 'num' ],
                        'delivery_money' => $order_info[ 'delivery_price' ],
                        'coupon_id' => 0,
                        'coupon_money' => 0,
                        'adjust_money' => 0,
                        'invoice_money' => 0,
                        'promotion_money' => 0,
                        'order_money' => $order_info[ 'order_money' ],
                        'balance_money' => 0,
                        'pay_money' => $order_info[ 'order_money' ],
                        'create_time' => time(),
                        'is_enable_refund' => $order_type[ 'order_type_id' ] == 4 ? 0 : 1,
                        'order_name' => $order_info[ 'exchange_name' ],
                        'goods_num' => $order_info[ 'num' ],
                        'delivery_type' => $order_info[ 'delivery_type' ],
                        'delivery_type_name' => $order_info[ 'delivery_type_name' ],
                        'delivery_store_id' => $order_info[ 'delivery_store_id' ],
                        'delivery_store_name' => $order_info[ 'delivery_store_name' ],
                        'delivery_store_info' => $order_info[ 'delivery_store_info' ],
                        'buyer_message' => $order_info[ 'buyer_message' ],

                        'invoice_delivery_money' => 0,
                        'taxpayer_number' => '',
                        'invoice_rate' => 0,
                        'invoice_content' => '',
                        'invoice_full_address' => '',
                        'is_invoice' => 0,
                        'invoice_type' => 0,
                        'invoice_title' => '',
                        'is_tax_invoice' => '',
                        'invoice_email' => '',
                        'invoice_title_type' => 0,

                        'buyer_ask_delivery_time' => $order_info[ 'buyer_ask_delivery_time' ],//定时达
                        'delivery_start_time' => $order_info[ 'delivery_start_time' ],
                        'delivery_end_time' => $order_info[ 'delivery_end_time' ],

                        'promotion_type' => 'pointexchange',
                        'promotion_type_name' => '积分兑换',
                        'store_id' => $order_info[ 'delivery_store_id' ]
                    ];
                    $order_id = model('order')->add($data_order);
                    $order_data[ 'relate_order_id' ] = $order_id;

                    $data_order_goods = [
                        'order_id' => $order_id,
                        'site_id' => $order_info[ 'site_id' ],
                        'order_no' => $order_no,
                        'member_id' => $order_info[ 'member_id' ],
                        'sku_id' => $sku_info[ 'sku_id' ],
                        'sku_name' => $sku_info[ 'sku_name' ],
                        'sku_image' => $sku_info[ 'sku_image' ],
                        'sku_no' => $sku_info[ 'sku_no' ],
                        'is_virtual' => $sku_info[ 'is_virtual' ],
                        'goods_class' => $sku_info[ 'goods_class' ],
                        'goods_class_name' => $sku_info[ 'goods_class_name' ],
                        'price' => $order_info[ 'exchange_price' ],
                        'cost_price' => $sku_info[ 'cost_price' ],
                        'num' => $order_info[ 'num' ],
                        'goods_money' => $order_info[ 'exchange_price' ] * $order_info[ 'num' ],
                        'cost_money' => $sku_info[ 'cost_price' ] * $order_info[ 'num' ],
                        'goods_id' => $sku_info[ 'goods_id' ],
                        'delivery_status' => OrderGoodsDict::wait_delivery,
                        'delivery_status_name' => OrderGoodsDict::getDeliveryStatus(OrderGoodsDict::wait_delivery),
                        'real_goods_money' => $order_info[ 'exchange_price' ] * $order_info[ 'num' ],
                        'coupon_money' => 0,
                        'promotion_money' => 0,

                        'goods_name' => $sku_info[ 'goods_name' ],
                        'sku_spec_format' => $sku_info[ 'sku_spec_format' ],
                        'store_id' => $order_info[ 'delivery_store_id' ],
                        'supplier_id' => $sku_info[ 'supplier_id' ] ?? 0
                    ];
                    model('order_goods')->add($data_order_goods);
                    $order_common = new OrderCommon();
                    $pay = new Pay();
                    $pay_info = $pay->getPayInfo($out_trade_no);

                    $res = $order_common->orderOnlinePay($pay_info[ 'data' ]);
                    if (isset($res[ 'code' ]) && $res[ 'code' ] != 0) {
                        Log::write('积分兑换商品订单支付失败：' . $res[ 'message' ]);
                    }
                    break;
                case 2://优惠券
                    $coupon_model = new Coupon();
                    $res = $coupon_model->giveCoupon([ [ 'coupon_type_id' => $order_info[ 'type_id' ], 'num' => $order_info[ 'num' ] ] ], $order_info[ 'site_id' ], $order_info[ 'member_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
                    break;
                case 3://红包
                    $member_account_model = new MemberAccount();
                    $res = $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], AccountDict::balance, $order_info[ 'balance' ], 'order', '积分兑换,', '积分兑换');
                    break;
            }

            model('promotion_exchange_order')->update($order_data, [ [ 'order_id', '=', $order_info[ 'order_id' ] ] ]);

            //积分兑换订单支付
            event('PointExchangeOrderPay', $order_info);

            model('promotion_exchange_order')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_exchange_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    private function orderType($type_name, $is_virtual = 0)
    {
        if ($type_name == 'express') {
            $order = new CommonOrder();
            return [
                'order_type_id' => 1,
                'order_type_name' => '普通订单',
                'order_status' => $order->order_status[ 0 ]
            ];
        } elseif ($type_name == 'store') {
            $order = new StoreOrder();
            return [
                'order_type_id' => 2,
                'order_type_name' => '自提订单',
                'order_status' => $order->order_status[ 0 ]
            ];
        } elseif ($type_name == 'local') {
            $order = new LocalOrder();
            return [
                'order_type_id' => 3,
                'order_type_name' => '外卖订单',
                'order_status' => $order->order_status[ 0 ]
            ];
        } elseif ($is_virtual) {
            $order = new VirtualOrder();
            return [
                'order_type_id' => 4,
                'order_type_name' => '虚拟订单',
                'order_status' => $order->order_status[ 0 ]
            ];
        }
    }

    /**
     * 关闭订单
     * @param $order_id
     * @return array|int|mixed|void
     */
    public function closeOrder($order_id)
    {
        $order_info = model('promotion_exchange_order')->getInfo([ [ 'order_id', '=', $order_id ] ], '*');

        if (empty($order_info)) return $this->error();

        model('promotion_exchange_order')->startTrans();
        try {
            $data = array (
                'order_status' => -1,
            );
            $result = model('promotion_exchange_order')->update($data, [ [ 'order_id', '=', $order_id ] ]);

            //返还积分
            $member_account_model = new MemberAccount();
            $member_account_result = $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'point', $order_info[ 'point' ], 'pointexchangerefund', $order_id, '积分兑换关闭返还');

            if ($member_account_result[ 'code' ] < 0) {
                model('promotion_exchange_order')->rollback();
                return $member_account_result;
            }

            //判断库存
            $exchange_model = new Exchange();
            switch ( $order_info[ 'type' ] ) {//兑换类型
                case '1'://商品
//                    $sku_info  = model('goods_sku')->getInfo( [ ['sku_id','=',$order_info['type_id']] ], '');
//                    if(!empty($sku_info)){
//                        //库存变化
//                        $goods_stock_model = new GoodsStock();
//                        $stock_result = $goods_stock_model->incStock(['sku_id' => $order_info['type_id'], 'num' => $order_info['num']]);
//                        if ($stock_result['code'] != 0) {
//                            model('promotion_exchange_order')->rollback();
//                            return $stock_result;
//                        }
//                    }
                    break;
                case '2'://优惠券
                    //返回优惠券库存
                    $coupon_model = new Coupon();
                    $coupon_info = $coupon_model->getCouponTypeInfo([ [ 'coupon_type_id', '=', $order_info[ 'type_id' ] ] ], 'coupon_type_id');
                    if (!empty($coupon_info)) {
                        $result = $coupon_model->incStock([ [ 'coupon_type_id', '=', $order_info[ 'type_id' ] ], [ 'num', '=', $order_info[ 'num' ] ] ]);
                    }
                    break;
                case '3'://红包
                    break;

            }

            //返还套餐库存
            $exchange_model->incStock([ 'id' => $order_info[ 'exchange_id' ], 'num' => $order_info[ 'num' ] ]);

            if ($order_info[ 'type' ] == 1 && $order_info[ 'order_money' ] > 0 && $order_info[ 'order_status' ] == 0) {
                //关闭支付
                $pay_model = new Pay();
                $result = $pay_model->closePay($order_info[ 'out_trade_no' ]);//关闭旧支付单据
                if ($result[ 'code' ] < 0) {
                    model('promotion_exchange_order')->rollback();
                    return $result;
                }
            }
            model('promotion_exchange_order')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_exchange_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取积分兑换订单信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getOrderInfo($condition, $field = '*')
    {
        $res = model('promotion_exchange_order')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取订单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getOrderList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_exchange_order')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取订单总和
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getOrderSum($where = [], $field = '', $alias = 'a', $join = null)
    {
        $data = model('promotion_exchange_order')->getSum($where, $field, $alias, $join);
        return $this->success($data);
    }

    /**
     * 获取积分兑换订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getExchangePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [])
    {
        $list = model('promotion_exchange_order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 发货
     * @param $param
     * @return array
     */
    public function delivery($param)
    {
        $order_id = $param[ 'order_id' ];
        $delivery_code = $param[ 'delivery_code' ];
        $order_data = array (
            'delivery_status' => OrderGoodsDict::delivery,
            'delivery_status_name' => OrderGoodsDict::getDeliveryStatus(OrderGoodsDict::delivery),
            'delivery_code' => $delivery_code
        );
        $res = model('promotion_exchange_order')->update($order_data, [ [ 'order_id', '=', $order_id ] ]);
        return $this->success();
    }
}