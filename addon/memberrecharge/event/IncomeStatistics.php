<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberrecharge\event;

use addon\memberrecharge\model\MemberrechargeOrder;

/**
 * 资金收入统计
 */
class IncomeStatistics
{
    public function handle($param)
    {
        $money = (new MemberrechargeOrder())->getOrderSum([ ['site_id', '=', $param['site_id'] ], ['pay_time', 'between', [$param['start_time'], $param['end_time']] ] ], 'buy_price')['data'];
        return [
            [
                'title' => '会员充值',
                'value' => $money,
                'desc' => '统计时间内，所有会员充值金额之和',
                'url' => 'memberrecharge://shop/memberrecharge/orderlists'
            ]
        ];
    }
}