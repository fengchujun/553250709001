<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\model\shop;

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 店铺地址库
 */
class ShopAddress extends BaseModel
{
    /**
     * 添加店铺地址库
     * @param array $data
     */
    public function addAddress($data)
    {
        $data["update_time"] = time();
        $res                 = model('shop_address')->add($data);
        Cache::tag("shop_address")->clear();
        return $this->success($res);
    }

    /**
     * 修改店铺地址库
     * @param array $data
     */
    public function editAddress($data, $condition)
    {
        $res = model('shop_address')->update($data, $condition);
        //修改对应店铺
        Cache::tag("shop_address")->clear();
        return $this->success($res);
    }

    /**
     * 删除店铺地址库
     * @param unknown $condition
     */
    public function deleteAddress($condition)
    {
        $res = model('shop_address')->delete($condition);
        Cache::tag("shop_address")->clear();
        return $this->success($res);
    }

    /**
     * 获取店铺地址库信息
     * @param unknown $condition
     * @param string $field
     */
    public function getAddressInfo($condition, $field = 'id, site_id, contact_name, mobile, postcode, province_id, city_id, district_id, community_id, address, full_address, is_return, is_return_default, is_delivery, update_time')
    {
        $res = model('shop_address')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取店铺地址库列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getAddressList($condition = [], $field = 'id, site_id, contact_name, mobile, postcode, province_id, city_id, district_id, community_id, address, full_address, is_return, is_return_default, is_delivery, update_time', $order = '', $limit = null)
    {
        $list = model('shop_address')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取店铺地址库分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getAddressPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = 'id, site_id, contact_name, mobile, postcode, province_id, city_id, district_id, community_id, address, full_address, is_return, is_return_default, is_delivery, update_time')
    {
        $list = model('shop_address')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }
}