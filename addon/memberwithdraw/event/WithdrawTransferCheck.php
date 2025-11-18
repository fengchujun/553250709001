<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberwithdraw\event;

use addon\memberwithdraw\model\Withdraw;


/**
 * 提现转账通知
 */
class WithdrawTransferCheck
{
    public function handle($param)
    {
        if($param['from_type'] == 'member_withdraw'){
            $model = new Withdraw();
            return $model->transferCheck($param['relate_tag']);
        }
    }
}