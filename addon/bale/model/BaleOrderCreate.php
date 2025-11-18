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

namespace addon\bale\model;

use app\model\BaseModel;
use app\model\order\OrderCreateTool;
use app\model\system\Pay;
use Exception;
use extend\exception\OrderException;

/**
 * 订单创建
 * Class BaleOrderCreate
 * @package addon\bale\model
 */
class BaleOrderCreate extends BaseModel
{

    use OrderCreateTool;

    public $bale_info = [];
    //打包一口价总价
    public $bale_money = 0;

    public function __construct()
    {
        $this->promotion_type = 'bale';
        $this->promotion_type_name = '打包一口价';
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
            //订单项目表
            $order_goods_insert_data = [];
            foreach ($this->goods_list as $order_goods_v) {
                $order_goods_insert_data[] = $this->getOrderGoodsInsertData($order_goods_v);
            }
            model('order_goods')->addList($order_goods_insert_data);
            //扣除余额(统一扣除)
            $this->useBalance();
            //批量库存处理(卡密商品支付后在扣出库存)//todo  可以再商品中设置扣除库存步骤
            $this->batchDecOrderGoodsStock();
            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            //支付单据
            $pay = new Pay();
            $pay->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '', $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);
        } catch (Exception $e) {
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
        $this->initMemberAddress();
        $this->initMemberAccount();//初始化会员账户

        //打包一口价id  查询订单商品数据
        $bale_model = new Bale();
        $this->bale_info = $bale_model->getBaleInfo([ [ 'bale_id', '=', $this->param[ 'bale_id' ] ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];

        //商品列表信息
        $this->getOrderGoodsCalculate();
        $this->shopOrderCalculate();

        //获取发票相关
        $this->getInovice();
        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }

    /**
     * 获取商品的计算信息
     */
    public function getOrderGoodsCalculate()
    {
        //传输打包一口价id组合','隔开要进行拆单
        $this->getBaleGoodsList();
        return true;
    }

    /**
     * 获取打包一口价商品列表信息
     * @return array
     */
    public function getBaleGoodsList()
    {
        //商品数据
        $sku_list_json = json_decode($this->param[ 'sku_list_json' ], true);
        $sku_ids = explode(',', substr($this->bale_info[ 'sku_ids' ], 1, -1));
        //判断商品是否在该活动中
        $goods_num = 0;
        $sku_list = [];
        foreach ($sku_list_json as $v) {
            if (in_array($v[ 'sku_id' ], $sku_ids)) {
                $goods_num += $v[ 'num' ];
                $sku_list[ $v[ 'sku_id' ] ] = $v[ 'num' ];
            } else {
                throw new OrderException('商品信息有误');
            }
        }
        //判断商品数量是否正确
        if ($goods_num % $this->bale_info[ 'num' ] != 0) throw new OrderException('商品数量有误');
        $this->goods_num = $goods_num;
        $this->bale_money = $this->bale_info[ 'price' ] * ( $this->goods_num / $this->bale_info[ 'num' ] );
        //组装商品列表
        $field = 'ngs.sku_id, ngs.sku_name, ngs.sku_no,ngs.price, ngs.discount_price, ngs.cost_price, ngs.stock, ngs.weight, ngs.volume, 
            ngs.sku_image, ngs.site_id, ngs.goods_state, ngs.is_virtual, ngs.is_free_shipping, ngs.shipping_template, ngs.goods_class, ngs.form_id,
            ngs.goods_class_name, ngs.goods_id, ngs.sku_spec_format,ngs.goods_name,ngs.support_trade_type,ns.site_name,ngs.supplier_id';
        $alias = 'ngs';
        $join = [
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ]
        ];
        $goods_list = model('goods_sku')->getList([ [ 'ngs.sku_id', 'in', array_column($sku_list_json, 'sku_id') ] ], $field, '', $alias, $join);
        if (!$goods_list) throw new OrderException('商品不存在！');

        foreach ($goods_list as $v) {
            $this->is_virtual = $v[ 'is_virtual' ];
            $v[ 'num' ] = $sku_list[ $v[ 'sku_id' ] ];
            $price = $v[ 'discount_price' ];
            $v[ 'price' ] = $price;
            $v[ 'goods_money' ] = $price * $v[ 'num' ];
            $v[ 'real_goods_money' ] = $v[ 'goods_money' ];
            $v[ 'coupon_money' ] = 0;//优惠券金额
            $v[ 'promotion_money' ] = 0;//优惠金额

            $this->site_name = $v[ 'site_name' ];
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
//            $this->goods_num += $v['num'];
            $this->goods_money += $v[ 'goods_money' ];
            //以;隔开的商品项
            $goods_list_str = $this->goods_list_str ?? '';
            if ($goods_list_str) {
                $this->goods_list_str = $goods_list_str . ';' . $v[ 'sku_id' ] . ':' . $v[ 'num' ];
            } else {
                $this->goods_list_str = $v[ 'sku_id' ] . ':' . $v[ 'num' ];
            }
        }

        //循环计算订单项商品价格(受打包一口价的影响)
        $rate = $this->bale_money / $this->goods_money;//计算打包一口价与原商品价格计算比率
        $rate = substr(sprintf('%.5f', $rate), 0, -1);
        $total_temp_money = $this->bale_money;
        $count = count($this->goods_list);
        foreach ($this->goods_list as $k => &$v) {
            if ($k == ( $count - 1 )) {
                $temp_money = $total_temp_money;
                $temp_price = round($temp_money / $v[ 'num' ], 3);
                $temp_price = substr(sprintf('%.3f', $temp_price), 0, -1);
                $temp_money = substr(sprintf('%.3f', $temp_money), 0, -1);
            } else {
                $temp_price = round($v[ 'discount_price' ] * $rate, 3);
                $temp_money = round($v[ 'discount_price' ] * $v[ 'num' ] * $rate, 3);
                $temp_price = substr(sprintf('%.3f', $temp_price), 0, -1);
                $temp_money = substr(sprintf('%.3f', $temp_money), 0, -1);
                $total_temp_money -= $temp_money;
            }
            $v[ 'price' ] = $temp_price;
            $v[ 'goods_money' ] = $temp_money;
            $v[ 'real_goods_money' ] = $temp_money;
        }
        $this->goods_money = $this->bale_money;//直接使用打包一口价价格
        return true;
    }

    /**
     * 获取店铺订单计算
     */
    public function shopOrderCalculate()
    {
        $this->is_free_delivery = $this->bale_info[ 'shipping_fee_type' ] == 1;
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
        //查询配送信息
        $this->getDeliveryData();

        return get_object_vars($this);
    }

}