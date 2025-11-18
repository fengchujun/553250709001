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
use app\model\member\Member;
use app\model\system\Pay as PayModel;

/**
 * 小程序虚拟发货
 * 支付后立即调用发货接口，微信会提示订单不存在，所以延迟一分钟执行，如果是
 * {"errcode":10060001,"errmsg":"支付单不存在 rid: 66235dcf-4803e8cf-5c30a69e"}
 */
class WeappVirtualDelivery
{
    public function handle($param)
    {
        return $this->delivery($param['relate_id']);
    }

    protected function delivery($out_trade_no)
    {
        //获取支付信息
        $pay_model = new PayModel();
        $pay_info = $pay_model->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)) return success();
        if ($pay_info[ 'pay_type' ] != 'wechatpay') return success();

        // 检测微信小程序是否已开通发货信息管理服务
        $weapp_model = new Weapp($pay_info[ 'site_id' ]);
        $is_trade_managed = $weapp_model->orderShippingIsTradeManaged()['data'];
        if (!$is_trade_managed) return $weapp_model->success();

        //用户信息
        $member_service = new Member();
        $member_info = $member_service->getMemberInfo([
            [ 'site_id', '=', $pay_info[ 'site_id' ] ],
            [ 'member_id', '=', $pay_info[ 'member_id' ] ]
        ], 'weapp_openid')[ 'data' ];
        if(empty($member_info)) return success();

        //组装发货信息
        $shipping_list = [
            [
                'tracking_no' => '', // 物流单号，物流快递发货时必填，示例值: 323244567777 字符字节限制: [1, 128]
                'express_company' => '', // 物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填， 示例值: DHL 字符字节限制: [1, 128]
                'item_desc' => $weapp_model->handleOrderShippingItemDesc([$pay_info['pay_body']]), // 商品信息，例如：微信红包抱枕*1个，限120个字以内
                'contact' => [
                    'consignor_contact' => '',
                    'receiver_contact' => ''
                ]
            ]
        ];
        $data = [
            'site_id' => $pay_info[ 'site_id' ],
            'out_trade_no' => $pay_info[ 'out_trade_no' ],
            'logistics_type' => Weapp::LOGISTICS_TYPE_VIRTUAL, // 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式
            'delivery_mode' => Weapp::UNIFIED_DELIVERY, // 发货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）
            'shipping_list' => $shipping_list,
            'weapp_openid' => $member_info[ 'weapp_openid' ], // 用户标识，用户在小程序appid下的唯一标识。 下单前需获取到用户的Openid 示例值: oUpF8uMuAJO_M2pxb1Q9zNjWeS6o 字符字节限制: [1, 128]
            'is_all_delivered' => true
        ];
        return $weapp_model->orderShippingUploadShippingInfo($data);
    }
}