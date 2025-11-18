<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\event;

use addon\weapp\model\Weapp;
use app\model\order\Order;
use app\model\shop\Shop as ShopModel;
use app\dict\order\OrderDict;
use think\facade\Log;

/**
 * 订单发货完成，小程序发货信息录入
 */
class OrderDeliveryAfterWeappDelivery
{
    public function handle($param)
    {
        try{
            //订单信息
            $order_model = new Order();
            $filed = 'o.order_id,o.site_id,o.order_type,o.out_trade_no,o.pay_type,o.mobile,o.promotion_type,m.weapp_openid';
            $join = [
                [ 'member m', 'o.member_id=m.member_id', 'left' ]
            ];
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $param[ 'relate_id' ] ] ], $filed, 'o', $join);
            if (empty($order_info)) return $order_model->success();
            if ($order_info[ 'pay_type' ] != 'wechatpay') return $order_model->success();

            // 检测微信小程序是否已开通发货信息管理服务
            $weapp_model = new Weapp($order_info[ 'site_id' ]);
            $is_trade_managed = $weapp_model->orderShippingIsTradeManaged()['data'];
            if (!$is_trade_managed) return $weapp_model->success();

            //商家信息
            $shop_model = new ShopModel();
            $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $order_info[ 'site_id' ] ] ], '')[ 'data' ];

            //物流模式和发货方式
            $logistics_type_config = [
                OrderDict::express => Weapp::LOGISTICS_TYPE_EXPRESS,
                OrderDict::local => Weapp::LOGISTICS_TYPE_LOCAL,
                OrderDict::store => Weapp::LOGISTICS_TYPE_STORE,
                OrderDict::virtual => Weapp::LOGISTICS_TYPE_VIRTUAL,
            ];
            $logistics_type = $logistics_type_config[$order_info['order_type']];
            $delivery_mode = $order_info['order_type'] == OrderDict::express ? Weapp::SPLIT_DELIVERY : Weapp::UNIFIED_DELIVERY;

            //小程序物流公司
            $delivery_list = [];
            if ($logistics_type == OrderDict::express) {
                $delivery_list = $weapp_model->orderShippingGetDeliveryList()[ 'data' ];
            }
            //订单商品
            $order_goods_field = 'order_goods_id,sku_name,num,delivery_no,delivery_status';
            $order_goods_list = $order_model->getOrderGoodsList([
                [ 'order_id', '=', $order_info[ 'order_id' ] ]
            ], $order_goods_field, 'order_goods_id asc')[ 'data' ];
            $order_goods_list = array_column($order_goods_list, null, 'order_goods_id');
            //寄件人联系方式
            $consignor_contact = $this->mobileShow($shop_info[ 'mobile' ]);
            //收件人联系方式
            $receiver_contact = $this->mobileShow($order_info[ 'mobile' ]);
            //组装小程序发货信息
            $shipping_list = [];
            $delivery_goods_count = 0;
            $is_all_delivered = true;
            if ($logistics_type == OrderDict::express) {
                $package_list = model('express_delivery_package')->getList([
                    [ 'order_id', '=', $order_info[ 'order_id' ] ],
                ], '*');
                foreach($package_list as $package_info){
                    //商品信息
                    $order_goods_ids = explode(',', $package_info['order_goods_id_array']);
                    $item_desc = [];
                    foreach($order_goods_ids as $order_goods_id){
                        $order_goods_info = $order_goods_list[$order_goods_id];
                        $item_desc_text = $order_goods_info['sku_name'].'*'.$order_goods_info['num'];
                        $item_desc[] = $item_desc_text;
                        $delivery_goods_count ++;
                    }
                    //物流公司
                    $express_company = '';
                    if (!empty($package_info[ 'express_company_name' ]) && !empty($delivery_list)) {
                        $delivery_index = array_search($package_info[ 'express_company_name' ], array_column($delivery_list, 'delivery_name'));
                        if ($delivery_index === false) continue;
                        $express_company = $delivery_list[ $delivery_index ][ 'delivery_id' ];
                    }
                    if(empty($express_company)) continue;
                    //数据结构
                    $item = [
                        'tracking_no' => $package_info[ 'delivery_no' ], // 物流单号，物流快递发货时必填，示例值: 323244567777 字符字节限制: [1, 128]
                        'express_company' => $express_company, // 物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填， 示例值: DHL 字符字节限制: [1, 128]
                        'item_desc' => $weapp_model->handleOrderShippingItemDesc($item_desc), // 商品信息，例如：微信红包抱枕*1个，限120个字以内
                        'contact' => [
                            'consignor_contact' => $consignor_contact,
                            'receiver_contact' => $receiver_contact,
                        ]
                    ];
                    //最多只能有9个包裹
                    if(count($shipping_list) < 9){
                        $shipping_list[] = $item;
                    }
                }
                if(empty($shipping_list)){
                    $logistics_type = Weapp::LOGISTICS_TYPE_LOCAL;
                    $delivery_mode = Weapp::UNIFIED_DELIVERY;
                }
                if($delivery_goods_count < count($order_goods_list)){
                    $is_all_delivered = false;
                }
            }
            //统一发货的发货信息
            if($delivery_mode == Weapp::UNIFIED_DELIVERY){
                $item_desc = [];
                foreach($order_goods_list as $order_goods_info){
                    $item_desc_text = $order_goods_info['sku_name'].'*'.$order_goods_info['num'];
                    $item_desc[] = $item_desc_text;
                }
                $shipping_list[] = [
                    'tracking_no' => '', // 物流单号，物流快递发货时必填，示例值: 323244567777 字符字节限制: [1, 128]
                    'express_company' => '', // 物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填， 示例值: DHL 字符字节限制: [1, 128]
                    'item_desc' => $weapp_model->handleOrderShippingItemDesc($item_desc), // 商品信息，例如：微信红包抱枕*1个，限120个字以内
                    'contact' => [
                        'consignor_contact' => $consignor_contact,
                        'receiver_contact' => $receiver_contact,
                    ]
                ];
            }

            $param = [
                'site_id' => $order_info['site_id'],
                'out_trade_no' => $order_info['out_trade_no'],
                'logistics_type' => $logistics_type,
                'delivery_mode' => $delivery_mode,
                'shipping_list' => $shipping_list,
                'weapp_openid' => $order_info[ 'weapp_openid' ],
                'is_all_delivered' => $is_all_delivered,
            ];
            $res = $weapp_model->orderShippingUploadShippingInfo($param);
            //如果是预售，尾款也需要同步发货信息
            if($order_info['promotion_type'] == 'presale'){
                $param['out_trade_no'] = $order_info['out_trade_no_2'];
                $res = $weapp_model->orderShippingUploadShippingInfo($param);
            }
            Log::write('小程序发货结果' . json_encode($res, JSON_UNESCAPED_UNICODE));
            //dd($param,$res);
            return $res;
        }catch(\Exception $e){
            Log::write('小程序发货错误' . json_encode([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
            //dd($e->getFile(),$e->getLine(),$e->getMessage());
            return error(-1, '小程序上传发货信息错误，'.$e->getMessage());
        }
    }

    // 寄件人和收件人联系方式，采用掩码传输，最后4位数字不能打掩码 示例值: `189****1234, 021-****1234, ****1234, 0**2-***1234, 0**2-******23-10, ****123-8008` 值限制: 0 ≤ value ≤ 1024
    protected function mobileShow($mobile)
    {
        if($mobile){
            $mobile =substr($mobile, 0, 3) . '****' . substr($mobile, 7);
        }
        return $mobile;
    }
}