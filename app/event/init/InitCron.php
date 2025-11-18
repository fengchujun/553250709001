<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\init;

use app\dict\system\ScheduleDict;
use app\model\system\Cron;
use think\facade\Cache;

/**
 * 初始化计划任务启动
 * @author Administrator
 *
 */
class InitCron
{
    public function handle()
    {
        //根据计划任务类型来判断
        if(config('cron.default') != ScheduleDict::default) return;
        if (defined('BIND_MODULE') && BIND_MODULE === 'install') {
            return;
        }
        $last_time = Cache::get("cron_last_load_time");
        if (empty($last_time)) {
            $last_time = 0;
        }
        $last_exec_time = Cache::get("cron_http_last_exec_time");
        if (empty($last_exec_time)) {
            $last_exec_time = 0;
        }
        $module = request()->module();
        if ($module != 'cron') {
            if (!defined('CRON_EXECUTE') && time() - $last_time > 100 && time() - $last_exec_time > 100) {
                Cache::set("cron_http_last_exec_time", time());
                defined('CRON_EXECUTE') or define('CRON_EXECUTE', 1);
                $url = url('cron/task/cronExecute');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_exec($ch);

//                // 获取错误信息并打印
//                $error = curl_error($ch);
//                if($error){
//                    //保存错误
//                    Cron::setError(ScheduleDict::default, $error);
//                }
//                // 关闭cURL资源句柄
//                curl_close($ch);
            }
        }
    }

}
