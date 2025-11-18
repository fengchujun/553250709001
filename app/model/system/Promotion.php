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

use app\model\BaseModel;
use app\model\order\Order;
use app\model\system\Config as ConfigModel;

/**
 * 活动整体管理
 */
class Promotion extends BaseModel
{
    /**
     * 获取营销活动展示
     */
    public function getPromotions($addons = [])
    {
        $show = event("ShowPromotion", []);
        $shop_promotion = [];
        foreach ($show as $k => $v) {
            if (!empty($v[ 'shop' ])) {
                if(empty($addons) || in_array($v['shop'][0]['name'], $addons)){
                    $shop_promotion = array_merge($shop_promotion, $v[ 'shop' ]);
                }
            }
        }
        return [
            'shop' => $shop_promotion
        ];
    }

    /**
     * 获取站点营销活动展示
     * @param $site_id
     * @return array
     */
    public function getSitePromotions($site_id, $addons = [])
    {
        $show = event("ShowPromotion", []);
        $promotion = [];
        foreach ($show as $k => $v) {
            if (!empty($v[ 'shop' ])) {
                if(empty($addons) || in_array($v['shop'][0]['name'], $addons)){
                    $promotion = array_merge($promotion, $v[ 'shop' ]);
                }
            }
        }
        return $promotion;
    }


    /**
     * 获取营销类型
     */
    public function getPromotionType()
    {
        $promotion_type = event("PromotionType");
        $promotion_type[] = [ "type" => "empty", "name" => "无营销活动" ];
        return $promotion_type;
    }

    /**
     * 获取营销活动总数
     */
    public function getPromotionCount($site_id)
    {
        $show = event("ShowPromotion", [ 'count' => 1, 'site_id' => $site_id ]);
        $count = 0;
        foreach ($show as $k => $v) {
            if (!empty($v[ 'shop' ])) {
                $summary = $v[ 'shop' ][ 'summary' ] ?? [];
                if (!empty($summary)) {
                    $count += $summary[ 'count' ];
                }
            }
            if (!empty($v[ 'member' ])) {
                $summary = $v[ 'member' ][ 'summary' ] ?? [];
                if (!empty($summary)) {
                    $count += $summary[ 'count' ];
                }
            }

        }
        return $count;
    }

    /**
     * 输入时间查看活动营销概况
     * @param $start_time
     * @param $end_time
     * @param $site_id
     * @return array
     */
    public function getPromotionSummary($start_time, $end_time, $site_id, $addons = [])
    {
        $summary = event("ShowPromotion", [ 'summary' => 1, 'start_time' => $start_time, 'end_time' => $end_time, 'site_id' => $site_id, 'promotion_tyye' => 'time_limit' ]);
        $promotion = [
            'time_limit' => [], // 限时类活动
        ];
        foreach ($summary as $k => $v) {
            $shop = $v[ 'shop' ][ 0 ] ?? [];
            if (empty($shop)) continue;
            if(empty($addons) || in_array($shop['name'], $addons)) {
                $summary_v = $shop['summary'] ?? [];
                if (isset($summary_v['time_limit'])) {
                    unset($shop['summary']);
                    array_push($promotion['time_limit'], array_merge($shop, $summary_v['time_limit']));
                }
            }
        }
        return $this->success($promotion);
    }

    /**
     * 设置活动专区页面配置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setPromotionZoneConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $config_key = strtoupper($data[ 'name' ]) . '_ZONE_CONFIG';
        $res = $config->setConfig($data, $data[ 'title' ] . '活动专区页面配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', $config_key ] ]);
        return $res;
    }

    /**
     * 获取活动专区页面配置
     * @param $name
     * @param $config_key
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getPromotionZoneConfig($name, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $config_key = strtoupper($name) . '_ZONE_CONFIG';
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', $config_key ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $promotion_zone_config = event('PromotionZoneConfig', [ 'name' => $name ], true);
            $res[ 'data' ][ 'value' ] = $promotion_zone_config[ 'value' ] ?? '';
        }
        return $res;
    }

    /**
     * 获取营销配置
     * @param $start_time
     * @param $end_time
     * @param $site_id
     * @return array
     */
    public function getPromotionConfig($start_time, $end_time, $site_id, $addons = [])
    {
        $promotion = event("ShowPromotion", [
            'summary' => 1,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'site_id' => $site_id,
            'promotion_type' => 'unlimited_time'
        ]);

        $promotion_data = [];
        foreach ($promotion as $key => $val){
            if(!empty($val[ 'shop' ][ 0 ])){
                if(empty($addons) || in_array($val['shop'][0]['name'], $addons)){
                    $shop = $val[ 'shop' ][0] ?? [];
                    if(empty($shop['summary'])){
                        unset($promotion[$key]);
                    }else{
                        array_push($promotion_data, array_merge($shop, $shop['summary']['unlimited_time']));
                    }
                }
            }
        }
        return $this->success($promotion_data);
    }


    /**
     * 获取营销数据统计
     * @param $start_time
     * @param $end_time
     * @param $site_id
     * @return array
     */
    public function  getPromotionStat($start_time, $end_time, $site_id)
    {
        $promotion = event("ShowPromotion", [
            'summary' => 1,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'site_id' => $site_id
        ]);

        $promotion_data = [
            'promotion_num' => 0, // 活动数量
            'in_progress_num' => 0, // 进行中活动数量,
            'order_num' => 0
        ];

        foreach ($promotion as $k => $v) {
            $shop = $v[ 'shop' ][ 0 ] ?? [];
            if (empty($shop)) continue;
            $promotion_data[ 'promotion_num' ] += 1;

            $summary_v = $shop[ 'summary' ] ?? [];
            if (isset($summary_v[ 'time_limit' ])) {
                $promotion_data[ 'in_progress_num' ] += $summary_v[ 'time_limit' ][ 'count' ];
            }
        }

        $promotion_data['order_num'] = ( new Order() )->getOrderCount([ [ 'site_id', '=', $site_id ], [ 'promotion_type', '<>', '' ], [ 'pay_status', '=', 1 ] ], 'order_id')[ 'data' ];

        return $this->success($promotion_data);
    }

    public function  getUnlimitedTimePromotion()
    {
        // 不限时类的活动
    }

    /**
     * 获取推广二维码
     * @param $param
     * @return array
     */
    public function getPromotionQrcode($param)
    {
        try{
            $page_name = $param['page_name'];
            $option = $param['option'];
            $app_type = $param['app_type'];
            $site_id = $param['site_id'];

            //找到页面配置
            $event_res = event('PromotionPage');
            $page_info = null;
            foreach($event_res as $list){
                foreach ($list as $info){
                    if($page_name == $info['name']){
                        $page_info = $info;
                        break 2;
                    }
                }
            }
            if(empty($page_info)){
                return $this->error(null, '页数数据有误');
            }

            //二维码名称
            $qrcode_name_arr = [];
            foreach ($option as $key=>$val){
                $qrcode_name_arr[] = $key.'_'.$val;
            }
            $qrcode_name = join('_', $qrcode_name_arr);

            //路径数据
            $wap_url = $page_info['wap_url'];
            $url_data = parse_url($wap_url);
            parse_str($url_data['query'], $query_data);
            foreach ($query_data as $key=>$val){
                if(!isset($option[$key])) return $this->error(null, 'option缺少'.$key.'参数');
                $query_data[$key] = $option[$key];
            }

            $params = [
                'site_id' => $site_id,
                'data' => $query_data,
                'page' => $url_data['path'],
                'app_type' => $app_type,
                'qrcode_path' => 'upload/qrcode/'.strtolower($page_name),
                'qrcode_name' => $qrcode_name,
            ];

            $res = event('PromotionQrcode', $params, true);
            return $this->success($res);
        }catch(\Exception $e){
            return $this->error([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ], '生成二维码失败');
        }
    }
}