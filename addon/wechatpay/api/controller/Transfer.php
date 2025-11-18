<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\api\controller;

use addon\memberwithdraw\model\Withdraw as WithdrawModel;
use addon\wechat\model\Config as WechatConfig;
use addon\wechatpay\model\Config;
use addon\wechatpay\model\Pay;
use addon\wechatpay\model\TransferConfig;
use app\exception\ApiException;
use app\model\system\PayTransfer;
use think\facade\Cache;
use think\facade\Log;
use app\api\controller\BaseApi;
use addon\weapp\model\Config as WeaAppConfig;
class Transfer extends BaseApi
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 微信转账v3用户主动收款回调
     */
    public function notify(){
        $pay = new Pay();
        $pay->transferNotify();
    }


    /**
     * 发起收款
     */

    public function transfer(){

        $id = $this->params['id'] ?? ''; //提现单id
        $transfer_type = $this->params['transfer_type'] ?? ''; //提现来源

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0 && $transfer_type != 'store_withdraw') return $this->response($token);
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $cache = Cache::get('transfer:' . $id);
        if(!empty($cache)){
            return $this->response($this->error('', '系统处理中,请务重复提交'));
        }

        try{
            Cache::set("transfer:".$id,1,10);

            //判断是公众号还是小程序
            if($transfer_type != 'store_withdraw'){
                $member_model = new \app\model\member\Member();
                $member_info = $member_model->getMemberInfo([['member_id', '=', $this->member_id]], 'wx_openid,weapp_openid')['data'];
                (new PayTransfer())->editTransfer([
                    'is_weapp'=>$this->params['app_type'] == 'weapp' ? 1 : 0,
                    'account_number'=>$this->params['app_type'] == 'weapp' ? $member_info['weapp_openid'] : $member_info['wx_openid'],
                ],[['relate_tag','=',$id],['from_type','=',$transfer_type]]);
            }

            $transfer_model = new \app\model\system\PayTransfer();
            $result = $transfer_model->transfer($transfer_type, $id, true);

            Cache::delete('transfer:' . $id);
            return $this->response($result);
        }catch (ApiException $errorException){
            Cache::delete('transfer:' . $id);
            Log::write('发起收款接口异常,原因'.$errorException->getMessage());
            return $this->response($this->error([],'系统异常,请稍后再试'));
        }
    }

    /**
     * 撤销转账
     */

    public function cancel(){
        $out_trade_no = $this->params['out_trade_no'] ?? '';
        if (empty($out_trade_no)) {
            return $this->response($this->error('', '商户单号有误'));
        }
        $pay = new Pay();
        $result = $pay->transferCancel($out_trade_no);
        return $this->response($result);
    }



    /**
     * 获取转账配置
     */
    public function getWithdrawConfig(){
        $config_model = new Config();
        $config = $config_model->getPayConfig($this->site_id)[ 'data' ][ 'value' ];
        $wechat_config = (new WechatConfig())->getWechatConfig($this->site_id);
        $weapp_config_result = (new WeaAppConfig())->getWeappConfig($this->site_id);
        $data = [
            'transfer_type'=>$config['transfer_type'] == 'v3' && $config['transfer_v3_type'] == $config_model::TRANSFER_V3_TYPE_USER ? 1 : 0,
            'mch_id'=>$config['mch_id'] ?? '',
            'wechat_appid'=>$wechat_config['data']['value']['appid'] ?? '',
            'weapp_appid'=>$weapp_config_result['data']['value']['appid'] ?? '',
        ];
        return $this->response($this->success($data));
    }

    /**
     * 更改状态为转账中
     */
    public function inProcess()
    {
        $relate_tag = $this->params['relate_tag'] ?? '';
        $from_type = $this->params['from_type'] ?? '';

        $transfer_model = new \app\model\system\PayTransfer();
        $transfer_info = $transfer_model->getTransferInfo([['from_type', '=', $from_type], ['relate_tag', '=', $relate_tag]])['data'];
        $res = $transfer_model->updateStatus(['status' => $transfer_model::STATUS_IN_PROCESS], $transfer_info['id'] ?? 0);

        return $this->response($res);
    }
}