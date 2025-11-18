<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use addon\pointcash\model\Config as PointCashConfig;
use app\dict\order\OrderDict;
use app\dict\order\OrderGoodsDict;
use app\dict\order\OrderPayDict;
use app\model\express\Config as ExpressConfig;
use app\model\order\ordercreate\CommonTool;
use app\model\order\ordercreate\DeliveryTool;
use app\model\order\ordercreate\GoodsTool;
use app\model\order\ordercreate\PromotionTool;
use app\model\system\Cron;
use app\model\system\Pay;
use extend\exception\OrderException;
use think\facade\Cache;
use think\facade\Queue;

/**
 * 订单创建  可调用的工具类
 */
trait OrderCreateTool
{
    use CommonTool;
    use PromotionTool;
    use DeliveryTool;
    use GoodsTool;

    public $site_id = 1;//站点id
    public $site_info;
    public $store_info;
    public $store_id = 0;//门店id
    public $available_store_ids = 'all';//可以用的门店
    public $cart_ids = [];
    public $order_no;
    public $out_trade_no;
    public $order_key;
    public $config = [];//配置
    public $coupon_id = 0;//优惠券
    public $coupon_money = 0;//优惠券金额
    public $order_type = [];//订单类型(array)
    public $is_point = 0;//是否使用积分
    public $member_id = 0;//会员id
    public $member_account = [];//会员账户
    public $member_level = [];//会员等级
    public $invoice = [];//发票
    public $delivery = [];
    public $is_limit_start_money = true;//是否限制起送金额
    public $is_free_delivery = false;//是否免邮
    public $buyer = [];//买家信息
    public $param = [];//参数
    public $order_data = [];
    public $goods_num = 0;//商品数量
    public $limit_purchase = [];//限购
    public $promotion = [];//活动优惠...
    public $manjian_rule_list = [];//满减优惠规则
    public $promotion_type;
    public $promotion_type_name;
    public $site_name = '';
    public $goods_money = 0; //商品金额
    public $delivery_money = 0; //配送费用
    public $adjust_money = 0; //调整金额
    public $invoice_money = 0; //发票费用
    public $promotion_money = 0; //优惠金额
    public $order_money = 0; //订单金额
    public $pay_money = 0; //支付总价
    public $is_virtual = 0;  //是否是虚拟类订单
    public $is_virtual_delivery = 0;  //虚拟商品是否发货
    public $order_name = '';  //订单详情
    public $goods_list_str = '';
    public $error = 0;  //是否有错误
    public $error_msg = '';  //错误描述
    public $error_show = false;
    public $pay_type = OrderPayDict::online_pay;
    public $invoice_delivery_money = 0;
    public $balance_money = 0;
    public $member_balance_money = 0; //会员账户余额(计算过程中会逐次减少)

    public $recommend_member_card = []; // 推荐会员卡

    public $recommend_member_card_data = [];//推荐会员卡配置规格
    public $member_card_money = 0; // 会员卡开卡金额

    public $member_goods_card = [];
    public $goods_list = [];//商品项数据结构
    public $order_id;
    public $point_money = 0; // 积分抵现金额
    public $order_goods_list = [];//订单项列表

    public $order_object_data;

    public $modules = [];
    public $log = [];
    public $order_from_type = 'order';
    public $sale_channel = 'all,online,offline';
    public $is_check_buyer_ask_delivery_time = true;//特殊业务比如预售不做时间检测

    /**
     * 设置错误,优先级
     * @param $error
     * @param $error_msg
     * @param string $priority 报错优先级  0  创建时提示  1 计算时提示
     */
    public function setError($error, $error_msg, $priority = '0')
    {
        $this->error = $error;
        $this->error_msg = $error_msg;
        if ($priority == 1) {
            $this->error_show = true;
        }
    }

    /**
     * 校验错误
     * @return array|true
     */
    public function checkError()
    {
        if ($this->error > 0) {
            return $this->error(['error_code' => $this->error], $this->error_msg);
        }
        return true;
    }

    /**
     * 获取订单创建缓存
     * @param $key
     * @return mixed
     */
    public function getOrderCache($key)
    {
        $order_cache = Cache::get($key, []);
        if (empty($order_cache)) throw new OrderException('订单已过期');
        foreach ($order_cache as $k => $v) {
            $this->$k = $v;
        }
        return $order_cache;
    }

    /**
     * 设置订单缓存
     * @param $data
     * @param $key
     * @return mixed
     */
    public function setOrderCache($data, $key = '')
    {
        if (empty($key)) {
            $key = create_no();
        }
        unset($data['param']);
        Cache::tag('order_cache')->set($key, $data, 30000);
        return $key;
    }

    /**
     * 删除订单缓存
     * @param $key
     * @return true
     */
    public function deleteOrderCache($key = '')
    {
        Cache::delete($key ?: $this->order_key);
        return true;
    }

    /**
     * 定义传入参数
     * @param $param
     * @return $this
     */
    public function setParam($param)
    {
        $this->param = $param;
        $this->member_id = $this->param['member_id'];
        $this->site_id = $this->param['site_id'];
        $this->order_from = $this->param['order_from'] ?? '';//订单来源
        $this->order_from_name = $this->param['order_from_name'] ?? '';//订单来源名称
        $this->sale_channel = $this->param['sale_channel'] ?? 'all,online,offline';//销售渠道
        return $this;
    }

    /**
     * 重新计算订单总额
     * @return void
     */
    public function getOrderMoney()
    {
        $this->order_money = round($this->goods_money + $this->delivery_money - $this->promotion_money + $this->member_card_money - $this->point_money - $this->coupon_money + $this->invoice_money + $this->invoice_delivery_money,2);
        $this->order_money = max($this->order_money, 0);
        return $this->order_money;
    }

    /**
     * 增加订单自动关闭事件
     */
    public function addOrderCronClose()
    {
        //计算订单自动关闭时间
        $order_config = $this->config('order');
        $now_time = time();
        if ($order_config['auto_close'] > 0) {
            $execute_time = $now_time + $order_config['auto_close'] * 60; //自动关闭时间
            $cron_model = new Cron();
            $cron_model->addCron(1, 0, '订单自动关闭', 'CronOrderClose', $execute_time, $this->order_id);
            // 订单催付通知
            // 未付款订单将会在订单关闭前10分钟对买家进行催付提醒
            if ($this->pay_money > 0) {
                $cron_model->addCron(1, 0, '订单催付通知', 'CronOrderUrgePayment', $execute_time - 600, $this->order_id);
            }
        }
    }

    /**
     * 配置设置或查询
     * @param $key
     * @return array|mixed
     */
    public function config($key)
    {
        //查询购物配置
        $config = $this->config[$key] ?? [];
        if (empty($this->config[$key])) {
            switch ($key) {
                case 'order'://交易配置
                    $config_model = new Config();
                    $order_config = $config_model->getOrderEventTimeConfig($this->site_id)['data'] ?? [];
                    $config = $order_config['value'] ?? [];
                    break;
                case 'point'://积分交易配置
                    $config_model = new PointCashConfig();
                    $config = $config_model->getPointCashConfig($this->site_id)['data'];
                    break;
                case 'balance'://余额交易配置
                    $config_model = new Config();
                    $config = $config_model->getBalanceConfig($this->site_id)['data']['value'] ?? [];
                    break;
                case 'delivery_type':
                    $config = (new ExpressConfig())->getExpressTypeList($this->site_id) ?? [];
                    break;
                case 'delivery_type_sort':
                    $config = (new ExpressConfig())->getDeliverTypeSort($this->site_id)['data'] ?? [];
                    break;
                case 'express'://物流配置
                    $express_config_model = new ExpressConfig();
                    $config = $express_config_model->getExpressConfig($this->site_id)['data'] ?? [];
                    break;
                case 'store'://门店自提配置
                    $express_config_model = new ExpressConfig();
                    $config = $express_config_model->getStoreConfig($this->site_id)['data'] ?? [];
                    break;
                case 'local'://本地配送配置
                    $express_config_model = new ExpressConfig();
                    $config = $express_config_model->getLocalDeliveryConfig($this->site_id)['data'] ?? [];
                    break;
                case 'store_business':
                    $store_config_model = new \addon\store\model\Config();
                    $config = $store_config_model->getStoreBusinessConfig($this->site_id)['data']['value'] ?? [];
                    break;
            }
            $this->config[$key] = $config;
        }
        return $config;
    }

    /**
     * 获取订单添加的公共数据
     * @param array $modules 数据组件标识
     * @param string $op and 包含  invert  无交集
     * @return array
     */
    public function getOrderInsertData($modules = [], $op = 'and')
    {
        $data = [];
        //公共的订单数据
        $this->order_no = $this->createOrderNo();
        $pay_model = new Pay();
        $this->out_trade_no = $pay_model->createOutTradeNo($this->member_id);
        $common_data = [
            'order_no' => $this->order_no,
            'out_trade_no' => $this->out_trade_no,
            'site_id' => $this->site_id,
            'site_name' => $this->site_name,
            'member_id' => $this->member_id,
            'order_from' => $this->order_from,
            'order_from_name' => $this->order_from_name,
            'buyer_ip' => request()->ip(),
        ];
        $data = array_merge($data, $common_data);
        //传入数据
        $buyer_message = $this->param['buyer_message'];
        $input_data = [
            'buyer_message' => $buyer_message,
        ];
        $data = array_merge($data, $input_data);
        //订单数据
        $order_data = [
            'goods_money' => $this->goods_money,
            'delivery_money' => $this->delivery_money,
            'coupon_money' => $this->coupon_money ?? 0,
            'point_money' => $this->point_money,
            'adjust_money' => $this->adjust_money,
            'invoice_money' => $this->invoice_money,
            'invoice_delivery_money' => $this->invoice_delivery_money,
            'promotion_money' => $this->promotion_money,
            'order_money' => $this->order_money,
            'balance_money' => $this->balance_money,
            'pay_money' => $this->pay_money,
            'member_card_money' => $this->member_card_money,

            'order_name' => $this->order_name,
            'goods_num' => $this->goods_num,
        ];
        $data = array_merge($data, $order_data);
        if ($this->getInsertDataWhereResult($modules, 'invoice', $op)) {
            //发票信息
            if (isset($this->param['is_invoice']) && $this->param['is_invoice'] == 1) {
                $invoice_data = [
                    'taxpayer_number' => $this->invoice['taxpayer_number'] ?? '',
                    'invoice_rate' => $this->invoice['invoice_rate'] ?? 0,
                    'invoice_content' => $this->invoice['invoice_content'] ?? '',
                    'invoice_full_address' => $this->invoice['invoice_full_address'] ?? '',
                    'is_invoice' => $this->param['is_invoice'] ?? 0,
                    'invoice_type' => $this->invoice['invoice_type'] ?? 0,
                    'invoice_title' => $this->invoice['invoice_title'] ?? '',
                    'is_tax_invoice' => $this->invoice['is_tax_invoice'] ?? '',
                    'invoice_email' => $this->invoice['invoice_email'] ?? '',
                    'invoice_title_type' => $this->invoice['invoice_title_type'] ?? 0,

                ];
                $data = array_merge($data, $invoice_data);
            }
        }
        if ($this->getInsertDataWhereResult($modules, 'delivery', $op)) {
            //配送数据
            $express_type_list = $this->config('delivery_type');
            $delivery_type_name = $express_type_list[$this->delivery['delivery_type']] ?? '';
            $buyer_ask_delivery_time = $this->delivery['buyer_ask_delivery_time'] ?? [];
            $delivery_data = [
                'delivery_type' => $this->delivery['delivery_type'],
                'delivery_type_name' => $delivery_type_name,
                'delivery_store_id' => $this->delivery['delivery_store_id'] ?? 0,
                'delivery_store_name' => $this->delivery['delivery_store_name'] ?? '',
                'delivery_store_info' => $this->delivery['delivery_store_info'] ?? '',
                'buyer_ask_delivery_time' => $buyer_ask_delivery_time['remark'] ?? '',//定时达
                'delivery_start_time' => $buyer_ask_delivery_time['start_time'] ?? '',//配送开始时间
                'delivery_end_time' => $buyer_ask_delivery_time['end_time'] ?? '',//配送结束时间
            ];
            $data = array_merge($data, $delivery_data);
        }
        if ($this->getInsertDataWhereResult($modules, 'take', $op)) {
            $this->orderType();
            //允许门店配送和虚拟商品传入手机号
            $order_type = $this->order_type['order_type_id'] ?? '';
            if(in_array($order_type, [OrderDict::virtual, OrderDict::store])){
                $this->delivery['member_address']['name'] = $this->param['member_address']['name'] ?? '';
                $this->delivery['member_address']['mobile'] = $this->param['member_address']['mobile'] ?? '';
            }
            //收货人数据
            $take_data = [
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
            ];
            $data = array_merge($data, $take_data);
        }

        //活动数据
        if ($this->getInsertDataWhereResult($modules, 'promotion', $op)) {
            $promotion_data = [
                'promotion_type' => $this->promotion_type,
                'promotion_type_name' => $this->promotion_type_name,
                'promotion_status_name' => '',
            ];
            $data = array_merge($data, $promotion_data);
        }
        //优惠数据
        if ($this->getInsertDataWhereResult($modules, 'discount', $op)) {
            $discount_data = [
                'coupon_id' => $this->coupon_id,
            ];
            $data = array_merge($data, $discount_data);
        }
        return $data;
    }

    /**
     * 生成订单编号
     * @return string
     */
    public function createOrderNo()
    {
        $time_str = date('YmdHi');
        $max_no = Cache::get($this->site_id . '_' . $this->member_id . '_' . $time_str);
        if (empty($max_no)) {
            $max_no = 1;
        } else {
            $max_no = $max_no + 1;
        }
        $order_no = $time_str . $this->member_id . sprintf('%03d', $max_no);
        Cache::set($this->site_id . '_' . $this->member_id . '_' . $time_str, $max_no);
        return $order_no;
    }

    /**
     * @param $modules
     * @param $key
     * @param $op
     * @return bool
     */
    public function getInsertDataWhereResult($modules, $key, $op)
    {
        if ($op == 'and') {
            if (!$modules || in_array($key, $modules)) {
                return true;
            }
        } else if ($op == 'invert') {
            if (!($modules && in_array($key, $modules))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 订单项数据整理
     * @param $data
     * @return array
     */
    public function getOrderGoodsInsertData($data)
    {
        if ($data['num'] < 0) throw new OrderException('商品购买数量不能小于0');
        return [
            'order_id' => $this->order_id,
            'site_id' => $this->site_id,
            'store_id' => $this->store_id,
            'order_no' => $this->order_no,
            'member_id' => $this->member_id,
            'sku_id' => $data['sku_id'],
            'sku_name' => $data['sku_name'],
            'sku_image' => $data['sku_image'],
            'sku_no' => $data['sku_no'],
            'is_virtual' => $data['is_virtual'],
            'goods_class' => $data['goods_class'],
            'goods_class_name' => $data['goods_class_name'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'],
            'num' => $data['num'],
            'goods_money' => $data['goods_money'],
            'cost_money' => $data['cost_price'] * $data['num'],
            'goods_id' => $data['goods_id'],
            'delivery_status' => OrderGoodsDict::wait_delivery,
            'delivery_status_name' => OrderGoodsDict::getDeliveryStatus(OrderGoodsDict::wait_delivery),
            'real_goods_money' => $data['real_goods_money'],
            'coupon_money' => $data['coupon_money'] ?? 0,
            'promotion_money' => $data['promotion_money'],

            'goods_name' => $data['goods_name'],
            'sku_spec_format' => $data['sku_spec_format'],

            'supplier_id' => $data['supplier_id'] ?? 0,
            'is_fenxiao' => $data['is_fenxiao'] ?? 1,


            'use_point' => $data['use_point'] ?? 0,
            'point_money' => $data['point_money'] ?? 0.00,

            'card_item_id' => $this->member_goods_card[$data['sku_id']] ?? 0,
            'card_promotion_money' => $data['card_promotion_money'] ?? 0.00,
        ];
    }

    /**
     * 获取订单项列表(只适用于订单  订单项已创建之后的时机)
     * @return array|mixed
     */
    public function getOrderGoodsList()
    {
        if (!$this->order_goods_list) {
            $this->order_goods_list = model('order_goods')->getList([['order_id', '=', $this->order_id]]);
        }
        return $this->order_goods_list;
    }


    /**
     * 获取订单对象数据
     * @return array
     */
    public function getOrderObjectData()
    {
        if (!$this->order_object_data) {
            $this->order_object_data = get_object_vars($this);
        }
        return $this->order_object_data;
    }


    /**
     * 订单类型判断
     * @return true
     */
    public function orderType()
    {
        if ($this->is_virtual == 1) {
            $order = new VirtualOrder();
            $this->order_type = [
                'order_type_id' => 4,
                'order_type_name' => '虚拟订单',
                'order_status' => $order->order_status[0]
            ];
        } else {
            if ($this->delivery['delivery_type'] == 'express') {
                $order = new Order();
                $this->order_type = [
                    'order_type_id' => 1,
                    'order_type_name' => '普通订单',
                    'order_status' => $order->order_status[0]
                ];
            } elseif ($this->delivery['delivery_type'] == 'store') {
                $order = new StoreOrder();
                $this->order_type = [
                    'order_type_id' => 2,
                    'order_type_name' => '自提订单',
                    'order_status' => $order->order_status[0]
                ];
            } elseif ($this->delivery['delivery_type'] == 'local') {
                $order = new LocalOrder();
                $this->order_type = [
                    'order_type_id' => 3,
                    'order_type_name' => '外卖订单',
                    'order_status' => $order->order_status[0]
                ];
            }
        }
        return true;
    }

    /**
     * 订单创建后事件
     * @return true
     */
    public function orderCreateAfter()
    {

        Queue::push('app\job\order\OrderCreateAfter', ['create_data' => get_object_vars($this), 'order_object' => $this]);
        return true;
    }

    /**
     * 注入对象
     * @param $data
     * @return $this
     */
    public function invokeClass($data)
    {

        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * 初始化订单开票基础信息
     */
    public function initInvoice($param)
    {
        $this->order_id = $param['order_id'] ?? 0;
        if(empty($this->order_id)){
            return $this->error('', '缺少订单id');
        }
        $order_info = model('order')->getInfo([['order_id', '=', $this->order_id]], 'order_id,order_status,goods_money,promotion_money,coupon_money,point_money');
        if (!$order_info) {
           return $this->error('', '订单不存在');
        }
        if($order_info['invoice_status'] == 1){
            return $this->error('', '订单已开票,请勿重复操作');
        }
        $this->param = $param;
        $this->invokeClass($order_info);
        return $this->success();
    }

    /**
     * 更新发票数据
     */
    public function saveInvoice(): array
    {
        $invoice_data = [
            'taxpayer_number' => $this->invoice['taxpayer_number'] ?? '',
            'invoice_rate' => $this->invoice['invoice_rate'] ?? 0,
            'invoice_content' => $this->invoice['invoice_content'] ?? '',
            'invoice_full_address' => $this->invoice['invoice_full_address'] ?? '',
            'is_invoice' => $this->param['is_invoice'] ?? 0,
            'invoice_type' => $this->invoice['invoice_type'] ?? 0,
            'invoice_title' => $this->invoice['invoice_title'] ?? '',
            'is_tax_invoice' => $this->invoice['is_tax_invoice'] ?? '',
            'invoice_email' => $this->invoice['invoice_email'] ?? '',
            'invoice_title_type' => $this->invoice['invoice_title_type'] ?? 0,
        ];
        $result = model("order")->update($invoice_data,[['order_id','=',$this->order_id]]);
        if($result){
            return $this->success($result);
        }
        return $this->error([],'发票信息更新失败,请重试');
    }

    public function setStoreId($store_id){
        $this->store_id = $store_id;
        return $this;
    }
}
