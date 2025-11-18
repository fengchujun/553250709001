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

use app\Controller;
use app\model\system\Site;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use extend\QRcode as QRcodeExtend;
use think\App;
use think\captcha\facade\Captcha as ThinkCaptcha;
use think\facade\Cache;
use think\facade\Session;

/**
 * 登录
 * Class Login
 * @package app\shop\controller
 */
class Login extends Controller
{

    protected $app_module = 'shop';
    protected $login_module;
    protected $site_id;
    protected $uid;
    protected $user_info;
    protected $app;

    /**
     * 模板布局
     * @var string|bool
     */
    protected $layout = 'layout/base';

    public function __construct(App $app = null)
    {
        parent::__construct();

        $this->app = $app;

        //检测基础登录
        $this->site_id = request()->siteid();
        if (empty($this->site_id)) {
            $this->site_id = input('site_id', 0);
            request()->siteid($this->site_id);
        }

        // 后台主题风格
        $config_model = new ConfigModel();
        $theme_config = $config_model->getThemeConfig()[ 'data' ][ 'value' ];
        $this->assign('theme_config', $theme_config);

        // 设置模版布局
        $this->app->view->engine()->layout($this->layout);
    }

    /**
     * 登录首页
     * @return mixed
     */
    public function login()
    {
        //如果登录状态还在就直接进入后台
        if (!request()->isJson()) {
            $this->site_id = request()->siteid();
            $user_model = new UserModel();
            $this->app_module = $user_model->loginModule($this->site_id);
            $this->uid = $user_model->uid($this->app_module, $this->site_id);
            $this->user_info = $user_model->userInfo($this->app_module, $this->site_id);
            if(!empty($this->uid) && !empty($this->user_info)) $this->redirect(addon_url('shop/index/index'));
        }

        $site_id = request()->siteid();
        $config_model = new ConfigModel();
        $config_info = $config_model->getCaptchaConfig();
        $config = $config_info[ 'data' ][ 'value' ];
        $this->assign('shop_login', $config[ 'shop_login' ]);
        if (request()->isJson()) {
            $username = input('username', '');
            $password = input('password', '');
            $login_module = input('login_module', 'shop');
            $user_model = new UserModel();
            if ($config['shop_login'] == 1) {
                $captcha_result = $this->checkCaptcha();
                //验证码
                if ($captcha_result['code'] != 0) {
                    return $captcha_result;
                }
            }
            $result = $user_model->login($username, $password, $login_module, $site_id);
            //登录后查看补丁
            Session::set('SYS_PATCH_ALERT',true);
            return $result;
        } else {
            //平台配置信息
            $site_model = new Site();
            $shop_info = $site_model->getSiteInfo([ [ 'site_id', '=', $site_id ] ], 'site_name,logo,seo_keywords,seo_description, create_time');

            $this->assign('shop_info', $shop_info[ 'data' ]);

            //加载版权信息
            $copyright = $config_model->getCopyright();
            $this->assign('copyright', $copyright[ 'data' ][ 'value' ]);

            //获取其他端 访问二维码
            $addon = [];
            $resultData = [];
            if (addon_is_exit('mobileshop', $site_id)) {
                $config_model = new \addon\mobileshop\model\Config();
                $addon[ 'mobileshop' ] = $config_model->getMShopDomainName($site_id);
                $addon[ 'weapp' ] = $config_model->getWeappConfig($site_id);
                if ($addon[ 'mobileshop' ][ 'code' ] == 0 && !empty($addon[ 'mobileshop' ][ 'data' ])) {
                    $path = 'upload/qrcode/shop' . '/';
                    $name = 'shop_qrcode_' . $site_id . '_' . 'mobileshop' . '.png';
                    $filename = $path . $name;
                    if (!file_exists($path)) {
                        mkdir($path, intval('0755', 8), true);
                    }
                    if (!file_exists($filename)) {
                        $url = $addon[ 'mobileshop' ][ 'data' ][ 'value' ][ 'domain_name_mobileshop' ];
                        QRcodeExtend::png($url, $filename, 'L', 4, 1);
                    }
                    $resultData[ 0 ][ 'message' ] = 'H5端';
                    $resultData[ 0 ][ 'img' ] = $filename;
                }
                if ($addon[ 'weapp' ][ 'code' ] == 0 && !empty($addon[ 'weapp' ][ 'data' ][ 'value' ]) && !empty($addon[ 'weapp' ][ 'data' ][ 'value' ][ 'qrcode' ])) {
                    $resultData[ 1 ][ 'message' ] = '小程序端';
                    $resultData[ 1 ][ 'img' ] = $addon[ 'weapp' ][ 'data' ][ 'value' ][ 'qrcode' ];
                }
            }

            // 验证码
            $captcha = $this->captcha()[ 'data' ];
            $this->assign('captcha', $captcha);
            $this->assign('port_data', $resultData);

            return $this->fetch('login/login');
        }
    }

    /**
     * 退出操作
     */
    public function logout()
    {
        $site_id = request()->siteid();
        $user_model = new UserModel();
        $login_module = $user_model->loginModule($site_id);
        $user_model->clearLogin($login_module, $site_id);
        $this->redirect(url('shop/login/login'));
    }

    /**
     * 验证码
     */
    public function captcha()
    {
        $captcha_data = ThinkCaptcha::create(null, true);
        $captcha_id = md5(uniqid(null, true));
        // 验证码10分钟有效
        Cache::set($captcha_id, $captcha_data[ 'code' ], 600);
        return success(0, '', [ 'id' => $captcha_id, 'img' => $captcha_data[ 'img' ] ]);
    }

    /**
     * 验证码验证
     */
    public function checkCaptcha()
    {
        $captcha = input('captcha', '');
        $captcha_id = input('captcha_id', '');

        if (empty($captcha)) return error(-1, '请输入验证码');

        $captcha_data = Cache::pull($captcha_id);
        if (empty($captcha_data)) return error(-1, '验证码已失效');

        if ($captcha != $captcha_data) return error(-1, '验证码错误');

        return success();
    }

    /**
     * 清理缓存
     */
    public function clearCache()
    {
        Cache::clear();
        return success('', '缓存更新成功', '');
    }

    /**
     * 修改密码
     * */
    public function modifyPassword()
    {
        if (request()->isJson()) {
            $site_id = request()->siteid();
            $user_model = new UserModel();
            $uid = $user_model->uid($this->app_module, $site_id);

            $old_pass = input('old_pass', '');
            $new_pass = input('new_pass', '123456');

            $condition = [
                [ 'uid', '=', $uid ],
                [ 'password', '=', data_md5($old_pass) ],
                [ 'site_id', '=', $site_id ]
            ];

            $res = $user_model->modifyAdminUserPassword($condition, $new_pass);
            if ($res[ 'code' ] == 0) {
                // 更新密码
                $user_info = $user_model->userInfo($this->app_module, $site_id);
                $user_info[ 'password' ] = data_md5($new_pass);
                $user_model->setUserInfo($user_info);
            }
            return $res;
        }
    }

}