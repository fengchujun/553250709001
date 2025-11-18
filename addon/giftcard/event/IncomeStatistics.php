<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\event;

use addon\giftcard\model\order\GiftCardOrder;

/**
 * 资金收入统计
 */
class IncomeStatistics
{
    public function handle($param)
    {
        $money = ( new GiftCardOrder() )->getOrderSum(
            [
                [ 'go.site_id', '=', $param[ 'site_id' ] ],
                [ 'go.pay_time', 'between', [ $param[ 'start_time' ], $param[ 'end_time' ] ] ]
            ], 'p.pay_money', 'go', [
            [
                'pay p', 'go.out_trade_no = p.out_trade_no', 'left'
            ]
        ])[ 'data' ];
        return [
            [
                'title' => '礼品卡订单',
                'value' => $money,
                'desc' => '统计时间内，所有礼品卡订单金额之和',
                'url' => 'giftcard://shop/order/order',
            ]
        ];
    }
}