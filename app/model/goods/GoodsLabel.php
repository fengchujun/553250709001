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
 * 商品分组
 */
class GoodsLabel extends BaseModel
{

    /**
     * 添加商品分组
     * @param array $data
     */
    public function addLabel($data)
    {
        $site_id = $data['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $data[ 'create_time' ] = time();
        $label_id = model('goods_label')->add($data);
        Cache::tag("goods_label_" . $site_id)->clear();
        return $this->success($label_id);
    }

    /**
     * 修改商品分组
     * @param array $data
     */
    public function editLabel($data)
    {
        $site_id = $data['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $label_info = model('goods_label')->getInfo([ [ 'id', '=', $data[ 'id' ] ] ], 'label_name');
        if (empty($label_info)) {
            return $this->error(null, '编辑条件有误');
        }
        //分组名称修改同步修改商品表字段
        if ($label_info[ 'label_name' ] != $data[ 'label_name' ]) {
            model('goods')->update([ 'label_name' => $data[ 'label_name' ] ], [ [ 'label_id', '=', $data[ 'id' ] ] ]);
        }

        $data[ 'update_time' ] = time();
        $res = model('goods_label')->update($data, [ [ 'id', '=', $data[ 'id' ] ], [ 'site_id', '=', $data[ 'site_id' ] ] ]);
        Cache::tag("goods_label_" . $site_id)->clear();
        return $this->success($res);
    }

    /**
     * 删除商品分组
     * @param array $condition
     */
    public function deleteLabel($condition)
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = $check_condition['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        //清空商品与标签的关联
        $list = model('goods_label')->getList($condition);
        if(!empty($list)){
            $label_ids = array_column($list, 'id');
            model('goods')->update([ 'label_name' => '', 'label_id' => 0 ], [ [ 'label_id', 'in', $label_ids ] ]);
        }

        $res = model('goods_label')->delete($condition);
        Cache::tag("goods_label_" . $site_id)->clear();
        return $this->success($res);
    }

    /**
     * 修改排序
     * @param $sort
     * @param $id
     * @param $site_id
     * @return array
     */
    public function modifySort($sort, $id, $site_id)
    {
        $site_id = $site_id ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $res = model('goods_label')->update([ 'sort' => $sort ], [ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ]);
        Cache::tag("goods_label_" . $site_id)->clear();
        return $this->success($res);
    }

    /**
     * 获取商品分组信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getLabelInfo($condition = [], $field = '*')
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = $check_condition['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $list = model('goods_label')->getInfo($condition, $field);
        return $this->success($list);
    }

    /**
     * 获取商品分组列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getLabelList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = $check_condition['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $list = model('goods_label')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取商品分组分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getLabelPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'sort asc', $field = '*')
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = $check_condition['site_id'] ?? '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $list = model('goods_label')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

}