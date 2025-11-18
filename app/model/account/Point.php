<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\account;

use app\model\account\Point as PointModel;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;

/**
 * 积分管理
 */
class Point extends BaseModel
{

    /**
     * 积分清零
     * @param $params
     * @return array
     */
    public function pointClear($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        try {
            set_time_limit(0);
            $condition = array (
                [ 'point', '>', 0 ]
            );
            if ($site_id > 0) {
                $condition[] = [ 'site_id', '=', $site_id ];
            }
            $list = model('member')->getList($condition, 'member_id,site_id, point');
            if (empty($list)) {
                return $this->success();
            }
            $member_account_model = new MemberAccount();
            $remark = empty($params[ 'remark' ]) ? '积分清零' : $params[ 'remark' ];
            foreach ($list as $k => $val) {
                $member_account_model->addMemberAccount($val[ 'site_id' ], $val[ 'member_id' ], "point", -$val[ 'point' ], 'point_set_zero', 0, $remark);
            }
            return $this->success();
        } catch (\Exception $e) {

            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 积分重置
     * @param $params
     * @return array
     */
    public function pointReset($params)
    {
        $site_id = $params[ 'site_id' ];
        //会员积分清零
        $condition = array (
            [ 'point', '<>', 0 ]
        );
        $common_condition = [];
        if ($site_id > 0) {
            $common_condition[] = [ 'site_id', '=', $site_id ];
        }
        $member_data = array (
            'point' => 0
        );
        model('member')->update($member_data, array_merge($condition, $common_condition));
        //会员积分记录清空删除
        $member_account_condition = array (
            [ 'account_type', '=', 'point' ]
        );
        model('member_account')->delete(array_merge($member_account_condition, $common_condition));
        return $this->success();
    }

    /**
     * 积分任务
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getPointTaskConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'POINT_TASK_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'status' => 0,//1开启 0关闭
                'type' => 'clear',//任务类型 clear 清零 reset 重置
                'time' => '1/1',//1每年1月1日
                'time_type' => 1,//时间类型 1每年
            ];
        }

        return $res;
    }

    /**
     * 会员配置
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setPointTaskConfig($data, $site_id)
    {
        //处理积分任务时间
        $res = $this->dealWithPointTaskCronTime($data);
        if($res['code'] < 0) return $res;
        $data = $res['data'];

        $config = new ConfigModel();
        $res = $config->setConfig($data, '积分配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'POINT_TASK_CONFIG' ] ]);

        //更新定时任务
        $this->updatePointTaskCron($site_id);
        return $res;
    }

    public function dealWithPointTaskCronTime($value)
    {
        if($value['status'] == 1){
            switch($value['time_type']){
                case 1:
                    $year = date('Y');
                    list($month,$day) = explode('/', $value['time']);
                    $this_year_time = strtotime("{$year}-{$month}-{$day}");
                    if($this_year_time > time()){
                        $cron_time = $this_year_time;
                    }else{
                        $cron_time = strtotime("+1 year", $this_year_time);
                    }
                    break;
                default:
                    return $this->error(null, '时间类型有误');
            }
            $value['cron_time'] = $cron_time;
        }
        return $this->success($value);
    }

    /**
     * 更新积分定时任务
     * @param $site_id
     * @param $app_module
     * @return void
     */
    public function updatePointTaskCron($site_id)
    {
        $value = $this->getPointTaskConfig($site_id)['data']['value'];
        $cron = new Cron();
        $cron->deleteCron([['event', '=', 'CronPointTask'], ['relate_id', '=', $site_id]]);
        if($value['status'] == 1){
            switch($value['time_type']){
                case 1:
                    $year = date('Y');
                    list($month,$day) = explode('/', $value['time']);
                    $this_year_time = strtotime("{$year}-{$month}-{$day}");
                    if($this_year_time > time()){
                        $cron_time = $this_year_time;
                    }else{
                        $cron_time = strtotime("+1 year", $this_year_time);
                    }
                    break;
            }
            if(!empty($cron_time)){
                $cron->addCron(1, 0, "定时积分任务", "CronPointTask", $cron_time, $site_id);
            }
        }
    }

    /**
     * 执行积分定时任务
     * @param $site_id
     * @return array
     */
    public function execPointTaskCron($site_id)
    {
        $point_model = new PointModel();
        $value = $this->getPointTaskConfig($site_id)['data']['value'];
        $res = $this->success();
        if($value['status'] == 1){
            switch($value['type']){
                case 'clear':
                    $res = $point_model->pointClear([
                        'site_id' => $site_id,
                        'remark' => '积分定时清零',
                    ]);
                    break;
                case 'reset':
                    $res = $point_model->pointReset([
                        'site_id' => $site_id,
                    ]);
                    break;
            }
        }
        if($res['code'] < 0) return $res;

        //重设配置
        $res = $this->setPointTaskConfig($value, $site_id);
        return $res;
    }
}