<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\api\controller;

use addon\giftcard\model\card\CardOperation;
use app\api\controller\BaseApi;

/**
 * 礼品卡使用
 */
class Carduse extends BaseApi
{

    /**
     *  储值礼品卡使用
     */
    public function balanceUse()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $member_card_id = $this->params[ 'member_card_id' ] ?? 0;
        $card_operation_model = new CardOperation();
        $params = array (
            'site_id' => $this->site_id,
            'member_id' => $this->member_id,
            'member_card_id' => $member_card_id
        );
        $result = $card_operation_model->cardUse($params);
        return $this->response($result);
    }

}