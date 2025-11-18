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

use app\model\stat\StatShop;

/**
 *  店铺小时统计(计划任务)
 */
class CronStatShopHour
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {
        $shop_stat = new StatShop();
        $shop_stat->cronShopStatHour();
    }
}