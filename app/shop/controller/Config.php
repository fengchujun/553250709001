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

use app\model\goods\Config as GoodsConfigModel;
use app\model\system\Pay;
use app\model\system\Servicer as ServicerModel;
use app\model\web\Config as ConfigModel;
use app\model\system\Api;
use extend\RSA;
use app\model\system\Upgrade;
use app\model\system\Config as SystemConfig;

/**
 * 设置 控制器
 */
class Config extends BaseShop
{
    public function copyright()
    {
        $upgrade_model = new Upgrade();
        $auth_info = $upgrade_model->authInfo();

        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright($this->site_id, $this->app_module);
        if (request()->isJson()) {
            $logo = input('logo', '');
            $data = [
                'icp' => input('icp', ''),
                'business_show_link' => input('business_show_link', ''),
                'gov_record' => input('gov_record', ''),
                'gov_url' => input('gov_url', ''),
                'market_supervision_url' => input('market_supervision_url', ''),
                'logo' => '',
                'company_name' => '',
                'copyright_link' => '',
                'copyright_desc' => ''
            ];
            if ($auth_info[ 'code' ] == 0) {
                $data[ 'logo' ] = input('logo', '');
                $data[ 'company_name' ] = input('company_name', '');
                $data[ 'copyright_link' ] = input('copyright_link', '');
                $data[ 'copyright_desc' ] = input('copyright_desc', '');
            }
            $this->addLog('修改版权配置');
            $res = $config_model->setCopyright($data, $this->site_id, $this->app_module);
            return $res;
        }
        $this->assign('is_auth', ($auth_info[ 'code' ] >= 0 ? 1 : 0));
        $this->assign('copyright_config', $copyright[ 'data' ][ 'value' ]);
        return $this->fetch('config/copyright');
    }

    /**
     * 支付管理
     */
    public function pay()
    {
        if (request()->isJson()) {
            $pay_model = new Pay();
            $list = $pay_model->getPayType([]);
            return $list;
        } else {
            return $this->fetch('config/pay');
        }
    }

    /**
     * 默认图设置
     */
    public function defaultPicture()
    {
        $upload_config_model = new ConfigModel();
        if (request()->isJson()) {
            $data = array (
                'goods' => input('goods', ''),
                'head' => input('head', ''),
                'store' => input('store', ''),
                'article' => input('article', ''),
            );
            $this->addLog('修改默认图配置');
            $res = $upload_config_model->setDefaultImg($data, $this->site_id, $this->app_module);
            return $res;
        } else {

            $upload_config_result = $upload_config_model->getDefaultImg($this->site_id, $this->app_module);
            $this->assign('default_img', $upload_config_result[ 'data' ][ 'value' ]);
            return $this->fetch('config/default_picture');
        }
    }

    /*
     * 售后保障
     */
    public function aftersale()
    {
        $goods_config_model = new GoodsConfigModel();
        if (request()->isJson()) {
            $content = input('content', '');//售后保障协议
            $is_display = input('is_display', 1);//默认显
            return $goods_config_model->setAfterSaleConfig('售后保障', $content, $this->site_id, $is_display);
        } else {

            $content = $goods_config_model->getAfterSaleConfig($this->site_id);
            $this->assign('content', $content[ 'data' ]);
            return $this->fetch('config/aftersale');
        }
    }

    /**
     * 验证码设置
     */
    public function captcha()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $data = [
                'shop_login' => input('shop_login', 0), // 后台登陆验证码是否启用 1：启用 0：不启用
                'shop_reception_login' => input('shop_reception_login', 0), // 前台登陆验证码是否启用 1：启用 0：不启用
                'shop_reception_register' => input('shop_reception_register', 0), // 前台注册验证码是否启用 1：启用 0：不启用
            ];
            return $config_model->setCaptchaConfig($data);
        } else {

            $config_info = $config_model->getCaptchaConfig();
            $this->assign('config_info', $config_info[ 'data' ][ 'value' ]);
            return $this->fetch('config/captcha');
        }
    }

    /**
     * api安全
     */
    public function api()
    {
        $api_model = new Api();
        if (request()->isJson()) {
            $is_use = input('is_use', 1);
            $public_key = input('public_key', '');
            $private_key = input('private_key', '');
            $long_time = input('long_time', '0');#限制时长 0位不限制  单位小时
            $data = array (
                'public_key' => $public_key,
                'private_key' => $private_key,
                'long_time' => $long_time
            );
            $result = $api_model->setApiConfig($data, $is_use);
            return $result;
        } else {
            $config_result = $api_model->getApiConfig();
            $config = $config_result[ 'data' ];
            $this->assign('config', $config);
            return $this->fetch('config/api');
        }
    }

    public function generateRSA()
    {
        if (request()->isJson()) {
            return RSA::getSecretKey();
        }
    }

    /**
     * 地图配置
     * @return mixed
     */
    public function map()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $tencent_map_key = input('tencent_map_key', '');
            $wap_is_open = input('wap_is_open', 0);
            $wap_valid_time = input('wap_valid_time', 0);

            $info = $config_model->checkQqMapKey($tencent_map_key, 1);
            if ($info[ 'status' ] != 0) {
                return $info;
            }
            $result = $config_model->setMapConfig([
                'tencent_map_key' => $tencent_map_key,
                'wap_is_open' => $wap_is_open,
                'wap_valid_time' => $wap_valid_time
            ]);
            return $result;
        } else {

            $config = $config_model->getMapConfig()[ 'data' ][ 'value' ];
            $this->assign('info', $config);
            return $this->fetch('config/map');
        }
    }

    /**
     * 客服配置
     */
    public function servicer()
    {
        $servicer_model = new ServicerModel();
        if (request()->isJson()) {
            $data = [
                'h5' => input('h5', []),
                'weapp' => input('weapp', []),
                'pc' => input('pc', []),
                'aliapp' => input('aliapp', []),
            ];
            return $servicer_model->setServicerConfig($data);
        } else {
            $config = $servicer_model->getServicerConfig()[ 'data' ] ?? [];
            $this->assign('config', $config[ 'value' ] ?? []);
            return $this->fetch('config/servicer');
        }

    }

    /**
     * 域名跳转配置
     */
    public function domainJumpConfig()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $jump_type = input('jump_type', '1');
            $result = $config_model->setDomainJumpConfig([
                'jump_type' => $jump_type
            ]);
            return $result;
        }
    }

    /**
     * 网站部署
     */
    public function siteDeploy()
    {
        $this->assign('root_url', __ROOT__);

        // 域名跳转配置
        $config_model = new ConfigModel();
        $config = $config_model->getDomainJumpConfig()[ 'data' ][ 'value' ];
        $this->assign('config', $config);

        // 后台主题风格列表
        $theme_list = $config_model->getThemeList()[ 'data' ];
        $this->assign('theme_list', $theme_list);

        // 后台主题风格
        $theme_config = $config_model->getThemeConfig()[ 'data' ][ 'value' ];
        $this->assign('theme_config', $theme_config);

        // 检测授权
        $upgrade_model = new Upgrade();
        $auth_info = $upgrade_model->authInfo();
        $this->assign('is_auth', ($auth_info[ 'code' ] >= 0 ? 1 : 0));

        return $this->fetch('config/site_deploy');
    }

    public function modifyConfigIsUse()
    {
        if (request()->isJson()) {
            $is_use = input('is_use', 1);
            $config_key = input('config_key', '');
            return (new SystemConfig())->modifyConfigIsUse($is_use, [['site_id', '=', $this->site_id], ['app_module', '=', $this->app_module], ['config_key', '=', $config_key]]);
        }
    }

    /**
     * 设置后台主题风格配置
     * @return array
     */
    public function setThemeConfig()
    {
        if (request()->isJson()) {
            $config_model = new ConfigModel();
            $data = [
                'title' => input('title', ''),
                'name' => input('name', ''),
                'color' => input('color', ''),
                'url' => input('url', '')
            ];
            $res = $config_model->setThemeConfig($data, $this->site_id);
            return $res;
        }
    }

}