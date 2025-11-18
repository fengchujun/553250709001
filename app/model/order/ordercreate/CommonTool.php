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

use app\model\member\Member;
use app\model\member\MemberAddress;
use app\model\store\Store;
use app\model\system\Site;

/**
 * 订单创建  可调用的工具类
 */
trait CommonTool
{

    /****************************************************************************** 站点 start *****************************************************************************/
    /**
     * 初始化站点信息
     * @return true
     */
    public function initSiteData()
    {
        $site_model = new Site();
        $site_condition = array(
            ['site_id', '=', $this->site_id]
        );
        $site_info = $site_model->getSiteInfo($site_condition)['data'] ?? [];
        $this->site_info = $site_info;
        $this->site_name = $site_info['site_name'] ?? '';
        return true;
    }
    /****************************************************************************** 站点 end *****************************************************************************/
    /****************************************************************************** 门店 start *****************************************************************************/
    /**
     * 初始化门店
     * @return true
     */
    public function initStore()
    {
        $store_id = $this->param['store_id'] ?? 0;
        $store_model = new Store();
        if ($store_id == 0) {

//            $is_allow_store = false;
//            //是否安装门店插件
//            //todo  只有存在门店插件,并且开启门店连锁模式,才可以所以传递store_id, 否则就只有门店配送和本地配送就可以接收store_id
//            if (addon_is_exit('store')) {
//                //查询门店运营插件
//                $store_config_model = new \addon\store\model\Config();
//                $store_config = $store_config_model->getStoreBusinessConfig($site_id)[ 'data' ][ 'value' ] ?? [];
//                if ($store_config[ 'store_business' ] == 'store') {
//                    $is_allow_store = true;
//                }
//            }
//            if($is_allow_store){
//                $store_info = $store_model->getDefaultStore($site_id)[ 'data' ] ?? [];
//                $data[ 'store_info' ] = $store_info;
//                $data[ 'store_id' ] = $store_info[ 'store_id' ];
//            }

        } else {
            $cashier_type = $this->param['cashier_type'] ?? '';
            if ($cashier_type == 'cashier') {

            } else {
                $is_allow_store = false;
                //是否安装门店插件
                //todo  只有存在门店插件,并且开启门店连锁模式,才可以所以传递store_id, 否则就只有门店配送和本地配送就可以接收store_id
                if (addon_is_exit('store')) {
                    //查询门店运营插件
                    $store_config = $this->config('store_business');
                    if ($store_config['store_business'] == 'store') {
                        $is_allow_store = true;
                    }
                }
                if (!$is_allow_store) {
                    $delivery_array = $this->param['delivery'] ?? [];
                    $delivery_type = $delivery_array['delivery_type'] ?? 'express';
                    if (!in_array($delivery_type, ['local', 'store'])) {
                        $store_id = 0;
                    } else {
                        $store_id = $this->param['delivery']['store_id'] ?? 0;
                    }
                }
            }
            $this->store_info = $store_model->getStoreInfo([['site_id', '=', $this->site_id], ['store_id', '=', $store_id]])['data'] ?? [];
            if (empty($this->store_info)) {
                $store_id = 0;
            }else{
                if($this->store_info['status'] == 0){
                    if($cashier_type != 'cashier'){
                        $this->setError(1, '当前门店休息中！');
                    }
                }else{
                    $delivery_array = $this->param['delivery'] ?? [];
                    $delivery_type = $delivery_array['delivery_type'] ?? 'express';
                    if($delivery_type == 'local'){
                        if(addon_is_exit('store') && $this->store_info['is_o2o'] == 0){
                            $this->setError(1, '同城配送未开启');
                        }
                        if($this->store_info['out_open_date_o2o_pay'] == 0){
                            $curr_time = time() - strtotime(date('Y-m-d'));
                            $open_date_config = json_decode($this->store_info['open_date_config'], true);
                            $o2o_pay = false;
                            if(!empty($open_date_config)){
                                foreach($open_date_config as $item){
                                    if($item['start_time'] <= $curr_time && $item['end_time'] >= $curr_time){
                                        $o2o_pay = true;
                                        break;
                                    }
                                }
                            }else{
                                $o2o_pay = true;
                            }
                            if(!$o2o_pay){
                                $this->setError(1, '请在营业时间内下单');
                            }
                        }
                    }
                    if($delivery_type == 'pickup'){
                        if(addon_is_exit('store') && $this->store_info['is_pickup'] == 0){
                            $this->setError(1, '门店自提未开启');
                        }
                    }
                }
            }

            $this->delivery['store_info'] = $this->store_info;
            $this->store_id = $store_id;
        }

        return true;

    }

    /**
     * 补齐门店数据
     * @return true
     */
    public function storeOrderData()
    {
        $temp_data = [];
        $delivery_store_id = $this->delivery['store_id'] ?? 0; //门店id

        if ($delivery_store_id > 0) {
            $store_model = new Store();
            $condition = array(
                ['store_id', '=', $delivery_store_id],
                ['site_id', '=', $this->site_id],
            );
            $store_info = $store_model->getStoreInfo($condition)['data'] ?? [];
            if (empty($store_info)) {
                $this->setError(1, '当前门店不存在！');
            }else if($store_info['status'] == 0){
                $this->setError(1, '当前门店休息中');
            }else if($store_info['is_pickup'] == 0){
                $this->setError(1, '当前门店未开启自提');
            } else {
                $this->delivery['delivery_store_id'] = $delivery_store_id;
                $delivery_store_name = $store_info['store_name'];
                $this->delivery['delivery_store_name'] = $delivery_store_name;
                $delivery_store_info = array(
                    'open_date' => $store_info['open_date'],
                    'full_address' => $store_info['full_address'] . $store_info['address'],
                    'longitude' => $store_info['longitude'],
                    'latitude' => $store_info['latitude'],
                    'telphone' => $store_info['telphone'],
                    'store_image' => $store_info['store_image'],
                    'time_type' => $store_info['time_type'],
                    'time_week' => $store_info['time_week'],
                    'start_time' => $store_info['start_time'],
                    'end_time' => $store_info['end_time'],
                );
                $this->delivery['delivery_store_info'] = json_encode($delivery_store_info, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $this->setError(1, '配送门店不可为空！');
        }
        return true;
    }

    /****************************************************************************** 门店 end *****************************************************************************/
    /****************************************************************************** 发票 start *****************************************************************************/
    /**
     * 获取发票信息
     * @return true
     */
    public function getInovice()
    {
        $order_config = $this->config('order');
        $invoice_status = $order_config['invoice_status'] ?? 0;
        $this->invoice['invoice_status'] = $invoice_status;
        if ($invoice_status == 1) {
            $invoice_content = $order_config['invoice_content'] ?? '';
            $invoice_content_array = explode(',', $invoice_content);
            $this->invoice['invoice_content_array'] = $invoice_content_array;
            $this->invoice['invoice_delivery_money'] = $order_config['invoice_money'] ?? 0;
            $this->invoice['invoice_rate'] = $order_config['invoice_rate'] ?? 0;
            $this->invoice['invoice_type'] = $order_config['invoice_type'] ?? '1,2';
        }
        return true;
    }

    /**
     * 计算发票信息
     * @return true
     */
    public function calculateInvoice()
    {
        $order_config = $this->config('order');
        $invoice_status = $order_config['invoice_status'] ?? 0;
        $this->invoice['invoice_status'] = $invoice_status;
        $invoice_money = 0;
        $invoice_delivery_money = 0;
        if ($invoice_status == 1) {
            $is_invoice = $this->param['is_invoice'] ?? 0;
            //是否需要发票
            if ($is_invoice) {
                $real_goods_money = $this->goods_money - $this->promotion_money - $this->coupon_money - $this->point_money;
                $invoice_money = round($real_goods_money * $this->invoice['invoice_rate'] / 100, 2);
                $invoice_type = $this->param['invoice_type'] ?? 1;
                $this->invoice = [
                    'invoice_title_type' => $this->param['invoice_title_type'],
                    'is_tax_invoice' => $this->param['is_tax_invoice'],
                    'taxpayer_number' => $this->param['taxpayer_number'],
                    'invoice_title' => $this->param['invoice_title'],
                    'invoice_type' => $this->param['invoice_type'],
                    'invoice_content' => $this->param['invoice_content'],
                    'invoice_rate' => $order_config['invoice_rate'],
                ];

                if ($invoice_type == 1) {
                    $invoice_delivery_money = $order_config['invoice_money'];
                    //未定义发票收货地址的话,会默认使用收发货地址
                    if (empty($this->param['invoice_full_address'])) {
                        if ($this->delivery['delivery_type'] == 'express' || $this->delivery['delivery_type'] == 'local') {
                            $invoice_full_address = $this->delivery['member_address']['full_address'] . $this->delivery['member_address']['address'];
                        } else if ($this->delivery['delivery_type'] == 'store') { //门店
                            $delivery_store_info = json_decode($this->delivery['delivery_store_info'], true);
                            $invoice_full_address = $delivery_store_info['full_address'];
                        }
                    } else {
                        $invoice_full_address = $this->param['invoice_full_address'] ?? '';
                    }
                    $this->invoice['invoice_full_address'] = $invoice_full_address ?? '';
                } else {
                    if (empty($this->param['invoice_email'])) {
                        $this->setError(1, '发票邮箱不能为空！');
                    } else {
                        $this->invoice['invoice_email'] = $this->param['invoice_email'];
                    }
                }
                if (empty($this->param['invoice_title']) || empty($this->param['invoice_type']) || empty($this->param['invoice_content'] || $this->param['invoice_title_type'] == 0)) {
                    $this->setError(1, '发票相关项不能为空！');
                }
                //企业抬头  必须填写税号
                if ($this->param['invoice_title_type'] == 2 && empty($this->param['taxpayer_number'])) {
                    $this->setError(1, '发票相关项不能为空！');
                }
            }
        }
        //发票费用和发票邮寄费用
        $this->invoice_money = $invoice_money;
        $this->invoice_delivery_money = $invoice_delivery_money;
        $this->order_money += $this->invoice_money + $this->invoice_delivery_money;
        return true;
    }
    /****************************************************************************** 发票 end *****************************************************************************/

    /****************************************************************************** 杂项 start *****************************************************************************/
    /**
     * 初始化收货地址
     * @return true
     */
    public function initMemberAddress()
    {
        $delivery_type = $this->param['delivery']['delivery_type'] ?? '';
        if (empty($this->param['delivery']['member_address'])) {
            $member_address = new MemberAddress();
            $type = 1;
            if ($delivery_type == 'local') {
                $type = 2;
            }
            $this->delivery['member_address'] = $member_address->getMemberAddressInfo([['member_id', '=', $this->member_id], ['is_default', '=', 1], ['type', 'in', $type]])['data'];
        } else {
            $this->delivery['member_address'] = $this->param['delivery']['member_address'];
        }
        if (!empty($this->delivery['member_address'])) {
            if ($delivery_type == 'local') {
                //外卖订单 如果收货地址没有定位的话,就不取用地址
                $type = $this->delivery['member_address']['type'] ?? 1;
                if ($type == 1) {
                    $this->delivery['member_address'] = [];
                }
            }
        }
        return true;
    }

    /**
     * 初始化会员账户
     * @return true
     */
    public function initMemberAccount()
    {
        $member_model = new Member();
        $member_info = $member_model->getMemberDetail($this->member_id, $this->site_id)['data'] ?? [];
        if (!empty($member_info)) {
            if (!empty($member_info['pay_password'])) {
                $is_pay_password = 1;
            } else {
                $is_pay_password = 0;
            }
            unset($member_info['pay_password']);
            $member_info['is_pay_password'] = $is_pay_password;
            $this->member_account = $member_info;

            //初始化会员等级
            $this->member_level = model('member_level')->getInfo([
                ['level_id', '=', $this->member_account['member_level']]
            ]);
        }

        return true;
    }
    /****************************************************************************** 杂项 end *****************************************************************************/
}
