<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use app\model\express\Config as ExpressConfig;
use app\model\express\ExpressDeliver;
use app\model\express\Local as LocalModel;
use app\model\shop\Shop as ShopModel;
use app\model\store\Store as StoreModel;
use app\model\system\Address as AddressModel;
use app\model\web\Config as ConfigModel;
use app\model\web\Config as WebConfig;

/**
 * 门店
 * Class Store
 * @package app\shop\controller
 */
class Store extends BaseShop
{

    /**
     * 门店列表
     * @return mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $store_model = new StoreModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
//            $order       = input("order", "create_time desc");
            $keyword = input('search_text', '');
            $status = input('status', '');
            $type = input('type', '');

            $condition = [];
            if ($type == 1) {
                if ($status != null) {
                    $condition[] = [ 'status', '=', $status ];
                    $condition[] = [ 'is_frozen', '=', 0 ];
                }
            } else if ($type == 2) {
                $condition[] = [ 'is_frozen', '=', $status ];
            }
            $condition[] = [ 'site_id', '=', $this->site_id ];
            //关键字查询
            if (!empty($keyword)) {
                $condition[] = ['store_name', 'like', '%' . $keyword . '%'];
            }
            $order = 'is_default desc,store_id desc';
            $list = $store_model->getStorePageList($condition, $page, $page_size, $order);
            return $list;
        } else {

            //判断门店插件是否存在
            $store_is_exit = addon_is_exit('store', $this->site_id);
            $this->assign('store_is_exit', $store_is_exit);
            $this->assign('title', $store_is_exit ? '门店' : '自提点');

            return $this->fetch('store/lists');
        }
    }

    /**
     * 添加门店
     * @return mixed
     */
    public function addStore()
    {
        $is_store = addon_is_exit('store');

        if (request()->isJson()) {
            $store_name = input('store_name', '');
            $telphone = input('telphone', '');
            $store_image = input('store_image', '');
            $status = input('status', 0);
            $province_id = input('province_id', 0);
            $city_id = input('city_id', 0);
            $district_id = input('district_id', 0);
            $community_id = input('community_id', 0);
            $address = input('address', '');
            $full_address = input('full_address', '');
            $longitude = input('longitude', 0);
            $latitude = input('latitude', 0);
            $is_pickup = input('is_pickup', 0);
            $is_o2o = input('is_o2o', 0);
            $open_date = input('open_date', '');
            $start_time = input('start_time', 0);
            $end_time = input('end_time', 0);
            $time_type = input('time_type', 0);
            $time_week = input('time_week', '');
            $stock_type = input('stock_type', '');
            if (!empty($time_week)) {
                $time_week = implode(',', $time_week);
            }
            $data = array (
                'store_name' => $store_name,
                'telphone' => $telphone,
                'store_image' => $store_image,
                'status' => $status,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'community_id' => $community_id,
                'address' => $address,
                'full_address' => $full_address,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'is_pickup' => $is_pickup,
                'open_date' => $open_date,
                'site_id' => $this->site_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_type' => $time_type,
                'time_week' => $time_week,
                'stock_type' => $stock_type,
                'time_interval' => input('time_interval', 30),
                'delivery_time' => input('delivery_time', ''),
                'advance_day' => input('advance_day', 0),
                'most_day' => input('most_day', 7),
                'store_images' => input('store_images', ''),
            );

            //判断是否开启多门店
            if ($is_store == 1) {
                $user_data = [
                    'username' => input('username', ''),
                    'password' => data_md5(input('password', '')),
                ];
            } else {
                $user_data = [];
            }
            $store_model = new StoreModel();
            $result = $store_model->addStore($data, $user_data, $is_store);
            return $result;
        } else {
            //查询省级数据列表
            $address_model = new AddressModel();
            $list = $address_model->getAreaList([ ['pid', '=', 0 ], ['level', '=', 1 ] ]);
            $this->assign('province_list', $list['data']);

            $this->assign('is_exit', $is_store);

            $this->assign('title', $is_store ? '门店' : '自提点');

            $this->assign('http_type', get_http_type());

            $config_model = new ConfigModel();
            $mp_config = $config_model->getMapConfig($this->site_id);
            $this->assign('tencent_map_key', $mp_config[ 'data' ][ 'value' ][ 'tencent_map_key' ]);
            //效验腾讯地图KEY
            $check_map_key = $config_model->checkQqMapKey($mp_config[ 'data' ][ 'value' ][ 'tencent_map_key' ]);
            $this->assign('check_map_key', $check_map_key);

            $express_type = ( new ExpressConfig() )->getEnabledExpressType($this->site_id);
            if (isset($express_type[ 'express' ])) unset($express_type[ 'express' ]);
            $this->assign('express_type', $express_type);

            return $this->fetch('store/add_store');
        }
    }

    /**
     * 编辑门店
     * @return mixed
     */
    public function editStore()
    {
        $is_exit = addon_is_exit('store');
        $store_id = input('store_id', 0);
        $condition = array (
            ['site_id', '=', $this->site_id ],
            ['store_id', '=', $store_id ]
        );
        $store_model = new StoreModel();
        if (request()->isJson()) {
            $store_name = input('store_name', '');
            $telphone = input('telphone', '');
            $store_image = input('store_image', '');
            $status = input('status', 0);
            $province_id = input('province_id', 0);
            $city_id = input('city_id', 0);
            $district_id = input('district_id', 0);
            $community_id = input('community_id', 0);
            $address = input('address', '');
            $full_address = input('full_address', '');
            $longitude = input('longitude', 0);
            $latitude = input('latitude', 0);
            $is_pickup = input('is_pickup', 0);
            $is_o2o = input('is_o2o', 0);
            $open_date = input('open_date', '');
            $start_time = input('start_time', 0);
            $end_time = input('end_time', 0);
            $time_type = input('time_type', 0);
            $time_week = input('time_week', '');
            $stock_type = input('stock_type', '');
            if (!empty($time_week)) {
                $time_week = implode(',', $time_week);
            }
            $data = array (
                'store_name' => $store_name,
                'telphone' => $telphone,
                'store_image' => $store_image,
                'status' => $status,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'community_id' => $community_id,
                'address' => $address,
                'full_address' => $full_address,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'is_pickup' => $is_pickup,
                'open_date' => $open_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_type' => $time_type,
                'time_week' => $time_week,
                'stock_type' => $stock_type,
                'time_interval' => input('time_interval', 30),
                'delivery_time' => input('delivery_time', ''),
                'advance_day' => input('advance_day', 0),
                'most_day' => input('most_day', 7),
                'store_images' => input('store_images', ''),
            );
            $user_type = input('user_type', 1);
            if ($is_exit == 1 && $user_type == 0) {
                $user_data = [
                    'username' => input('username', ''),
                    'password' => data_md5(input('password', '')),
                ];
            } else {
                $user_data = [];
            }

            $result = $store_model->editStore($data, $condition, $user_data, $is_exit, $user_type);
            return $result;
        } else {
            //查询省级数据列表
            $address_model = new AddressModel();
            $list = $address_model->getAreaList([ ['pid', '=', 0 ], ['level', '=', 1 ] ]);
            $this->assign('province_list', $list['data']);
            $info_result = $store_model->getStoreDetail($condition);//门店信息
            $info = $info_result['data'];

            if (empty($info)) $this->error('未获取到门店数据', href_url('shop/store/lists'));

            $this->assign('info', $info);
            $this->assign('store_id', $store_id);

            $this->assign('is_exit', $is_exit);
            $this->assign('title', $is_exit ? '门店' : '自提点');
            $this->assign('http_type', get_http_type());

            $config_model = new ConfigModel();
            $mp_config = $config_model->getMapConfig($this->site_id);
            $this->assign('tencent_map_key', $mp_config[ 'data' ][ 'value' ][ 'tencent_map_key' ]);
            //效验腾讯地图KEY
            $check_map_key = $config_model->checkQqMapKey($mp_config[ 'data' ][ 'value' ][ 'tencent_map_key' ]);
            $this->assign('check_map_key', $check_map_key);

            $express_type = ( new ExpressConfig() )->getEnabledExpressType($this->site_id);
            if (isset($express_type[ 'express' ])) unset($express_type[ 'express' ]);
            $this->assign('express_type', $express_type);

            return $this->fetch('store/edit_store');
        }
    }

    /**
     * 删除门店
     * @return mixed
     */
    public function deleteStore()
    {
        if (request()->isJson()) {
            $store_id = input('store_id', 0);
            $condition = array (
                ['site_id', '=', $this->site_id ],
                ['store_id', '=', $store_id ]
            );
            $store_model = new StoreModel();
            $result = $store_model->deleteStore($condition);
            return $result;
        }
    }

    public function frozenStore()
    {
        if (request()->isJson()) {
            $store_id = input('store_id', 0);
            $is_frozen = input('is_frozen', 0);
            $condition = [
                ['site_id', '=', $this->site_id ],
                ['store_id', '=', $store_id ]
            ];
            $store_model = new StoreModel();
            $res = $store_model->frozenStore($condition, $is_frozen);
            return $res;
        }
    }

    /**
     * 重置密码
     */
    public function modifyPassword()
    {
        if (request()->isJson()) {
            $store_id = input('store_id', '');
            $password = input('password', '123456');
            $store_model = new StoreModel();
            return $store_model->resetStorePassword($password, [ [ 'store_id', '=', $store_id ] ]);
        }
    }

    /**
     *  配送员列表
     */
    public function deliverLists()
    {
        $store_id = input('store_id', 0);

        $deliver_model = new ExpressDeliver();
        if (request()->isJson()) {
            $page = input('page', '1');
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                [
                    'site_id', '=', $this->site_id,
                ],
                [
                    'store_id', '=', $store_id,
                ]
            ];
            $search_text = input('search_text', '');
            if (!empty($search_text)) {
                $condition[] = [ 'deliver_name', 'like', '%' . $search_text . '%' ];
            }
            $deliver_lists = $deliver_model->getDeliverPageLists($condition, '*', 'create_time desc', $page, $page_size);
            return $deliver_lists;
        } else {
            $this->assign('store_id', $store_id);
            return $this->fetch('store/deliverlists');
        }
    }

    /**
     *  添加配送员
     */
    public function addDeliver()
    {
        $store_id = input('store_id', 0);
        $this->assign('store_id', $store_id);
        return $this->fetch('local/adddeliver');
    }

    /**
     *  编辑配送员
     */
    public function editDeliver()
    {
        $store_id = input('store_id', 0);
        $this->assign('store_id', $store_id);
        $deliver_model = new ExpressDeliver();
        $deliver_id = input('deliver_id', 0);
        $this->assign('deliver_id', $deliver_id);
        $deliver_info = $deliver_model->getDeliverInfo($deliver_id, $this->site_id);
        $this->assign('deliver_info', $deliver_info[ 'data' ]);
        return $this->fetch('local/editdeliver');
    }

    /**
     * 选择门店
     * @return mixed
     */
    public function selectStore()
    {
        $store_ids = input('store_ids', '');
        $multiple_type = input('multiple_type', 0);//0多选 1单选
        $condition = [ [ 'site_id', '=', $this->site_id ] ];
        if($store_ids !== '' && $store_ids !== 'all'){
            $condition[] = ['store_id', 'in', $store_ids];
        }

        $is_express = input('is_express', '');
        $is_pickup =  input('is_pickup', '');
        $is_o2o =   input('is_o2o', '');

        if($is_express !== ''){
            $condition[] = ['is_express', '=', $is_express];
        }
        if($is_pickup !== ''){
            $condition[] = ['is_pickup', '=', $is_pickup];
        }
        if($is_o2o !== ''){
            $condition[] = ['is_o2o', '=', $is_o2o];
        }
        $store_list = ( new StoreModel() )->getStoreList($condition, 'store_id,store_name,status,address,full_address,is_frozen');
        $this->assign('store_list', $store_list[ 'data' ]);

        //选中数据
        $store_id = explode(',', input('store_id', ''));
        $this->assign('store_id', $store_id);
        $this->assign('multiple_type', $multiple_type);
        return $this->fetch('store/select');
    }
}