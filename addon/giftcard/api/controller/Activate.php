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

use addon\giftcard\model\card\RealCard;
use app\api\controller\BaseApi;


/**
 * 激活礼品卡
 */
class Activate extends BaseApi
{

    /**
     * 激活
     */
    public function activate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $card_no = $this->params[ 'card_no' ] ?? '';
        $card_cdk = $this->params[ 'card_cdk' ] ?? '';
        $realcard_model = new RealCard();
        $params = array (
            'card_no' => $card_no,
            'card_cdk' => $card_cdk,
            'member_id' => $this->member_id,
            'site_id' => $this->site_id
        );
        $result = $realcard_model->memberCardActivate($params);
        return $this->response($result);
    }

}