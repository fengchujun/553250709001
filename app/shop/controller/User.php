<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use app\model\store\Store;
use app\model\system\Group;
use app\model\system\Menu;
use app\model\system\User as UserModel;
use addon\cashier\model\Group as StoreUserGroup;
use app\model\system\UserGroup;
use think\facade\Db;

/**
 * 用户
 * Class User
 * @package app\shop\controller
 */
class User extends BaseShop
{
    /**
     * 用户列表
     * @return mixed
     */
    public function user()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', '');
            $search_keys = input('search_keys', '');

            $condition = [];
            $condition[] = ['', 'exp', Db::raw("uid = ".$this->uid." or create_user_data like '%\"".$this->uid."\"%'")];
            $condition[] = ['site_id', '=', $this->site_id];
            $condition[] = ['app_module', '=', $this->app_module];
            if (!empty($search_keys)) {
                $condition[] = ['username', 'like', '%' . $search_keys . '%'];
            }
            if ($status != '') {
                $condition['status'] = ['status', '=', $status];
            }

            $user_model = new UserModel();
            $list = $user_model->getUserPageList($condition, $page, $page_size, 'create_time desc', '*');
            if (!empty($list['data']['list']) && addon_is_exit('cashier', $this->site_id)) {
                $uids = '';
                foreach ($list['data']['list'] as $k => $v) {
                    if (empty($uids)) {
                        $uids = $v['uid'];
                    } else {
                        $uids = $uids . ',' . $v['uid'];
                    }
                }
                $join = [
                    ['store s', 's.store_id = ug.store_id', 'left'],
                    ['cashier_auth_group cag', 'cag.group_id = ug.group_id', 'left']
                ];
                $user_group_list = (new UserGroup())->getUserList([['ug.uid', 'in', $uids]], 'ug.uid,s.store_name,cag.group_name', '', 'ug', $join)['data'];
                foreach ($list['data']['list'] as $k => $v) {
                    $list['data']['list'][$k]['user_group_list'] = [];
                    foreach ($user_group_list as $k_user_group => $v_user_group) {
                        if ($v['uid'] == $v_user_group['uid']) {
                            $list['data']['list'][$k]['user_group_list'][] = $v_user_group;
                        }
                    }
                }
            }
            return $list;
        } else {
            $this->assign('store_is_exist', addon_is_exit('store', $this->site_id));
            $this->assign('cashier_is_exist', addon_is_exit('cashier', $this->site_id));
            return $this->fetch('user/user_list');
        }
    }

    /**
     * 添加用户
     * @return mixed
     */
    public function addUser()
    {
        if (request()->isJson()) {

            $username = input("username", "");
            $password = input("password", "");
            $group_id = input("group_id", "");
            $store = input("store", "[]");

            $user_model = new UserModel();
            $data = array (
                "username" => $username,
                "password" => $password,
                "group_id" => $group_id,
                "app_module" => $this->app_module,
                "site_id" => $this->site_id,
                "store" => json_decode($store, true),
                "create_uid" => $this->uid,
            );

            $result = $user_model->addUser($data, '', 'add');
            return $result;
        } else {
            //当前用户信息
            $create_user_info = $this->getUserInfo();
            if(empty($create_user_info)){
                $this->error('用户信息有误');
            }
            $create_user_store_group = array_column($create_user_info['user_group_list'], null, 'store_id');
            $this->assign('create_user_store_group', $create_user_store_group);

            $group_model = new Group();
            $group_condition = [
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
                ['', 'exp', Db::raw("group_id = ".$this->user_info['group_id']." or create_user_data like '%\"".$this->uid."\"%'")],
            ];
            $group_list_result = $group_model->getGroupList($group_condition);
            $group_list = $group_list_result['data'];
            $this->assign('group_list', $group_list);

            $cashier_is_exist = addon_is_exit('cashier', $this->site_id);
            $this->assign('store_is_exist', addon_is_exit('store', $this->site_id));
            $this->assign('cashier_is_exist', $cashier_is_exist);
            if ($cashier_is_exist) {
                $store_user_group_condition = [
                    ['', 'exp', Db::raw("keyword = '' OR site_id = {$this->site_id}")],
                ];
                if($create_user_info['create_uid'] == 0){
                    $store_user_group_condition[] = ['create_uid', 'in', [0,$this->uid]];
                }else{
                    $create_user_group_ids = array_column($create_user_info['user_group_list'], 'group_id');
                    if(empty($create_user_group_ids)) $create_user_group_ids = [-1];
                    $create_user_group_ids = join(',', $create_user_group_ids);
                    $store_user_group_condition[] = ['', 'exp', Db::raw("group_id in (".$create_user_group_ids.") or create_uid = ".$create_user_info['uid'])];
                }
                $store_user_group = (new StoreUserGroup())->getGroupList($store_user_group_condition, 'group_id,group_name,store_id')['data'];
                $this->assign('store_user_group', $store_user_group);
                $store_info = (new Store())->getDefaultStore($this->site_id)['data'] ?? [];
                $this->assign('default_store_id', $store_info['store_id'] ?? 0);

                //可以管理的门店
                if($create_user_info['create_uid'] > 0){
                    $store_ids = array_column($create_user_info['user_group_list'], 'store_id');
                    if(empty($store_ids)) $store_ids = [-1];
                    $store_ids = join(',', $store_ids);
                }else{
                    $store_ids = '';
                }
                $this->assign('store_ids', $store_ids);
            }

            return $this->fetch('user/add_user');
        }
    }

    /**
     * 编辑用户
     * @return mixed
     */
    public function editUser()
    {
        $user_model = new UserModel();
        if (request()->isJson()) {
            $group_id = input("group_id", "");
            $status = input("status", "");
            $uid = input("uid", 0);
            $store = input("store", "[]");

            //用户信息
            $condition = array (
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $user_info_result = $user_model->getUserInfo($condition, 'is_admin, uid');
            $user_info = $user_info_result['data'];

            if ($user_info['is_admin']) {
                return error('-1', '超级管理员不可编辑');
            }

            $condition = array (
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $data = array (
                "group_id" => $group_id,
                "status" => $status,
                "store" => json_decode($store, true),
            );

            $this->addLog('编辑用户:' . $uid);

            $result = $user_model->editUser($data, $condition);
            return $result;
        } else {
            $uid = input('uid', 0);
            //用户信息
            $condition = array (
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $user_info_result = $user_model->getUserInfo($condition, '*');
            $user_info = $user_info_result['data'];

            if (empty($user_info)) $this->error('未获取到用户数据', href_url('shop/user/user'));
            if ($user_info['is_admin']) $this->error('超级管理员不可编辑');

            $this->assign('uid', $uid);
            $this->assign('edit_user_info', $user_info);

            //创建用户数据
            $create_user_info = $this->getUserInfo($user_info['create_uid']);
            if(empty($create_user_info)) $create_user_info = ['uid' => $user_info['create_uid'], 'create_uid' => 0, 'group_id' => 0, 'user_group_list' => []];
            $create_user_store_group = array_column($create_user_info['user_group_list'], null, 'store_id');
            $this->assign('create_user_store_group', $create_user_store_group);

            //用户组
            $group_model = new Group();
            $group_condition = [
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
                ['', 'exp', Db::raw("group_id = ".$create_user_info['group_id']." or create_user_data like '%\"".$create_user_info['uid']."\"%'")],
            ];
            $group_list_result = $group_model->getGroupList($group_condition);
            $group_list = $group_list_result['data'];
            $this->assign('group_list', $group_list);

            $cashier_is_exist = addon_is_exit('cashier', $this->site_id);
            $this->assign('store_is_exist', addon_is_exit('store', $this->site_id));
            $this->assign('cashier_is_exist', $cashier_is_exist);
            if ($cashier_is_exist) {
                $store_user_group_condition = [
                    ['', 'exp', Db::raw("keyword = '' OR site_id = {$this->site_id}")],
                ];
                if($create_user_info['create_uid'] == 0){
                    $store_user_group_condition[] = ['create_uid', 'in', [0,$this->uid]];
                }else{
                    $create_user_group_ids = array_column($create_user_info['user_group_list'], 'group_id');
                    if(empty($create_user_group_ids)) $create_user_group_ids = [-1];
                    $create_user_group_ids = join(',', $create_user_group_ids);
                    $store_user_group_condition[] = ['', 'exp', Db::raw("group_id in (".$create_user_group_ids.") or create_uid = ".$create_user_info['uid'])];
                }
                $store_user_group = (new StoreUserGroup())->getGroupList($store_user_group_condition, 'group_id,group_name,store_id')['data'];
                $this->assign('store_user_group', $store_user_group);
                $store_info = (new Store())->getDefaultStore($this->site_id)['data'] ?? [];
                $this->assign('default_store_id', $store_info['store_id'] ?? 0);

                //可以管理的门店
                if($create_user_info['create_uid'] > 0){
                    $store_ids = array_column($create_user_info['user_group_list'], 'store_id');
                    if(empty($store_ids)) $store_ids = [-1];
                    $store_ids = join(',', $store_ids);
                }else{
                    $store_ids = '';
                }
                $this->assign('store_ids', $store_ids);
            }

            return $this->fetch('user/edit_user');
        }

    }

    /**
     * 删除用户
     */
    public function deleteUser()
    {
        if (request()->isJson()) {
            $uid = input('uid', 0);
            $user_model = new UserModel();

            //用户信息
            $condition = array (
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $user_info_result = $user_model->getUserInfo($condition, 'is_admin, uid');
            $user_info = $user_info_result['data'];

            if ($user_info['is_admin']) {
                return error('-1', '超级管理员不可编辑');
            }

            $condition = array (
                ['uid', '=', $uid],
                ['app_module', '=', $this->app_module],
                ['site_id', '=', $this->site_id],
            );
            $result = $user_model->deleteUser($condition);
            return $result;
        }
    }

    public function childUserCount()
    {
        if (request()->isJson()) {
            $uid = input('uid', 0);
            $user_model = new UserModel();
            $uid_arr = $user_model->getUserColumn([['create_user_data', 'like', '%"'.$uid.'"%']], 'uid');
            return $user_model->success(count($uid_arr));
        }
    }

    /**
     * 清除后台所有用户的登录信息
     */
    public function deleteUserLoginInfo()
    {
        $app_module = $this->app_module;
        $site_id = $this->site_id;
        $user_model = new UserModel();
        $result = $user_model->deleteUserLoginInfo($app_module, $site_id);
        return $result;
    }

    /**
     * 编辑管理员状态
     */
    public function modifyUserStatus()
    {
        if (request()->isJson()) {
            $uid = input('uid', 0);
            $status = input('status', 0);
            $user_model = new UserModel();

            //用户信息
            $condition = [
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            ];
            $user_info_result = $user_model->getUserInfo($condition, 'is_admin, uid');
            $user_info = $user_info_result['data'];

            if ($user_info['is_admin']) {
                return error('-1', '超级管理员不可编辑');
            }

            $condition = array (
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $result = $user_model->modifyUserStatus($status, $condition);
            return $result;
        }
    }

    /**
     * 重置密码
     */
    public function modifyPassword()
    {
        if (request()->isJson()) {
            $password = input('password', '123456');
            $uid = input('uid', 0);
            $site_id = $this->site_id;
            $user_model = new UserModel();

            //用户信息
            $condition = [
                ['uid', '=', $uid],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module]
            ];
            $user_info_result = $user_model->getUserInfo($condition, 'is_admin, uid');
            $user_info = $user_info_result['data'];

            if ($user_info['is_admin']) {
                return error('-1', '超级管理员不可编辑');
            }

            return $user_model->modifyUserPassword($password, [['uid', '=', $uid], ['site_id', '=', $site_id]]);
        }
    }

    /**
     * 用户列表
     * @return mixed
     */
    public function group()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_keys = input('search_keys', '');

            $condition = [
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
                ['', 'exp', Db::raw("group_id = ".$this->user_info['group_id']." or create_user_data like '%\"".$this->uid."\"%'")],
            ];
            $group_model = new Group();
            $list = $group_model->getGroupPageList($condition, $page, $page_size, 'group_id desc', '*');
            return $list;
        } else {
            return $this->fetch('user/group_list');
        }
    }

    /**
     * 添加用户组
     * @return mixed
     */
    public function addGroup()
    {
        if (request()->isJson()) {
            $group_name = input('group_name', '');
            $menu_array = input('menu_array', '');
            $desc = input('desc', '');
            $group_model = new Group();
            $data = array (
                'group_name' => $group_name,
                'site_id' => $this->site_id,
                'app_module' => $this->app_module,
                'group_status' => 1,
                'menu_array' => $menu_array,
                'desc' => $desc,
                'is_system' => 0,
                'create_time' => time(),
                'create_uid' => $this->uid,
            );
            $result = $group_model->addGroup($data);
            return $result;
        } else {
            $menu_model = new Menu();
            $condition = [
                ['app_module', '=', $this->app_module],
                ['is_control', '=', 1],
            ];
            if(!empty($this->group_info['menu_array'])){
                $condition[] = ['name', 'in', $this->group_info['menu_array']];
            }
            $menu_list = $menu_model->getMenuList($condition, '*', 'level asc,sort ASC');
            $menu_tree = list_to_tree($menu_list['data'], 'name', 'parent', 'child_list', '');
            $this->assign('tree_data', $menu_tree);
            return $this->fetch('user/add_group');
        }
    }

    /**
     * 编辑用户组
     * @return mixed
     */
    public function editGroup()
    {
        $group_model = new Group();
        if (request()->isJson()) {
            $group_name = input('group_name', '');
            $menu_array = input('menu_array', '');
            $group_id = input('group_id', 0);
            $desc = input('desc', '');

            $data = array (
                'group_name' => $group_name,
                'menu_array' => $menu_array,
                'desc' => $desc,
            );
            $condition = array (
                ['group_id', '=', $group_id],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module]
            );
            $result = $group_model->editGroup($data, $condition);
            return $result;
        } else {
            $group_id = input('group_id', 0);
            $condition = array (
                ['group_id', '=', $group_id],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module]
            );
            $group_info_result = $group_model->getGroupInfo($condition);
            $group_info = $group_info_result['data'];

            if (empty($group_info)) $this->error('未获取到用户组数据', href_url('shop/user/group'));

            $this->assign('group_info', $group_info);
            $this->assign('group_id', $group_id);

            //创建用户
            $create_user_info = $this->getUserInfo($group_info['create_uid']);
            $create_user_group_info = $group_model->getGroupInfo([
                ['group_id', '=', $create_user_info['group_id']],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            ])['data'];

            //获取菜单权限
            $menu_model = new Menu();
            $condition = [
                ['app_module', '=', $this->app_module],
                ['is_control', '=', 1],
            ];
            if(!empty($create_user_group_info['menu_array'])){
                $menu_array = $group_info['menu_array'].','.$create_user_group_info['menu_array'];
                $menu_array = array_unique(explode(',', $menu_array));
                $condition[] = ['name', 'in', $menu_array];
            }
            $menu_list = $menu_model->getMenuList($condition, '*', 'level asc,sort ASC');

            //处理选中数据
            $group_array = $group_info['menu_array'];
            $checked_array = explode(',', $group_array);
            foreach ($menu_list['data'] as $key => $val) {
                if (in_array($val['name'], $checked_array)) {
                    $menu_list['data'][$key]['checked'] = true;
                } else {
                    $menu_list['data'][$key]['checked'] = false;
                }
            }
            $menu_tree = list_to_tree($menu_list['data'], 'name', 'parent', 'child_list', '');
            $this->assign('tree_data', $menu_tree);

            return $this->fetch('user/edit_group');
        }
    }

    /**
     * 删除用户组
     */
    public function deleteGroup()
    {
        if (request()->isJson()) {
            $group_id = input('group_id', '');
            $condition = array (
                ['group_id', '=', $group_id],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $group_model = new Group();
            $result = $group_model->deleteGroup($condition);
            return $result;
        }
    }

    /**
     * 用户组状态
     */
    public function modifyGroupStatus()
    {
        if (request()->isJson()) {
            $group_id = input('group_id', 0);
            $status = input('status', 0);
            $group_model = new Group();
            $condition = array (
                ['group_id', '=', $group_id],
                ['site_id', '=', $this->site_id],
                ['app_module', '=', $this->app_module],
            );
            $result = $group_model->modifyGroupStatus($status, $condition);
            return $result;
        }
    }

    /**
     * 用户日志
     */
    public function userLog()
    {
        $user_model = new UserModel();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $uid = input('uid', '0');

            $condition = [];
            $condition[] = ['site_id', '=', $this->site_id];
            $search_keys = input('search_keys', '');
            if (!empty($search_keys)) {
                $condition[] = ['action_name', 'like', '%' . $search_keys . '%'];
            }
            if ($uid > 0) {
                $condition[] = ['uid', '=', $uid];
            }

            $list = $user_model->getUserLogPageList($condition, $page, $page_size, 'create_time desc');
            return $list;
        } else {
            //获取站点所有用户
            $condition = [];
            $condition[] = ['site_id', '=', $this->site_id];
            $condition[] = ['app_module', '=', $this->app_module];
            $user_list_result = $user_model->getUserList($condition);
            $user_list = $user_list_result['data'];
            $this->assign('user_list', $user_list);

            return $this->fetch('user/user_log');
        }
    }

    /**
     * 批量删除日志
     */
    public function deleteUserLog()
    {
        if (request()->isJson()) {
            $user_model = new UserModel();
            $id = input('id', '');
            $condition = array (
                ['id', 'in', $id],
                ['site_id', '=', $this->site_id],
            );
            $res = $user_model->deleteUserLog($condition);
            return $res;
        }
    }
}