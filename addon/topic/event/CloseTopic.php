<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\topic\event;

use addon\topic\model\Topic;

/**
 * 关闭活动
 */
class CloseTopic
{

    public function handle($params)
    {
        $topic = new Topic();
        $res   = $topic->cronCloseTopic($params['relate_id']);
        return $res;
    }
}