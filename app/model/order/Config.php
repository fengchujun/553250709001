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

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;
use app\model\system\Document;

/**
 * 订单交易设置
 */
class Config extends BaseModel
{
    /**
     * 获取订单事件时间设置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderEventTimeConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_EVENT_TIME_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                'auto_close' => 30,//订单未付款自动关闭时间 数字 单位(分钟)
                'auto_take_delivery' => 14,//订单发货后自动收货时间 数字 单位(天)
                'auto_complete' => 7,//订单收货后自动完成时间 数字 单位(天)
                'after_sales_time' => 0,//订单完成后可维权时间 数字 单位(天)
                'invoice_status' => 0,//发票状态（0关闭 1开启）
                'invoice_rate' => 0,//发票比率（0关闭 1开启）
                'invoice_content' => '',//发内容（0关闭 1开启）
                'invoice_money' => 0,//发票运费（0关闭 1开启）
            ];
        }
        $res['data']['value']['invoice_type'] = $res['data']['value']['invoice_type'] ?? '1,2';
        return $res;
    }

    /**
     * 专用于订单事件相关的配置
     * @return void
     */
    public static function getOrderConfig($site_id = 1){
        $config_model = new Config();
        $order_config = $config_model->getOrderEventTimeConfig($site_id)['data'] ?? [];
        return $order_config['value'] ?? [];
    }
    /**
     * 设置订单事件时间
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setOrderEventTimeConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '订单事件时间设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_EVENT_TIME_CONFIG']]);
    }

    /**
     * 获取订单返积分设置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderBackPointConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_BACK_POINT_CONFIG']]);
    }

    /**
     * 设置订单返积分
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setOrderBackPointConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '订单返积分设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_BACK_POINT_CONFIG']]);
    }

    /**
     * 获取订单评价设置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderEvaluateConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_EVALUATE_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                'evaluate_status' => 1,//订单评价状态（0关闭 1开启）
                'evaluate_show' => 1,//显示评价（0关闭 1开启）
                'evaluate_audit' => 1,//评价审核状态（0关闭 1开启）
            ];
        }
        return $res;
    }

    /**
     * 设置订单评价设置
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setOrderEvaluateConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '订单事件时间设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_EVALUATE_CONFIG']]);
    }

    /**
     * 设置余额支付配置
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setBalanceConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '余额支付配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'BALANCE_SHOW_CONFIG']]);
    }

    /**
     * 获取余额支付配置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getBalanceConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'BALANCE_SHOW_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                'balance_show' => 1 //余额支付配置（0关闭 1开启）
            ];
        }
        return $res;
    }

    /**
     * 设置订单提醒配置
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setOrderRemindConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '订单提醒配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_REMIND_CONFIG']]);
    }

    /**
     * 获取订单提醒配置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderRemindConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_REMIND_CONFIG']]);
        $res['data']['value'] = assignData($res['data']['value'], [
            'order_pay_audio' => 'public/static/audio/order_pay_remind.mp3',
            'cashier_order_pay_audio' => 'public/static/audio/cashier_order_pay_remind.mp3',
        ]);
        return $res;
    }

    /**
     * 获取订单退款配置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderRefundConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_REFUND_CONFIG']]);
        $res['data']['value'] = assignData($res['data']['value'], [
            'reason_type' => "未按约定时间发货\n拍错/多拍/不喜欢\n协商一致退款\n其他",
            'auto_refund'=> $res['data']['value']['auto_refund'] ?? 0
        ]);
        return $res;
    }

    /**
     * 设置订单退款配置
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setOrderRefundConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '订单退款配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_REFUND_CONFIG']]);
    }

    /**
     * 订单核销设置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setOrderVerifyConfig($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        return $config->setConfig($data, '核销到期提醒', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_VERIFY_CONFIG']]);
    }

    /**
     * 订单核销设置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getOrderVerifyConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'ORDER_VERIFY_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                'order_verify_time_out' => 1,//核销临期提醒时间
            ];
        }
        return $res;
    }

    /**
     * 注册协议
     * @param $title
     * @param $content
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setTransactionDocument($title, $content, $site_id, $app_module = 'shop')
    {
        $document = new Document();
        return $document->setDocument($title, $content, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['document_key', '=', 'TRANSACTION_AGREEMENT']]);
    }

    /**
     * 查询注册协议
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getTransactionDocument($site_id, $app_module = 'shop')
    {
        $document = new Document();
        return $document->getDocument([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['document_key', '=', 'TRANSACTION_AGREEMENT']]);
    }
}