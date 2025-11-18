<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\dict\system\ScheduleDict;
use app\model\BaseModel;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;
use think\facade\Db;

/**
 * 计划任务管理
 * @author Administrator
 *
 */
class Cron extends BaseModel
{
    //任务类型
    const TYPE_FIXED = 1;//定时
    const TYPE_LOOP = 2;//循环

    //最大错误次数
    protected $max_error_num = 3;

    public $time_diff = 60;//默认半个小时检测一次

    /**
     * 添加计划任务
     * @param int $type 任务类型  1.固定任务 2.循环任务
     * @param int $period 执行周期
     * @param string $name 任务名称
     * @param string $event 执行事件
     * @param int $execute_time 待执行时间
     * @param int $relate_id 关联id
     * @param int $period_type 周期类型
     */
    public function addCron($type, $period, $name, $event, $execute_time, $relate_id, $period_type = 0)
    {
        $data = [
            'type' => $type,
            'period' => $period,
            'period_type' => $period_type,
            'name' => $name,
            'event' => $event,
            'execute_time' => $execute_time,
            'relate_id' => $relate_id,
            'create_time' => time()
        ];
        $res = model('cron')->add($data);
        return $this->success($res);
    }

    /**
     * 删除计划任务
     * @param $condition
     * @return array
     */
    public function deleteCron($condition)
    {
        $res = model('cron')->delete($condition);
        return $this->success($res);
    }

    /**
     * 执行任务
     */
    public function execute($type = 'default')
    {
        if(config('cron.default') != $type) return true;
        Log::write('计划任务方式：'.$type);
        $this->writeSchedule();//写入计划任务标记运行

        $system_config_model = new SystemConfig();
        $config = $system_config_model->getSystemConfig()[ 'data' ] ?? [];
        $is_open_queue = $config[ 'is_open_queue' ] ?? 0;
        $query_execute_time = $is_open_queue == 1 ? time() + 60 : time();

        $type_fixed = self::TYPE_FIXED;
        $type_loop = self::TYPE_LOOP;

        $list = model('cron')->getList([
            ['execute_time', '<=', $query_execute_time],
            ['', 'exp', Db::raw("(type = {$type_fixed} and error_num < {$this->max_error_num}) or type = {$type_loop}")],
        ], '*', '', '', [], '', 150);
        $now_time = time();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                Log::write('任务信息');
                Log::write($v);
                $event_res = checkQueue($v, function($params) {
                    //加入消息队列
                    $job_handler_classname = 'Cronexecute';
                    try {
                        if ($params[ 'execute_time' ] <= time()) {
                            Queue::push($job_handler_classname, $params);
                        } else {
                            Queue::later($params[ 'execute_time' ] - time(), $job_handler_classname, $params);
                        }
                    } catch (\Exception $e) {
                        $res = $this->error($e->getMessage(), $e->getMessage());
                    }
                    return $res ?? $this->success();
                }, function($params) {
                    try {
                        $res = $this->success();
                        $res_list = event($params[ 'event' ], [ 'relate_id' => $params[ 'relate_id' ] ]);
                        foreach($res_list as $val){
                            if(isset($val['code']) && $val['code'] < 0){
                                $res = $val;
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        $res = $this->error([
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'message' => $e->getMessage(),
                        ], '定时任务执行捕获异常');
                    }

                    $data_log = [
                        'name' => $params[ 'name' ],
                        'event' => $params[ 'event' ],
                        'relate_id' => $params[ 'relate_id' ],
                        'message' => json_encode($res)
                    ];
                    $this->addCronLog($data_log);
                    return $res;
                });
                Log::write('任务结果');
                Log::write($event_res);

                //有错误更新错误次数，下次决定是否继续执行
                $event_code = $event_res[ 'code' ] ?? 0;
                if ($event_code < 0) {
                    model('cron')->update([
                        'error_num' => Db::raw("error_num + 1"),
                    ], [ [ 'id', '=', $v[ 'id' ] ] ]);
                    continue;
                }

                if ($v[ 'type' ] == self::TYPE_LOOP) {
                    //循环任务更新下次执行时间
                    $period = $v[ 'period' ] == 0 ? 1 : $v[ 'period' ];
                    switch ( $v[ 'period_type' ] ) {
                        case 0://分
                            $execute_time = $now_time + $period * 60;
                            break;
                        case 1://天
                            $execute_time = strtotime('+' . $period . 'day', $v[ 'execute_time' ]);
                            break;
                        case 2://周
                            $execute_time = strtotime('+' . $period . 'week', $v[ 'execute_time' ]);
                            break;
                        case 3://月
                            $execute_time = strtotime('+' . $period . 'month', $v[ 'execute_time' ]);
                            break;
                    }
                    if(!empty($execute_time)){
                        model('cron')->update([
                            'execute_time' => $execute_time,
                        ], [ [ 'id', '=', $v[ 'id' ] ] ]);
                    }
                } else {
                    //固定任务删除
                    model('cron')->delete([ [ 'id', '=', $v[ 'id' ] ] ]);
                }
            }
        }
        return true;
    }

    /**
     * 添加自动任务日志
     * @param $data
     * @return array
     */
    public function addCronLog($data)
    {
        // 日常不需要添加，调试使用
//        $data[ 'execute_time' ] = time();
//        model('cron_log')->add($data);
        return $this->success();
    }


    /**
     * 检测自动任务标识缓存是否已过期
     */
    public function checkCron()
    {
        $diff = $this->time_diff;
        $now_time = time();
        $cron_cache = Cache::get('cron_cache');
        if (empty($cron_cache)) {
            //todo 不存在缓存标识,并不视为任务停止
            //创建缓存标识,当前时间填充
            Cache::set('cron_cache', [ 'time' => $now_time, 'error' => '' ]);
        } else {
            $time = $cron_cache[ 'time' ];
            $error = $cron_cache[ 'error' ] ?? '';
            $attempts = $cron_cache[ 'attempts' ] ?? 0;//尝试次数
            if (!empty($error) || ( $now_time - $time ) > $diff) {
                $message = '自动任务已停止';
                if (!empty($error)) {
                    $message .= ',停止原因:' . $error;
                } else {
                    $system_config_model = new \app\model\system\SystemConfig();
                    $config = $system_config_model->getSystemConfig()[ 'data' ] ?? [];
                    $is_open_queue = $config[ 'is_open_queue' ] ?? 0;
                    if (!$is_open_queue) {//如果不是消息队列的话,可以尝试异步调用一下
                        if ($attempts < 1) {
                            Cache::set('cron_cache', [ 'time' => $now_time, 'error' => '', 'attempts' => 1 ]);
                            $url = url('cron/task/execute');
                            http($url, 1);
                            return $this->success();
                        }
                    } else {
                        //消息队列无法启动,应该在前端引导跳转到官方的手册
                    }
                }
                //判断任务是 消息队列自动任务,还是默认睡眠sleep自动任务
                return $this->error([], $message);
            }

        }
        return $this->success();

    }

    /**
     * 设置自动任务
     * @param array $params
     * @return array
     */
    public function setCron($params = [])
    {
        $cron_cache = Cache::get('cron_cache');
        if (empty($cron_cache)) {
            $cron_cache = [];
        }
//        $code = $params['code'] ?? 0;
//        if($code < 0){
//            $error = $params['message'] ?? '位置的错误';
//            $cron_cache['error'] = $error;
//        }

        $cron_cache[ 'time' ] = time();
        $cron_cache[ 'attempts' ] = 0;
        Cache::set('cron_cache', $cron_cache);
        return $this->success();
    }

    /**
     * 校验计划任务是否正常运行
     * @return bool
     */
    public function checkSchedule()
    {
        $file = root_path('runtime') . '.schedule';
        if (file_exists($file)) {
            $time = file_get_contents($file);
            if (!empty($time) && abs($time - time()) < 90) {
                return $this->success();
            }
        }
        $remark = '计划任务已停止！当前启动的任务方式：'.ScheduleDict::getType(config('cron.default')).'。 <a href="https://www.kancloud.cn/niucloud/niushop_b2c_v5/3212243" target="_blank" style="margin-left: 0px">查看文档</a>';
        $error = self::getError(config('cron.default'));
        if(!empty($error)){
            $remark .= $error;
        }
        return $this->error([], $remark);
    }

    /**
     * 写入校验计划任务
     * @return true
     */
    public function writeSchedule(){
        $file = root_path('runtime').'.schedule';
        file_put_contents($file, time());
        return true;
    }

    /**
     * 保存自动任务的错误
     * @param $type
     * @param $error
     * @return true
     */
    public static function setError($type, $error = ''){
        Cache::set('cron_error', [$type => $error]);
        return true;
    }

    /**
     * 获取错误
     * @param $type
     * @return mixed
     */
    public static function getError($type = ''){
        $error = Cache::get('cron_error');
        if(!empty($type))
            return $error;
        return $error[$type];
    }
}