<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\event\message;

use app\model\member\Withdraw;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 会员提现成功发送消息
 */
class messageUserWithdrawalSuccess
{
    /**
     * @param $param
     * @return void|null
     * @throws GuzzleException
     */
    public function handle($param)
    {
        //发送订单消息
        if ($param["keywords"] == "USER_WITHDRAWAL_SUCCESS") {
            $model = new Withdraw();
            return $model->messageUserWithdrawalSuccess($param);
        }
    }

}