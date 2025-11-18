<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\supermember\event;

use addon\supermember\model\MemberLevelOrder;

/**
 * 资金收入统计
 */
class IncomeStatistics
{
    public function handle($param)
    {
        $money = (new MemberLevelOrder())->getOrderSum([ ['site_id', '=', $param['site_id'] ], ['pay_type', '<>', 'BALANCE'], ['pay_time', 'between', [$param['start_time'], $param['end_time']] ] ], 'order_money')['data'];
        return [
            [
                'title' => '会员开卡',
                'value' => $money,
                'desc' => '统计时间内，所有会员开卡支付金额之和',
                'url' => 'supermember://shop/membercard/order'
            ]
        ];
    }
}