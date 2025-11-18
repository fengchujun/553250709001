<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\model\goods;

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 商品服务
 */
class GoodsService extends BaseModel
{

    /**
     * 添加商品服务
     * @param array $data
     */
    public function addService($data)
    {

        $data['create_time'] = time();
        $service_id          = model('goods_service')->add($data);
        return $this->success($service_id);
    }

    /**
     * 添加多个商品服务
     * @param array $data
     */
    public function addServiceList($data)
    {

        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = time();
        }
        $service_id = model('goods_service')->addList($data);
        return $this->success($service_id);
    }

    /**
     * 修改商品服务
     * @param array $data
     * @return multitype:string
     */
    public function editService($data, $condition)
    {
        $res = model('goods_service')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除商品服务
     * @param array $condition
     */
    public function deleteService($condition)
    {

        $res = model('goods_service')->delete($condition);
        return $this->success($res);
    }

    /**
     * 获取商品服务
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getServiceInfo($condition, $field = '*')
    {
        $info = model('goods_service')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取商品服务列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getServiceList($condition = [], $field = 'id,site_id,service_name,desc,create_time,icon', $order = 'create_time desc', $limit = null)
    {

        $list = model('goods_service')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取商品服务分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getServicePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = 'id,site_id,service_name,desc,create_time,icon')
    {

        $list = model('goods_service')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

}