<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\divideticket\event;

use addon\divideticket\model\DivideticketFriendsGroup;

/**
 * 关闭瓜分活动
 * Class DivideticketLaunchClose
 * @package addon\divideticket\event
 */
class DivideticketLaunchClose
{
    public function handle($params)
    {
        $divideticket_friends_group = new DivideticketFriendsGroup();
        $res = $divideticket_friends_group->cronCloseDivideticketLaunchLaunch($params['relate_id']);
        return $res;
    }
}