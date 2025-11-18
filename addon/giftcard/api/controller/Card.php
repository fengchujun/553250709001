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

use addon\giftcard\model\card\Card as CardModel;
use app\api\controller\BaseApi;

/**
 * 礼品卡
 */
class Card extends BaseApi
{

    /**
     * 活动详情
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $card_id = $this->params[ 'card_id' ] ?? 0;
        $card_model = new CardModel();
        $params = array (
            'site_id' => $this->site_id,
            'card_id' => $card_id
        );
        $detail = $card_model->getCardDetail($params);
        return $this->response($detail);
    }

}