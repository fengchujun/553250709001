<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\model\ordercreate;

use addon\giftcard\model\card\Card;
use addon\giftcard\model\card\CardOperation;
use addon\giftcard\model\membercard\MemberCard;
use app\model\BaseModel;
use app\model\order\Config;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use app\model\store\Store;
use app\model\express\Express;
use app\model\system\Pay;
use app\model\express\Config as ExpressConfig;
use app\model\express\Local;
use extend\exception\OrderException;

/**
 * 订单创建(礼品卡)
 */
class GiftcardOrderCreate extends BaseModel
{

    use OrderCreateTool;

    public $cart_id = 0;
    public $member_card_id = 0;

    public function __construct()
    {
        $this->promotion_type = 'giftcard';
        $this->promotion_type_name = '礼品卡';
    }

    /**
     * 订单创建
     */
    public function create()
    {
        //计算
        $this->confirm();
        if ($this->error > 0) {
            return $this->error([ 'error_code' => $this->error ], $this->error_msg);
        }
        //订单创建数据
        $order_insert_data = $this->getOrderInsertData([ 'discount' ], 'invert');
        $order_insert_data[ 'store_id' ] = $this->store_id;
        $order_insert_data[ 'create_time' ] = time();
        $order_insert_data[ 'is_enable_refund' ] = 0;
        //订单类型以及状态
        $this->orderType();
        $order_insert_data[ 'order_type' ] = $this->order_type[ 'order_type_id' ];
        $order_insert_data[ 'order_type_name' ] = $this->order_type[ 'order_type_name' ];
        $order_insert_data[ 'order_status_name' ] = $this->order_type[ 'order_status' ][ 'name' ];
        $order_insert_data[ 'order_status_action' ] = json_encode($this->order_type[ 'order_status' ], JSON_UNESCAPED_UNICODE);
        model('order')->startTrans();
        //循环生成多个订单
        try {


            $this->order_id = model('order')->add($order_insert_data);

            $order_goods_insert_data = [];
            //订单项目表
            foreach ($this->goods_list as &$order_goods_v) {
                $order_goods_insert_data[] = $this->getOrderGoodsInsertData($order_goods_v);
            }
            model('order_goods')->addList($order_goods_insert_data);

            $card_goods_json = [];
            foreach ($this->getOrderGoodsList() as $k => $v) {
                $goods_item = $this->goods_list[ $k ];
                $card_goods_json[] = [
                    'order_goods_id' => $v[ 'order_goods_id' ],
                    'card_goods_id' => $goods_item[ 'id' ],
                    'num' => $v[ 'num' ],
                ];
            }
            $card_use_params = array (
                'site_id' => $this->site_id,
                'order_id' => $this->order_id,
                'member_id' => $this->member_id,
                'member_card_id' => $this->member_card_id,
                'card_goods_json' => $card_goods_json
            );
            $card_operation_model = new CardOperation();
            $use_res = $card_operation_model->cardUse($card_use_params);
            if($use_res['code'] < 0){
                model('order')->rollback();
                return $use_res;
            }

            //批量库存处理(卡密商品支付后在扣出库存)//todo  可以再商品中设置扣除库存步骤
            $this->batchDecOrderGoodsStock();
            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            $pay = new Pay();
            //生成整体支付单据
            $pay_res = $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '/pages/order/detail?order_id=' . $this->order_id, $this->order_id, $this->member_id);
            if($pay_res['code'] < 0){
                model('order')->rollback();
                return $pay_res;
            }

            return $this->success($this->out_trade_no);
        } catch (\Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单计算
     */
    public function calculate()
    {
        //初始化地址
        $this->initMemberAddress();
        //初始化会员账户
        $this->initMemberAccount();
        //商品列表信息
        $this->getOrderGoodsCalculate();
        //订单计算
        $this->shopOrderCalculate();
        //获取发票相关
        $this->getInovice();
        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }

    /**
     * 待付款订单
     */
    public function orderPayment()
    {
        $this->calculate();
        //查询配送信息
        $this->getDeliveryData();
        //订单初始项
        event('OrderPayment', [ 'order_object' => $this ]);
        return get_object_vars($this);

    }

    /**
     * 获取商品的计算信息
     * @param unknown $data
     */
    public function getOrderGoodsCalculate()
    {
        $this->getCardGoodsList();
        return true;
    }

    /**
     * 获取组合套餐商品列表信息
     */
    public function getCardGoodsList()
    {
        $this->member_card_id = $this->param[ 'member_card_id' ];
        $member_card_model = new MemberCard();
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'member_card_id', '=', $this->member_card_id ],
            [ 'member_id', '=', $this->member_id ]
        );
        $member_card_info = $member_card_model->getMemberCardInfo($condition)[ 'data' ] ?? [];
        if (empty($member_card_info)) return $this->error([], '当前礼品卡不存在或已被领取！');

        $card_id = $member_card_info[ 'card_id' ];

        $card_condition = array (
            [ 'card_id', '=', $card_id ],
            [ 'member_id', '=', $this->member_id ]
        );
        $card_model = new Card();
        $card_info = $card_model->getCardInfo($card_condition)[ 'data' ] ?? [];
        if (empty($card_info)) throw new OrderException('当前礼品卡不存在或已被使用！');
        if ($card_info[ 'status' ] != 'to_use') throw new OrderException('当前礼品卡已被使用！');
        $goods_sku_list = $this->param[ 'goods_sku_list' ];
        //组装商品列表
        $field = ' gcg.total_num,gcg.id,gcg.sku_id, ngs.sku_name, ngs.sku_no,
            ngs.price, ngs.discount_price, ngs.cost_price, ngs.stock, ngs.weight, ngs.volume, ngs.sku_image, 
            ngs.site_id, ngs.goods_state, ngs.is_virtual, ngs.supplier_id,ngs.form_id,
            ngs.is_free_shipping, ngs.shipping_template, ngs.goods_class, ngs.goods_class_name, ngs.goods_id, ns.site_name,ngs.sku_spec_format,ngs.goods_name,ngs.support_trade_type';
        $alias = 'gcg';
        $join = [
            [
                'goods_sku ngs',
                'gcg.sku_id = ngs.sku_id',
                'inner'
            ],

            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ]
        ];
        $card_model = new Card();
        $condition = array (
            [ 'card_id', '=', $card_id ]
        );
        $card_info = $card_model->getCardInfo($condition)[ 'data' ] ?? [];
        $card_right_goods_type = $card_info[ 'card_right_goods_type' ];

        $card_goods_list = $card_model->getCardGoodsList($condition, $field, '', null, $alias, $join)[ 'data' ] ?? [];
        $card_goods_list_column = array_column($card_goods_list, null, 'sku_id');

        if (!empty($card_goods_list)) {
            if ($card_right_goods_type == 'item') {
                foreach ($card_goods_list as $v) {
                    $this->is_virtual = $v[ 'is_virtual' ];
                    $num = $v[ 'total_num' ];
                    $v[ 'num' ] = $num;
                    $price = $v[ 'discount_price' ];
                    $v[ 'price' ] = $price;
                    $goods_money = $price * $num;
                    $v[ 'goods_money' ] = $goods_money;
                    $promotion_money = 0;
                    $v[ 'real_goods_money' ] = $goods_money;
                    $v[ 'coupon_money' ] = 0;//优惠券金额
                    $v[ 'promotion_money' ] = $promotion_money;//优惠金额


                    $this->goods_list[] = $v;
                    $order_name = $this->order_name ?? '';
                    if ($order_name) {
                        $len = strlen_mb($order_name);
                        if ($len > 200) {
                            $this->order_name = str_sub($order_name, 200);
                        } else {
                            $this->order_name = string_split($order_name, ',', $v[ 'sku_name' ]);
                        }
                    } else {
                        $this->order_name = string_split('', ',', $v[ 'sku_name' ]);
                    }
                    $this->site_name = $v[ 'site_name' ];
                    $this->goods_num += $v[ 'num' ];
                    $this->goods_money += $v[ 'goods_money' ];
                    //以;隔开的商品项
                    $goods_list_str = $this->goods_list_str ?? '';
                    if ($goods_list_str) {
                        $this->goods_list_str = $goods_list_str . ';' . $v[ 'sku_id' ] . ':' . $v[ 'num' ];
                    } else {
                        $this->goods_list_str = $v[ 'sku_id' ] . ':' . $v[ 'num' ];
                    }
                }
            } else {
                foreach ($goods_sku_list as $v) {
                    $sku_info = $card_goods_list_column[ $v[ 'sku_id' ] ] ?? [];
                    $v = array_merge($v, $sku_info);
                    $this->is_virtual = $v[ 'is_virtual' ];
                    $price = $sku_info[ 'discount_price' ];
                    $v[ 'price' ] = $price;
                    $goods_money = $price * $v[ 'num' ];
                    $v[ 'goods_money' ] = $goods_money;
                    $promotion_money = 0;
                    $v[ 'real_goods_money' ] = $goods_money;
                    $v[ 'coupon_money' ] = 0;//优惠券金额
                    $v[ 'promotion_money' ] = $promotion_money;//优惠金额

                    $this->goods_list[] = $v;
                    $order_name = $this->order_name ?? '';
                    if ($order_name) {
                        $len = strlen_mb($order_name);
                        if ($len > 200) {
                            $this->order_name = str_sub($order_name, 200);
                        } else {
                            $this->order_name = string_split($order_name, ',', $v[ 'sku_name' ]);
                        }
                    } else {
                        $this->order_name = string_split('', ',', $v[ 'sku_name' ]);
                    }
                    $this->site_name = $v[ 'site_name' ];
                    $this->goods_num += $v[ 'num' ];
                    $this->goods_money += $v[ 'goods_money' ];
                    //以;隔开的商品项
                    $goods_list_str = $this->goods_list_str ?? '';
                    if ($goods_list_str) {
                        $this->goods_list_str = $goods_list_str . ';' . $v[ 'sku_id' ] . ':' . $v[ 'num' ];
                    } else {
                        $this->goods_list_str = $v[ 'sku_id' ] . ':' . $v[ 'num' ];
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取店铺订单计算
     */
    public function shopOrderCalculate()
    {
        $promotion_money = 0;
        foreach ($this->goods_list as &$v) {
            $item_goods_money = $v[ 'goods_money' ];
            $item_promotion_money = $item_goods_money;
            $real_goods_money = $item_goods_money - $item_promotion_money;
            $v[ 'real_goods_money' ] = $real_goods_money;
            $v[ 'promotion_money' ] = $item_promotion_money;
            $promotion_money += $item_promotion_money;
        }
        $this->promotion_money = $promotion_money;
        $this->is_free_delivery = true;
        //重新计算订单总额
        $this->getOrderMoney();
        //理论上是多余的操作
        if ($this->order_money < 0) {
            $this->order_money = 0;
        }
        //总结计算
        $this->pay_money = $this->order_money;

        return true;

    }

    /**
     * 计算后的进一步计算(不存缓存,每次都是重新计算)
     * @return array
     */
    public function confirm()
    {
        $order_key = $this->param[ 'order_key' ];
        $this->getOrderCache($order_key);
        //初始化地址
        $this->initMemberAddress();
        //初始化门店信息
        $this->initStore();
        //配送计算
        $this->calculateDelivery();
        //批量校验配送方式
        $this->batchCheckDeliveryType();
        //计算发票相关
        $this->calculateInvoice();
        //计算余额
        $this->calculateBalcnce();
        $this->pay_money = $this->order_money - $this->balance_money;
        //设置过的商品项信息
        return get_object_vars($this);
    }
}