<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace app\api\controller;

use app\model\store\Store as StoreModel;
use extend\api\HttpClient;
use app\model\web\Config as ConfigModel;
use app\model\goods\Goods;

/**
 * 门店
 * @author Administrator
 *
 */
class Store extends BaseApi
{

    /**
     * 列表信息
     */
    public function page()
    {

        $latitude = $this->params['latitude'] ?? null; // 纬度
        $longitude = $this->params['longitude'] ?? null; // 经度
        $keyword = $this->params['keyword'] ?? ''; // 搜索关键词
        $status = $this->params['status'] ?? 'all';//营业状态
        $store_ids = $this->params['store_ids'] ?? '';//指定门店id
        if($store_ids == 'undefined') $store_ids = 'all';

        $store_model = new StoreModel();
        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'is_frozen', '=', 0],
        ];

        //营业状态
        if(!in_array($status, [1,0,'all'])) $status = 1;
        if($status != 'all'){
            $condition[] = ['status', '=', $status];
        }else{
            $condition[] = ['status|close_show', '=', 1];
        }
        //指定门店
        if(!empty($store_ids) && $store_ids != 'all'){
            $condition[] = ['store_id', 'in', $store_ids];
        }

        if (!empty($keyword)) {
            $condition[] = [ 'store_name', 'like', '%' . paramFilter($keyword) . '%' ];
        }

        $latlng = [
            'lat' => $latitude,
            'lng' => $longitude,
        ];
        $field = '*';
        $list_result = $store_model->getLocationStoreList($condition, $field, $latlng);

        $list = $list_result[ 'data' ];

        if (!empty($longitude) && !empty($latitude) && !empty($list)) {
            foreach ($list as $k => $item) {
                if ($item[ 'longitude' ] && $item[ 'latitude' ]) {
                    $distance = getDistance((float) $item[ 'longitude' ], (float) $item[ 'latitude' ], (float) $longitude, (float) $latitude);
                    $list[ $k ][ 'distance' ] = $distance / 1000;
                } else {
                    $list[ $k ][ 'distance' ] = 0;
                }
            }
            // 按距离就近排序
            array_multisort(array_column($list, 'distance'), SORT_ASC, $list);
        }

        $default_store_id = 0;
        if (!empty($list)) {
            $default_store_id = $list[ 0 ][ 'store_id' ];
        }
        return $this->response($this->success([ 'list' => $list, 'store_id' => $default_store_id ]));
    }

    /**
     * 获取离自己最近的一个门店
     */
    public function nearestStore()
    {
        $this->initStoreData();

        $latitude = $this->params['latitude'] ?? null; // 纬度
        $longitude = $this->params['longitude'] ?? null; // 经度
        $store_model = new StoreModel();
        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'status', '=', 1 ],
            [ 'is_frozen', '=', 0 ]
        ];

//        if ($this->store_data[ 'config' ][ 'store_business' ] == 'shop') {
//            // 平台运营模式，直接取默认门店
//            if (!empty($this->store_data[ 'store_info' ])) {
//                return $this->response($this->success($this->store_data[ 'store_info' ]));
//            }
//        }
        if (in_array($this->store_data[ 'config' ][ 'store_business' ],['store','shop'])) {
            // 连锁门店运营模式，查询距离自己最近的门店
            $latlng = [
                'lat' => $latitude,
                'lng' => $longitude,
            ];
            $field = '*';
            $list = $store_model->getLocationStoreList($condition, $field, $latlng)[ 'data' ];

            if (!empty($longitude) && !empty($latitude) && !empty($list)) {
                foreach ($list as $k => $item) {
                    if ($item[ 'longitude' ] && $item[ 'latitude' ]) {
                        $distance = getDistance((float) $item[ 'longitude' ], (float) $item[ 'latitude' ], (float) $longitude, (float) $latitude);
                        $list[ $k ][ 'distance' ] = $distance / 1000;
                    } else {
                        $list[ $k ][ 'distance' ] = 0;
                    }
                }
                // 按距离就近排序
                array_multisort(array_column($list, 'distance'), SORT_ASC, $list);
                return $this->response($this->success($list[ 0 ]));
            }

        }
        return $this->response($this->error());

    }

    /**
     * 基础信息
     * @return false|string
     */
    public function info()
    {
        $store_id = $this->params['store_id'] ?? 0;
        $latitude = $this->params['latitude'] ?? null; // 纬度
        $longitude = $this->params['longitude'] ?? null; // 经度

        if (empty($store_id)) {
            return $this->response($this->error('', 'REQUEST_STORE_ID'));
        }
        $condition = [
            [ 'store_id', '=', $store_id ],
            [ 'site_id', '=', $this->site_id ],
        ];

        $latitude = paramFilter($latitude);
        $longitude = paramFilter($longitude);

        $latlng = [
            'lat' => $latitude,
            'lng' => $longitude,
        ];

        $store_model = new StoreModel();
        $field = 'store_id,store_name,telphone,store_image,site_id,address,full_address,longitude,latitude,open_date,label_name,store_images,store_introduce,is_default,is_express,is_pickup,is_o2o,is_frozen,status,close_desc';
        if (!empty($latlng[ 'lat' ]) && !empty($latlng[ 'lng' ])) {
            $field .= ',FORMAT(st_distance ( point ( ' . $latlng[ 'lng' ] . ', ' . $latlng[ 'lat' ] . ' ), point ( longitude, latitude ) ) * 111195 / 1000, 2) as distance';
        }
        $res = $store_model->getStoreInfo($condition, $field);
        if (!empty($res[ 'data' ])) {
            if (!empty($res[ 'data' ][ 'store_images' ])) {
                $res[ 'data' ][ 'store_images' ] = ( new Goods() )->getGoodsImage(explode(',', $res[ 'data' ][ 'store_images' ]), $this->site_id)[ 'data' ];
            }
        }
        return $this->response($res);
    }

    /**
     * 根据经纬度获取位置
     * @return false|string
     */
    public function getLocation()
    {
        $latitude = $this->params['latitude'] ?? null; // 纬度
        $longitude = $this->params['longitude'] ?? null; // 经度

        $qq_map = new \app\model\map\QqMap();
        $res = $qq_map->locationToDetail([
            'location' => $latitude . ',' . $longitude,
        ]);
        if ($res[ 'status' ] == 0) {
            $result = $res[ 'result' ];
            return $this->response($this->success($result));
        } else {
            return $this->response($this->error('', $res[ 'message' ]));
        }
    }

    /**
     * 分页查询门店
     * @return false|string
     */
    public function getStorePage()
    {
        $latitude = $this->params['latitude'] ?? null; // 纬度
        $longitude = $this->params['longitude'] ?? null; // 经度
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $type = $this->params['type'] ?? '';
        $store_ids = $this->params['store_ids'] ?? '';//指定门店id

        $store_model = new StoreModel();

        if($type == 'local'){
            $store_condition = [
                ['site_id', '=', $this->site_id],
            ];
            if (addon_is_exit('store', $this->site_id)) {
                if(empty($store_ids)){
                    $store_condition[] = ['is_o2o', '=', 1];
                    $store_condition[] = ['status', '=', 1];
                }
                $store_condition[] = ['is_frozen', '=', 0];
            } else {
                $store_condition[] = ['is_default', '=', 1];
            }
        }else{
            $store_condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'is_frozen', '=', 0 ],
            ];
            if(empty($store_ids)){
                $store_condition[] = ['is_pickup', '=', 1];
                $store_condition[] = ['status', '=', 1];
            }
        }
        //指定门店
        if(!empty($store_ids) && $store_ids != 'all'){
            $store_condition[] = ['store_id', 'in', $store_ids];
        }
        $latlng = [
            'lat' => $latitude,
            'lng' => $longitude,
        ];
        $store_list = $store_model->getLocationStorePageList($store_condition, $page, $page_size, '*', $latlng);
        return $this->response($store_list);
    }
}