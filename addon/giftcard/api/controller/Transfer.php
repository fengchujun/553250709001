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

use addon\giftcard\model\transfer\Blessing;
use addon\giftcard\model\transfer\Transfer as TransferModel;
use app\api\controller\BaseApi;

/**
 * 礼品卡
 */
class Transfer extends BaseApi
{

    /**
     * 转赠
     */
    public function transfer()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $no = $this->params[ 'no' ] ?? '';
        $blessing_model = new Blessing();
        $info = $blessing_model->getMemberCardBlessingInfo([ [ 'no', '=', $no ] ])[ 'data' ] ?? [];
        $transfer_model = new TransferModel();
        $params = array (
            'site_id' => $this->site_id,
            'member_id' => $this->member_id,
            'blessing_id' => $info[ 'blessing_id' ] ?? 0
        );
        $result = $transfer_model->transfer($params);
        return $this->response($result);
    }


    public function blessingDetail()
    {
        $token = $this->checkToken();
//        if ($token['code'] < 0) return $this->response($token);

        $no = $this->params[ 'no' ] ?? '';
        $blessing_model = new Blessing();
        $params = array (
            'site_id' => $this->site_id,
            'no' => $no,
            'member_id' => $this->member_id
        );
        $result = $blessing_model->getBlessingDetail($params);

        return $this->response($result);
    }
}