<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\web;

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 帮助中心管理
 * @author Administrator
 *
 */
class Help extends BaseModel
{
    /****************************************************************帮助******************************************/
    /**
     * 添加帮助
     * @param array $data
     */
    public function addHelp($data)
    {
        $help_id = model('help')->add($data);
        return $this->success($help_id);
    }

    /**
     * 修改帮助
     * @param array $data
     */
    public function editHelp($data, $condition)
    {
        $res = model('help')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除帮助
     * @param $condition
     * @return array
     */
    public function deleteHelp($condition)
    {
        $res = model('help')->delete($condition);
        return $this->success($res);
    }

    /**
     * 获取帮助详情
     * @param $help_id
     * @return array
     */
    public function getHelpInfo($help_id, $field = 'id, title, content, class_id, class_name, sort, link_address, create_time, modify_time')
    {
        $res = model('help')->getInfo([ [ 'id', '=', $help_id ] ], $field);
        return $this->success($res);
    }

    /**
     * 获取菜单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getHelpList($condition = [], $field = 'id, title, content, class_id, class_name, sort, create_time', $order = '', $limit = null)
    {
        $list = model('help')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取帮助分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getHelpPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'sort asc,create_time desc', $field = 'id, title, content, class_id, class_name, sort, link_address, create_time')
    {
        $list = model('help')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 修改排序
     * @param int $sort
     * @param int $help_id
     */
    public function modifyHelpSort($sort, $help_id)
    {
        $res = model('help')->update([ 'sort' => $sort ], [ [ 'id', '=', $help_id ] ]);
        return $this->success($res);
    }

    /****************************************************************帮助表******************************************/
    /**
     * 添加帮助类型
     * @param array $data
     */
    public function addHelpClass($data)
    {

        $model = model('help_class');
        $res = $model->add($data);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }

    /**
     * 修改帮助类型(主键修改，不修改排序)
     * @param array $data
     * @param int $class_id
     */
    public function editHelpClass($data, $class_id)
    {
        $res = model('help_class')->update($data, [ [ 'class_id', '=', $class_id ] ]);
        if ($res !== false) {
            model('help')->update([ 'class_name' => $data[ 'class_name' ] ], [ [ 'class_id', '=', $class_id ] ]);
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }

    /**
     * 获取帮助分类详情
     * @param array $condition
     * @param string $field
     */
    public function getHelpClassInfo($condition, $field = 'class_id, class_name, sort')
    {
        $res = model('help_class')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取帮助分类分页列表
     * @param array $condition
     * @param number $page
     * @param number $page_size
     * @param string $order
     * @param string $field
     */
    public function getHelpClassPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'sort asc,create_time desc', $field = 'class_id, class_name, sort')
    {

        $res = model('help_class')->pageList($condition, $field, $order, $page, $page_size);
        $check_condition = array_column($condition, 2, 0);
        if ($check_condition) {
            unset($check_condition[ 'class_name' ]);
        }
        $help_data = model('help')->getList($check_condition, 'class_id');
        $arr = [];
        if ($help_data) {
            $arr = array_column($help_data, 'class_id');
            $arr = array_unique($arr);
        }
        if ($res[ 'list' ]) {
            foreach ($res[ 'list' ] as $k => $v) {

                if (in_array($v[ 'class_id' ], $arr)) {
                    $res[ 'list' ][ $k ][ 'child' ] = 1;
                } else {
                    $res[ 'list' ][ $k ][ 'child' ] = 0;
                }
            }
        }
        return $this->success($res);
    }

    /**
     * 获取帮助分类列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param number $limit
     */
    public function getHelpClassList($condition = [], $field = 'class_id, class_name, sort', $order = '', $limit = null)
    {
        $res = model('help_class')->getList($condition, $field, $order, $alias = 'a', $join = [], $group = '', $limit);
        return $this->success($res);
    }

    /**
     * 删除帮助分类
     * @param array $condition
     */
    public function deleteHelpClass($condition)
    {

        $model = model('help_class');
        $res = $model->delete($condition);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }

    /**
     * 修改排序
     * @param int $sort
     * @param int $class_id
     */
    public function modifyHelpClassSort($sort, $class_id)
    {
        $res = model('help_class')->update([ 'sort' => $sort ], [ [ 'class_id', '=', $class_id ] ]);
        return $this->success($res);
    }

    /**
     * 生成推广二维码链接
     * @param $qrcode_param
     * @param $site_id
     * @return array
     */
    public function urlQrcode($qrcode_param, $app_type, $site_id)
    {
        $h5_page = '/pages_tool/help/detail';
        $pc_page = '/cms/help/detail';
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'pc_data' => $qrcode_param,
            'page' => $h5_page,
            'h5_path' => $h5_page . '?id=' . $qrcode_param[ 'id' ],
            'pc_page' => $pc_page,
            'pc_path' => $pc_page . '?id=' . $qrcode_param[ 'id' ],
            'qrcode_path' => 'upload/qrcode/help',
            'qrcode_name' => 'help_qrcode' . $qrcode_param[ 'id' ] . '_' . $site_id,
            'app_type' => $app_type,
        ];

        $solitaire = event('PromotionQrcode', $params);
        return $this->success($solitaire[ 0 ]);
    }

}
