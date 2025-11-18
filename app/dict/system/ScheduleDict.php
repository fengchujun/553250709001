<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\system;


/**
 * 计划任务属性
 */
class ScheduleDict
{
    const default = 'default';
    const url = 'url';
    const cli = 'cli';

    /**
     * 计划任务类型
     * @param $type
     * @return string|string[]
     */
    public static function getType($type = ''){
        $list = [
            self::default => '系统任务',
            self::url => '接口启动',
            self::cli => '命令启动',
        ];
        if($type) return $list[$type] ?? '';
        return $list;
    }

    /**
     * 获取错误列表
     * @param $code
     * @return void
     */
    public static function getError($code = ''){
        $list = array(
            self::default => [
                'curl_ssl_error' => '',
            ],
            self::url => [

            ],
            self::cli => [

            ],
        );
        if($code) return $list[$code] ?? '';
        return $list;
    }
}
