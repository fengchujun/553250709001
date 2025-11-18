<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\express;

use app\model\BaseModel;
use app\model\shop\Shop;
use app\model\store\Store;

/**
 * 外卖配送
 */
class Local extends BaseModel
{
    /**
     * 添加站点外卖配送配置
     * @param $data
     * @return array
     */
    public function addLocal($data)
    {
        $id = model('local')->add($data);
        return $this->success($id);
    }

    /**
     * 修改站点外卖配送配置
     * @param $data
     * @param $condition
     * @return array
     */
    public function editLocal($data, $condition)
    {
        $res = model('local')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除站点外卖配送 (通常删除站点会用到)
     * @param $condition
     * @return array
     */
    public function deleteLocal($condition)
    {
        $res = model('local')->delete($condition);
        return $this->success($res);
    }

    /**
     * 获取站点外卖配送信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getLocalInfo($condition, $field = '*')
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = $check_condition['site_id'] ?? '';
        $store_id = $check_condition['store_id'] ?? 0;

        $info = model('local')->getInfo($condition, $field);
        if (empty($info)) {
            $local_data = array(
                'site_id' => $site_id,
                'store_id' => $store_id,
                'update_time' => time()
            );
            $this->addLocal($local_data);
            $info = model('local')->getInfo($condition, $field);
        }
        if (!empty($info)) {
            $local_area_array = [];
            if (!empty($info['local_area_json'])) {
                $local_area_array = json_decode($info['local_area_json'], true);
            }
            $info['local_area_array'] = $local_area_array;
            $time_week = [];
            if (!empty($info['time_week'])) {
                $time_week = explode(',', $info['time_week']);
            }
            $info['time_week'] = $time_week;
            $area_array = [];
            if (!empty($info['area_array'])) {
                $area_array = explode(',', $info['area_array']);
            }
            $info['area_array'] = $area_array;
            if (empty($info['delivery_time'])) {
                $info['delivery_time'] = [
                    ['start_time' => $info['start_time'], 'end_time' => $info['end_time']]
                ];
            } else {
                $info['delivery_time'] = json_decode($info['delivery_time'], true);
            }
        }
        return $this->success($info);
    }

    /**
     * 获取站点外卖配送列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getLocalList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('local')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取站点外卖配送分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getLocalPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('local')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 计算费用
     * @param $shop_goods
     * @param $data
     * @return array
     */
    public function calculate($params)
    {
        /** @var \app\model\order\OrderCreate $order_object */
        $order_object = $params['order_object'];
        $is_limit_start_money = $order_object->is_limit_start_money ?? true;//是否限制起送价

        $site_id = $order_object->site_id;
        $local_condition = array(
            ['site_id', '=', $site_id]
        );
        $store_id = $order_object->store_id ?? 0;
        //todo  应该判断一下是否有门店运营插件
        if (!addon_is_exit('store')) {
            $store_id = 0;
        }
        if ($store_id > 0) $local_condition[] = ['store_id', '=', $store_id];
        $local_info = $this->getLocalInfo($local_condition)['data'];
        if (empty($local_info)) return $this->error('', '没有可以配送的门店');
        $start_price_error = 0;//起送价错误
        $distance_error = 0;//配送距离错误
        $time_error = 0;
        $error_code = 12;
        if (!addon_is_exit('store')) {
            $condition_store_id = $local_info['store_id'];
        } else {
            $condition_store_id = $store_id;
        }
        $delivery_restrictions_res = (new  \app\model\storegoods\StoreGoods)->getStoreGoodsSkuInfo([
            ['sku_id', 'in', array_column($order_object->goods_list, "sku_id")],
            ['is_delivery_restrictions', '=', 0],
            ['store_id', '=', $condition_store_id],
        ]);
        if (!empty($delivery_restrictions_res['data'])) {
            $is_limit_start_money = false;  // 存在不限制起送的商品直接去除限制
        }


        if (empty($order_object->delivery['member_address'])) {
            return $this->error(['code' => $error_code], '请选择同城配送地址');
        }

        $delivery_time_data = $order_object->delivery['buyer_ask_delivery_time'] ?? [];
        $delivery_start_time = $delivery_time_data['start_time'] ?? '';//配送时间左限
        $delivery_end_time = $delivery_time_data['end_time'] ?? '';//配送时间右限
        $error = '所选地址无法配送';
        //判断时间   是否在时间段内
        if ($local_info['time_is_open'] == 1 && $order_object->is_check_buyer_ask_delivery_time) {
            if ($delivery_start_time && $delivery_end_time) {
                if ($local_info['time_type'] == 1) {//自定义
                    $start_week = date('w', $delivery_start_time);
                    $end_week = date('w', $delivery_end_time);
                    if (!(in_array($start_week, $local_info['time_week']) && in_array($end_week, $local_info['time_week']))) {//判断是否是营业日
                        $time_error++;
                        $error = '配送时间不在营业时间内';
                    }
                }

                //判断运营时段
                $start_daytime = strtotime(date('Y-m-d H:i:s', $delivery_start_time)) - strtotime(date('Y-m-d 00:00:00', $delivery_start_time));
                $end_daytime = strtotime(date('Y-m-d H:i:s', $delivery_end_time)) - strtotime(date('Y-m-d 00:00:00', $delivery_end_time));
                $delivery_time_range = $local_info['delivery_time'] ?? [];
                if ($delivery_time_range) {
                    foreach ($delivery_time_range as $v) {
                        $temp_start_time = ($v['start_time']);
                        $temp_end_time = ($v['end_time']);
                        if (!($start_daytime >= $temp_start_time && $end_daytime >= $temp_start_time && $start_daytime <= $temp_end_time && $end_daytime <= $temp_end_time)) {
                            $time_error++;
                            $error = '配送时间不在营业时间段内';
                        } else {
                            $time_error = 0;
                            $error = '';
                            break;
                        }
                    }

                }
            } else {
                $time_error++;
                $error = '请选择配送时间';
            }

        } else {
            $error = '门店未开启配送时间';
        }

        if ($store_id == 0) {
            $shop_model = new Shop();
            $shop_info = $shop_model->getShopInfo([['site_id', '=', $site_id]])['data'] ?? [];
        } else {
            $store_model = new Store();
            $shop_info = $store_model->getStoreInfo([['site_id', '=', $site_id], ['store_id', '=', $store_id]])['data'] ?? [];
        }
        if (empty($shop_info['longitude']) || empty($shop_info['latitude'])) {
            return $this->error(['code' => $error_code], '商家未设置配送地址');
        }
        $is_delivery = false;
        $start_money_array = [];
        if ($local_info['area_type'] == 1 || $local_info['area_type'] == 2) {
            if ($order_object->delivery['member_address']['longitude'] == 0 && $order_object->delivery['member_address']['latitude'] == 0) {
                return $this->error(['code' => $error_code], '下单地址缺少定位信息');
            }

            $shop_longitude = $shop_info['longitude'];
            $shop_latitude = $shop_info['latitude'];
            $longitude = $order_object->delivery['member_address']['longitude'];
            $latitude = $order_object->delivery['member_address']['latitude'];
            if ($local_info['area_type'] == 1) {
                $distance = $this->getDistance($latitude, $longitude, $shop_latitude, $shop_longitude);
                if ($distance <= $local_info['start_distance']) {//是否在起送距离以内
                    $delivery_money = $local_info['start_delivery_money'];
                } else {
                    $diff_distance = $distance - $local_info['start_distance'];//减去起送距离 求得差
                    if ($local_info['continued_distance'] == 0) return $this->error(['code' => $error_code], '当前配送地址不支持配送');
                    $delivery_money = $local_info['start_delivery_money'] + ceil($diff_distance / $local_info['continued_distance']) * $local_info['continued_delivery_money'];
                }
            } else {
                $delivery_money_array = [];
            }
            $local_area_array = $local_info['local_area_array'];

            if (!empty($local_area_array)) {
                foreach ($local_area_array as $k => $v) {
                    //起送价是否满足
                    if (!$is_limit_start_money || $order_object->goods_money >= $v['start_price']) {
                        $path = $v['path'];
                        if ($v['rule_type'] == 'circle') {//半径

                            $item_longitude = $path['center']['longitude'];
                            $item_latitude = $path['center']['latitude'];
                            $radius = $path['radius'];
                            $item_distance = $this->getDistance($latitude, $longitude, $item_latitude, $item_longitude);
                            //判断有无超出范围
                            if ($item_distance <= $radius / 1000) {
                                $is_delivery = true;
                                if ($local_info['area_type'] == 2) {
                                    //非半径   配送费是取设置的
                                    $delivery_money_array[] = $v['delivery_money'];
                                }
                            } else {
                                $distance_error++;
                            }
                        } else if ($v['rule_type'] == 'polygon') {//多边形

                            $point = array(
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                            );
                            //判断坐标是否在多边形内
                            if (is_point_in_polygon($point, $path)) {
                                $is_delivery = true;
                                if ($local_info['area_type'] == 2) {
                                    //非半径   配送费是取设置的
                                    $delivery_money_array[] = $v['delivery_money'];
                                }
                            } else {
                                $distance_error++;
                            }
                        }
                    } else {
                        $start_money_array[] = $v['start_price'];
                        $start_price_error++;
                    }
                }

                //区域配送 存在相交的区域,以配送费低的区域价格来计算
                if ($local_info['area_type'] == 2) {
                    if (!empty($delivery_money_array)) {
                        $delivery_money = min($delivery_money_array);
                    }

                }
            }
        } else {
            //行政区域配送

            if (!$is_limit_start_money || $order_object->goods_money >= $local_info['start_money']) {

                $district_id = $order_object->delivery['member_address']['district_id'];//区县地域id
                $area_array = $local_info['area_array'];
                if (in_array($district_id, $area_array)) {
                    //启用阶梯价 的话  也是必须要具体坐标的
                    if ($local_info['is_open_step'] == 1) {

                        if ($order_object->delivery['member_address']['longitude'] == 0 && $order_object->delivery['member_address']['latitude'] == 0) {
                            return $this->error(['code' => $error_code], '当前配送地址没有配置定位坐标');
                        }

                        $is_delivery = true;
                        $shop_longitude = $shop_info['longitude'];
                        $shop_latitude = $shop_info['latitude'];
                        $longitude = $order_object->delivery['member_address']['longitude'];
                        $latitude = $order_object->delivery['member_address']['latitude'];

                        $distance = $this->getDistance($latitude, $longitude, $shop_latitude, $shop_longitude);
                        if ($distance <= $local_info['start_distance']) {//是否在起送距离以内
                            $delivery_money = $local_info['start_delivery_money'];
                        } else {
                            $diff_distance = $distance - $local_info['start_distance'];//减去起送距离 求得差
                            $delivery_money = $local_info['start_delivery_money'] + ceil($diff_distance / $local_info['continued_distance']) * $local_info['continued_delivery_money'];
                        }

                    } else {
                        $is_delivery = true;
                        $delivery_money = $local_info['delivery_money'];
                    }
                } else {
                    $distance_error++;//配送距离错误
                }

            } else {
                $start_price_error++;//起送价错误
                $start_money_array[] = $local_info['start_money'];
            }
        }

        if ($is_delivery) {
            if ($delivery_money > 0) {
                $man_type = $local_info['man_type'];
                $man_money = $local_info['man_money'];
                switch ($man_type) {
                    case 'free':
                        if ($order_object->goods_money >= $man_money) {
                            $delivery_money = 0;
                        }
                        break;
                    case 'discount':
                        if ($order_object->goods_money >= $man_money) {
                            $man_discount = $local_info['man_discount'];
                            $delivery_money -= $man_discount;
                            $delivery_money = max($delivery_money, 0);
                        }
                        break;
                }

            }
            $return_result = array(
                'delivery_money' => $delivery_money,
            );
            if ($time_error > 0) {
                $return_result['code'] = 1;
                $return_result['error'] = $error;
            }
            return $this->success($return_result);
        } else {
            if ($distance_error > 0) {
                $error = '当前地址不在该门店配送区域，请重新选择可配送该区域的门店';
                $error_code = 10;
            } else if ($start_price_error > 0 && !isset($order_object->delivery['unlimited_start_money'])) {//todo  这儿要定义不限制起送价
                $error = '当前商品金额尚不满足商家配送的最低起送价格1';
                $error_code = 11;
            }
            return $this->error(['code' => $error_code, 'start_money_array' => $start_money_array], $error);
        }

    }

    /**
     * 区域是否支持配送
     * @param $data
     * @return array
     */
    public function isSupportDelivery($data)
    {
        $local_condition = array(
            ['site_id', '=', $data['site_id']],
        );
        if (!empty($data['store_id'])) $local_condition[] = ['store_id', '=', $data['store_id']];
        $local_info = $this->getLocalInfo($local_condition)['data'];

        $distance_error = 0;//配送距离错误
        $error_code = 12;

        $is_delivery = false;

        if ($local_info['area_type'] == 1 || $local_info['area_type'] == 2) {
            if ($data['longitude'] == 0 && $data['latitude'] == 0) {
                return $this->error(['code' => $error_code], '超出配送范围');
            }
            $longitude = $data['longitude'];
            $latitude = $data['latitude'];
            $local_area_array = $local_info['local_area_array'];
            if (!empty($local_area_array)) {
                foreach ($local_area_array as $k => $v) {
                    $path = $v['path'];
                    if ($v['rule_type'] == 'circle') {//半径

                        $item_longitude = $path['center']['longitude'];
                        $item_latitude = $path['center']['latitude'];
                        $radius = $path['radius'];
                        $item_distance = $this->getDistance($latitude, $longitude, $item_latitude, $item_longitude);
                        //判断有无超出范围
                        if ($item_distance <= $radius / 1000) {
                            $is_delivery = true;
                        } else {
                            $distance_error++;
                        }
                    } else if ($v['rule_type'] == 'polygon') {//多边形

                        $point = array(
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                        );
                        //判断坐标是否在多边形内
                        if (is_point_in_polygon($point, $path)) {
                            $is_delivery = true;
                        } else {
                            $distance_error++;
                        }
                    }
                }
            }
        } else {
            //行政区域配送
            $district_id = $data['district_id'];//区县地域id
            $area_array = $local_info['area_array'];
            if (in_array($district_id, $area_array)) {
                //启用阶梯价 的话  也是必须要具体坐标的
                if ($local_info['is_open_step'] == 1) {
                    if ($data['longitude'] == 0 && $data['latitude'] == 0) {
                        return $this->error(['code' => $error_code], '超出配送范围');
                    }
                    $is_delivery = true;
                } else {
                    $is_delivery = true;
                }
            } else {
                $distance_error++;//配送距离错误
            }
        }
        if (!$is_delivery && $distance_error) {
            $error = '超出配送范围';
            return $this->error(['code' => $error_code], $error);
        }
        return $this->success(1);

    }

    /**
     * 判断可用的区域
     * @param $type
     * @param $latlng
     * @param $range
     */
    public function getWithAreaList($type, $latlng, $range)
    {

    }

    /**
     * 求两个已知经纬度之间的距离,单位为km
     * @param lng1,lng2 经度
     * @param lat1,lat2 纬度
     * @return float 距离，单位为km
     **/
    public function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6371;
        return round($s, 1);
    }

    /**
     * 积分兑换计算费用
     * @param $goods_info
     * @param $data
     * @return array
     */
    public function pointExchangeCalculate($goods_info, $data)
    {
        $local_condition = array(
            ['site_id', '=', $data['site_id']]
        );
        if (isset($data['delivery']['store_id'])) $local_condition[] = ['store_id', '=', $data['delivery']['store_id']];
        $local_info = $this->getLocalInfo($local_condition)['data'];
        if (empty($local_info)) return $this->error('', '没有可以配送的门店');

        $start_price_error = 0;//起送价错误
        $distance_error = 0;//配送距离错误
        $time_error = 0;
        $error_code = 12;
        $error = '';
        //判断时间   是否在时间段内
        if ($local_info['time_is_open'] == 1) {
            $week = date('w');
            if (in_array($week, $local_info['time_week'])) {
                $time = $goods_info['buyer_ask_delivery_time'];
                if ($time == 0) {
                    $time_error++;
                    $error = '请选择配送时间';
                }
            } else {
                $time_error++;
                $error = '配送时间不在营业时间内';
            }

        }

        $shop_model = new Shop();
        $shop_info = $shop_model->getShopInfo([['site_id', '=', $data['site_id']]])['data'];
        $is_delivery = false;

        $start_money_array = [];
        if ($local_info['area_type'] == 1 || $local_info['area_type'] == 2) {
            if (empty($data['member_address'])) {
                return $this->error(['code' => $error_code], '请设置配送地址');
            }
            if ($data['member_address']['longitude'] == 0 && $data['member_address']['latitude'] == 0) {
                return $this->error(['code' => $error_code], '当前配送地址没有配置定位坐标');
            }

            $shop_longitude = $shop_info['longitude'];
            $shop_latitude = $shop_info['latitude'];
            $longitude = $data['member_address']['longitude'];
            $latitude = $data['member_address']['latitude'];
            if ($local_info['area_type'] == 1) {
                $distance = $this->getDistance($latitude, $longitude, $shop_latitude, $shop_longitude);
                if ($distance <= $local_info['start_distance']) {//是否在起送距离以内
                    $delivery_money = $local_info['start_delivery_money'];
                } else {
                    $diff_distance = $distance - $local_info['start_distance'];//减去起送距离 求得差
                    $delivery_money = $local_info['start_delivery_money'] + ceil($diff_distance / $local_info['continued_distance']) * $local_info['continued_delivery_money'];
                }
            } else {
                $delivery_money_array = [];
            }
            $local_area_array = $local_info['local_area_array'];

            if (!empty($local_area_array)) {
                foreach ($local_area_array as $k => $v) {
                    //起送价是否满足
                    if ($data['exchange_info']['price'] >= $v['start_price']) {
                        $path = $v['path'];
                        if ($v['rule_type'] == 'circle') {//半径

                            $item_longitude = $path['center']['longitude'];
                            $item_latitude = $path['center']['latitude'];
                            $radius = $path['radius'];
                            $item_distance = $this->getDistance($latitude, $longitude, $item_latitude, $item_longitude);
                            //判断有无超出范围
                            if ($item_distance <= $radius / 1000) {
                                $is_delivery = true;
                                if ($local_info['area_type'] == 2) {
                                    //                                   //非半径   配送费是取设置的
                                    $delivery_money_array[] = $v['delivery_money'];
                                }
                            } else {
                                $distance_error++;
                            }
                        } else if ($v['rule_type'] == 'polygon') {//多边形

                            $point = array(
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                            );
                            //判断坐标是否在多边形内
                            if (is_point_in_polygon($point, $path)) {
                                $is_delivery = true;
                                if ($local_info['area_type'] == 2) {
                                    //非半径   配送费是取设置的
                                    $delivery_money_array[] = $v['delivery_money'];
                                }
                            } else {
                                $distance_error++;
                            }
                        }
                    } else {
                        $start_money_array[] = $v['start_price'];
                        $start_price_error++;
                    }
                }

                //区域配送 存在相交的区域,以配送费低的区域价格来计算
                if ($local_info['area_type'] == 2) {
                    if (!empty($delivery_money_array)) {
                        $delivery_money = min($delivery_money_array);
                    }

                }
            }
        } else {
            //行政区域配送

            if ($data['exchange_info']['price'] >= $local_info['start_money']) {

                $district_id = $data['member_address']['district_id'];//区县地域id
                $area_array = $local_info['area_array'];
                if (in_array($district_id, $area_array)) {
                    //启用阶梯价 的话  也是必须要具体坐标的
                    if ($local_info['is_open_step'] == 1) {

                        if ($data['member_address']['longitude'] == 0 && $data['member_address']['latitude'] == 0) {
                            return $this->error(['code' => $error_code], '当前配送地址没有配置定位坐标');
                        }

                        $is_delivery = true;
                        $shop_longitude = $shop_info['longitude'];
                        $shop_latitude = $shop_info['latitude'];
                        $longitude = $data['member_address']['longitude'];
                        $latitude = $data['member_address']['latitude'];

                        $distance = $this->getDistance($latitude, $longitude, $shop_latitude, $shop_longitude);
                        if ($distance <= $local_info['start_distance']) {//是否在起送距离以内
                            $delivery_money = $local_info['start_delivery_money'];
                        } else {
                            $diff_distance = $distance - $local_info['start_distance'];//减去起送距离 求得差
                            $delivery_money = $local_info['start_delivery_money'] + ceil($diff_distance / $local_info['continued_distance']) * $local_info['continued_delivery_money'];
                        }

                    } else {
                        $is_delivery = true;
                        $delivery_money = $local_info['delivery_money'];
                    }
                } else {
                    $distance_error++;//配送距离错误
                }

            } else {
                $start_price_error++;//起送价错误
                $start_money_array[] = $local_info['start_money'];
            }
        }

        if ($is_delivery) {
            $return_result = array(
                'delivery_money' => $delivery_money,
                'start_money_array' => $start_money_array,
            );
            if ($time_error > 0) {
                $return_result['code'] = 1;
                $return_result['error'] = $error;
            }
            return $this->success($return_result);
        } else {
            if ($distance_error > 0) {
                $error = '当前地址不在该门店配送区域，请重新选择可配送该区域的门店';
                $error_code = 10;
            } else if ($start_price_error > 0) {
                $error = '当前商品金额尚不满足商家配送的最低起送价格';
                $error_code = 11;
            }
            return $this->error(['code' => $error_code], $error);
        }

    }

    /**
     * 核验是否可以开启本地配送
     * @param $site_id
     * @return array
     */
    public function checkIsCanTradeLocal($site_id)
    {
        $store = new Store();
        $default_store = $store->getStoreInfo([['site_id', '=', $site_id], ['is_default', '=', 1]], 'store_id')['data'] ?? [];
        $store_id = $default_store['store_id'] ?? 0;

        $local_info = $this->getLocalInfo([['site_id', '=', $site_id], ['store_id', '=', $store_id]])['data'] ?? [];
        if (empty($local_info))
            return $this->error([], '您未完成起送金额、配送费 、配送区域等同城送配置项设置，需设置并提交保存后，才能开启同城配送开关。');

        //判断配置有没有配置完善
        $local_area_array = $local_info['local_area_array'];
        $area_array = $local_info['area_array'];
        if (empty($local_area_array) && empty($area_array))
            return $this->error([], '您未完成起送金额、配送费 、配送区域等同城送配置项设置，需设置并提交保存后，才能开启同城配送开关。');

        return $this->success();
    }

    public function getInfo($condition, $field = '*'){
        $info = model('local')->getInfo($condition, $field);
        return $this->success($info);
    }
}