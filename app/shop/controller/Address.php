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

use app\model\system\Address as AddressModel;
use app\model\web\Config as ConfigModel;
use app\Controller;

/**
 * 地址控制器
 * Class Order
 * @package app\shop\controller
 */
class Address extends BaseShop
{
    /**
     * 通过ajax得到运费模板的地区数据
     */
    public function getAreaList()
    {
        $address_model = new AddressModel();
        $level         = input('level', 1);
        $pid           = input('pid', 0);
        $child         = input('child', 0);
        $condition     = array(
            'level' => $level,
            'pid' => $pid
        );
        $list          = $address_model->getAreaList($condition, 'id, pid, name, shortname, level', 'id asc');
        if (!empty($list['data']) && $child) {
            foreach ($list['data'] as $k => $item) {
                $list['data'][$k]['child_num'] = $address_model->getAreaCount([ ['pid', '=', $item['id'] ] ])['data'];
            }
        }
        return $list;
    }

    /**
     * 获取地理位置id
     */
    public function getGeographicId()
    {
        $address_model = new AddressModel();
        $address       = request()->post('address', ',,');
        $address_array = explode(',', $address);
        $province      = $address_array[0];
        $city          = $address_array[1];
        $district      = $address_array[2];
        $subdistrict   = $address_array[3];
        $province_list = $address_model->getAreaList(['name' => $province, 'level' => 1], 'id', '');
        $province_id   = !empty($province_list['data']) ? $province_list['data'][0]['id'] : 0;
        $city_list     = ($province_id > 0) && !empty($city) ? $address_model->getAreaList(['name' => $city, 'level' => 2, 'pid' => $province_id], 'id', '') : [];
        $city_id       = !empty($city_list['data']) ? $city_list['data'][0]['id'] : 0;
        $district_list = !empty($district) && $city_id > 0 && $province_id > 0 ? $address_model->getAreaList(['name' => $district, 'level' => 3, 'pid' => $city_id], 'id', '') : [];
        $district_id   = !empty($district_list['data']) ? $district_list['data'][0]['id'] : 0;

        $subdistrict_list = !empty($subdistrict) && $city_id > 0 && $province_id > 0 && $district_id > 0 ? $address_model->getAreaList(['name' => $subdistrict, 'level' => 4, 'pid' => $district_id], 'id', '') : [];
        $subdistrict_id   = !empty($subdistrict_list['data']) ? $subdistrict_list['data'][0]['id'] : 0;
        return ['province_id' => $province_id, 'city_id' => $city_id, 'district_id' => $district_id, 'subdistrict_id' => $subdistrict_id];
    }

    /**
     * 地区管理
     * @return mixed
     */
    public function manage()
    {
        $address_model = new AddressModel();
        $list          = $address_model->getAreaList([['level', '=', 1]], 'id, pid, name, shortname, level', 'id asc')['data'];
        if (!empty($list)) {
            foreach ($list as $k => $item) {
                $list[$k]['child_num'] = $address_model->getAreaCount([ ['pid', '=', $item['id'] ] ])['data'];
            }
        }
        $this->assign('area', $list);

        return $this->fetch('address/manage');
    }

    /**
     * 保存地区
     */
    public function saveArea(){
        if (request()->isJson()) {
            $address_model = new AddressModel();
            $data = [
                'id' => input('id'),
                'name' => input('name', ''),
                'shortname' => input('shortname', ''),
                'pid' => input('pid', 0),
                'level' => input('level', 1),
            ];
            return $address_model->saveArea($data);
        }
    }

    /**
     * 删除地区
     * @return array
     */
    public function deleteArea(){
        if (request()->isJson()) {
            $address_model = new AddressModel();
            $id = input('id');
            $level = input('level');
            return $address_model->deleteArae($id, $level);
        }
    }

    /**
     * 地址字符串获取详情
     */
    public function addressToDetail()
    {
        if (request()->isJson()) {
            $address = input('address', '');
            $qq_map = new \app\model\map\QqMap();
            $res = $qq_map->addressToDetail([
                'address' => $address,
            ]);
            return $res;
        }
    }

    /**
     * 经纬度获取详情
     */
    public function locationToDetail()
    {
        if (request()->isJson()) {
            $location = input('location', '');
            $qq_map = new \app\model\map\QqMap();
            $res = $qq_map->locationToDetail([
                'location' => $location,
            ]);
            return $res;
        }
    }

    /**
     * 地区树
     */
    public function getAreaTree()
    {
        if(request()->isJson()){
            $tree_level = input('tree_level', 3);
            $tree_ids = input('tree_ids', '');

            $condition = [];
            $condition[] = ['level', '<=', $tree_level];
            if(!empty($tree_ids)){
                $condition[] = ['id', 'in', $tree_ids];
            }

            $category_model = new AddressModel();
            $list = $category_model->getAreaList($condition, "id, name as title, pid, level", "id asc")['data'];
            $tree = list_to_tree($list, 'id', 'pid', 'children', 0);
            $tree = keyArrToIndexArr($tree, 'children');
            return $category_model->success($tree);
        }
    }


    /**
     * 解析地址
     */
    public function analysesAddress()
    {
        if(request()->isJson()){
            $address = input('address', '');
            $address_model = new AddressModel();
            return $address_model->analysesAddress($address);
        }
    }


    public function geMapConfig(){
        $map_model = new \app\model\map\QqMap();
        $res = [
            'key'=>$map_model->getKey()
        ];

        return success(0,'请求成功',$res);
    }
}