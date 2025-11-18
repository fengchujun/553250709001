<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\store;

use app\model\BaseModel;
use app\model\stat\StatStore;
use Carbon\Carbon;
use think\db\exception\DbException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use app\model\system\Stat as SystemStat;

/**
 * 统计
 * @author Administrator
 *
 */
class Stat extends BaseModel
{
    /**
     * 添加店铺统计(按照天统计)
     * @param array $data
     */
    public function addStoreStat($data)
    {
        return (new StatStore())->addStoreStat($data);
//        Log::write('addStoreStat' . '-' . date('y-m-d H:i:s', time()) . '-' . json_encode($data));
//        $site_id = $data[ 'site_id' ];
//        $store_id = $data[ 'store_id' ] ?? 0;
//        $carbon = Carbon::now();
//        $condition = [
//            'site_id' => $site_id,
//            'year' => $carbon->year,
//            'month' => $carbon->month,
//            'day' => $carbon->day,
//            'store_id' => $store_id,
//        ];
//
//        $info = model('stat_store')->getInfo($condition, 'id');
//
//        //在这里会整体处理总支出   总收入
//
//        $stat_data = $this->getStatData($data);
//        if (empty($info)) {
//            $insert_data = [
//                'site_id' => $site_id,
//                'store_id' => $store_id,
//                'year' => $carbon->year,
//                'month' => $carbon->month,
//                'day' => $carbon->day,
//                'day_time' => time(),
//                'create_time' => time()
//            ];
//            $insert_data = array_merge($insert_data, $stat_data);
//            $res = model('stat_store')->add(
//                $insert_data
//            );
//
//        } else {
//            $update_data = array ();
//            if (!empty($stat_data)) {
//                foreach ($stat_data as $k => $v) {
//                    if ($v > 0) {
//                        $update_data[ $k ] = Db::raw($k . '+' . $v);
//                    } else if ($v < 0) {
//                        $update_data[ $k ] = Db::raw($k . '-' . abs($v));
//                    }
//                }
//            }
//            if (!empty($update_data)) {
//                $res = Db::name('stat_store')->where($condition)
//                    ->update($update_data);
//                Log::write('addStoreStat' . Db::name('stat_store')->getLastSql());
//                Cache::tag("cache_table" . "stat_store")->clear();
//
//            }
//        }
//        //增加当天时统计
//        $this->addShopHourStat($data, $carbon);
//        // 添加店铺统计
//        $shop_stat = [
//            'site_id' => $site_id
//        ];
//        foreach ($stat_data as $k => $value) {
//            $shop_stat[ 'cashier_' . $k ] = $value;
//        }
//        ( new SystemStat() )->addShopStat($shop_stat);
//        return $this->success($res ?? 0);

    }

    /**
     * 增加当日的时统计记录
     * @param $data
     * @param $carbon
     * @return array
     * @throws DbException
     */
    public function addShopHourStat($data, $carbon)
    {
        $site_id = $data[ 'site_id' ];
        $store_id = $data[ 'store_id' ] ?? 0;
        $condition = [
            'site_id' => $site_id,
            'store_id' => $store_id,
            'year' => $carbon->year,
            'month' => $carbon->month,
            'day' => $carbon->day,
            'hour' => $carbon->hour
        ];
        $info = model('stat_store_hour')->getInfo($condition, 'id');

        //在这里会整体处理总支出   总收入  总预计收入

        $stat_data = $this->getStatData($data);

        if (empty($info)) {
            $insert_data = [
                'site_id' => $site_id,
                'store_id' => $store_id,
                'year' => $carbon->year,
                'month' => $carbon->month,
                'day' => $carbon->day,
                'day_time' => time(),
                'create_time' => time(),
                'hour' => $carbon->hour
            ];
            $insert_data = array_merge($insert_data, $stat_data);
            $res = model('stat_store_hour')->add(
                $insert_data
            );

        } else {
            $update_data = array ();
            if (!empty($stat_data)) {
                foreach ($stat_data as $k => $v) {
                    if ($v > 0) {
                        $update_data[ $k ] = Db::raw($k . '+' . $v);
                    } else if ($v < 0) {
                        $update_data[ $k ] = Db::raw($k . '-' . abs($v));
                    }
                }
            }
            if (!empty($update_data)) {
                $res = Db::name('stat_store_hour')->where($condition)
                    ->update($update_data);
                Cache::tag("cache_table" . "stat_store_hour")->clear();
            }

        }
        return $this->success($res ?? 0);
    }

    /**
     * 整理数据
     * @param $data
     * @return mixed
     */
    public function getStatData($data)
    {
        unset($data[ 'site_id' ]);
        unset($data[ 'store_id' ]);
        $data = array_filter($data);
        return $data;
    }

    /**
     * 获取店铺统计（按照天查询）
     * @param unknown $site_id 0表示平台
     * @param unknown $year
     * @param unknown $month
     * @param unknown $day
     */
    public function getStatShop($site_id, $year, $month, $day, $store_id = 0)
    {
        $condition = [
            'site_id' => $site_id,
            'year' => $year,
            'month' => $month,
            'day' => $day
        ];
        if (!empty($store_id)) $condition[ 'store_id' ] = $store_id;

        $info = model('stat_store')->setIsCache(0)->getInfo($condition);

        if (empty($info)) {
            $condition[ 'day_time' ] = strtotime(date("{$year}-{$month}-{$day}"));
            model('stat_store')->add($condition);
            $info = model('stat_store')->getInfo($condition);
        }
        return $this->success($info);
    }

    /**
     * 获取数据之和
     * @param $site_id
     * @param $start_time
     * @param $end_time
     * @param int $store_id
     * @return array
     */
    public function getShopStatSum($site_id, $start_time, $end_time, $store_id = 0)
    {
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'day_time', '>=', $start_time ],
            [ 'day_time', '<=', $end_time ],
        ];
        if (!empty($store_id)) {
            $condition[] = [ 'store_id', '=', $store_id ];
        }
        $field = array_map(function($field) {
            return "ifnull(sum($field), 0) as $field";
        }, $this->getStatField());
        $field[] = $this->getEstimatedRevenueSum();
        $list = model('stat_store')->getInfo($condition, implode(',', $field));
        return $this->success($list);
    }

    /**
     * 获取预计收入总和
     */
    private function getEstimatedRevenueSum()
    {
        return 'ifnull(sum(billing_money) + sum(buycard_money) + sum(recharge_money) - sum(refund_money), 0) as expected_earnings_total_money';
    }

    /**
     * 获取预计收入
     */
    private function getEstimatedRevenue()
    {
        return 'billing_money + buycard_money + recharge_money - refund_money as expected_earnings_total_money';
    }

    /**
     * 获取店铺统计列表
     * @param $site_id
     * @param $start_time
     * @param $end_time
     * @param int $store_id
     * @return array
     */
    public function getShopStatList($site_id, $start_time, $end_time, $store_id = 0)
    {
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'day_time', '>=', $start_time ],
            [ 'day_time', '<=', $end_time ],
        ];
        if (!empty($store_id)) {
            $condition[] = [ 'store_id', '=', $store_id ];
        }
        $field = '*,' . $this->getEstimatedRevenue();
        $list = model('stat_store')->getList($condition, $field);
        return $this->success($list);
    }

    /**
     * 获取小时统计数据
     * @param $site_id
     * @param $year
     * @param $month
     * @param $day
     * @param int $store_id
     * @return array
     */
    public function getShopStatHourList($site_id, $year, $month, $day, $store_id = 0)
    {
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'year', '=', $year ],
            [ 'month', '=', $month ],
            [ 'day', '=', $day ],
        ];
        if (!empty($store_id)) $condition[] = [ 'store_id', '=', $store_id ];
        $field = '*,' . $this->getEstimatedRevenue();
        $list = model('stat_store_hour')->getList($condition, $field, 'id desc');
        return $this->success($list);
    }

    /**
     * 获取天统计表统计字段
     * @return array
     */
    public function getStatField()
    {
        $fields = Db::name('stat_store')->getTableFields('');
        $fields = array_values(array_diff($fields, [ 'id', 'site_id', 'year', 'month', 'day', 'day_time', 'store_id' ]));
        return $fields;
    }

    /**
     * 获取时统计表统计字段
     * @return array
     */
    public function getStatHourField()
    {
        $fields = Db::name('stat_store_hour')->getTableFields('');
        $fields = array_values(array_diff($fields, [ 'id', 'site_id', 'year', 'month', 'day', 'hour', 'day_time', 'store_id' ]));
        return $fields;
    }

    /**
     * 统计入库(按天)
     * @param $data
     */
    public function addStatStoreModel($data)
    {
        $condition = [
            'site_id' => $data['site_id'],
            'store_id' => $data['store_id'],
            'year' => $data['year'],
            'month' => $data['month'],
            'day' => $data['day']
        ];
        $info = model('stat_store')->getInfo($condition, 'id');
        if(empty($info)){
            model('stat_store')->add($data);
        }else{
            $update_data = [];

            if(isset($data['site_id'])) unset($data['site_id']);
            if(isset($data['store_id'])) unset($data['store_id']);
            if(isset($data['year'])) unset($data['year']);
            if(isset($data['month'])) unset($data['month']);
            if(isset($data['day'])) unset($data['day']);
            if(isset($data['day_time'])) unset($data['day_time']);

            foreach ($data as $k => $v) {
                if ($v > 0) {
                    $update_data[ $k ] = Db::raw($k . '+' . $v);
                } else if ($v < 0) {
                    $update_data[ $k ] = Db::raw($k . '-' . abs($v));
                }
            }
            model('stat_store')->update($update_data, $condition);
        }
    }

    /**
     * 统计入库(按时)
     * @param $data
     */
    public function addStatStoreHourModel($data)
    {
        $condition = [
            'site_id' => $data['site_id'],
            'store_id' => $data['store_id'],
            'year' => $data['year'],
            'month' => $data['month'],
            'day' => $data['day'],
            'hour' => $data['hour']
        ];
        $info = model('stat_store_hour')->getInfo($condition, 'id');
        if(empty($info)){
            model('stat_store_hour')->add($data);
        }else{
            $update_data = [];

            if(isset($data['site_id'])) unset($data['site_id']);
            if(isset($data['store_id'])) unset($data['store_id']);
            if(isset($data['year'])) unset($data['year']);
            if(isset($data['month'])) unset($data['month']);
            if(isset($data['day'])) unset($data['day']);
            if(isset($data['hour'])) unset($data['hour']);
            if(isset($data['day_time'])) unset($data['day_time']);

            foreach ($data as $k => $v) {
                if ($v > 0) {
                    $update_data[ $k ] = Db::raw($k . '+' . $v);
                } else if ($v < 0) {
                    $update_data[ $k ] = Db::raw($k . '-' . abs($v));
                }
            }
            model('stat_store_hour')->update($update_data, $condition);
        }
    }
}