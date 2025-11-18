<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * 4.0.1升级测试
 */

namespace addon\memberregister\event;

use addon\memberregister\model\Register as RegisterModel;
use app\model\member\MemberAccount as MemberAccountModel;
use addon\coupon\model\Coupon;

/**
 * 会员注册奖励
 */
class MemberRegisterAward
{
    /**
     * @param $param
     * @return array|\multitype
     */
    public function handle($param)
    {
        $register_model = new RegisterModel();
        $register_config = $register_model->getConfig($param[ 'site_id' ])[ 'data' ];

        if ($register_config[ 'is_use' ]) {
            return $register_config[ 'value' ];
        }else{
            return [];
        }

    }

}