<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\express\LocalPackage;
use app\model\message\Message;
use app\model\system\Cron;
use Exception;
use think\facade\Db;
use think\facade\Queue;

/**
 * 订单任务事件
 * @author Administrator
 */
class OrderCron extends BaseModel
{
    public static function close($data){
        $order_id = $data['order_id'];
        $now_time = time();
        $order_config = Config::getOrderConfig($data['site_id']);
        if ($order_config['auto_close'] > 0) {
            $execute_time = $now_time + $order_config['auto_close'] * 60; //自动关闭时间
        }
        $cron_model = new Cron();
        $cron_model->addCron(1, 0, '订单自动关闭', 'CronOrderClose', $execute_time, $order_id);
        return true;
    }


    public function urgepayment(){

    }

    /**
     * 自动完成事件
     * @param $data
     * @return array
     */
    public static function complete($data){
        $order_id = $data['order_id'];
        $site_id = $data['site_id'];
        //获取订单自动完成时间
        $order_config = Config::getOrderConfig($site_id);
        $now_time = time();
        if (!empty($order_config)) {
            $execute_time = $now_time + $order_config[ 'auto_complete' ] * 86400;//自动完成时间
        } else {
            $execute_time = $now_time + 86400;//尚未配置  默认一天
        }
        //设置订单自动完成事件
        $cron_model = new Cron();
        $cron_model->addCron(1, 0, '订单自动完成', 'CronOrderComplete', $execute_time, $order_id);
        return true;
    }

    /**
     * 关闭售后
     * @param $data
     * @return void
     */
    public static function afterSaleClose($data){
        $after_sales_time = $data['after_sales_time'];
        $order_id = $data['order_id'];
        $cron = new Cron();
        $execute_time = strtotime("+ {$after_sales_time} day");
        $cron->addCron(1, 0, '订单售后自动关闭', 'CronOrderAfterSaleClose', $execute_time, $order_id);
        return true;
    }

    /**
     * 自动收货
     * @param $data
     * @return true
     */
    public static function takeDelivery($data){
        $order_id = $data['order_id'];
        $site_id = $data['site_id'];
        $expire_time = $data['expire_time'] ?? 0;
        if($expire_time == 0){
            // 获取订单自动收货时间
            $order_config = Config::getOrderConfig($site_id);
            $now_time = time(); //当前时间
            if ($order_config['auto_take_delivery'] > 0) {
                $execute_time = $now_time + $order_config['auto_take_delivery'] * 86400; // 自动收货时间
            }
        }
        if(!empty($execute_time)){
            $cron_model = new Cron();
            $cron_model->addCron(1, 0, '订单自动收货', 'CronOrderTakeDelivery', $execute_time, $order_id);
        }
        return true;
    }
}