<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\stat;

use app\model\BaseModel;
use Carbon\Carbon;

use extend\Stat;
use think\facade\Db;
use think\facade\Log;

/**
 * 统计
 * @author Administrator
 *
 */
class StatShop extends BaseModel
{
    /**
     * 添加店铺统计(按照天统计)
     * @param array $data
     */
    public function addShopStat($data)
    {
        $carbon = Carbon::now();
        $dir = __UPLOAD__.'/stat/stat_shop/';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return $this->error(sprintf('Directory "%s" was not created', $dir));
        }
        $filename = $dir.$carbon->year.'_'.$carbon->month.'_'.$carbon->day.'_'.$carbon->second.'_'.unique_random().'.json';
        $stat_extend = new Stat($filename, 'stat_shop');
        $stat_extend->handleData($data);//写入文件

        //增加当天时统计
        $this->addShopHourStat($data, $carbon);
        return $this->success();
    }

    public function cronShopStat()
    {
        $path = __UPLOAD__.'/stat/stat_shop';
        if(!is_dir($path)) return;

        $result = $this->scanFile($path);
        if(empty($result)) return;

        try {
            $json_array = [];
            foreach ($result as $key => $val){
                $stat_extend = new Stat($path.'/'.$val, 'stat_shop');
                $json_array[] = $stat_extend->load();
                unlink($path.'/'.$val);
            }


            $data_array = [];
            foreach ($json_array as $json_k => $json_v){
                $k = $json_v['year'].'_'.$json_v['month'].'_'.$json_v['day'];
                if (isset($data_array[$k])){
                    foreach ($data_array[$k] as $data_k => $data_v){
                        if($data_k != 'site_id' && $data_k != 'year' && $data_k != 'month' && $data_k != 'day' && $data_k != 'day_time'){
                            if ($json_v[$data_k] > 0) {
                                $data_array[$k][$data_k] += $json_v[$data_k];
                            } else if ($json_v[$data_k] < 0) {
                                $data_array[$k][$data_k] -= abs($json_v[$data_k]);
                            }
                        }
                    }
                }else{
                    $data_array[$k] = $json_v;
                }
            }
            Log::write(time().'stat_shop_'.json_encode($data_array));
            $system_stat = new \app\model\system\Stat();
            foreach ($data_array as $json_k => $json_v){
                $system_stat->addStatShopModel($json_v);
            }
        } catch (\Exception $e) {

        }

    }

    /**
     * 增加当日的时统计记录
     * @param $data
     * @param $carbon
     * @return array
     */
    public function addShopHourStat($data, $carbon)
    {
        $dir = __UPLOAD__.'/stat/stat_shop_hour/';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return $this->error(sprintf('Directory "%s" was not created', $dir));
        }
        $filename = $dir.$carbon->year.'_'.$carbon->month.'_'.$carbon->day.'_'.$carbon->hour.'_'.$carbon->second.'_'.unique_random().'.json';
        $stat_extend = new Stat($filename, 'stat_shop_hour');
        $stat_extend->handleData($data);//写入文件
        return $this->success();
    }

    public function cronShopStatHour()
    {
        $path = __UPLOAD__.'/stat/stat_shop_hour';
        if(!is_dir($path)) return;

        $result = $this->scanFile($path);
        if(empty($result)) return;

        try {
            $json_array = [];
            foreach ($result as $key => $val){
                $stat_extend = new Stat($path.'/'.$val, 'stat_shop_hour');
                $json_array[] = $stat_extend->load();
                unlink($path.'/'.$val);
            }

            $data_array = [];
            foreach ($json_array as $json_k => $json_v){
                $k = $json_v['year'].'_'.$json_v['month'].'_'.$json_v['day'];
                if (isset($data_array[$k])){
                    foreach ($data_array[$k] as $data_k => $data_v){
                        if($data_k != 'site_id' && $data_k != 'year' && $data_k != 'month' && $data_k != 'day' && $data_k != 'day_time'){
                            if ($json_v[$data_k] > 0) {
                                $data_array[$k][$data_k] += $json_v[$data_k];
                            } else if ($json_v[$data_k] < 0) {
                                $data_array[$k][$data_k] -= abs($json_v[$data_k]);
                            }
                        }
                    }
                }else{
                    $data_array[$k] = $json_v;
                }
            }
            Log::write(time().'stat_shop_hour_'.json_encode($data_array));
            $system_stat = new \app\model\system\Stat();
            foreach ($json_array as $json_k => $json_v){
                $system_stat->addStatShopHourModel($json_v);
            }
        } catch (\Exception $e) {

        }
    }


    public function scanFile($path) {
        $result = [];
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $this->scanFile($path . '/' . $file);
                } else {
                    $result[] = basename($file);
                }
            }
        }
        return $result;
    }
}