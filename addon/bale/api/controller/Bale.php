<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bale\api\controller;

use app\api\controller\BaseApi;
use addon\bale\model\Bale as BaleModel;

/**
 * 打包一口价
 */
class Bale extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {
        $bale_id = $this->params['bale_id'] ?? 0;
        if (empty($bale_id)) {
            return $this->response($this->error('', 'REQUEST_BALE_ID'));
        }

        $bale_model = new BaleModel();
        $info = $bale_model->getBaleDetail($bale_id, $this->site_id);
        return $this->response($info);
    }

}