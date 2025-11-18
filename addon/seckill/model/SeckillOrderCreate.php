<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\model;

use addon\store\model\StoreGoodsSku;
use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\Order;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use app\model\order\OrderRefund;
use app\model\store\Store;
use extend\exception\OrderException;
use think\facade\Cache;
use app\model\express\Express;
use app\model\system\Pay;
use app\model\express\Config as ExpressConfig;
use app\model\order\Config;
use app\model\express\Local;

/**
 * 订单创建(秒杀)
 *
 * @author Administrator
 *
 */
class SeckillOrderCreate extends BaseModel
{

    use OrderCreateTool;

    public $seckill_id = 0;
    public $seckill_info = [];

    public function __construct()
    {
        $this->promotion_type = 'seckill';
        $this->promotion_type_name = '秒杀';
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
        $order_insert_data[ 'promotion_id' ] = $this->seckill_info['id'];
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

                model("promotion_seckill_goods")->setDec([ [ 'sku_id', '=', $order_goods_v[ 'sku_id' ] ], [ 'seckill_id', '=', $this->seckill_info[ "id" ] ] ], "stock", $order_goods_v[ 'num' ]);
                model("promotion_seckill")->setDec([ [ 'id', '=', $this->seckill_info[ "id" ] ] ], "goods_stock", $order_goods_v[ 'num' ]);
                // 增加销量 秒杀要用sale_num计算秒杀量，和普通订单付款后才算销量不一样
                model("promotion_seckill")->setInc([ [ 'id', '=', $this->seckill_info[ "id" ] ] ], "sale_num", $order_goods_v[ 'num' ]);
            }
            model('order_goods')->addList($order_goods_insert_data);

            //扣除余额(统一扣除)
            $this->useBalance();
            //批量库存处理(卡密商品支付后在扣出库存)
            $this->batchDecOrderGoodsStock();
            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            //支付单据
            $pay = new Pay();
            $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '', $this->order_id, $this->member_id);
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
     * 订单计算
     */
    public function calculate()
    {
        //初始化会员地址
        $this->initMemberAddress();
        $this->initMemberAccount();//初始化会员账户
        //商品列表信息
        $this->getOrderGoodsCalculate();
        //查询秒杀信息
        $seckill_model = new Seckill();
        $seckill_info = $seckill_model->getSeckillInfo($this->seckill_id)[ 'data' ] ?? [];
        if (empty($seckill_info)) throw new OrderException("找不到可用的秒杀活动");
        $this->seckill_info = $seckill_info;
        //判断秒杀时间段是否符合
        $today_time = strtotime(date("Y-m-d"), time());
        $time = time() - $today_time;//当日时间戳
        if ($time < $this->seckill_info[ "seckill_start_time" ] || $time > $this->seckill_info[ "seckill_end_time" ]) {
            $this->error = 1;
            $this->error_msg = "当前商品秒杀活动未开启或已过期！";
        }

        //秒杀库存
        if ($this->goods_list[ 0 ]) {
            $seckill_goods = $seckill_model->getSeckillGoodsInfo([ [ 'psg.seckill_id', '=', $this->seckill_id ], [ 'psg.sku_id', '=', $this->goods_list[ 0 ][ 'sku_id' ] ] ], 'psg.stock')[ 'data' ] ?? [];
            $seckill_goods_stock = $seckill_goods[ 'stock' ];
            if ($this->goods_list[ 0 ][ 'num' ] > $seckill_goods_stock) {
                $this->error = 1;
                $this->error_msg = "该商品库存不足";
            }
        }

        // 秒杀商品限购 按每日某时段限购
        if ($this->goods_list[ 0 ][ 'limit_num' ] > 0) {
            $purchased_num = $this->getGoodsPurchasedNum($this->goods_list[ 0 ][ 'sku_id' ], $this->member_id, $this->seckill_info[ 'id' ]);
            if (($purchased_num + $this->goods_list[ 0 ][ 'num' ]) > $this->goods_list[ 0 ][ 'limit_num' ]) {
                $this->error = 1;
                $this->error_msg = "该商品每人限购{$this->goods_list[ 0 ]['limit_num']}件，您已购买{$purchased_num}件";
            }
        }
        $this->shopOrderCalculate();
        //获取发票相关
        $this->getInovice();
        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }

    /**
     * 待付款订单
     * @param unknown $data
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
     * @param unknown $data
     */
    public function getOrderGoodsCalculate()
    {
        $this->getSeckillGoodsInfo();
        return true;
    }

    /**
     * 获取秒杀商品列表信息
     * @param $id
     * @param $num
     * @param $data
     * @return array
     */
    public function getSeckillGoodsInfo()
    {
        $id = $this->param[ 'id' ];
        $sku_id = $this->param[ 'sku_id' ];
        $num = $this->param[ 'num' ];
        //组装商品列表
        $field = 'npsg.sku_id,npsg.seckill_id,npsg.seckill_price,npsg.max_buy as limit_num,ngs.sku_name, ngs.sku_no,
            ngs.price, ngs.discount_price, ngs.cost_price, ngs.stock, ngs.weight, ngs.volume, ngs.sku_image, 
            ngs.site_id, ns.site_name, ngs.goods_state, ngs.is_virtual,ngs.supplier_id,ngs.form_id,
            ngs.is_free_shipping, ngs.shipping_template, ngs.goods_class, ngs.goods_class_name,ngs.goods_id,ngs.sku_spec_format,ngs.goods_name,ngs.support_trade_type';
        $alias = 'npsg';
        $join = [
            [
                'goods_sku ngs',
                'npsg.sku_id = ngs.sku_id',
                'inner'
            ],
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ]
        ];

        $condition = [
            [ 'npsg.sku_id', '=', $sku_id ],
            [ 'npsg.seckill_id', '=', $id ],
            [ 'npsg.site_id', '=', $this->site_id ]
        ];
        $info = model("promotion_seckill_goods")->getInfo($condition, $field, $alias, $join);
        if (empty($info)) throw new OrderException('无效的商品！');
        //判断是否是虚拟订单
        $this->seckill_id = $info[ 'seckill_id' ];
        if ($info[ 'is_virtual' ]) {
            $this->is_virtual = 1;
        } else {
            $this->is_virtual = 0;
        }
        $info[ "num" ] = $num;
        $price = $info[ "seckill_price" ];//订单项商品单价
        $goods_money = $price * $info[ 'num' ];
        $info[ "price" ] = $price;
        $info[ 'goods_money' ] = $goods_money;//订单项商品总价
        $info[ 'real_goods_money' ] = $goods_money;//真实商品金额
        $info[ 'coupon_money' ] = 0;//优惠券金额
        $info[ 'promotion_money' ] = 0;//优惠金额

        $this->site_name = $info[ 'site_name' ];
        $this->goods_money = $goods_money;
        $this->goods_list_str = $info[ 'sku_id' ] . ':' . $info[ 'num' ];
        $this->order_name = string_split("", ",", $info[ 'sku_name' ]);
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


    /**
     * 获取会员该秒杀时段已购该商品数
     * @param $goods_id
     * @param $member_id
     * @return float
     */
    public function getGoodsPurchasedNum($sku_id, $member_id, $seckill_id)
    {
        $join = [
            [ 'order o', 'o.order_id = og.order_id', 'left' ]
        ];
        $num = model('order_goods')->getSum([
            [ 'og.member_id', '=', $member_id ],
            [ 'og.sku_id', '=', $sku_id ],
            [ 'o.order_status', '<>', Order::ORDER_CLOSE ],
            [ 'o.promotion_type', '=', 'seckill' ],
            [ 'o.promotion_id', '=', $seckill_id ],
            [ 'og.refund_status', '<>', OrderRefundDict::REFUND_COMPLETE ],
            [ 'o.create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), time() ] ]
        ], 'og.num', 'og', $join);
        return $num;
    }


}