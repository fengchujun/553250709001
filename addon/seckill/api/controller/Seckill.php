<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\api\controller;

use addon\seckill\model\Seckill as SeckillModel;
use app\api\controller\BaseApi;


/**
 * 秒杀
 */
class Seckill extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {

        $today_time = strtotime(date("Y-m-d"), time());
        $time = time() - $today_time;//当日时间戳

        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'seckill_end_time', '>=', $time ]
        ];
        $order = 'seckill_start_time asc';
        $field = 'id,name,seckill_start_time,seckill_end_time';

        $seckill_model = new SeckillModel();
        $today_list = $seckill_model->getGoodsSeckillTimeList($condition, $field, $order);
        $today_list = is_array($today_list[ 'data' ]) ? array_values($today_list[ 'data' ]) : [];
        foreach ($today_list as $key => $val) {
            $val = $seckill_model->transformSeckillTime($val);
            $today_list[ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
            $today_list[ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            $today_list[ $key ][ 'type' ] = "today";
        }

        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'seckill_end_time', '<', $time ]
        ];

        $tomorrow_list = $seckill_model->getGoodsSeckillTimeList($condition, $field, $order);
        $tomorrow_list = is_array($tomorrow_list[ 'data' ]) ? array_values($tomorrow_list[ 'data' ]) : [];
        foreach ($tomorrow_list as $key => $val) {
            $val = $seckill_model->transformSeckillTime($val);
            $tomorrow_list[ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
            $tomorrow_list[ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            $tomorrow_list[ $key ][ 'type' ] = "tomorrow";
        }

        $res = [
            'list' => array_merge($today_list, $tomorrow_list)
        ];
        return $this->response($this->success($res));
    }
}