<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberwithdraw\shop\controller;

use addon\memberwithdraw\model\Withdraw as WithdrawModel;
use app\shop\controller\BaseShop;

/**
 * 会员提现
 */
class Withdraw extends BaseShop
{

    /**
     * 转账
     */
    public function transfer()
    {
        if (request()->isJson()) {
            $id = input('id', 0);
            $withdraw_model = new WithdrawModel();
            $result = $withdraw_model->transfer($id);
            return $result;
        }
    }

}