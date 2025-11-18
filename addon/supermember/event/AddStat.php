<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace addon\supermember\event;

use addon\supermember\model\MemberLevelStat;

/**
 * 礼品卡统计
 */
class AddStat
{

    public function handle($params)
    {
        $type = $params['type'];
        if($type == 'member_level_order'){
            $member_level_stat_model = new MemberLevelStat();
            $res = $member_level_stat_model->addMemberLevelStat($params['data']);
            return $res;
        }

    }

}