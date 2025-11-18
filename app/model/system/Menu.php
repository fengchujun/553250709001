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

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 菜单表
 * @author Administrator
 *
 */
class Menu extends BaseModel
{
    public $list = [];

    /***************************************** 系统菜单开始*****************************************************************************/
    /**
     * 获取菜单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getMenuList($condition = [], $field = 'id, app_module, title, name, parent, level, url, is_show, sort, is_icon, picture, picture_select, is_control, type', $order = '', $limit = null)
    {
        $list = model('menu')->getList($condition, $field, $order, '', '', '', $limit);

        return $this->success($list);
    }

    /**
     * 获取菜单数量
     * @param $condition
     * @return array
     */
    public function getMenuCount($condition)
    {
        $count = model('menu')->getCount($condition);
        return $this->success($count);
    }

    /**
     * 获取菜单树
     * @param int $level
     * @return array
     */
    public function menuTree($level = 0)
    {
        $condition = [];
        if ($level > 0) {
            $condition = [
                [ 'level', 'elt', $level ]
            ];
        }
        $list = $this->getMenuList($condition, 'id, app_module, title, name, parent, level, url, is_show, sort, is_icon, picture, picture_select, is_control, type', 'sort asc');
        $tree = list_to_tree($list[ 'data' ], 'menu_id', 'parent', 'child_list');
        return $this->success($tree);
    }

    /**
     * 通过主键获取菜单信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getMenuInfo($condition, $field = 'id, app_module, name, title, parent, level, url, is_show, sort, `desc`, picture, is_icon, picture_select, is_control, addon, type')
    {
        $menu_info = model('menu')->getInfo($condition, $field);
        return $this->success($menu_info);
    }

    /**
     * 获取第一个菜单信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getFirstMenuInfo($condition, $field = 'id, app_module, name, title, parent, level, url, is_show, sort, `desc`, picture, is_icon, picture_select, is_control, addon, type')
    {
        $info = model('menu')->getFirstData($condition, $field, 'sort asc');
        return $this->success($info);
    }

    /**
     * 通过url和端口查询对应菜单信息
     * @param $url
     * @param $app_module
     * @param string $addon
     * @return array
     */
    public function getMenuInfoByUrl($url, $app_module, $addon = '')
    {
        $info = model('menu')->getFirstData([ [ 'url', "=", $url ], [ 'app_module', "=", $app_module ] ], 'id, app_module, name, title, parent, level, url, is_show, sort, `desc`, picture, is_icon, picture_select, is_control, addon, type', 'level desc');
        return $this->success($info);
    }

    /**
     * 刷新菜单
     * @param $app_module
     * @param $addon
     * @return array
     */
    public function refreshMenu($app_module, $addon)
    {
        try {

            if (empty($addon)) {
                $tree_name = 'config/menu_' . $app_module . '.php';
            } else {
                $tree_name = 'addon/' . $addon . '/config/menu_' . $app_module . '.php';
            }

            model('menu')->delete([ [ 'app_module', "=", $app_module ], [ 'addon', "=", $addon ] ]);

            if(!is_file(root_path().'/'.$tree_name)){
                return $this->success();
            }
            $tree = require $tree_name;

            $list = $this->getAddonMenuList($tree, $app_module, $addon);
            if (!empty($list)) {
                $res = model('menu')->addList($list);
                return $this->success($res);
            } else {
                return $this->success();
            }
        } catch (\Exception $e) {
            return $this->error(['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], $e->getMessage());
//            halt($list);
        }

    }

    /**
     * 刷新收银端权限
     * @param $addon
     * @return array
     */
    public function refreshCashierAuth($addon)
    {
        $tree_name = 'addon/' . $addon . '/config/cashier_auth.php';
        if (!file_exists($tree_name)) return $this->error();

        $tree = require $tree_name;
        if (!$tree) return $this->error();

        model('cashier_auth')->delete([ [ 'addon', '=', $addon ] ]);

        $list = [];
        $this->getCashierAuthList($tree, $addon, '', $list);
        $res = model('cashier_auth')->addList($list);

        // 清除缓存
        Cache::clear('cashier_menu');
        return $this->success();
    }

    /**
     * 获取收银端权限集
     * @param $tree
     * @param $addon
     * @param string $parent
     * @param array $list
     */
    private function getCashierAuthList($tree, $addon, $parent = '', &$list = [])
    {
        foreach ($tree as $item) {
            $children = $item[ 'children' ] ?? [];
            if (isset($item[ 'children' ])) unset($item[ 'children' ]);

            $item = array_merge($item, [ 'addon' => $addon, 'parent' => $item[ 'parent' ] ?? $parent ]);
            ksort($item);
            array_push($list, $item);

            if (!empty($children)) $this->getCashierAuthList($children, $addon, $item[ 'name' ], $list);
        }
    }

    /**
     * 刷新全部菜单
     */
    public function refreshAllMenu()
    {
        $res = [];
        $shop_menu_res = $this->refreshMenu("shop", '');
        if($shop_menu_res['code'] < 0) $res['shop'] = $shop_menu_res;
        $addon_model = new Addon();
        $addon_list = $addon_model->getAddonList([], 'name');
        $addon_list = $addon_list[ 'data' ];
        foreach ($addon_list as $k_addon => $v_addon) {
            $addon_menu_res = $this->refreshMenu('shop', $v_addon[ 'name' ]);
            if($addon_menu_res['code'] < 0) $res[$v_addon['name']] = $addon_menu_res;
        }
        return $this->success($res);
    }

    /**
     * 刷新店铺端菜单
     * @param $addon
     * @param string $app_module
     * @return array|int
     */
    public function cacheMenu($addon, $app_module = 'shop')
    {
        if (!empty($addon)) {
            $tree_name = 'addon/' . $addon . '/config/menu_' . $app_module . '.php';
        } else {
            $tree_name = $addon . '/config/menu_' . $app_module . '.php';
        }

        if (file_exists($tree_name)) {
            model('menu')->delete([ [ 'app_module', "=", $app_module ], [ 'addon', "=", $addon ] ]);
            $tree = require $tree_name;
            $list = $this->getAddonMenuList($tree, $app_module, $addon);
            if (!empty($list)) {
                $res = model('menu')->addList($list);
                return $res;
            } else {
                return $this->success();
            }
        } else {
            return $this->success();
        }
    }

    /**
     * 获取菜单
     * @param $tree
     * @param $app_module
     * @param $addon
     * @return array|\think\response\Json
     */
    public function getAddonMenuList($tree, $app_module, $addon)
    {
        try {
            $list = [];
            if (!$tree) {
                return [];
            }

            foreach ($tree as $k => $v) {
                $parent = '';
                if (isset($v[ 'parent' ])) {
                    if ($v[ 'parent' ] == '') {
                        $parent = '';
                        $level = 1;
                    } else {
                        $parent_menu_info = model('menu')->getInfo([
                            [ 'name', "=", $v[ 'parent' ] ]
                        ]);
                        if ($parent_menu_info) {
                            $parent = $parent_menu_info[ 'name' ];
                            $level = $parent_menu_info[ 'level' ] + 1;
                        } else {
                            $level = 1;
                        }
                    }
                } else {
                    $parent = '';
                    $level = 1;
                }
                $item = [
                    'app_module' => $app_module,
                    'addon' => $addon,
                    'title' => $v[ 'title' ],
                    'name' => $v[ 'name' ],
                    'parent' => $parent,
                    'level' => $level,
                    'url' => $v[ 'url' ],
                    'is_show' => $v['is_show'] ?? 1,
                    'sort' => $v['sort'] ?? 100,
                    'is_icon' => $v['is_icon'] ?? 0,
                    'picture' => $v['picture'] ?? '',
                    'picture_select' => $v['picture_selected'] ?? '',
                    'is_control' => $v['is_control'] ?? 1,
                    'desc' => $v['desc'] ?? '',
                    'type' => $v['type'] ?? 'page',//页面page 按钮button
                ];

                array_push($list, $item);

                if (isset($v[ 'child_list' ])) {
                    $this->list = [];
                    $this->menuTreeToList($v[ 'child_list' ], $app_module, $addon, $v[ 'name' ], $level + 1);
                    $list = array_merge($list, $this->list);
                }
            }
            return $list;

        } catch (\Exception $e) {
            return $this->error(-1, $e->getMessage() . ",File：" . $e->getFile() . "，line：" . $e->getLine());
        }
    }

    /**
     * 菜单树转化为列表
     * @param $tree
     * @param $app_module
     * @param string $addon
     * @param string $parent
     * @param int $level
     */
    private function menuTreeToList($tree, $app_module, $addon = '', $parent = '', $level = 1)
    {
        if (is_array($tree)) {
            foreach ($tree as $key => $value) {
                $item = [
                    'app_module' => $app_module,
                    'addon' => $addon,
                    'title' => $value[ 'title' ],
                    'name' => $value[ 'name' ],
                    'parent' => $parent,
                    'level' => $level,
                    'url' => $value[ 'url' ],
                    'is_show' => $value['is_show'] ?? 1,
                    'sort' => $value['sort'] ?? 100,
                    'is_icon' => $value['is_icon'] ?? 0,
                    'picture' => $value['picture'] ?? '',
                    'picture_select' => $value['picture_selected'] ?? '',
                    'is_control' => $value['is_control'] ?? 1,
                    'desc' => $value['desc'] ?? '',
                    'type' => $value['type'] ?? 'page',
                ];
                $refer = $value;
                if (isset($refer[ 'child_list' ])) {
                    unset($refer[ 'child_list' ]);
                    array_push($this->list, $item);
                    $p_name = $refer[ 'name' ];
                    $this->menuTreeToList($value[ 'child_list' ], $app_module, $addon, $p_name, $level + 1);
                } else {
                    array_push($this->list, $item);
                }
            }
        }
    }

    /**
     * 清空菜单表，防止自增ID越来越大
     */
    public function truncateMenu()
    {
        $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
        model('menu')->execute("TRUNCATE TABLE {$prefix}menu");
    }

    /**
     * 清空收银台菜单表，防止自增ID越来越大
     */
    public function truncateCashierAuth()
    {
        $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
        model('menu')->execute("TRUNCATE TABLE {$prefix}cashier_auth");
    }

    /**
     * 清空组件、链接表，防止自增ID越来越大
     */
    public function truncateDiyView()
    {
        $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
        model('menu')->execute("TRUNCATE TABLE {$prefix}diy_view_util");
        model('menu')->execute("TRUNCATE TABLE {$prefix}link");
    }

}