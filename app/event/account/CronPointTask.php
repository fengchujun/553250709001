<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\event\account;

use app\model\account\Point as PointModel;

/**
 * 登录成功发送通知
 */
class CronPointTask
{
    public function handle($param)
    {
        return (new PointModel())->execPointTaskCron($param['relate_id']);
    }
}