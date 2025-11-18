<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace addon\giftcard\event;

use addon\giftcard\model\card\CardOperation;
use addon\giftcard\model\giftcard\GiftCard;

/**
 * 礼品卡过期(轮询 一天一次)
 */
class CronCardExpire
{

    public function handle($params)
    {
        $card_operation_model = new CardOperation();
        $card_condition = [
            [ 'valid_time', 'between', [ 1, time() ] ]
        ];
        $card_ids = model('giftcard_card')->getColumn($card_condition, 'card_id');
        $res = $card_operation_model->cardExpire([ 'card_ids' => $card_ids ]);

        // 礼品卡到期
        $gift_card_model = new GiftCard();
        $gift_card_model->modifyStatus(0, [
            [ 'validity_type', '=', 'date' ],
            [ 'validity_time', '<', time() ]
        ]);

        return $res;
    }

}