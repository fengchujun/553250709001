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

use app\model\express\Express;
use app\model\express\Local;
use app\model\store\Store;

/**
 * 订单创建  可调用的工具类
 */
trait DeliveryTool
{

    /**
     * 获取配送配置数据
     * @return true
     */
    public function getDeliveryData()
    {
        $curr_store_id = $this->param['store_id'] ?? 0;
        $jielong_id = $this->param['jielong_id'] ?? 0;
        $express_type = [];
        if ($this->is_virtual == 0) {
            //todo 没有活动才会参与计算
            if (empty($this->promotion_type)) $trade_result = event('OrderCreateCommonData', ['type' => 'trade', 'order_object' => $this], true);
            if (!empty($trade_result)) {
                if ($trade_result['code'] >= 0) {
                    $express_type = $trade_result['data'];
                }
            } else {
                $deliver_type_sort = $this->config('delivery_type_sort');
                $deliver_sort_list = explode(',', $deliver_type_sort['value']['deliver_type']);
                //根据当前的定位查询本地配送门店或自提门店
                $latitude = $this->param['latitude'] ?? 0;
                $longitude = $this->param['longitude'] ?? 0;
                foreach ($deliver_sort_list as $type) {
                    // 物流
                    if ($type == 'express') {
                        $express_config = $this->config('express');
                        if ($express_config['is_use'] == 1) {
                            $title = $express_config['value']['express_name'];
                            if ($title == '') {
                                $title = Express::express_type['express']['title'];
                            }

                            $express_type[] = ['title' => $title, 'name' => 'express'];
                        }

                    }
                    // 自提
                    if ($type == 'store') {
                        $store_config = $this->config('store');
                        if ($store_config['is_use'] == 1) {
                            //根据坐标查询门店
                            $store_model = new Store();
                            $store_condition = [
                                ['site_id', '=', $this->site_id],
                                //['is_pickup', '=', 1],
                                //['status', '=', 1],
                                ['', 'exp', \think\facade\Db::raw('(is_pickup = 1 && status = 1) or store_id = '.$curr_store_id)],
                                ['is_frozen', '=', 0],
                            ];
                            //考虑门店库存和上下架，进一步过滤可以选择的门店
                            $this->getAvailableStoreIds();
                            if($this->available_store_ids != 'all'){
                                $store_condition[] = ['store_id', 'in', $this->available_store_ids];
                            }
                            $latlng = [
                                'lat' => $latitude,
                                'lng' => $longitude,
                            ];
                            $store_list = $store_model->getLocationStoreList($store_condition, '*', $latlng)['data'] ?? [];
                            $store_list = $this->currStoreToFirstData($store_list);
                            $title = $store_config['value']['store_name'];
                            if ($title == '') {
                                $title = Express::express_type['store']['title'];
                            }
                            $express_type[] = ['title' => $title, 'name' => 'store', 'store_list' => $store_list];
                        }

                    }
                    // 外卖
                    if ($type == 'local') {
                        $local_config = $this->config('local');
                        if ($local_config['is_use'] == 1) {
                            //查询本店的通讯地址
                            $title = $local_config['value']['local_name'];
                            if ($title == '') {
                                $title = '外卖配送';
                            }
                            $store_model = new Store();
                            $store_condition = [
                                ['site_id', '=', $this->site_id],
                            ];
                            if (addon_is_exit('store', $this->site_id)) {
                                //$store_condition[] = ['is_o2o', '=', 1];
                                //$store_condition[] = ['status', '=', 1];
                                $store_condition[] = ['', 'exp', \think\facade\Db::raw('(is_o2o = 1 && status = 1) or store_id = '.$curr_store_id)];
                                $store_condition[] = ['is_frozen', '=', 0];
                            } else {
                                $store_condition[] = ['is_default', '=', 1];
                            }
                            //考虑门店库存和上下架，进一步过滤可以选择的门店
                            $this->getAvailableStoreIds();
                            if($this->available_store_ids != 'all'){
                                $store_condition[] = ['store_id', 'in', $this->available_store_ids];
                            }
                            $latlng = [
                                'lat' => $latitude,
                                'lng' => $longitude,
                            ];
                            $store_list_result = $store_model->getLocationStoreList($store_condition, '*', $latlng);
                            $store_list = $store_list_result['data'];
                            $store_list = $this->currStoreToFirstData($store_list);
                            $express_type[] = ['title' => $title, 'name' => 'local', 'store_list' => $store_list];
                        }

                    }
                }
            }
        }

        if(!empty($jielong_id)){
            //社群接龙只保留门店自提
            foreach ($express_type as $key => $value){
                if($value['name'] != 'store'){
                    unset($express_type[$key]);
                    continue;
                }
                $store_list = $value['store_list'] ?? [];
                if(empty($store_list)){
                    $this->setError(1, '没有可以自提的门店！');
                }
            }
            $this->jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielong_id ] ], '*');
            $express_type = array_values($express_type);
        }
        $this->delivery['express_type'] = $express_type;
        return true;
    }

    /**
     * 配送计算
     * @return true
     */
    public function calculateDelivery()
    {
        //整理配送时间格式
        $this->getDeliveryTime();
        //计算邮费
        if ($this->is_virtual == 1) {
            //虚拟订单  运费为0
            $delivery_money = 0;
            $this->delivery['delivery_type'] = '';
        } else {
            $deliver_type_sort = $this->config('delivery_type_sort');
            $deliver_sort_list = explode(',', $deliver_type_sort['value']['deliver_type']);
            //查询店铺是否开启快递配送
            $express_config = $this->config('express');

            //查询店铺是否开启门店自提
            $store_config = $this->config('store');

            //查询店铺是否开启外卖配送
            $local_config = $this->config('local');
            //todo 没有活动才会参与计算
            if (empty($this->promotion_type)) {
                $trade_calc_result = event('OrderCreateCommonData', ['type' => 'trade_calc', 'order_object' => $this, 'deliver_sort_list' => $deliver_sort_list], true);
            }
            if (empty($trade_calc_result)) {
                //如果本地配送开启, 则查询出本地配送的配置
                if ($local_config['is_use'] == 1 && isset($this->param['delivery']['store_id'])) {
                    $local_model = new Local();
                    $local_info = $local_model->getLocalInfo([['site_id', '=', $this->site_id], ['store_id', '=', $this->param['delivery']['store_id']]])['data'];
                    $this->delivery['local']['info'] = $local_info;
                } else {
                    $this->delivery['local']['info'] = [];
                }
                $delivery_array = $this->param['delivery'] ?? [];
                $delivery_type = $delivery_array['delivery_type'] ?? 'express';
                if ($delivery_type == 'store') {
                    //门店自提
                    $delivery_money = 0;
                    $this->delivery['delivery_type'] = 'store';
                    if ($store_config['is_use'] == 0) {
                        $this->setError(1, '门店自提方式未开启！');
                    }
                    if (empty($this->param['delivery']['store_id'])) {
                        $this->setError(1, '门店未选择！');
                    }
                    $this->delivery['store_id'] = $this->param['delivery']['store_id'] ?? 0;
                    $this->storeOrderData();
                    $this->store_id = $this->param['delivery']['store_id'] ?? 0;

                } else {
                    if (empty($this->delivery['member_address'])) {
                        $delivery_money = 0;
                        $this->delivery['delivery_type'] = 'express';
                        $this->setError(1, '未配置默认收货地址！');
                    } else {
                        if ($delivery_type == 'express') {
                            if ($express_config['is_use'] == 1) {
                                //物流配送
                                $express = new Express();
                                $express_fee_result = $express->calculate(['order_object' => $this]);
                                if ($express_fee_result['code'] < 0) {
                                    $this->setError(1, $express_fee_result['message']);
                                    $delivery_fee = 0;
                                } else {
                                    $delivery_fee = $express_fee_result['data']['delivery_fee'];
                                }
                            } else {
                                $this->setError(1, '物流配送方式未开启！');
                                $delivery_fee = 0;
                            }
                            $this->delivery_money = $delivery_fee;
                            $this->delivery['delivery_type'] = 'express';
                        } else if ($delivery_type == 'local') {
                            //外卖配送
                            $delivery_money = 0;
                            $this->delivery['delivery_type'] = 'local';
                            if ($local_config['is_use'] == 0) {
                                $this->setError(1, '外卖配送方式未开启！');
                            } else {
                                if (empty($this->param['delivery']['store_id'])) {
                                    $this->setError(1, '门店未选择！');
                                }

                                $this->store_id = $this->param['delivery']['store_id'] ?? 0;

                                $local_model = new Local();
                                $local_result = $local_model->calculate(['order_object' => $this]);

                                $this->delivery['start_money'] = 0;
                                if ($local_result['code'] < 0) {
                                    $this->delivery['start_money'] = $local_result['data']['start_money_array'][0] ?? 0;
                                    $this->setError($local_result['data']['code'], $local_result['message'], 1);
                                } else {
                                    $this->delivery_money = $local_result['data']['delivery_money'];
                                    if (!empty($local_result['data']['code'])) {
                                        $this->setError($local_result['data']['code'], $local_result['data']['error'], 1);
                                    }
                                }

                                $this->delivery['error'] = $this->error;
                                $this->delivery['error_msg'] = $this->error_msg;

                            }
                        }
                    }
                }
            }

            //检测门店是否可用
            if($this->available_store_ids != 'all' && !empty($this->store_id)){
                $available_store_ids = $this->available_store_ids ? explode(',', trim($this->available_store_ids, ',')) : [];
                if(!in_array($this->store_id,$available_store_ids)){
                    $this->setError(1, '所选门店不可用！');
                }
            }

            //是否符合免邮
            if ($this->is_free_delivery) {
                $this->delivery_money = 0;
            }
            //重新计算订单总额
            $this->getOrderMoney();
        }
        return true;
    }


    /**
     * 校验商品项的配送方式支持方式
     * @param $goods
     * @return true
     */
    public function checkDeliveryType($goods)
    {
        $delivery_type = $this->param['delivery']['delivery_type'] ?? '';
        if ($delivery_type && strpos($goods['support_trade_type'], $delivery_type) === false) {
            $express_type_list = $this->config('delivery_type');
            $delivery_type_name = $express_type_list[$delivery_type] ?? '';
            $this->error = 1;
            $this->error_msg = '有商品不支持' . $delivery_type_name;
        }
        return true;
    }

    /**
     * 批量校验配送方式
     * @return true
     */
    public function batchCheckDeliveryType()
    {
        $delivery_type = $this->param['delivery']['delivery_type'] ?? '';
        if (!$this->is_virtual) {
            if (!$delivery_type) {
                $this->error = 1;
                $this->error_msg = '请选择有效的配送方式';
            } else {
                $express_type_list = $this->config('delivery_type');
                $delivery_type_name = $express_type_list[$delivery_type] ?? '';
                foreach ($this->goods_list as $v) {
                    if (strpos($v['support_trade_type'], $delivery_type) === false) {
                        $this->error = 1;
                        //$this->error_msg = '商品' . $v['goods_name'] . '不支持' . $delivery_type_name;
                        $this->error_msg = '部分商品不支持' . $delivery_type_name;
                    }
                }
            }

        }
        return true;
    }

    /**
     * 获取配送时间格式
     * @return true
     */
    public function getDeliveryTime(){
        $delivery_time = $this->param['delivery']['buyer_ask_delivery_time'] ?? [];
        $remark = '';
        $start_date = $delivery_time['start_date'] ?? '';
        $end_date = $delivery_time['end_date'] ?? '';
        if($start_date && $end_date){
            $start_time = strtotime($start_date);
            $end_time = strtotime($end_date);
            $start_ymd = date('Y-m-d', $start_time);
            $end_ymd = date('Y-m-d', $end_time);
            if($start_ymd == $end_ymd){
                $remark = $start_ymd.' '.date('H:i:s', $start_time).' ~ '.date('H:i:s', $end_time);
            }else{
                $remark = $start_date .' ~ '. $end_date;
            }

        }else if($start_date && !$end_date){
            $remark = $start_date;
            $start_time = strtotime($start_date);
        }else if(!$start_date && $end_date){
            $remark = $end_date;
            $end_time = strtotime($end_date);
        }

        if(!empty($this->jielong_id)){
            $jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $this->jielong_id ] ], '*');
            if(!empty($jielong_info)){
                $start_time = $jielong_info['take_start_time'];
                $start_date = date("Y-m-d",$jielong_info['take_start_time']);
                $end_time = $jielong_info['take_end_time'];
                $end_date =  date("Y-m-d",$jielong_info['take_end_time']);
                $remark = $start_date.' ~ '.$end_date;
            }
        }

        $this->delivery['buyer_ask_delivery_time'] = [
            'start_date' => $start_date,
            'start_time' => $start_time ?? '',
            'end_date' => $end_date,
            'end_time' => $end_time ?? '',
            'remark' => $remark
        ];
        return true;
    }

    /**
     * 获取可以购买的门店数据
     * @return int[]|string[]
     */
    protected function getAvailableStoreIds()
    {
        if(addon_is_exit('store')){
            //要注意是独立库存还是总部库存
            $alias = 'sgs';
            $join = [
                ['store s', 'sgs.store_id = s.store_id', 'inner'],
                ['goods_sku gs', 'sgs.sku_id = gs.sku_id', 'inner'],
            ];
            $condition = [
                ['sgs.sku_id', 'in', array_column($this->goods_list, 'sku_id')],
                ['sgs.status', '=', 1],
            ];
            if($this->available_store_ids != 'all'){
                $condition[] = ['sgs.store_id', 'in', $this->available_store_ids];
            }
            $field = "sgs.sku_id,sgs.store_id,IF(s.stock_type = 'store', sgs.stock, gs.stock) as stock";
            $store_sku_list = model('store_goods_sku')->getList($condition, $field, '', $alias, $join);

            $store_id_data = [];
            $sku_num_data = array_column($this->goods_list, 'num','sku_id');
            $goods_model = new \app\model\goods\Goods();
            foreach($store_sku_list as $store_sku_info){
                //如果库存不足尝试库存转换
                if($store_sku_info['stock'] < $sku_num_data[$store_sku_info['sku_id']]){
                    $store_sku_info = $goods_model->goodsStockTransform([$store_sku_info], $store_sku_info['store_id'], 'store')[0];
                }
                if($store_sku_info['stock'] >= $sku_num_data[$store_sku_info['sku_id']]){
                    $store_id_data[$store_sku_info['store_id']][$store_sku_info['sku_id']] = $store_sku_info['sku_id'];
                }
            }
            foreach($store_id_data as $store_id=>$data){
                if(count($data) < count($sku_num_data)){
                    unset($store_id_data[$store_id]);
                }
            }
            $this->available_store_ids = array_keys($store_id_data);
            $curr_store_id = $this->param['store_id'] ?? 0;
            if(!in_array($curr_store_id, $this->available_store_ids)){
                $this->available_store_ids[] = $curr_store_id;
            }
        }
    }

    /**
     * 当前门店排到首位
     * @param $store_list
     * @return array
     */
    protected function currStoreToFirstData($store_list)
    {
        $curr_store_id = $this->param['store_id'] ?? 0;
        $curr_store = null;
        foreach($store_list as $key=>$store_info){
            if($store_info['store_id'] == $curr_store_id){
                $curr_store = $store_info;
                unset($store_list[$key]);
            }
        }
        if(!is_null($curr_store)){
            array_unshift($store_list, $curr_store);
        }
        return array_values($store_list);
    }
}