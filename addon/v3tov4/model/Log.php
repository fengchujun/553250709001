<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\v3tov4\model;

use app\model\BaseModel;

/**
 * v3Tov4迁移数据日志
 * @author Administrator
 *
 */
class Log extends BaseModel
{
    /**
     * 添加日志
     * @param $data
     * @return array
     */
    public function addLogList($data)
    {
        $res = model("v3_upgrade_log")->addList($data);
        return $this->success($res);
    }

    /**
     * 编辑日志
     * @param $data
     * @param $condition
     * @return array
     */
    public function editLog($data, $condition = [])
    {
        $res = model("v3_upgrade_log")->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除日志
     * @param $ids
     * @return array
     */
    public function deleteLog($ids)
    {
        $res = model("v3_upgrade_log")->delete([ [ 'id', 'in', $ids ] ]);
        return $this->success($res);
    }

    /**
     * 获取最新的模块迁移数据
     * @param $module
     * @param $status
     * @return array
     */
    public function getLogFirstData($module, $status)
    {
        $res = model("v3_upgrade_log")->getFirstData([ [ 'module', '=', $module ], [ 'status', '=', $status ] ], 'id,module,title,create_time,remark,status', 'create_time desc');
        return $this->success($res);
    }

    /**
     * 获取日志分页
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getLogPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = 'id,module,title,create_time,remark,status')
    {
        $list = model('v3_upgrade_log')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }
}