<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\model;

use addon\store\model\StoreGoodsSku;
use app\model\BaseModel;
use app\model\order\OrderCreate;
use app\model\order\OrderCreateTool;
use app\model\store\Store;
use extend\exception\OrderException;
use think\facade\Cache;
use app\model\express\Express;
use app\model\system\Pay;
use app\model\express\Config as ExpressConfig;
use app\model\order\Config;
use app\model\express\Local;

/**
 * 订单创建(团购)
 */
class GroupbuyOrderCreate extends BaseModel
{

    use OrderCreateTool;
    public $groupbuy_info = [];

    public function __construct()
    {
        $this->promotion_type = 'groupbuy';
        $this->promotion_type_name = '团购';
    }

    /**
     * 订单创建
     * @param unknown $data
     */
    public function create()
    {
        //计算
        $this->confirm();

        if ($this->error > 0) {
            return $this->error([ 'error_code' => $this->error ], $this->error_msg);
        }
        model('order')->startTrans();
        //循环生成多个订单
        try {

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
            $this->order_id = model('order')->add($order_insert_data);
            $order_goods_insert_data = [];
            //订单项目表
            foreach ($this->goods_list as $order_goods_v) {
                $order_goods_insert_data[] = $this->getOrderGoodsInsertData($order_goods_v);
            }
            model('order_goods')->addList($order_goods_insert_data);
            //使用余额
            $this->useBalance();
            //批量扣除库存
            $this->batchDecOrderGoodsStock();
            model('order')->commit();
            //订单创建后事件
            $this->orderCreateAfter();
            //生成整体支付单据
            $pay_model = new Pay();
            $pay_model->addPay($this->site_id, $this->out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'OrderPayNotify', '', $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);

        } catch (\Exception $e) {
            model('order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单计算
     * @param unknown $data
     */
    public function calculate()
    {
        $this->initMemberAddress();//初始化买家地址
        $this->initMemberAccount();//初始化会员账户
        //查询团购信息
        $groupbuy_model = new Groupbuy();
        $groupbuy_id = $this->param[ 'groupbuy_id' ];
        $this->groupbuy_info = $groupbuy_model->getGroupbuyInfo(
                [
                    [ 'pg.groupbuy_id', '=', $groupbuy_id ],
                    [ 'pg.site_id', '=', $this->site_id ],
                    [ 'g.goods_state', '=', 1 ],
                    [ 'g.is_delete', '=', 0 ]
                ]
            )[ 'data' ] ?? [];
        if (!$this->groupbuy_info) throw new OrderException('该团购不存在');
        //商品列表信息
        $this->getOrderGoodsCalculate();
        //判断活动是否过期或开启
        if ($this->groupbuy_info[ 'status' ] != 2) {
            $this->error = 1;
            $this->error_msg = '当前商品团购活动未开启或已过期！';
        }
        //判断购买数是否超过限购
        if ($this->groupbuy_info[ 'buy_num' ] > $this->param[ 'num' ]) {
            $this->error = 1;
            $this->error_msg = '该商品限制购买不能少于' . $this->groupbuy_info[ 'buy_num' ] . '件！';
        }
        //计算
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
     * 获取商品的计算信息
     * @param unknown $data
     */
    public function getOrderGoodsCalculate()
    {
        $this->getGroupbuyGoodsInfo();
        return true;
    }

    /**
     * 获取团购商品列表信息
     * @param unknown $bl_id
     */
    public function getGroupbuyGoodsInfo()
    {
        //组装商品列表
        $field = 'ngs.sku_id,ngs.sku_name, ngs.sku_no,ngs.price, ngs.discount_price, ngs.cost_price, ngs.stock, ngs.weight, ngs.volume, ngs.sku_image, ngs.site_id, ngs.goods_state, ngs.is_virtual,ngs.supplier_id,ngs.form_id,
        ngs.is_free_shipping, ngs.shipping_template, ngs.goods_class, ngs.goods_class_name,ngs.goods_id, ns.site_name,ngs.sku_spec_format,ngs.goods_name,g.goods_image,ngs.support_trade_type';
        $join = [
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ],
            [ 'goods g', 'ngs.goods_id = g.goods_id', 'inner' ],
        ];
        $info = model('goods_sku')->getInfo([ [ 'ngs.sku_id', '=', $this->param[ 'sku_id' ] ], [ 'ngs.site_id', '=', $this->site_id ] ], $field, 'ngs', $join);
        if (!empty($info)) {
            //判断是否是虚拟订单
            if ($info[ 'is_virtual' ]) {
                $this->is_virtual = 1;
            } else {
                $this->is_virtual = 0;
            }
            $info[ 'num' ] = $this->param[ 'num' ];
            $price = $this->groupbuy_info[ 'groupbuy_price' ];
            $goods_money = $price * $info[ 'num' ];
            $info[ 'price' ] = $price;
            $info[ 'goods_money' ] = $goods_money;
            $info[ 'real_goods_money' ] = $goods_money;//真实商品金额
            $info[ 'coupon_money' ] = 0;//优惠券金额
            $info[ 'promotion_money' ] = 0;//优惠金额
            $info[ 'stock' ] = numberFormat($info[ 'stock' ]);
            $this->goods_list[] = $info;

            $this->site_name = $info[ 'site_name' ];
            $this->goods_money = $goods_money;
            $this->goods_list_str = $info[ 'sku_id' ] . ':' . $info[ 'num' ];
            $this->order_name = string_split('', ',', $info[ 'sku_name' ]);
            $this->goods_num = $info[ 'num' ];
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

}