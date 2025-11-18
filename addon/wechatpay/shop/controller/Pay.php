<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\shop\controller;

use addon\wechatpay\model\Config as ConfigModel;
use app\model\upload\Upload;
use app\shop\controller\BaseShop;
use think\facade\Config;

/**
 * 支付 控制器
 */
class Pay extends BaseShop
{
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $appid = input("appid", "");//公众账号ID
            $mch_id = input("mch_id", "");//商户号
            $pay_signkey = input("pay_signkey", "");//支付签名串API密钥
            $apiclient_cert = input("apiclient_cert", "");//支付证书cert
            $apiclient_key = input("apiclient_key", "");//支付证书key
            $pay_status = input("pay_status", 0);//支付启用状态
            $refund_status = input("refund_status", 0);//退款启用状态
            $transfer_status = input("transfer_status", 0);//转账启用状态
            $transfer_type = input("transfer_type", 'v2');
            $api_type = input("api_type", 'v2');
            $v3_pay_signkey = input('v3_pay_signkey', '');
            $plateform_certificate_serial = input('plateform_certificate_serial', '');
            $plateform_certificate = input('plateform_certificate', '');
            $transfer_v3_type = input('transfer_v3_type','');

            $member_withdraw_scene = input('member_withdraw_scene',''); //转账场景编号
            $store_withdraw_scene = input('store_withdraw_scene','');
            $fenxiao_withdraw_scene = input('fenxiao_withdraw_scene','');

            $member_withdraw_code = input('member_withdraw_code',''); //转账场景ID
            $store_withdraw_code= input('store_withdraw_code','');
            $fenxiao_withdraw_code = input('fenxiao_withdraw_code','');

            $member_withdraw_recv = input('member_withdraw_recv',''); //转账场景说明
            $store_withdraw_recv= input('store_withdraw_recv','');
            $fenxiao_withdraw_recv = input('fenxiao_withdraw_recv','');

            $transfer_info = $config_model->getTransferSceneInfo(request()->all());

            $data = array (
                "appid" => $appid,
                "mch_id" => $mch_id,
                "pay_signkey" => $pay_signkey,
                "apiclient_cert" => $apiclient_cert,
                "apiclient_key" => $apiclient_key,
                "refund_status" => $refund_status,
                "pay_status" => $pay_status,
                "transfer_status" => $transfer_status,
                'transfer_type' => $transfer_type,
                'plateform_cert' => '',
                'plateform_certificate' => $plateform_certificate,
                'plateform_certificate_serial' => $plateform_certificate_serial,
                'api_type' => $api_type,
                'v3_pay_signkey' => $v3_pay_signkey,
                'transfer_v3_type'=>$transfer_v3_type,
                'member_withdraw_scene'=>$member_withdraw_scene,
                'store_withdraw_scene'=>$store_withdraw_scene,
                'fenxiao_withdraw_scene'=>$fenxiao_withdraw_scene,
                'member_withdraw_code'=>$member_withdraw_code,
                'store_withdraw_code'=>$store_withdraw_code,
                'fenxiao_withdraw_code'=>$fenxiao_withdraw_code,
                'member_withdraw_info' => $transfer_info['member_withdraw_info'],
                'fenxiao_withdraw_info'=>  $transfer_info['fenxiao_withdraw_info'],
                'store_withdraw_info' =>  $transfer_info['store_withdraw_info'],
                'member_withdraw_recv'=>$member_withdraw_recv,
                'store_withdraw_recv'=>$store_withdraw_recv,
                'fenxiao_withdraw_recv'=>$fenxiao_withdraw_recv,
            );

            $result = $config_model->setPayConfig($data, $this->site_id, $this->app_module);
            return $result;
        } else {
            $info = $config_model->getPayConfig($this->site_id, $this->app_module, true)[ 'data' ][ 'value' ];

            $this->assign("info", $info);
            $this->assign("scene_config",$config_model->getTransferSceneConfig());

            return $this->fetch("pay/config");
        }
    }

    /**
     * 上传微信支付证书
     */
    public function uploadWechatCert()
    {
        $upload_model = new Upload();
        $site_id = request()->siteid();
        $name = input("name", "");
        $extend_type = [ 'pem' ];
        $param = array (
            "name" => "file",
            "extend_type" => $extend_type
        );

        $site_id = max($site_id, 0);
        $result = $upload_model->setPath("common/wechat/cert/" . $site_id . "/")->file($param);
        return $result;
    }
}