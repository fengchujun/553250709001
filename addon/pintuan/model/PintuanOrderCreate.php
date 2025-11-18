<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\model;

use app\model\BaseModel;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use extend\exception\OrderException;
use app\model\system\Pay;

/**
 * 订单创建(拼团)
 */
class PintuanOrderCreate extends BaseModel
{

    use OrderCreateTool;
    public $group_id = 0;
    public $pintuan_info = [];
    public $pintuan_id = 0;
    public $pintuan_group_info = [];
    public $extend = [];

    public function __construct()
    {
        $this->promotion_type = 'pintuan';
        $this->promotion_type_name = '拼团';
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
        $order_insert_data[ 'promotion_status_name' ] = '待参团';
        $order_insert_data[ 'extend' ] = empty($this->param[ 'extend' ]) ? '' : json_encode($this->param[ 'extend' ]);
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
            //同步生成拼团
            $pintuan_order_model = new PintuanOrder();
            $result = $pintuan_order_model->addPintuanOrder($this->getOrderObjectData(), $this->group_id, $this->pintuan_id);

            if ($result[ 'code' ] != 0) {
                model('order')->rollback();
                return $result;
            }
            //扣除余额(统一扣除)
            $this->useBalance();
            //批量库存处理(卡密商品支付后在扣出库存)
            $this->batchDecOrderGoodsStock();

            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            //支付单据
            $pay = new Pay();
            $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '/pages_promotion/pintuan/share?id=' . $result[ 'data' ], $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);
        } catch (\Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }

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

    /**
     * 计算
     */
    public function calculate()
    {
        //要提前赋值，商品计算要用到
        $this->group_id = $this->param[ 'group_id' ];
        //初始化会员地址
        $this->initMemberAddress();
        //初始化会员账户
        $this->initMemberAccount();
        //商品列表信息
        $this->getOrderGoodsCalculate();
        //查询拼团信息
        $pintuan_model = new Pintuan();
        $this->pintuan_info = $pintuan_model->getPintuanInfo([ [ 'pintuan_id', '=', $this->pintuan_id ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];
        if (empty($this->pintuan_info)) throw new OrderException('找不到有效的拼团活动！');
        //判断购买数是否超过限购
        if ($this->pintuan_info[ 'buy_num' ] < $this->param[ 'num' ] && $this->pintuan_info[ 'buy_num' ] > 0) {
            $this->error = 1;
            $this->error_msg = '该商品限制购买不能大于' . $this->pintuan_info[ 'buy_num' ] . '件！';
        }

        //判断是否可参团
        $pintuan_order = new PintuanOrder();
        $result = $pintuan_order->isCanJoinGroup($this->group_id, $this->member_id);
        if ($result[ 'code' ] < 0) throw new OrderException($result[ 'message' ]);

        //查询拼团组信息
        $pintuan_model = new PintuanGroup();
        $this->pintuan_group_info = $pintuan_model->getPintuanGroupInfo([ [ 'group_id', '=', $this->group_id ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];
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
        //计算
        $this->calculate();
        //查询配送信息
        $this->getDeliveryData();
        //订单初始项
        event('OrderPayment', [ 'order_object' => $this ]);
        return get_object_vars($this);
    }

    /**
     * 获取商品的计算信息
     */
    public function getOrderGoodsCalculate()
    {
        $this->getPintuanGoodsInfo();
        return true;
    }

    /**
     * 获取拼团商品列表信息
     */
    public function getPintuanGoodsInfo()
    {
        $id = $this->param[ 'id' ];
        $num = $this->param[ 'num' ];
        //组装商品列表
        $field = 'nppg.id,nppg.sku_id,nppg.pintuan_id,nppg.pintuan_price,nppg.promotion_price,ngs.sku_name, ngs.sku_no,
            ngs.price, ngs.discount_price, ngs.cost_price, ngs.stock, ngs.weight, ngs.volume, ngs.sku_image, 
            ngs.site_id, ns.site_name, ngs.goods_state, ngs.is_virtual, ngs.support_trade_type,ngs.supplier_id,ngs.form_id,
            ngs.is_free_shipping, ngs.shipping_template, ngs.goods_class, ngs.goods_class_name,ngs.goods_id,ngs.sku_spec_format,ngs.goods_name,g.goods_image,
            nppg.pintuan_price_2,nppg.pintuan_price_3';
        $alias = 'nppg';
        $join = [
            [
                'goods_sku ngs',
                'nppg.sku_id = ngs.sku_id',
                'inner'
            ],
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ],
            [ 'goods g', 'ngs.goods_id = g.goods_id', 'inner' ],
        ];
        $info = model('promotion_pintuan_goods')->getInfo([ [ 'nppg.id', '=', $id ], [ 'nppg.site_id', '=', $this->site_id ] ], $field, $alias, $join);

        if (!$info) throw new OrderException('商品不存在！');

        $pintuan_model = new Pintuan();
        $this->pintuan_id = $info[ 'pintuan_id' ];

        $pintuan_info = $pintuan_model->getPintuanInfo([ [ 'pintuan_id', '=', $this->pintuan_id ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];

        //判断是否是虚拟订单
        if ($info[ 'is_virtual' ]) {
            $this->is_virtual = 1;
        } else {
            $this->is_virtual = 0;
        }
        $info[ 'num' ] = $num;

        $pintuan_type = $pintuan_info[ 'pintuan_type' ];
        switch ($pintuan_type) {
            case 'ordinary'://默认拼团方式
                //判断是否是开团 团长
                if ($this->group_id > 0) {
                    $price = $info[ 'pintuan_price' ];//参团价
                } else {
                    $price = $info[ 'promotion_price' ];//开团价
                }
                break;
            case 'ladder'://阶梯拼团
                $pintuan_num = $pintuan_info[ 'pintuan_num' ];
                $pintuan_num_2 = $pintuan_info[ 'pintuan_num_2' ];
                $pintuan_num_3 = $pintuan_info[ 'pintuan_num_3' ];
                $this->extend = $this->param[ 'extend' ] ?? [];
                $price = $info[ 'pintuan_price' ];//一级参团价
                if (empty($this->extend)) {

                    $group_model = new PintuanGroup();
                    $pintuan_group_condition = array (
                        [ 'site_id', '=', $this->site_id ],
                        [ 'group_id', '=', $this->group_id ]
                    );
                    $group_info = $group_model->getPintuanGroupInfo($pintuan_group_condition)[ 'data' ] ?? [];
                    if (empty($group_info)) {
                        break;
                    }
                    $pintuan_ladder = $group_info[ 'pintuan_num' ];
                } else {
                    $pintuan_ladder = $this->extend[ 'pintuan_num' ];
                }

                switch ($pintuan_ladder) {
                    case $pintuan_num:
                        $price = $info[ 'pintuan_price' ];//一级参团价
                        break;
                    case $pintuan_num_2:
                        $price = $info[ 'pintuan_price_2' ];//二级参团价
                        break;
                    case $pintuan_num_3:
                        $price = $info[ 'pintuan_price_3' ];//三级参团价
                        break;
                }
                break;
        }
        $goods_money = $price * $info[ 'num' ];
        $info[ 'price' ] = $price;
        $info[ 'goods_money' ] = $goods_money;

        $info[ 'real_goods_money' ] = $goods_money;//真实商品金额
        $info[ 'coupon_money' ] = 0;//优惠券金额
        $info[ 'promotion_money' ] = 0;//优惠金额

        $this->site_name = $info[ 'site_name' ];
        $this->goods_money = $goods_money;
        $this->goods_list_str = $info[ 'sku_id' ] . ':' . $info[ 'num' ];
        $this->order_name = string_split('', ',', $info[ 'sku_name' ]);
        $this->goods_num = $info[ 'num' ];
        $this->goods_list[] = $info;
        return true;
    }

    /**
     * 获取店铺订单计算
     */
    public function shopOrderCalculate()
    {
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

}