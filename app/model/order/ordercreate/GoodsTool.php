<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\ordercreate;

use app\dict\goods\GoodsDict;
use app\model\goods\Goods;
use app\model\order\OrderStock;
use extend\exception\OrderException;

/**
 * 订单创建  可调用的工具类
 */
trait GoodsTool
{

    /**
     * 校验限购
     * @return true
     */
    public function checkLimitPurchase()
    {
        if ($this->limit_purchase) {
            foreach ($this->limit_purchase as $item) {
                //商品长度处理
                $goods_name = str_sub($item['goods_name'], 12, true, 'end');
                if ($item['min_buy'] > 0 && $item['num'] < $item['min_buy']) {
                    $this->setError(1, "商品“{$goods_name}”{$item['min_buy']}件起售");
                    break;
                }

                if ($item['is_limit'] == 1 && $item['max_buy'] > 0) {  // 商品做限制购买
                    if ($item['limit_type'] == 1) { // 单次限制
                        if ($item['num'] > $item['max_buy']) {
                            $this->setError(1, "商品“{$goods_name}”每人限购{$item['max_buy']}件");
                            break;
                        }
                    } else { // 长期限制
                        $goods_model = new Goods();
                        $purchased_num = $goods_model->getGoodsPurchasedNum($item['goods_id'], $this->member_id);
                        if (($purchased_num + $item['num']) > $item['max_buy']) {
                            $this->setError(1, "商品“{$goods_name}”每人限购{$item['max_buy']}件，您已购买{$purchased_num}件");
                            break;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 计算商品的单价
     * @param $goods_info
     * @return array
     */
    public function getGoodsPrice($goods_info)
    {
        //判断是否存在限时折扣
        $discount_price = $goods_info['discount_price'];
        $price = $discount_price;
        //计算当前会员的会员购买价
        $member_result = $this->getGoodsMemberPrice($goods_info);
        if ($member_result['code'] >= 0) {
            $member_price = $member_result['data'];
            if ($member_price < $price) {
                $price = $member_price;
            }
        }
        return $this->success($price);
    }

    /**
     * 获取商品会员价格
     * @param $goods_info
     * @return array
     */
    public function getGoodsMemberPrice($goods_info)
    {
        if ($this->member_id > 0) {
            if (addon_is_exit('memberprice') && !empty($this->member_level)) {
                if ($goods_info['is_consume_discount']) {
                    $price = $goods_info['price'];
                    if ($goods_info['discount_config'] == 1) {
                        // 自定义优惠
                        $goods_info['member_price'] = json_decode($goods_info['member_price'], true);
                        $value = $goods_info['member_price'][$goods_info['discount_method']][$this->member_account['member_level']] ?? 0;
                        switch ($goods_info['discount_method']) {
                            case 'discount':
                                // 打折
                                if ($value == 0) {
                                    $member_price = $price;
                                } else{
                                    $member_price = number_format($price * $value / 10, 2, '.', '');
                                }
                                break;
                            case 'manjian':
                                if ($value == 0) {
                                    $member_price = $price;
                                } else{
                                    // 满减
                                    $member_price = number_format($price - $value, 2, '.', '');
                                }
                                break;
                            case 'fixed_price':
                                if ($value == 0) {
                                    $member_price = $goods_info['price'];
                                } else{
                                    // 指定价格
                                    $member_price = number_format($value, 2, '.', '');
                                }
                                break;
                        }
                    } else {
                        // 默认按会员享受折扣计算
                        $member_price = number_format($price * $this->member_level['consume_discount'] / 100, 2, '.', '');
                    }
                    return $this->success($member_price);
                }
            }
        }
        return $this->error();
    }

    /**
     * 商品库存批量转换
     * @throws \Exception
     */
    public function batchGoodsStockTransform()
    {
        //自动库存转换
        if(addon_is_exit('stock')){
            $store_id = $this->param['delivery']['store_id'] ?? $this->param['store_id'];
            $transform_model = new \addon\stock\model\stock\Transform();
            $transform_stock_data = $transform_model->getGoodsStockTransformData([
                'sku_ids' => array_column($this->goods_list, 'sku_id'),
                'store_id' => $store_id,
                'store_business' => 'store',
            ]);
            foreach ($this->goods_list as $v) {
                $goods_class = $v['goods_class'] ?? 0;
                if (in_array($goods_class, [GoodsDict::real, GoodsDict::weigh])) {
                    $transform_data = $transform_stock_data[$v['sku_id']] ?? null;
                    if(!is_null($transform_data) && $v['stock'] < $v['num'] && $transform_data['transform_stock'] >= $v['num']){
                        $res = $transform_model->autoGoodsStockTransform([
                            'transform_data' => $transform_data,
                            'buy_num' => $v['num'],
                            'site_id' => $this->site_id,
                            'store_id' => $store_id,
                        ]);
                        if($res['code'] < 0) throw new \Exception('库存转换错误：'.$res['message']);
                    }
                }
            }
        }
    }

    /**
     * 订单项扣除库存
     * @return true
     */
    public function batchDecOrderGoodsStock()
    {
        $goods_sku_data = [];
        foreach ($this->goods_list as $v) {
            $goods_class = $v['goods_class'] ?? 0;
            if (in_array($goods_class, [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                $goods_sku_data[] = [
                    'sku_id' => $v['sku_id'],
                    'num' => $v['num'],
                ];
            }
//            $stock_result = $this->skuDecStock($v, $this->store_id);
//            if ($stock_result['code'] != 0) throw new OrderException($stock_result['message']);
        }

        if($goods_sku_data){
            $order_stock = new OrderStock();
            $stock_result = $order_stock->decOrderSaleStock([
                'goods_sku_data' => $goods_sku_data,
                'store_id' => $this->store_id,
                'create_order_data' => get_object_vars($this)
            ]);
            if ($stock_result['code'] < 0) throw new OrderException($stock_result['message']);
        }
        return true;
    }

    /**
     * 扣除商品库存
     * @param $goods_info
     * @param int $store_id
     * @return array
     */
    public function skuDecStock($goods_info, $store_id = 0)
    {
        $goods_class = $goods_info['goods_class'] ?? 0;
        if (!empty($goods_class)) {
            if (in_array($goods_class, [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                $order_stock = new OrderStock();
                $goods_sku_data = [
                    [
                        'sku_id' => $goods_info['sku_id'],
                        'num' => $goods_info['num']
                    ]
                ];
                $stock_result = $order_stock->decOrderSaleStock([
                    'goods_sku_data' => $goods_sku_data,
                    'store_id' => $store_id,
                    'create_order_data' => get_object_vars($this)
                ]);
                if ($stock_result['code'] < 0) {
                    return $stock_result;
                }
            }
        }
        return $this->success();
    }

    /**
     * 库存校验是否足够
     * @return void
     */
    public function checkStock(){
        $goods_sku_data = [];
        foreach ($this->goods_list as $v) {
            $goods_class = $v['goods_class'] ?? 0;
            if (in_array($goods_class, [GoodsDict::real, GoodsDict::virtual, GoodsDict::virtualcard, GoodsDict::service, GoodsDict::card, GoodsDict::weigh])) {
                $goods_sku_data[] = [
                    'sku_id' => $v['sku_id'],
                    'num' => $v['num'],
                    'sku_name' => $v['sku_name']
                ];
            }
        }
        if($goods_sku_data){
            $order_stock = new OrderStock();
            $order_stock->checkStock([
                'goods_sku_data' => $goods_sku_data,
                'store_id' => $this->store_id,
                'create_order_data' => get_object_vars($this)
            ]);
        }
        return true;
    }
}
