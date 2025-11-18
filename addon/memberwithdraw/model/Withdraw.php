<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberwithdraw\model;

use app\model\BaseModel;
use app\model\member\Withdraw as MemberWithdraw;
use app\model\system\PayTransfer;

/**
 * 会员提现
 */
class Withdraw extends BaseModel
{

    /**
     * 转账
     * @param $condition
     */
    public function transfer($id)
    {
        $withdraw_model = new MemberWithdraw();
        $info_result = $withdraw_model->getMemberWithdrawInfo([ [ "id", "=", $id ] ], "withdraw_no,account_number,realname,money,memo,transfer_type,site_id,applet_type,member_id,status");
        if (empty($info_result[ "data" ]))
            return $this->error();

        $info = $info_result[ "data" ];
        if (!in_array($info[ "transfer_type" ], [ "wechatpay", "alipay" ]))
            return $this->error('', "当前提现方式不支持在线转账");

        $pay_transfer_model = new PayTransfer();
        $transfer_res = $pay_transfer_model->transfer('member_withdraw', $id);
        return $transfer_res;
    }

    /**
     * 转账结果通知
     * @param $param
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function transferNotify($param)
    {
        $id = $param['relate_tag'];
        $site_id = $param['site_id'];
        $withdraw_model = new MemberWithdraw();
        $withdraw_info = $withdraw_model->getMemberWithdrawInfo([
            ['id', '=', $id ],
            ['site_id', '=', $site_id],
        ], "*")['data'];
        if(empty($withdraw_info)){
            return $this->error(null, '提现信息有误');
        }

        //成功的处理
        switch($param['status']){
            case PayTransfer::STATUS_IN_PROCESS:
                return $withdraw_model->transferInProcess([
                    'id' => $withdraw_info['id'],
                    'site_id' => $withdraw_info['site_id'],
                ]);
                break;
            case PayTransfer::STATUS_SUCCESS:
                return $withdraw_model->transferFinish([
                    'id' => $withdraw_info['id'],
                    'site_id' => $withdraw_info['site_id'],
                ]);
                break;
            case PayTransfer::STATUS_FAIL:
                $resp_data = json_decode($param['resp_data'], true);
                $fail_reason = $resp_data['fail_reason'] ?? '';
                return $withdraw_model->transferFail([
                    'id' => $withdraw_info['id'],
                    'site_id' => $withdraw_info['site_id'],
                    'fail_reason' => $fail_reason,
                ]);
                break;
            default:
                return $this->error(null, '转账结果状态有误');
        }
    }


    /**
     * 转账检测
     * @param $id
     */
    public function transferCheck($id)
    {
        $withdraw_model = new MemberWithdraw();
        $info_result = $withdraw_model->getMemberWithdrawInfo([ [ "id", "=", $id ] ], "withdraw_no,account_number,realname,money,memo,transfer_type,status");
        if (empty($info_result["data"]))
            return $this->error(null, '提现信息缺失');

        $info = $info_result["data"];
        if(!in_array($info["transfer_type"], ["wechatpay","alipay"]))
            return $this->error('', "当前提现方式不支持在线转账");
        if($info['status'] != $withdraw_model::STATUS_WAIT_TRANSFER){
            return $this->error('', "当前提现单非待转账状态");
        }
        return $this->success();
    }
}