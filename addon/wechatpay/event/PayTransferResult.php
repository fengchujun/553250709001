<?php
namespace addon\wechatpay\event;

use addon\wechatpay\model\Config;
use addon\wechatpay\model\V3;
use app\model\member\Withdraw;

class PayTransferResult
{
    public function handle($params)
    {
        //TODO 本地测试流程
//        if(request()->ip() == '127.0.0.1'){
//            $pay_transfer_model = new \app\model\system\PayTransfer();
//            /*return $pay_transfer_model->success([
//                'status' => $pay_transfer_model::STATUS_SUCCESS,
//            ]);*/
//            /*return $pay_transfer_model->success([
//                'status' => $pay_transfer_model::STATUS_FAIL,
//                'fail_reason' => '用户姓名校验失败',
//                'fail_code' => 'NAME_NOT_CORRECT',
//            ]);*/
//        }
        $pay_config = (new Config())->getPayConfig($params['site_id'])['data']['value'];
        $pay_config['site_id'] = $params['site_id'];
        if (!empty($pay_config) && $pay_config['transfer_v3_type'] == Config::TRANSFER_V3_TYPE_SHOP) {
            return (new V3($pay_config))->getTransferResult($params);
        }
        return (new V3($pay_config))->success();
    }
}