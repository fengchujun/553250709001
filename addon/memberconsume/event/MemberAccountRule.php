<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberconsume\event;

use addon\memberconsume\model\Consume;

/**
 * 会员账户变化规则
 */
class MemberAccountRule
{

    public function handle($data)
    {
        $config = new Consume();
        $info   = $config->getConfig($data['site_id']);
        $return = [];
            if ($info['data']['is_use'] == 1) {
                $return['point'] = "会员消费，订单支付返积分,比率" . $info['data']['value']['return_point_rate'] . "%";
                $return['growth'] = "会员消费订单支付返成长值,比率" . $info['data']['value']['return_growth_rate'] . "%";
            }

        return $return;

    }
}