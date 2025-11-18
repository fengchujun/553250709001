<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\shop\controller;


use addon\giftcard\model\card\RealCard;
use app\Controller;

/**
 * 礼品卡批次控制器
 */
class Cardimportlog extends Controller
{
    /**
     * 录入卡项
     */
    public function cdkLog()
    {
        set_time_limit(0);
        $import_id = input('import_id', 0);
        $real_card_model = new RealCard();
        $result = $real_card_model->cdkLog([
            'import_id' => $import_id,
            'operator_data' => []
        ]);

        return $result;
    }
}