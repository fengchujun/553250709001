<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace app\api\controller;


/**
 * 订单创建
 */
class BaseOrderCreateApi extends BaseApi
{
    /**
     * 公共参数
     * @return array
     */
    public function getCommonParam()
    {
        return [
            'site_id' => $this->site_id,
            'store_id' => $this->params['store_id'] ?? 0,
            'member_id' => $this->member_id,
            'order_from' => $this->params['app_type'],
            'order_from_name' => $this->params['app_type_name'],
            'sale_channel' => 'all,online',
        ];
    }

    /**
     * 获取发票参数
     * @return array
     */
    public function getInvoiceParam()
    {
        return [
            'is_invoice' => $this->params['is_invoice'] ?? 0,
            'invoice_type' => $this->params['invoice_type'] ?? 0,
            'invoice_title' => $this->params['invoice_title'] ?? '',
            'taxpayer_number' => $this->params['taxpayer_number'] ?? '',
            'invoice_content' => $this->params['invoice_content'] ?? '',
            'invoice_full_address' => $this->params['invoice_full_address'] ?? '',
            'is_tax_invoice' => $this->params['is_tax_invoice'] ?? 0,
            'invoice_email' => $this->params['invoice_email'] ?? '',
            'invoice_title_type' => $this->params['invoice_title_type'] ?? 0,
        ];
    }

    /**
     * 获取配送相关参数
     * @return array
     */
    public function getDeliveryParam()
    {
        if(isset($this->params['delivery']) && !is_string($this->params['delivery'])) $this->params['delivery'] = '';
        if(!empty($this->params['member_address']) && is_array($this->params['member_address'])){
            $this->params['member_address'] = json_encode($this->params['member_address']);
        };
        $data = [
            //运费相关
            'delivery' => isset($this->params['delivery']) && !empty($this->params['delivery']) ? json_decode($this->params['delivery'], true) : [],
            'member_address' => isset($this->params['member_address']) && !empty($this->params['member_address']) ? json_decode($this->params['member_address'], true) : [],
            'latitude' => $this->params['latitude'] ?? '',
            'longitude' => $this->params['longitude'] ?? '',
        ];
        return $data;
    }

    /**
     * 传入参数
     * @return array
     */
    public function getInputParam()
    {
        return [
            //留言
            'buyer_message' => $this->params[ 'buyer_message' ] ?? '',
            //自定义表单
            'form_data' => isset($this->params[ 'form_data' ]) && !empty($this->params[ 'form_data' ]) ? json_decode($this->params[ 'form_data' ], true) : [],
        ];
    }
}