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

use app\dict\order\OrderGoodsDict;
use app\model\BaseModel;
use app\model\express\Config as ExpressConfig;
use app\model\express\Express;
use app\model\express\Local;
use app\model\goods\Goods;
use app\model\member\Member;
use app\model\member\MemberAccount;
use app\model\member\MemberAddress;
use app\model\order\Config;
use app\model\order\LocalOrder;
use app\model\order\Order as CommonOrder;
use app\model\order\OrderCreateTool;
use app\model\order\StoreOrder;
use app\model\store\Store;
use app\model\system\Cron;
use app\model\system\Pay;
use extend\exception\OrderException;
use think\facade\Cache;

/**
 * 积分兑换
 */
class OrderCreate extends BaseModel
{
    use OrderCreateTool;


    public $point = 0;
    public $balance = 0;
    public $exchange_info = [];


    public function __construct()
    {
        $this->promotion_type = 'pointexchange';
        $this->promotion_type_name = '积分商城';
        $this->is_limit_start_money = false;
    }

    /**
     * 创建订单
     */
    public function create()
    {
        //计算
        $this->confirm();
        if ($this->error > 0) {
            return $this->error([ 'error_code' => $this->error ], $this->error_msg);
        }
        model('promotion_exchange_order')->startTrans();
        try {
            $this->order_no = $this->createOrderNo();
            $pay_model = new Pay();
            $this->out_trade_no = $pay_model->createOutTradeNo($this->member_id);
            //配送数据
            if ($this->exchange_info[ 'type' ] == 1) {
                $express_type_list = $this->config('delivery_type');
                $delivery_type_name = $express_type_list[ $this->delivery[ 'delivery_type' ] ] ?? '';
            }
            $order_id = 0;
            $delivery_time_data = $this->delivery[ 'buyer_ask_delivery_time' ] ?? [];
            $order_data = array (
                'order_no' => $this->order_no,
                'member_id' => $this->member_id,
                'out_trade_no' => $this->out_trade_no,
                'point' => $this->point,
                'exchange_price' => $this->exchange_info[ 'price' ],
                'delivery_price' => $this->delivery_money,
                'price' => $this->exchange_info[ 'price' ],
                'order_money' => $this->order_money,
                'create_time' => time(),
                'exchange_id' => $this->exchange_info[ 'id' ],
                'exchange_goods_id' => $this->exchange_info[ 'exchange_goods_id' ],
                'exchange_name' => $this->exchange_info[ 'name' ],
                'exchange_image' => $this->exchange_info[ 'image' ],
                'num' => $this->goods_num,
                'order_status' => 0,
                'type' => $this->exchange_info[ 'type' ],
                'type_name' => $this->exchange_info[ 'type_name' ],
                'name' => $this->delivery[ 'member_address' ][ 'name' ] ?? '',
                'mobile' => $this->delivery[ 'member_address' ][ 'mobile' ] ?? '',
                'telephone' => $this->delivery[ 'member_address' ][ 'telephone' ] ?? '',
                'province_id' => $this->delivery[ 'member_address' ][ 'province_id' ] ?? '',
                'city_id' => $this->delivery[ 'member_address' ][ 'city_id' ] ?? '',
                'district_id' => $this->delivery[ 'member_address' ][ 'district_id' ] ?? '',
                'community_id' => $this->delivery[ 'member_address' ][ 'community_id' ] ?? '',
                'address' => $this->delivery[ 'member_address' ][ 'address' ] ?? '',
                'full_address' => $this->delivery[ 'member_address' ][ 'full_address' ] ?? '',
                'longitude' => $this->delivery[ 'member_address' ][ 'longitude' ] ?? '',
                'latitude' => $this->delivery[ 'member_address' ][ 'latitude' ] ?? '',
                'delivery_store_id' => $this->delivery[ 'store_id' ] ?? 0,
                'delivery_store_name' => $this->delivery[ 'delivery_store_name' ] ?? '',
                'delivery_store_info' => $this->delivery[ 'delivery_store_info' ] ?? '',
                //配送时间
                'buyer_ask_delivery_time' => $delivery_time_data[ 'remark' ] ?? '',//定时达
                'delivery_start_time' => $delivery_time_data[ 'start_time' ] ?? '',//配送开始时间
                'delivery_end_time' => $delivery_time_data[ 'end_time' ] ?? '',//配送结束时间

                'order_from' => $this->order_from,
                'order_from_name' => $this->order_from_name,
                'buyer_message' => $this->param[ 'buyer_message' ],
                'type_id' => $this->exchange_info[ 'type_id' ],
                'balance' => $this->balance,
                'site_id' => $this->site_id,
                'order_id' => $order_id,
                'delivery_type' => $this->delivery[ 'delivery_type' ] ?? '',
                'delivery_type_name' => $delivery_type_name ?? '',
                'delivery_status' => OrderGoodsDict::wait_delivery,
                'delivery_status_name' => OrderGoodsDict::getDeliveryStatus(OrderGoodsDict::wait_delivery),
            );

            $this->order_id = model('promotion_exchange_order')->add($order_data);
            //判断库存
            $exchange_model = new Exchange();

            //减去套餐的库存
            $exchange_result = $exchange_model->decStock([ 'id' => $this->exchange_info[ 'id' ], 'num' => $this->goods_num ]);
            if ($exchange_result[ 'code' ] < 0) {
                model('promotion_exchange_order')->rollback();
                return $exchange_result;
            }
            //扣除积分
            $member_account_model = new MemberAccount();
            $member_account_result = $member_account_model->addMemberAccount($this->site_id, $this->member_id, 'point', -$this->point, 'pointexchange', $order_id, '积分兑换扣除');
            if ($member_account_result[ 'code' ] < 0) {
                model('promotion_exchange_order')->rollback();
                return $this->error($member_account_result,'账户积分不足');
            }
            if ($this->exchange_info[ 'type' ] == 1) {
                $goods_info = $this->goods_list[ 0 ];
                $stock_result = $this->skuDecStock($goods_info, $this->store_id);
                if ($stock_result[ 'code' ] != 0) {
                    model('promotion_exchange_order')->rollback();
                    return $stock_result;
                }
            }
            model('promotion_exchange_order')->commit();
            // 积分兑换订单生成后操作
            event('PointExchangeOrderCreate', [ 'order_id' => $order_id, 'create_data' => get_object_vars($this) ]);
            //支付单据
            $pay_model->addPay($this->site_id, $this->out_trade_no, 'POINT', $this->order_name, $this->order_name, $this->order_money, '', 'PointexchangeOrderPayNotify', '', $this->order_id, $this->member_id);
            return $this->success($this->out_trade_no);
        } catch (\Exception $e) {
            model('promotion_exchange_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 待支付订单
     * @param $data
     */
    public function payment()
    {
        $this->calculate();//计算并查询套餐信息
        if ($this->exchange_info[ 'type' ] == 1) {
            //查询配送信息
            $this->getDeliveryData();
        }

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
        if ($this->exchange_info[ 'type' ] == 1) {
            //配送计算
            $this->calculateDelivery();

            if ($this->exchange_info[ 'is_free_shipping' ] == 0 && $this->exchange_info[ 'delivery_type' ] == 0) {
                //固定运费
                $this->delivery_money = $this->exchange_info[ 'delivery_price' ];
            }
        }

        $this->order_money = $this->goods_money + $this->delivery_money;
        $this->pay_money = $this->order_money;
        //设置过的商品项信息
        return get_object_vars($this);
    }

    /**
     * 计算
     * @param $data
     */
    public function calculate()
    {
        $this->initMemberAddress();
        $this->initMemberAccount(); //初始化会员账户


        $id = $this->param[ 'id' ];
        $sku_id = $this->param[ 'sku_id' ];
        $num = $this->param[ 'num' ];
        $exchange_model = new Exchange();
        $exchange_info = $exchange_model->getExchangeInfo($id, '*', $sku_id)[ 'data' ] ?? [];
        if (empty($exchange_info)) throw new OrderException('找不到对应的积分兑换活动！');
        $this->exchange_info = $exchange_info;
        if ($this->exchange_info[ 'state' ] == 0) {
            $this->error = 1;
            $this->error_msg = '当前兑换活动未开启！';
        }

        if ($this->exchange_info[ 'type' ] == 1 && $exchange_info[ 'limit_num' ] > 0) {
            // 已兑换数量
            $exchangeed_num = model('promotion_exchange_order')->getSum([ [ 'exchange_id', '=', $this->exchange_info[ 'id' ] ], [ 'order_status', '<>', '-1' ], [ 'member_id', '=', $this->member_id ] ], 'num');
            if (($exchangeed_num + $num) > $this->exchange_info[ 'limit_num' ]) {
                $this->error = 1;
                $this->error_msg = '最多可以兑换'.$this->exchange_info[ 'limit_num' ].'件';
                if($exchangeed_num > 0){
                    $this->error_msg .= '，您已兑换'.$exchangeed_num.'件';
                }
            }
        }

        if ($this->exchange_info[ 'stock' ] <= 0) {
            if ($this->exchange_info[ 'type' ] == 2 && $this->exchange_info[ 'stock' ] < 0) {
            } else {
                $this->error = 1;
                $this->error_msg = '当前兑换库存不足！';
            }
        }

        //兑换类型为1时   兑换物品为商品(相对优惠券和红包来说较为特殊)
        if ($this->exchange_info[ 'type' ] == 1) {
            $goods_model = new Goods();
            $goods_info = $goods_model->getGoodsSkuInfo([ [ 'sku_id', '=', $this->exchange_info[ 'sku_id' ] ], [ 'site_id', '=', $this->site_id ] ], '*')[ 'data' ] ?? [];
            if (empty($goods_info)) throw new OrderException('商品不存在！');
            $goods_info[ 'num' ] = $num;
            if ($this->exchange_info[ 'type' ] == 1) {
                if ($exchange_info[ 'is_free_shipping' ] == 1) {
                    //免邮
                    $goods_info[ 'is_free_shipping' ] = 1;
                } else {
                    if ($this->exchange_info[ 'delivery_type' ] == 2) {

                    } else if ($this->exchange_info[ 'delivery_type' ] == 1) {
                        //运费模板
                        $goods_info[ 'shipping_template' ] = $this->exchange_info[ 'shipping_template' ];

                    }
                }
            }
            $goods_info[ 'goods_money' ] = $this->exchange_info[ 'price' ] * $num;
            $this->goods_list[] = $goods_info;
            $this->is_virtual = $goods_info[ 'is_virtual' ];


        }
        $point = $this->exchange_info[ 'point' ];
        $price = $this->exchange_info[ 'price' ];
        $balance = $exchange_info[ 'balance' ];
        $goods_num = $num;

        $this->goods_money = $price * $num;

        $this->order_money = $this->goods_money;
        $this->pay_money = $this->order_money;
        $this->order_name = $this->exchange_info[ 'name' ] . '【' . $this->exchange_info[ 'type_name' ] . '】';
        $this->point = $point * $num;
        $this->price = $price * $num;
        $this->goods_num = $goods_num;
        $this->balance = $balance * $num;

        $this->order_key = create_no();
        $this->setOrderCache(get_object_vars($this), $this->order_key);
        return true;
    }


    /**
     * 增加订单自动关闭事件
     */
    public function addOrderCronClose()
    {
        //计算订单自动关闭时间
        $config_model = new Config();
        $order_config_result = $config_model->getOrderEventTimeConfig($this->site_id);
        $order_config = $order_config_result[ 'data' ];
        $now_time = time();
        if (!empty($order_config)) {
            $execute_time = $now_time + $order_config[ 'value' ][ 'auto_close' ] * 60;//自动关闭时间
        } else {
            $execute_time = $now_time + 3600;//尚未配置  默认一天
        }
        $cron_model = new Cron();
        $cron_model->addCron(1, 0, '积分兑换订单自动关闭', 'CronExchangeOrderClose', $execute_time, $this->order_id);
        return true;
    }
}