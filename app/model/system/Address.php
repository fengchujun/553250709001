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

use extend\api\HttpClient;
use think\facade\Cache;
use app\model\BaseModel;
use think\facade\Db;
use think\helper\Str;

/**
 * 地区表
 */
class Address extends BaseModel
{
    /**
     * 获取地区列表
     * @param unknown $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     * @return multitype:string mixed
     */
    public function getAreaList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $area_list = model('area')->getList($condition, $field, $order, $limit);
        return $this->success($area_list);
    }

    /**
     * 获取地区详情
     */
    public function getAreaInfo($circle)
    {
        $info = model('area')->getInfo([['id', '=', $circle]]);
        return $this->success($info);
    }

    /**
     * 获取地区数量
     * @param $condition
     * @return array
     */
    public function getAreaCount($condition)
    {
        $count = model('area')->getCount($condition);
        return $this->success($count);
    }

    /**
     * 获取省市子项
     */
    public function getAreas($circle = 0)
    {
        $list = model('area')->getList([['pid', '=', $circle]]);
        return $this->success($list);
    }

    /**
     * 获取整理后的地址
     */
    public function getAddressTree($level = 4)
    {
        $condition = [['level', '<=', $level]];
        $json_condition = json_encode($condition);
        $cache = Cache::get('area_getAddressTree' . $json_condition);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $area_list = $this->getAreaList($condition, 'id, pid, name, level', 'id asc')['data'];
        //组装数据
        $refer_list = [];
        foreach ($area_list as $key => $val) {
            $refer_list[$val['level']][$val['pid']]['child_list'][$val['id']] = $area_list[$key];
            if (isset($refer_list[$val['level']][$val['pid']]['child_num'])) {
                $refer_list[$val['level']][$val['pid']]['child_num'] += 1;
            } else {
                $refer_list[$val['level']][$val['pid']]['child_num'] = 1;
            }
        }
        Cache::tag('area')->set('area_getAddressTree' . $json_condition, $refer_list);
        return $this->success($refer_list);
    }

    /**
     * 获取地址树结构
     * @param $level
     * @return array
     */
    public function getAddressTreeList($level)
    {
        $condition = [['level', '<=', $level]];
        $json_condition = json_encode($condition);
        $cache = Cache::get('area_getAddressTreeList' . $json_condition);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $area_list = $this->getAreaList($condition, 'id, pid, name', 'id asc')['data'];
        $tree = $this->toTree($area_list);
        Cache::tag('area')->set('area_getAddressTreeList' . $json_condition, $tree);
        return $this->success($tree);
    }

    /**
     * 列表转树结构
     * @param $array
     * @param int $pid
     * @return array
     */
    public function toTree($array, $pid = 0)
    {
        $tree = array();
        foreach ($array as $key => $value) {
            if ($value['pid'] == $pid) {
                $value['children'] = $this->toTree($array, $value['id']);
                $tree[] = $value;
            }
        }
        return $tree;
    }

    /**
     * 获取地址
     * @param array $condition
     * @param string $field
     * @return multitype:number unknown
     */
    public function getAreasInfo(array $condition, string $field = '*')
    {
        $info = model('area')->getInfo($condition, $field);
        if ($info) return $this->success($info);
        return $this->error();
    }


    /**
     * 通过地址查询
     */
    public function getAddressByLatlng($post_data)
    {
        $qq_map = new \app\model\map\QqMap();
        $res = $qq_map->locationToDetail([
            'location' => $post_data['latlng'],
        ]);
        if ($res['status'] == 0) {
            $return_array = $res['result']['address_component'] ?? [];
            $return_data = array(
                'province' => $return_array['province'] ?? '',
                'city' => $return_array['city'] ?? '',
                'district' => $return_array['district'] ?? '',
                'address' => $return_array['street'] ?? '',
                'full_address' => $res['result']['address'] ?? '',
                'town_info' => $res['result']['address_reference']['town'] ?? null,
            );
            return $this->success($return_data);
        } else {
            return $this->error([], $res['message']);
        }
    }

    /**
     * 通过地址查询
     */
    public function getAddressByName($address)
    {
        $qq_map = new \app\model\map\QqMap();
        $res = $qq_map->addressToDetail([
            'address' => $address,
        ]);
        if ($res['status'] == 0) {
            $return_array = $res['result']['location'] ?? [];
            $return_data = array(
                'longitude' => $return_array['lng'] ?? '',
                'latitude' => $return_array['lat'] ?? '',
            );
            return $this->success($return_data);
        } else {
            return $this->error([], $res['message']);
        }
    }

    /**
     * 编辑地区
     * @param $data
     * @return array
     */
    public function saveArea($data)
    {
        $count = model('area')->getCount([['id', '=', $data['id']]]);
        if ($count) {
            $res = model('area')->update($data, [['id', '=', $data['id']]]);
        } else {
            $res = model('area')->add($data);
        }
        if ($res) {
            Cache::clear('area');
            return $this->success($res);
        }
        return $this->error();
    }

    /**
     * 删除地区
     * @param $id
     * @param $level
     * @return array
     */
    public function deleteArae($id, $level)
    {
        switch ((int)$level) {
            case 1:
                $child = model('area')->getColumn([['pid', '=', $id]], 'id');
                if (empty($child)) {
                    $condition = [['id', '=', $id], ['level', '=', $level]];
                } else {
                    $child = implode(',', $child);
                    $condition = [['', 'exp', Db::raw("(id = $id AND level = $level) OR (id in ($child) AND level = 2) OR (pid in ($child) AND level = 3)")]];
                }
                break;
            case 2:
                $condition = [['', 'exp', Db::raw("(id = $id AND level = 2) OR (pid = $id AND level = 3)")]];
                break;
            case 3:
                $condition = [['id', '=', $id], ['level', '=', $level]];
                break;
        }
        $res = model('area')->delete($condition);
        if ($res) {
            Cache::clear('area');
            return $this->success($res);
        }
        return $this->error();
    }

    /**
     * 解析地址字符串,匹配系统地址库
     * @param $address
     * @return array
     */
    public function analysesAddress($address): array
    {
        $res = $this->analysesAddressByTaobao($address);  //匹配淘宝,京东地址
        if($res['code'] == 0){
            $address = $res['data']['address'];
        }else{
            $address = $this->addressReplace($address); //去除特定字符
            $pdd_res = $this->analysesAddressByPdd($address); //匹配拼多多地址
            $res['data'] = array_merge($pdd_res['data'],$res['data']);
            if($pdd_res['code'] == 0){
                $address = $res['data']['address'];
            }else{
                $other_res = $this->analysesAddressByThird($address); //无固定格式
                $res['data'] = array_merge($other_res['data'],$res['data']);
                if ($other_res['code'] == 0){
                    $address = $res['data']['address'];
                }
            }
        }

        $mobile = $res['data']['mobile'] ?? '';
        $name = $res['data']['name'] ?? '';
        $detail = $res['data']['detail'] ?? '';

        $qq_map = new \app\model\map\QqMap();

        if(!empty($address)){
            $res = $qq_map->addressToDetail([
                'address' => $address.$detail,
            ]);

            if ($res['status'] == 0) {
                $lng = $res['result']['location']['lng'] ?? '';
                $lat = $res['result']['location']['lat'] ?? '';
                $return_array = $res['result']['address_components'] ?? [];
                $province = $return_array['province'] ?? '';
                $city = $return_array['city'] ?? '';
                $district = $return_array['district'] ?? '';
                if (empty($mobile) && empty($name) && empty($province)) {
                    return $this->error([], '解析有误,请检查格式是否正确');
                }
                $province_id = model('area')->getValue([['name', 'like', "%" . $province . '%'], ['level', '=', 1]], 'id');
                if (!empty($province_id) && !empty($city)) {
                    $city_id = model('area')->getValue([['name', 'like', "%" . $city . '%'], ['pid', '=', $province_id], ['level', '=', 2]], 'id');
                }
                if (!empty($city_id) && !empty($district)) {
                    $district_id = model('area')->getValue([['name', 'like', "%" . $district . '%'], ['pid', '=', $city_id], ['level', '=', 3]], 'id');
                }

                if(empty($detail)){
                    $province_temp = str_replace('省','',$province);
                    $city_temp = str_replace('市','',$city);
                    $district_temp = str_replace('区','',$district);
                    $address = $this->removePrefix($address, $province);
                    $address = $this->removePrefix($address, $province_temp);
                    $detail =str_replace([$province, $city,$district], ['','',''], $address);
                    $detail =str_replace([$province_temp, $city_temp,$district_temp], ['','',''], $detail);
                }
            }else{
                if (empty($mobile) && empty($name) ) {
                    return $this->error([], '解析有误,请检查格式是否正确');
                }
            }
        }else{
            if (empty($mobile) && empty($name) ) {
                return $this->error([], '解析有误,请检查格式是否正确');
            }
        }
        return $this->success([
            'name'=>$name,
            'mobile'=>$mobile,
            'province_id' => $province_id ?? '',
            'city_id' => $city_id ?? '',
            'district_id' => $district_id ?? '',
            'province_name' => $province ?? '',
            'city_name' => $city ?? '',
            'district_name' => $district ?? '',
            'detail' => $detail,
            'lng' => $lng ?? '',
            'lat' => $lat ?? '',
        ]);
    }


    public function analysesAddressByTaobao($address_str)
    {
        $pattern = [
            'name' => '/ ^\s*(?:收件人|收货人|姓名)\s*[:：]\s*(?<name>.*)\s*/ux',
            'mobile' => '/ ^\s*手机(?:号码|号)\s*[:：]\s* (?<phone>1[3-9]\d{9})\s* /ux',
            'address' => '/ ^\s*所在地区\s*[:：]\s* (?<region>.*)\s* /ux',
            'detail' => '/ ^\s*详细地址\s*[:：]\s* (?<detail>.*)\s* /ux',
        ];

        $address_str_split = preg_split('/\r\n?|\n/', $address_str);
        $res = [];
        foreach($address_str_split as $val){
            if(preg_match($pattern['name'], $val, $matches)){
                $res['name'] = $matches['name'];
            }elseif(preg_match($pattern['mobile'], $val, $matches)){
                $res['mobile'] = $matches['phone'];
            }elseif(preg_match($pattern['address'], $val, $matches)){
                $res['address'] = $matches['region'];
            }elseif(preg_match($pattern['detail'], $val, $matches)){
                $res['detail'] = $matches['detail'];
            }
        }

        if(count($res) < 4){
            return $this->error($res);
        }
        return $this->success($res);
    }

    public function analysesAddressByPdd($address_str)
    {
        $address_str = preg_replace('/\s+/ux', ' ', $address_str);
        $address_arr = explode(" ", $address_str);

        if(count($address_arr) < 3){
            return $this->error([]);
        }
        $patterns = [
            'address'  => '/(?<region>.*?(?:省|自治区|直辖市|市).*?(?:市|自治州|地区).*?(?:区|县|旗|市))/u',
            'mobile'   => '/(?<mobile>1[2-9]\d{9})/',         // 11位手机号
            'name'    => '/^(?<name>[^\d\s]+)|[^\d\s]+$/u', // 姓名（不含数字和空格）
        ];
        $result = [];
        // 分别匹配各个部分
        foreach ($patterns as $key => $pattern) {
            foreach ($address_arr as $key1=> $str){
                if (preg_match($pattern, $str, $matches)) {
                    if($key == 'address' && isset($address_arr[$key1+1])){
                        //如果地址中有详细地址，则保存到detail中
                        $result['detail'] = $address_arr[$key1+1];
                        unset($address_arr[$key1+1]);
                    }
                    unset($address_arr[$key1]);
                    $result[$key] = trim($str);
                }
            }
        }
        if(isset($result['detail']) && empty($address_arr)){
            return $this->success($result);
        }
        return $this->error($result);
    }


    public function analysesAddressByThird($address_str){
        $patterns = [
            '/ ^\s*手机(?:号码|号)\s*[:：]\s* (?<phone>1[3-9]\d{9})\s* /ux',
            // 规则1：姓名 + 电话 + 地址
            '/^(?<name>[\u4e00-\u9fa5·]+)\s+(?<mobile>\d{11}|0\d{2,3}-?\d{7,9})\s+(?<address>.+)$/u',
            // 规则2：地址 + 姓名 + 电话
            '/^(?<address>.+省?.+区)\s+(?<name>[\u4e00-\u9fa5·]+)\s+(?<mobile>\d{11})$/u',
            '/^(?<name>[^\d]+)(?<mobile>1[3-9]\d{9})(?<address>[^\n]*)(?<detail>.*)$/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address_str, $matches)) {
                $res = [
                    'name' => $matches['name'] ?? '',
                    'mobile' => $matches['mobile'] ?? '',
                    'address' => $matches['address'] ?? '',
                ];
                return $this->success($res);
            }
        }
        return $this->error([]);
    }

    public function  removePrefix($str, $delimiter = "_") {
        $pos = strpos($str, $delimiter);
        return ($pos !== false) ? substr($str, $pos + strlen($delimiter)) : $str;
    }

    public function addressReplace($address){
        return str_replace(['所在地区','详细地址',':'],['','',''],trim($address,' '));
    }
}
