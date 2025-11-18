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

use addon\weapp\model\Config as WeappConfigModel;
use app\Controller;
use app\model\store\Store as StoreModel;
use app\model\system\Addon;
use app\model\system\Group as GroupModel;
use app\model\system\Menu;
use app\model\system\Site;
use app\model\system\User as UserModel;
use app\model\upload\Config as UploadConfigModel;
use app\model\web\Config as ConfigModel;
use app\model\web\DiyView as DiyViewModel;
use think\App;
use think\facade\Config;

class BaseShop extends Controller
{

    protected $uid;
    protected $user_info;
    protected $url;
    protected $group_info;
    protected $site_id;
    protected $store_id;
    protected $shop_info;
    protected $store_info;
    protected $app_module = SHOP_MODULE;
    protected $replace = [];
    protected $addon = '';

    /**
     * 模板空布局文件，初始化数据
     * @var string|bool
     */
    protected $layout = 'app/shop/view/layout/base.html';

    // 模版布局文件，加载菜单+内容，default：默认模板，后续支持扩展
    protected $template = 'app/shop/view/layout/default.html';


    public function __construct(App $app = null)
    {
        //执行父类构造函数
        parent::__construct();

        $this->app = $app;

        //检测基础登录
        $this->site_id = request()->siteid();

        $user_model = new UserModel();
        $this->app_module = $user_model->loginModule($this->site_id);
        $this->uid = $user_model->uid($this->app_module, $this->site_id);

        // 验证登录
        if (empty($this->uid)) {
            $this->redirect(url('shop/login/login'));
        }

        $this->url = request()->parseUrl();
        $this->addon = request()->addon() ? request()->addon() : '';
        $this->user_info = $user_model->userInfo($this->app_module, $this->site_id);

        $this->assign('user_info', $this->user_info);
        $this->assign('app_module', $this->app_module);

        // 检测用户组
        $this->getGroupInfo();

        if ($this->app_module == 'store') {
            //检测用户组,通过用户组查询对应门店id
            $store_model = new StoreModel();
            $this->store_info = $store_model->getStoreInfo([ [ 'store_id', '=', $this->store_id ] ])[ 'data' ];
            if ($this->store_info[ 'is_frozen' ]) {
                $this->error('该门店已关闭，请联系店铺管理员开启');
            }
        }

        //权限检测
        if (!($this->user_info[ 'is_admin' ] == 1 || $this->group_info['is_system'] == 1)) {
            $check_res = $user_model->checkAndGetRedirectUrl(['url' => $this->url, 'app_module' => $this->app_module], $this->group_info);
            if(!$check_res['is_auth']){
                $error_tips = '权限不足，请联系管理员';
                if (request()->isJson()) {
                    echo json_encode(error(-1, $error_tips));exit;
                } elseif (request()->isAjax()) {
                    if(request()->type() == 'html') $this->error($error_tips);
                }else{
                    if(!empty($check_res[ 'redirect_url' ])){
                        $this->redirect(addon_url($check_res[ 'redirect_url' ]));
                    }else{
                        $this->error($error_tips, url('shop/login/login'));
                    }
                }
            }
        }

        //获取店铺信息
        $site_model = new Site();
        $this->shop_info = $site_model->getSiteInfo([ [ 'site_id', '=', $this->site_id ] ], 'site_id,site_name,logo,seo_keywords,seo_description, create_time')[ 'data' ];
        $this->assign('shop_info', $this->shop_info);

        // 加载自定义图标库
        $diy_view = new DiyViewModel();
        $diy_icon_url = $diy_view->getIconUrl()[ 'data' ];
        $this->assign('load_diy_icon_url', $diy_icon_url);

        // 上传图片配置
        $uplode_config_model = new UploadConfigModel();
        $upload_config = $uplode_config_model->getUploadConfig($this->site_id);
        $this->assign('upload_max_filesize', $upload_config[ 'data' ][ 'value' ][ 'upload' ][ 'max_filesize' ] / 1024);

        // 后台主题风格
        $config_model = new ConfigModel();
        $theme_config = $config_model->getThemeConfig()[ 'data' ][ 'value' ];
        $this->assign('theme_config', $theme_config);

        // 在设置模板布局之前，设置变量输出
        $config_view = Config::get('view');
        $config_view[ 'tpl_replace_string' ] = array_merge($config_view[ 'tpl_replace_string' ], $this->replace);
        Config::set($config_view, 'view');

        if (!request()->isAjax()) {
            $this->initBaseInfo();
            $this->loadTemplate();
        }

    }

    /**
     * 加载基础信息
     */
    private function initBaseInfo()
    {

        $this->assign('url', $this->url);

        //加载版权信息
        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright();
        $this->assign('copyright', $copyright[ 'data' ][ 'value' ]);

        // 查询小程序配置信息
        if (addon_is_exit('weapp', $this->site_id)) {
            $weapp_config_model = new WeappConfigModel();
            $weapp_config = $weapp_config_model->getWeappConfig($this->site_id)[ 'data' ][ 'value' ];
            $this->assign('base_weapp_config', $weapp_config);
        }
    }

    /**
     * 获取当前用户的用户组
     */
    private function getGroupInfo()
    {
        $group_model = new GroupModel();
        $group_info_result = $group_model->getGroupInfo([ [ 'group_id', '=', $this->user_info[ 'group_id' ] ], [ 'app_module', '=', $this->app_module ] ]);
        $this->group_info = $group_info_result[ 'data' ];
        // 验证登录
        if (empty($this->group_info)) {
            $this->redirect(url('shop/login/login'));
        }
        if ($this->app_module == 'store') {
            //门店登录,用户权限对应站点id是门店id
            $this->store_id = $this->group_info[ 'site_id' ];
        }
    }

    /**
     * 获取菜单
     */
    private function getMenuList()
    {
        $field = 'id, app_module, addon, title, name, parent, level, url, is_show, sort, picture, picture_select,type';
        $menu_model = new Menu();
        if ($this->user_info[ 'is_admin' ] || $this->group_info[ 'is_system' ] == 1) {
            $menus = $menu_model->getMenuList([ [ 'app_module', '=', $this->app_module ], ], $field, 'level asc,sort asc');
        } else {
            $menu_array = "'".str_replace(',',"','", $this->group_info[ 'menu_array' ])."'";
            $menus = $menu_model->getMenuList([
                [ 'app_module', '=', $this->app_module ],
                ['', 'exp', \think\facade\Db::raw("name in ({$menu_array}) or is_control = 0")]
            ], $field, 'level asc,sort asc');
        }

        return $menus[ 'data' ];
    }

    /**
     * 添加日志
     * @param $action_name
     * @param array $data
     */
    protected function addLog($action_name, $data = [])
    {
        $user = new UserModel();
        $user->addUserLog($this->uid, $this->user_info[ 'username' ], $this->site_id, $action_name, $data);
    }

    public function __call($method, $args)
    {
        return $this->fetch(app()->getRootPath() . 'public/error/error.html');
    }

    /**
     * 加载模板页面
     */
    public function loadTemplate()
    {
        if (!request()->isAjax()) {

            // 设置模版布局
            $this->app->view->engine()->layout($this->layout);

            // 请求方式，空：返回菜单+内容，iframe：iframe标签模式，返回内容，download：返回下载资源
            $request_mode = input('request_mode', '');
            if (empty($request_mode)) {
                echo $this->fetch($this->template);
                exit;
            }

        }
    }

    /**
     * 查询菜单列表
     * @return array
     */
    public function menu()
    {
        if (request()->isJson()) {
            $res = [];
            $menus = $this->getMenuList();
            $addon_model = new Addon();
            $res['quick_addon_menu'] = $addon_model->getAddonQuickMenuConfig($this->site_id, $this->app_module)[ 'data' ][ 'value' ];
            $res['user_is_admin'] = $this->user_info[ 'is_admin' ] || $this->group_info[ 'is_system' ] == 1 ? 1 : 0;
            $res['yuan_menu'] = $menus;
            return $res;
        }
    }

    /**
     * 获取用户信息
     * @return mixed
     */
    protected function getUserInfo($uid = null)
    {
        $condition = array (
            ['uid', '=', $uid ?? $this->uid],
            ['site_id', '=', $this->site_id],
            ['app_module', '=', $this->app_module],
        );
        $user_model = new UserModel();
        $user_info = $user_model->getUserInfo($condition, '*')['data'];
        return $user_info;
    }
}