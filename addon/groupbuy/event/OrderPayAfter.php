<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\groupbuy\event;

use addon\groupbuy\model\Groupbuy;

/**
 * 活动展示
 */
class OrderPayAfter
{

    /**
     * 活动展示
     *
     * @return multitype:number unknown
     */
    public function handle($param)
    {
        if ($param['promotion_type'] == 'groupbuy') {
            $model = new Groupbuy();
            $res           = $model->orderPay($param);
            return $res;
        }
    }
}