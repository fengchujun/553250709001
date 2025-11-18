<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecommend\event;

use addon\memberrecommend\model\MemberRecommend;

/**
 * 注册成功发放奖励
 */
class MemberRegister
{

    public function handle($param)
    {
        $memberrecommend = new MemberRecommend();
        $res = $memberrecommend->receiveAward($param);
    }

}