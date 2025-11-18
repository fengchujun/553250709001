<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\form\model;

use app\model\BaseModel;

/**
 * 表单数据
 */
class FormData extends BaseModel
{

    /**
     * 获取表单数据分页列表
     * @param $condition
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @param $alias
     * @param $join
     * @return array
     */
    public function getFormDataPageList($condition, $field, $order, $page, $limit, $alias, $join)
    {

        $list = model('form_data')->pageList($condition, $field, $order, $page, $limit, $alias, $join);
        return $this->success($list);
    }

    /**
     * 查询表单数据
     * @param  array  $where
     * @param  bool  $field
     * @param  string  $alias
     * @param  null  $join
     * @return array
     */
    public function getFormDataInfo($where = [], $field = true, $alias = 'a', $join = null)
    {
        $info = model('form_data')->getInfo($where, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 删除表单数据
     * @param  array  $condition
     * @return array
     */
    public function deleteFormData($condition = [])
    {
        $res = model('form_data')->delete($condition);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }
}