<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\stat;

use app\model\stat\StatStore;
use app\model\system\Stat;

/**
 * 门店统计任务执行
 */
class CronStatCron
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {
        $id = $data['relate_id'];
        $info = model('stat_shop_cron')->getInfo([['id', '=', $id]]);
        if(empty($info)){
            return [];
        }
        $stat_model = new Stat();
        $stat_model->switchStat(['type' => $info['type'], 'data' => json_decode($info['data'], true),'async'=>false]);
        model('stat_shop_cron')->delete([['id', '=', $id]]);
    }
}