<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\presale\model;

use addon\coupon\model\Coupon;
use addon\store\model\StoreGoodsSku;
use addon\store\model\StoreMember as StoreMemberModel;
use app\model\BaseModel;
use app\model\express\Config as ExpressConfig;
use app\model\express\Express;
use app\model\express\Local;
use app\model\order\Config;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use app\model\store\Store;
use app\model\system\Pay;
use extend\exception\OrderException;
use think\facade\Cache;

/**
 * 订单创建(商品预售)
 *
 * @author Administrator
 *
 */
class PresaleOrderCreate extends BaseModel
{

    use OrderCreateTool;

//    private $goods_money = 0;//商品金额
//    private $balance_money = 0;//余额
//    private $delivery_money = 0;//配送费用
//    private $coupon_money = 0;//优惠券金额
//    private $adjust_money = 0;//调整金额
//    private $invoice_money = 0;//发票费用
//    private $promotion_money = 0;//优惠金额
//    private $order_money = 0;//订金金额
//    private $pay_money = 0;//支付总价
//
//    private $is_virtual = 0;  //是否是虚拟类订单
//    private $order_name = '';  //订单详情
//    private $goods_num = 0;  //商品种数
//    private $member_balance_money = 0;//会员账户余额(计算过程中会逐次减少)
//    private $pay_type = 'ONLINE_PAY';//支付方式
//    private $invoice_delivery_money = 0;
//    private $error = 0;  //是否有错误
//    private $error_msg = '';  //错误描述
//    private $recommend_member_card; // 推荐会员卡


    public $final_money = 0;//尾款金额
    public $presale_id = 0;
    public $deduction_money = 0;//抵扣金额
    public $promotion_presale_info = [];
    public $presale_info = [];
    public $pay_end_time = 0;
    public $is_deposit_back = 0;

    /************************************************** 定金支付 start *********************************************************************/

    /**
     * 订单创建
     * @param unknown $data
     */
    public function create()
    {
        //计算
        $this->confirm();
        if ($this->error > 0) {
            return $this->error(['error_code' => $this->error], $this->error_msg);
        }
        $pay = new Pay();
        $this->out_trade_no = $pay->createOutTradeNo($this->member_id);

        $presale_common_order = new PresaleOrderCommon();
        $order_status_data = $presale_common_order->getOrderStatus();
        $order_status = $order_status_data['data'];
        $this->order_no = $this->createOrderNo();
        model('promotion_presale_order')->startTrans();
        //循环生成多个订单
        try {

            $this->orderType();
            $express_type_list = $this->config('delivery_type');
            $delivery_type_name = $express_type_list[$this->delivery['delivery_type']] ?? '';

            $sku_info = $this->goods_list[0];
            $data_order = [
                'site_id' => $this->site_id,
                'site_name' => $this->site_name,
                'presale_id' => $this->presale_id,
                'order_no' => $this->order_no,

                'order_from' => $this->order_from,
                'order_from_name' => $this->order_from_name,
                'order_type' => $this->order_type['order_type_id'],
                'order_type_name' => $this->order_type['order_type_name'],
                'order_status_name' => $order_status[$presale_common_order::ORDER_CREATE]['name'],
                'order_status_action' => json_encode($order_status[$presale_common_order::ORDER_CREATE], JSON_UNESCAPED_UNICODE),
                'pay_start_time' => $this->presale_info['pay_start_time'],
                'pay_end_time' => $this->presale_info['pay_end_time'],

                'goods_id' => $this->presale_info['goods_id'],
                'goods_name' => $this->presale_info['goods_name'],

                'sku_id' => $sku_info['sku_id'],
                'sku_name' => $sku_info['sku_name'],
                'sku_image' => $sku_info['sku_image'],
                'sku_no' => $sku_info['sku_no'],
                'is_virtual' => $this->is_virtual,
                'goods_class' => $sku_info['goods_class'],
                'goods_class_name' => $sku_info['goods_class_name'],
                'cost_price' => $sku_info['cost_price'],
                'sku_spec_format' => $sku_info['sku_spec_format'],

                'member_id' => $this->member_id,
                'name' => $this->delivery['member_address']['name'] ?? '',
                'mobile' => $this->delivery['member_address']['mobile'] ?? '',
                'telephone' => $this->delivery['member_address']['telephone'] ?? '',
                'province_id' => $this->delivery['member_address']['province_id'] ?? '',
                'city_id' => $this->delivery['member_address']['city_id'] ?? '',
                'district_id' => $this->delivery['member_address']['district_id'] ?? '',
                'community_id' => $this->delivery['member_address']['community_id'] ?? '',
                'address' => $this->delivery['member_address']['address'] ?? '',
                'full_address' => $this->delivery['member_address']['full_address'] ?? '',
                'longitude' => $this->delivery['member_address']['longitude'] ?? '',
                'latitude' => $this->delivery['member_address']['latitude'] ?? '',
                'buyer_ip' => request()->ip(),

                'buyer_message' => $this->param['buyer_message'],

                'num' => $this->goods_num,


                'price' => $sku_info['price'],
                'goods_money' => $this->goods_money,
                'balance_deposit_money' => $this->balance_money,

                'delivery_money' => $this->delivery_money,
                'promotion_money' => $this->promotion_money,
                'coupon_id' => $this->coupon_id ?? 0,
                'coupon_money' => $this->coupon_money,
                'invoice_money' => $this->invoice_money,
                'invoice_delivery_money' => $this->invoice_delivery_money,

                'order_money' => $this->order_money,
                'delivery_type' => $this->delivery['delivery_type'],
                'delivery_type_name' => $delivery_type_name,
                'delivery_store_id' => $this->delivery['store_id'] ?? 0,
                'delivery_store_name' => $this->delivery['delivery_store_name'] ?? '',
                'delivery_store_info' => $this->delivery['delivery_store_info'] ?? '',
                'buyer_ask_delivery_time' => $this->delivery['buyer_ask_delivery_time']['remark'] ?? '',//定时达
                'delivery_start_time' => $this->delivery['buyer_ask_delivery_time']['delivery_start_time'] ?? '',//配送开始时间
                'delivery_end_time' => $this->delivery['buyer_ask_delivery_time']['delivery_end_time'] ?? '',//配送结束时间


                'order_status' => '0',
                'create_time' => time(),

                //预售相关
                'deposit_out_trade_no' => $this->out_trade_no,
                'is_deposit_back' => $this->promotion_presale_info['is_deposit_back'],
                'deposit_agreement' => $this->promotion_presale_info['deposit_agreement'],
                'presale_deposit' => $this->presale_info['presale_deposit'],//定金单价
                'presale_deposit_money' => $this->presale_deposit_money,//定金总额
                'presale_price' => $this->presale_info['presale_price'],//抵扣单价
                'presale_money' => $this->presale_money,//抵扣总额
                'pay_deposit_money' => $this->pay_money,
                'final_money' => $this->final_money,
                'is_fenxiao' => $this->presale_info['is_fenxiao'],

            ];
            if (isset($this->param['is_invoice']) && $this->param['is_invoice'] == 1) {
                $data_order = array_merge($data_order,
                    [
                        'is_invoice' => $this->param['is_invoice'] ?? 0,
                        'invoice_type' => $this->invoice['invoice_type'] ?? 0,
                        'invoice_title' => $this->invoice['invoice_title'] ?? '',
                        'taxpayer_number' => $this->invoice['taxpayer_number'] ?? '',
                        'invoice_rate' => $this->invoice['invoice_rate'] ?? 0,
                        'invoice_content' => $this->invoice['invoice_content'] ?? '',
                        'invoice_full_address' => $this->invoice['invoice_full_address'] ?? '',
                        'is_tax_invoice' => $this->invoice['is_tax_invoice'] ?? '',
                        'invoice_email' => $this->invoice['invoice_email'] ?? '',
                        'invoice_title_type' => $this->invoice['invoice_title_type'] ?? 0
                    ]
                );
            }
            $this->order_id = model('promotion_presale_order')->add($data_order);

            //预售订单创建
            event('PresaleOrderCreate', ['id' => $this->order_id]);

            //添加门店关注记录和减少门店商品库存 最新代码位置
            $result_list = $this->addStoreMemberAndDecStock($data_order);
            if ($result_list['code'] < 0) {
                model('promotion_presale_order')->rollback();
                return $result_list;
            }

            //减少库存
            $stock_result = $this->decStock();
            if ($stock_result['code'] != 0) {
                model('promotion_presale_order')->rollback();
                return $stock_result;
            }
            //使用余额
            $this->useBalance();
            //批量库存处理(卡密商品支付后在扣出库存)//todo  可以再商品中设置扣除库存步骤
//            $this->batchDecOrderGoodsStock();
            //添加门店关注记录和减少门店商品库存 原本代码位置

            model('promotion_presale_order')->commit();
            //删除订单缓存
            $this->deleteOrderCache();
            //增加关闭订单自动事件
            $presale_order_model = new PresaleOrderCommon();
            $presale_order_model->addDepositOrderCronClose($this->order_id, $this->site_id);
            //如果退定金 增加尾款支付到期时间退定金操作
            if ($this->is_deposit_back == 0) {
                $presale_order_model->addRefundOrderCronClose($this->order_id, $this->site_id, $this->pay_end_time);
            }
            //生成整体支付单据
            $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'DepositOrderPayNotify', '', $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);
        } catch ( \Exception $e ) {
            model('promotion_presale_order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单计算（定金）
     * @param unknown $data
     */
    public function depositCalculate()
    {
        $this->initMemberAddress(); //初始化地址
        $this->initMemberAccount();//初始化会员账户

        $this->presale_id = $this->param['presale_id'];
        $sku_id = $this->param['sku_id'];
        $num = $this->param['num'];
        //查询预售活动信息
        $this->promotion_presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $this->presale_id]]);
        if (empty($this->promotion_presale_info)) {
            $this->error = 1;
            $this->error_msg = '预售活动不存在！';
        }
        //查询预售信息
        $join = [
            ['promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner'],
            ['goods g', 'g.goods_id = ppg.goods_id', 'inner']
        ];
        $condition = [
            ['ppg.presale_id', '=', $this->presale_id],
            ['ppg.sku_id', '=', $sku_id],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $field = 'pp.*,ppg.sku_id,ppg.presale_stock,ppg.presale_deposit,ppg.presale_price,g.goods_name,g.is_virtual';
        $this->presale_info = model('promotion_presale_goods')->getInfo($condition, $field, 'ppg', $join);
        $this->is_virtual = $this->presale_info['is_virtual'];

        if (empty($this->presale_info)) {
            $this->error = 1;
            $this->error_msg = '商品不存在！';
        }
        //判断活动是否过期或开启
        if ($this->presale_info['status'] != 1) {
            $this->error = 1;
            $this->error_msg = '当前商品预售活动未开启或已过期！';
        }
        //判断购买数是否超过限购
        if ($this->presale_info['presale_num'] < $num && $this->presale_info['presale_num'] > 0) {
            $this->error = 1;
            $this->error_msg = '该商品限制购买不能大于' . $this->presale_info['presale_num'] . '件！';
        }

        //判断是否已存在订单
        $presale_order_count = model('promotion_presale_order')->getCount(
            [
                ['member_id', '=', $this->member_id],
                ['presale_id', '=', $this->presale_id],
                ['order_status', '>=', 0],
                ['refund_status', '=', 0]
            ]);
        if ($presale_order_count > 0) {
            $this->error = 1;
            $this->error_msg = '预售期间，同一商品只可购买一次！';
        }

        //商品列表信息
        $this->getOrderGoodsCalculate();
        //订单计算
        $this->shopOrderCalculate();

        //定金金额
        $this->presale_deposit_money = $this->presale_info['presale_deposit'] * $num;
        //余额抵扣(判断是否使用余额)
        if ($this->member_balance_money > 0) {
            $balance_money = min($this->presale_deposit_money, $this->member_balance_money);
        } else {
            $balance_money = 0;
        }
        $this->pay_money = $this->presale_deposit_money - $balance_money;//计算出实际支付金额

        $this->member_balance_money -= $balance_money;//预减少账户余额
        $this->balance_money += $balance_money;//累计余额

        $this->order_money = $this->final_money + $this->presale_deposit_money;

        $this->presale_money = $this->presale_info['presale_price'] * $this->goods_num;
        $this->is_deposit_back = $this->presale_info['is_deposit_back'];
        $this->pay_end_time = $this->presale_info['pay_end_time'];

        //获取发票相关
        $this->getInovice();
        //todo  统一检测库存(创建订单操作时扣除库存同理)
        // 商品限购判断
        $this->checkLimitPurchase();
        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }

    /**
     * 待付款订单（定金）
     * @param unknown $data
     */
    public function depositOrderPayment()
    {
        $this->depositCalculate();
        $this->getDeliveryData();
        //订单初始项
        event('OrderPayment', ['order_object' => $this]);
        return get_object_vars($this);
    }

    /**
     * 获取商品的计算信息
     * @param unknown $data
     */
    public function getOrderGoodsCalculate()
    {
        $this->getPresaleShopGoodsList();
        return true;
    }

    /**
     * 获取立即购买商品信息
     * @param $data
     * @return array
     */
    public function getPresaleShopGoodsList()
    {
        $sku_id = $this->param['sku_id'];
        $num = $this->param['num'];
        $join = [
            ['site ns', 'ngs.site_id = ns.site_id', 'inner']
        ];
        $field = 'sku_id, sku_name, sku_no, price, discount_price,cost_price, stock, volume, weight, sku_image, ngs.site_id, goods_state, is_virtual, is_free_shipping, shipping_template,goods_class, goods_class_name, goods_id, ns.site_name,sku_spec_format,goods_name,max_buy,min_buy,support_trade_type, is_limit, limit_type,form_id';
        $sku_info = model('goods_sku')->getInfo([['sku_id', '=', $sku_id], ['ngs.site_id', '=', $this->site_id]], $field, 'ngs', $join);
        if (empty($sku_info)) throw new OrderException('不存在的商品！');

        $price = $sku_info['price'];

        $sku_info['num'] = $num;
        $goods_money = $price * $num;
        $sku_info['price'] = $price;
        $sku_info['goods_money'] = $goods_money;
        $sku_info['real_goods_money'] = $goods_money;
        $sku_info['coupon_money'] = 0; //优惠券金额
        $sku_info['promotion_money'] = 0; //优惠金额
        $sku_info['stock'] = numberFormat($sku_info['stock']);
        $goods_list[] = $sku_info;

        $this->goods_money = $goods_money;
        $this->site_name = $sku_info['site_name'];
        $this->goods_list_str = $sku_info['sku_id'] . ':' . $sku_info['num'];
        $this->goods_list = $goods_list;
        $this->order_name = $sku_info['sku_name'];
        $this->goods_num = $sku_info['num'];
        $this->limit_purchase = [
            'goods_' . $sku_info['goods_id'] => [
                'goods_id' => $sku_info['goods_id'],
                'goods_name' => $sku_info['sku_name'],
                'num' => $sku_info['num'],
                'max_buy' => $sku_info['max_buy'],
                'min_buy' => $sku_info['min_buy'],
                'is_limit' => $sku_info['is_limit'],
                'limit_type' => $sku_info['limit_type'],
            ]
        ];
        return true;
    }

    /**
     * 库存变化
     * @return array
     */
    public function decStock()
    {
        $condition = array(
            ['site_id', '=', $this->site_id],
            ['presale_id', '=', $this->presale_id],
            ['sku_id', '=', $this->goods_list[0]['sku_id']]
        );
        $presale_info = model('promotion_presale_goods')->getInfo($condition, 'presale_stock');
        if (empty($presale_info))
            return $this->error();

        if ($presale_info['presale_stock'] <= 0)
            return $this->error('', '库存不足！');

        //编辑sku库存
        $res = model('promotion_presale_goods')->setDec($condition, 'presale_stock', $this->goods_num);
        //减少总库存 2021.06.10
        if ($res === false)
            return $this->error();

        return $this->success($res);
    }

    /**
     * 计算后的进一步计算(不存缓存,每次都是重新计算)
     * @return array
     */
    public function confirm()
    {
        $order_key = $this->param['order_key'];
        $this->getOrderCache($order_key);
        //初始化地址
        $this->initMemberAddress();
        //初始化门店信息
        $this->initStore();
        //配送计算
        $this->is_check_buyer_ask_delivery_time = false;
        $this->calculateDelivery();
        //批量校验配送方式
        $this->batchCheckDeliveryType();
        //计算发票相关
        $this->calculateInvoice();
        //尾款金额
        $this->final_money = $this->final_money + $this->invoice_money + $this->invoice_delivery_money;
        //定金金额
        $this->presale_deposit_money = $this->presale_info['presale_deposit'] * $this->goods_num;
        //余额抵扣(判断是否使用余额)
        //使用余额
        $is_use_balance = $this->param['is_balance'] ?? 0;

        if ($is_use_balance > 0) {
            //余额付款
            $this->member_balance_money = $this->member_account['balance_total'] ?? 0;
            if ($this->member_balance_money > 0) {
                $balance_money = min($this->presale_deposit_money, $this->member_balance_money);
            } else {
                $balance_money = 0;
            }
            $this->balance_money = $balance_money;
        }
        $this->pay_money = $this->presale_deposit_money - $this->balance_money;//计算出实际支付金额
        $this->member_balance_money -= $this->balance_money;//预减少账户余额
        $this->order_money = $this->final_money + $this->presale_deposit_money;
        return get_object_vars($this);
    }

    /**
     * 获取店铺订单计算
     */
    public function shopOrderCalculate()
    {

        //实际抵扣金额
        if ($this->presale_info['presale_price'] == 0) {//全款预售
            $this->deduction_money = 0;
        } else {
            $this->deduction_money = $this->presale_info['presale_price'] * $this->goods_num - $this->presale_info['presale_deposit'] * $this->goods_num;
        }
        //尾款金额
        $this->final_money = $this->goods_money - $this->presale_info['presale_deposit'] * $this->goods_num - $this->promotion_money - $this->deduction_money + $this->delivery_money;

        //理论上是多余的操作
        if ($this->final_money < 0) {
            $this->final_money = 0;
        }


        return true;
    }


    /**
     * 添加门店关注记录和减少门店商品库存
     * @param $data
     * @return array
     */
    public function addStoreMemberAndDecStock($data)
    {
        if (!empty($data['delivery_store_id'])) {
            //添加店铺关注记录
            $shop_member_model = new StoreMemberModel();
            $res = $shop_member_model->addStoreMember($data['delivery_store_id'], $data['member_id']);
            if ($res['code'] < 0) {
                return $res;
            }
            $stock_result = $this->skuDecStock($data, $data['delivery_store_id']);
            if ($stock_result['code'] < 0) {

                return $stock_result;
            }
//            $store_goods_sku_model = new StoreGoodsSku();
//            $stock_result = $store_goods_sku_model->decStock([ 'store_id' => $data[ 'delivery_store_id' ], 'sku_id' => $data[ 'sku_id' ], 'stock' => $data[ 'num' ] ]);
//            if ($stock_result[ 'code' ] < 0) {
//                return $this->error('', '当前门店库存不足,请选择其他门店');
//            }
        }
        return $this->success();
    }


    /************************************************** 定金支付 end *********************************************************************/

    /************************************************** 尾款支付 start *********************************************************************/

    /**
     * 订单计算（尾款）
     * @param unknown $data
     */
    public function finalCalculate($data)
    {
        $this->initMemberAccount();//初始化会员账户
        //余额付款
        if ($data['is_balance'] > 0) {
            $this->member_balance_money = $this->member_account['balance_total'] ?? 0;
        }
        //查询预售订单信息
        $presale_order_model = new PresaleOrder();
        $order_info = $presale_order_model->getPresaleOrderInfo([['id', '=', $data['id']], ['site_id', '=', $this->site_id]])['data'] ?? [];

        $data['order_info'] = $order_info;

        //判断是否可以支付尾款
        if ($order_info['pay_start_time'] > time()) {
            $this->error = 1;
            $this->error_msg = '尾款支付时间还未开始！';
        }
        if ($order_info['pay_end_time'] < time()) {
            $this->error = 1;
            $this->error_msg = '尾款支付时间已过，已停止支付！';
        }

        //尾款总金额（尾款实际金额 + 发票 + 物流等）
        $order_money = $order_info['final_money'];

        //余额抵扣(判断是否使用余额)
        if ($this->member_balance_money > 0) {
            if ($order_money <= $this->member_balance_money) {
                $balance_money = $order_money;
            } else {
                $balance_money = $this->member_balance_money;
            }
        } else {
            $balance_money = 0;
        }
        $pay_money = $order_money - $balance_money;//计算出实际支付金额
        $this->member_balance_money -= $balance_money;//预减少账户余额
        $this->balance_money += $balance_money;//累计余额
        $is_use = 1;

        $this->pay_money += $pay_money;
        //总结计算
        $data['balance_final_money'] = $this->balance_money;
        $data['pay_final_money'] = $this->pay_money;
        $data['is_use_balance'] = $is_use;
        $data['balance_money'] = $this->balance_money;
        return $data;
    }


    /**
     * 尾款支付
     * @param $data
     * @return array
     */
    public function payfinalMoneyPresaleOrder($data)
    {
        //查询出会员相关信息
        $calculate_data = $this->finalCalculate($data);
        if (isset($calculate_data['code']) && $calculate_data['code'] < 0)
            return $calculate_data;
        if ($this->error > 0) {
            return $this->error(['error_code' => $this->error], $this->error_msg);
        }
        $pay = new Pay();
        $out_trade_no = $pay->createOutTradeNo();
        $order_data = [
            'balance_final_money' => $calculate_data['balance_final_money'],
            'pay_final_money' => $calculate_data['pay_final_money'],
            'final_out_trade_no' => $out_trade_no,
        ];
        model('promotion_presale_order')->startTrans();
        try {
            model('promotion_presale_order')->update($order_data, [['site_id', '=', $data['site_id']], ['id', '=', $data['id']]]);
            $this->order_id = $data['id'];
            //扣除余额(统一扣除)
            $this->order_from_type = 'presale_order';
            $this->useBalance();
            $order_name = $calculate_data['order_info']['sku_name'];
            //生成整体支付单据
            $pay->addPay($data['site_id'], $out_trade_no, $this->pay_type, $order_name, $order_name, $this->pay_money, '', 'FinalOrderPayNotify', '', $this->order_id, $this->member_id);
            model('promotion_presale_order')->commit();
            return $this->success($out_trade_no);
        } catch ( \Exception $e ) {
            model()->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /************************************************** 尾款支付 end *********************************************************************/

}