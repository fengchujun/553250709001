<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\model;

use app\model\BaseModel;
use app\model\order\OrderCommon;
use app\model\order\OrderCreateTool;
use app\model\system\Pay;
use Exception;

/**
 * 订单创建（砍价）
 */
class BargainOrderCreate extends BaseModel
{

    use OrderCreateTool;

    public function __construct()
    {
        $this->promotion_type = 'bargain';
        $this->promotion_type_name = '砍价';
    }

    public $bargain_info = [];

    /**
     * 订单创建
     */
    public function create()
    {
        $this->confirm();
        //校验错误
        $error_result = $this->checkError();
        if ($error_result !== true) {
            return $error_result;
        }
        $bargain_model = new Bargain();
        $pay = new Pay();
        $is_fenxiao = $this->bargain_info[ 'is_fenxiao' ];
        model('order')->startTrans();

        //循环生成多个订单
        try {
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
            $order_insert_data[ 'is_fenxiao' ] = $is_fenxiao;

            $this->order_id = model('order')->add($order_insert_data);
            //订单项目表
            $order_goods_insert_data = [];
            foreach ($this->goods_list as $order_goods_v) {
                $order_goods_v[ 'is_fenxiao' ] = $is_fenxiao;
                $order_goods_insert_data[] = $this->getOrderGoodsInsertData($order_goods_v);
            }
            model('order_goods')->addList($order_goods_insert_data);

            //扣除余额(统一扣除)
            $this->useBalance();

            // 砍价绑定订单id
            $bargain_data = [ 'order_id' => $this->order_id ];
            $bargain_data[ 'status' ] = 2;
            //未砍到低价都为砍价失败
            if ($this->bargain_info[ 'curr_price' ] == $this->bargain_info[ 'floor_price' ]) {
                $bargain_data[ 'status' ] = 1;
            }
            model('promotion_bargain_launch')->update($bargain_data, [ [ 'launch_id', '=', $this->bargain_info[ 'launch_id' ] ] ]);

            //批量库存处理(卡密商品支付后在扣出库存)
            $this->batchDecOrderGoodsStock();

            //扣除商品库存
            foreach ($this->goods_list as $v) {
                //活动库存
                $bargain_stock_result = $bargain_model->decStock([ 'bargain_id' => $this->bargain_info[ 'bargain_id' ], 'sku_id' => $v[ 'sku_id' ], 'num' => $v[ 'num' ] ]);
                if ($bargain_stock_result[ 'code' ] < 0) {
                    model('order')->rollback();
                    return $bargain_stock_result;
                }
            }
            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            //生成整体支付单据
            $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '', $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);

        } catch (Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单计算
     */
    public function calculate()
    {
        $this->initMemberAddress(); //初始化地址
        $this->initMemberAccount(); //初始化会员账户
        //商品列表信息
        $this->getOrderGoodsCalculate();
        //优惠以及附属计算
        $this->shopOrderCalculate();
        //获取发票相关
        $this->getInovice();
        //定义缓存,并返回key值
        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }

    /**
     * 获取商品的计算信息
     * @return true
     */
    public function getOrderGoodsCalculate()
    {
        //查询砍价信息
        $bargain_model = new Bargain();
        $launch_id = $this->param[ 'id' ];
        $this->bargain_info = $bargain_model->getBargainLaunchDetail([ [ 'launch_id', '=', $launch_id ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];
        if (empty($this->bargain_info)) throw new Exception('找不到您的砍价记录');
        //判断砍价是否成功
        if ($this->bargain_info[ 'buy_type' ] == 1 && $this->bargain_info[ 'status' ] != 1) {
            $this->error = 1;
            $this->error_msg = '该商品您尚未砍价成功！';
        }
        //判断砍价是否已经下单了
        $bargain_order_id = $this->bargain_info[ 'order_id' ] ?? 0;
        if (!$bargain_order_id) {
            $bargain_order_info = model('order')->getInfo([ [ 'order_id', '=', $bargain_order_id ] ], 'order_status');
            if ($bargain_order_info && $bargain_order_info[ 'order_status' ] != OrderCommon::ORDER_CLOSE) {
                $this->error = 1;
                $this->error_msg = '本次砍价您已下单过了！';
            }
        }

        $this->getBargainGoodsInfo();
        return true;
    }

    /**
     * 获取砍价商品列表信息
     * @return true
     */
    public function getBargainGoodsInfo()
    {
        //组装商品列表
        $field = 'sku_id,sku_name, sku_no,
            price, discount_price, cost_price, stock, weight, volume, sku_image, 
            ngs.site_id, goods_state, is_virtual, support_trade_type,ngs.supplier_id,ngs.form_id,
            is_free_shipping, shipping_template, goods_class, goods_class_name,goods_id, ns.site_name,ngs.sku_spec_format,ngs.goods_name';
        $join = [
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ]
        ];
        $info = model('goods_sku')->getInfo([ [ 'ngs.sku_id', '=', $this->bargain_info[ 'sku_id' ] ], [ 'ngs.site_id', '=', $this->site_id ] ], $field, 'ngs', $join);
        if (!empty($info)) {
            $num = $this->param[ 'num' ];
            //判断是否是虚拟订单
            if ($info[ 'is_virtual' ]) {
                $this->is_virtual = 1;
            } else {
                $this->is_virtual = 0;
            }
            $info[ 'num' ] = $num;
            $price = $this->bargain_info[ 'curr_price' ];
            $goods_money = $price * $num;
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
        }
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

    /**
     * 待付款订单
     */
    public function orderPayment()
    {
        //计算
        $this->calculate();
        //配送信息数据
        $this->getDeliveryData();
        //订单初始项
        event('OrderPayment', [ 'order_object' => $this ]);

        return get_object_vars($this);
    }

    /**
     * 抵扣优惠项计算
     * @return array
     * @throws Exception
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