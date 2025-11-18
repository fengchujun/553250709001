<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace extend;

use Carbon\Carbon;

/**
 * 文件处理
 * @author Administrator
 *
 */
class Stat
{
    private $json_stat = [
        'stat_shop' => [
            'site_id' => 1,
            'year' => 0,
            'month' => 0,
            'day' => 0,
            'day_time' => 0,
            'order_total' => 0.00,
            'shipping_total' => 0.00,
            'refund_total' => 0.00,
            'order_pay_count' => 0,
            'goods_pay_count' => 0,
            'shop_money' => 0,
            'platform_money' => 0,
            'create_time' => 0,
            'modify_time' => 0,
            'collect_shop' => 0,
            'collect_goods' => 0,
            'visit_count' => 0,
            'order_count' => 0,
            'goods_count' => 0,
            'add_goods_count' => 0,
            'member_count' => 0,
            'order_member_count' => 0,
            'order_refund_count' => 0,
            'order_refund_grand_count' => 0,
            'order_refund_grand_total_money' => 0.00,
            'coupon_member_count' => 0,
            'member_level_count' => 0,
            'member_level_total_money' => 0.00,
            'member_level_grand_count' => 0,
            'member_level_grand_total_money' => 0.00,
            'member_recharge_count' => 0,
            'member_recharge_grand_count' => 0.00,
            'member_recharge_total_money'=> 0.00,
            'member_recharge_grand_total_money' => 0.00,
            'member_recharge_member_count' => 0,
            'member_giftcard_count' => 0,
            'member_giftcard_grand_count' => 0,
            'member_giftcard_total_money' => 0.00,
            'h5_visit_count' => 0,
            'wechat_visit_count' => 0,
            'weapp_visit_count' => 0,
            'pc_visit_count' => 0,
            'expected_earnings_total_money' => 0.00,
            'expenditure_total_money' => 0.00,
            'earnings_total_money' => 0.00,
            'member_withdraw_count' => 0,
            'member_withdraw_total_money' => 0.00,
            'coupon_count' => 0,
            'add_coupon_count' => 0,
            'order_pay_money' => 0.00,
            'add_fenxiao_member_count' => 0,
            'fenxiao_order_total_money' => 0.00,
            'fenxiao_order_count' => 0,
            'goods_on_type_count' => 0,
            'goods_visited_type_count' => 0,
            'goods_order_type_count' => 0,
            'goods_exposure_count' => 0,
            'goods_visit_count' => 0,
            'goods_visit_member_count' => 0,
            'goods_cart_count' => 0,
            'goods_order_count' => 0.000,
            'order_create_money' => 0.00,
            'order_create_count' => 0,
            'balance_deduction' => 0.00,
            'cashier_billing_count' => 0,
            'cashier_billing_money' => 0.00,
            'cashier_buycard_count' => 0,
            'cashier_buycard_money' => 0.00,
            'cashier_recharge_count' => 0,
            'cashier_recharge_money' => 0.00,
            'cashier_refund_count' => 0,
            'cashier_refund_money' => 0.00,
            'cashier_order_member_count' => 0,
            'cashier_balance_money' => 0.00,
            'cashier_online_pay_money' => 0.00,
            'cashier_online_refund_money' => 0.00,
            'cashier_balance_deduction' => 0.00
        ],
        'stat_shop_hour' => [
            'site_id' => 1,
            'year' => 0,
            'month' => 0,
            'day' => 0,
            'hour' => 0,
            'day_time' => 0,
            'order_total' => 0.00,
            'shipping_total' => 0.00,
            'refund_total' => 0.00,
            'order_pay_count' => 0,
            'goods_pay_count' => 0,
            'shop_money' => 0,
            'platform_money' => 0,
            'create_time' => 0,
            'modify_time' => 0,
            'collect_shop' => 0,
            'collect_goods' => 0,
            'visit_count' => 0,
            'order_count' => 0,
            'goods_count' => 0,
            'add_goods_count' => 0,
            'member_count' => 0,
            'order_member_count' => 0,
            'order_refund_count' => 0,
            'order_refund_grand_count' => 0,
            'order_refund_grand_total_money' => 0.00,
            'coupon_member_count' => 0,
            'member_level_count' => 0,
            'member_level_total_money' => 0.00,
            'member_level_grand_count' => 0,
            'member_level_grand_total_money' => 0.00,
            'member_recharge_count' => 0,
            'member_recharge_grand_count' => 0.00,
            'member_recharge_total_money'=> 0.00,
            'member_recharge_grand_total_money' => 0.00,
            'member_recharge_member_count' => 0,
            'member_giftcard_count' => 0,
            'member_giftcard_grand_count' => 0,
            'member_giftcard_total_money' => 0.00,
            'h5_visit_count' => 0,
            'wechat_visit_count' => 0,
            'weapp_visit_count' => 0,
            'pc_visit_count' => 0,
            'expected_earnings_total_money' => 0.00,
            'expenditure_total_money' => 0.00,
            'earnings_total_money' => 0.00,
            'member_withdraw_count' => 0,
            'member_withdraw_total_money' => 0.00,
            'coupon_count' => 0,
            'add_coupon_count' => 0,
            'order_pay_money' => 0.00,
            'add_fenxiao_member_count' => 0,
            'fenxiao_order_total_money' => 0.00,
            'fenxiao_order_count' => 0,
            'goods_on_type_count' => 0,
            'goods_visited_type_count' => 0,
            'goods_order_type_count' => 0,
            'goods_exposure_count' => 0,
            'goods_visit_count' => 0,
            'goods_visit_member_count' => 0,
            'goods_cart_count' => 0,
            'goods_order_count' => 0.000,
            'order_create_money' => 0.00,
            'order_create_count' => 0,
            'balance_deduction' => 0.00,
            'cashier_billing_count' => 0,
            'cashier_billing_money' => 0.00,
            'cashier_buycard_count' => 0,
            'cashier_buycard_money' => 0.00,
            'cashier_recharge_count' => 0,
            'cashier_recharge_money' => 0.00,
            'cashier_refund_count' => 0,
            'cashier_refund_money' => 0.00,
            'cashier_order_member_count' => 0,
            'cashier_balance_money' => 0.00,
            'cashier_online_pay_money' => 0.00,
            'cashier_online_refund_money' => 0.00,
            'cashier_balance_deduction' => 0.00
        ],
        'stat_store' => [
            'site_id' => 1,
            'store_id' => 0,
            'year'  => 0,
            'month' => 0,
            'day' => 0,
            'day_time' => 0,
            'billing_count' => 0,
            'billing_money' => 0.00,
            'buycard_count' => 0,
            'buycard_money' => 0.00,
            'recharge_count' => 0,
            'recharge_money' => 0.00,
            'refund_count' => 0,
            'refund_money' => 0.00,
            'order_member_count' => 0,
            'balance_money' => 0.00,
            'online_pay_money' => 0.00,
            'online_refund_money' => 0.00,
            'balance_deduction' => 0.00,
        ],
        'stat_store_hour' => [
            'site_id' => 1,
            'store_id' => 0,
            'year'  => 0,
            'month' => 0,
            'day' => 0,
            'hour' => 0,
            'day_time' => 0,
            'billing_count' => 0,
            'billing_money' => 0.00,
            'buycard_count' => 0,
            'buycard_money' => 0.00,
            'recharge_count' => 0,
            'recharge_money' => 0.00,
            'refund_count' => 0,
            'refund_money' => 0.00,
            'order_member_count' => 0,
            'balance_money' => 0.00,
            'online_pay_money' => 0.00,
            'online_refund_money' => 0.00,
            'balance_deduction' => 0.00,
        ]
    ];
    private $filename;
    private $json_date;

    public function __construct($filename, $type) {
        $this->filename = $filename;
        $this->json_date = $this->json_stat[$type];
    }

    /**
     * 不存在文件，创建
     */
    public function check()
    {
        if(!file_exists($this->filename)){
        }else{
        }
    }

    /**
     * 储存文件
     * @param $data
     */
    public function saveFile($data) {
        $jsonData = json_encode($data);
        while (!empty(cache($this->filename."_lock"))){}
        cache($this->filename."_lock", 1);
        //修改内容
        file_put_contents($this->filename, $jsonData);
        cache($this->filename."_lock", 0);
    }

    /**
     * 读取文件
     * @return mixed
     */
    public function load() {
        $jsonData = file_get_contents($this->filename);
        $data = json_decode($jsonData, true);
        return $data;
    }

    /**
     * 数据处理
     * @param $data
     */
    public function handleData($data)
    {
        $file_data = $this->json_date;

        foreach ($data as $key => $val){
            $file_data[$key] = $val;
        }

        $carbon = Carbon::now();
        $file_data['year'] = $carbon->year;
        $file_data['month'] = $carbon->month;
        $file_data['day'] = $carbon->day;
        $file_data['day_time'] = time();
        if (isset($file_data['hour'])) $file_data['hour'] = $carbon->hour;

        $this->saveFile($file_data);
    }
}