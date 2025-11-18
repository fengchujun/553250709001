<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\virtualevaluation\model;

use app\model\BaseModel;

/**
 * 虚拟评价
 */
class VirtualStock extends BaseModel
{
    /**
     * 获取虚拟评价库分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getStockPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = 'stock_id,stock_name,num,create_time,modify_time,site_id')
    {
        $list = model('virtual_stock')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 添加虚拟评价库分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function addStock($data)
    {
        $stock_id = model('virtual_stock')->add($data);
        return $this->success($stock_id);
    }

    /**
     * 删除虚拟评价库
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function deleteStock($stock_id, $site_id)
    {
        $stock_id = model('virtual_stock')->delete([ [ 'stock_id', '=', $stock_id ], [ 'site_id', '=', $site_id ] ]);

        return $this->success($stock_id);
    }

    /**
     * 更新虚拟评价库
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function updateStock($data, $site_id)
    {
        $stock_id = model('virtual_stock')->update($data, [ [ 'stock_id', '=', $data[ 'stock_id' ] ], [ 'site_id', '=', $site_id ] ]);

        return $this->success($stock_id);
    }

    /**
     * 获取虚拟评价库信息
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getStockInfo($stock_id, $site_id)
    {
        $stock_info = model('virtual_stock')->getInfo([ [ 'stock_id', '=', $stock_id ], [ 'site_id', '=', $site_id ] ], '*');

        return $this->success($stock_info);
    }

    /**
     * 获取虚拟评价库信息
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getContentsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('stock_content')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 添加虚拟评价库评论
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function addContent($data)
    {
        $stock_id = model('stock_content')->add($data);
        if ($stock_id) {
            model("virtual_stock")->setInc([ [ 'stock_id', '=', $data[ 'stock_id' ] ] ], "num", 1);
        }
        return $this->success($stock_id);
    }

    /**
     * 删除虚拟评价库评论
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function deleteContent($id, $site_id)
    {
        $stock_data = model('stock_content')->getInfo([ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'stock_id');
        model("virtual_stock")->setDec([ [ 'stock_id', '=', $stock_data[ 'stock_id' ] ] ], "num", 1);

        $stock_id = model('stock_content')->delete([ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ]);

        return $this->success($stock_id);
    }

    /**
     * 获取虚拟评价库信息
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getContentInfo($id, $site_id)
    {
        $stock_info = model('stock_content')->getInfo([ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ], '*');

        return $this->success($stock_info);
    }

    /**
     * 修改虚拟评价库信息
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function editContentInfo($data, $site_id)
    {
        $content_info = model('stock_content')->update($data, [ [ 'id', '=', $data[ 'id' ] ], [ 'site_id', '=', $site_id ] ]);

        return $this->success($content_info);
    }

    /**
     * 获取评论库列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getStockList($condition = [], $field = '*', $order = 'stock_id asc', $limit = null)
    {
        $list = model('virtual_stock')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

}