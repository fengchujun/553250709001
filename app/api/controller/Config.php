<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 */

namespace app\api\controller;

use app\model\express\Config as ExpressConfig;
use app\model\system\Api;
use app\model\system\Promotion as PromotionModel;
use app\model\system\Servicer;
use app\model\system\Site as SiteModel;
use app\model\web\Config as ConfigModel;
use app\model\web\DiyView as DiyViewModel;

class Config extends BaseApi
{

    /**
     * 详情信息
     */
    public function defaultimg()
    {
        $upload_config_model = new ConfigModel();
        $res = $upload_config_model->getDefaultImg($this->site_id, 'shop');
        if (!empty($res[ 'data' ][ 'value' ])) {
            return $this->response($this->success($res[ 'data' ][ 'value' ]));
        } else {
            return $this->response($this->error());
        }
    }

    /**
     * 版权信息
     */
    public function copyright()
    {
        $config_model = new ConfigModel();
        $res = $config_model->getCopyright($this->site_id, 'shop');
        return $this->response($this->success($res[ 'data' ][ 'value' ]));
    }

    /**
     * 获取当前时间戳
     * @return false|string
     */
    public function time()
    {
        $time = time();
        return $this->response($this->success($time));
    }

    /**
     * 获取验证码配置
     */
    public function getCaptchaConfig()
    {
        $config_model = new ConfigModel();
        $info = $config_model->getCaptchaConfig();
        return $this->response($this->success($info[ 'data' ][ 'value' ]));
    }

    /**
     * 客服配置
     */
    public function servicer()
    {
        $servicer_model = new Servicer();
        $result = $servicer_model->getServicerConfig()[ 'data' ] ?? [];
        return $this->response($this->success($result[ 'value' ] ?? []));
    }

    /**
     * 系统初始化配置信息
     * @return false|string
     */
    public function init()
    {
        // 获取用户标签（如果已登录）
        $member_label = '';
        $member_info = null;
        if (!empty($this->params['token'])) {
            $check_result = $this->checkToken();
            if ($check_result['code'] == 0 && !empty($this->member_id)) {
                // 获取会员信息
                $member_model = new \app\model\member\Member();
                $member_info = $member_model->getInfo([
                    ['member_id', '=', $this->member_id],
                    ['site_id', '=', $this->site_id]
                ], 'member_id,member_label,member_label_name');

                if (!empty($member_info)) {
                    // member_label 格式可能是 ",1," 或 "1" 或 "1,2"
                    $member_label = trim($member_info['member_label'], ',');
                }
            }
        }

        $diy_view = new DiyViewModel();
        $diy_style = $diy_view->getStyleConfig($this->site_id)[ 'data' ][ 'value' ];

        // 底部导航 - 根据用户标签返回不同配置
        if ($member_label == '1') {
            // member_label = 1 的用户，使用特定的底部导航配置
            $diy_bottom_nav = $diy_view->getBottomNavConfig($this->site_id)[ 'data' ][ 'value' ];
            // 这里可以自定义修改 $diy_bottom_nav 的内容
        } elseif ($member_label == '2') {
            // member_label = 2 的用户，使用另一种底部导航配置
            $diy_bottom_nav = $diy_view->getBottomNavConfig($this->site_id)[ 'data' ][ 'value' ];
            // 这里可以自定义修改 $diy_bottom_nav 的内容
        } else {
            // 未登录或其他标签的用户，使用默认配置
            $diy_bottom_nav = $diy_view->getBottomNavConfig($this->site_id)[ 'data' ][ 'value' ];
        }

        // 插件存在性
        $addon = new \app\model\system\Addon();
        $addon_is_exist = $addon->addonIsExist();

        // 默认图
        $config_model = new ConfigModel();
        $default_img = $config_model->getDefaultImg($this->site_id, 'shop')[ 'data' ][ 'value' ];

        // 版权信息
        $copyright = $config_model->getCopyright($this->site_id, 'shop')[ 'data' ][ 'value' ];

        $map_config = $config_model->getMapConfig($this->site_id, 'shop')[ 'data' ][ 'value' ];

        $website_model = new SiteModel();
        $site_info = $website_model->getSiteInfo([ [ 'site_id', '=', $this->site_id ] ], 'site_id,site_domain,site_name,logo,seo_title,seo_keywords,seo_description,site_tel,logo_square')[ 'data' ];

        $servicer_model = new Servicer();
        $servicer_info = $servicer_model->getServicerConfig()[ 'data' ][ 'value' ] ?? [];

        $this->initStoreData();

        //微信配置状态
        $wechat_config_status = 0;
        if(addon_is_exit('wechat')){
            $config_model = new \addon\wechat\model\Config();
            $config_info = $config_model->getWechatConfig($this->site_id)['data']['value'];
            if (!empty($config_info[ 'appid' ]) && !empty($config_info[ 'appsecret' ])) {
                $wechat_config_status = 1;
            }
        }

        $res = [
            'style_theme' => $diy_style,
            'diy_bottom_nav' => $diy_bottom_nav,
            'addon_is_exist' => $addon_is_exist,
            'default_img' => $default_img,
            'copyright' => $copyright,
            'site_info' => $site_info,
            'servicer' => $servicer_info,
            'store_config' => $this->store_data[ 'config' ],
            'map_config' => $map_config,
            'wechat_config_status' => $wechat_config_status,
            'member_label' => $member_label, // 返回用户标签，前端可以根据此字段做判断
        ];

        if (!empty($this->store_data[ 'store_info' ])) {
            $res[ 'store_info' ] = $this->store_data[ 'store_info' ];
        }

        return $this->response($this->success($res));
    }

    /**
     * 获取pc首页商品分类配置
     * @return false|string
     */
    public function categoryconfig()
    {
        $config_model = new ConfigModel();
        $config_info = $config_model->getCategoryConfig($this->site_id);
        return $this->response($this->success($config_info[ 'data' ][ 'value' ]));
    }

    /**
     *配送方式配置信息（启用的）
     * @return false|string
     */
    public function enabledExpressType()
    {
        $express_type = ( new ExpressConfig() )->getEnabledExpressType($this->site_id);
        return $this->response($this->success($express_type));
    }

    /**
     * 获取活动专区页面配置
     * @return false|string
     */
    public function promotionZoneConfig()
    {
        $name = $this->params['name'] ?? ''; // 活动名称标识
        if (empty($name)) {
            return $this->response($this->error([], '缺少必填参数name'));
        }
        $promotion_model = new PromotionModel();
        $res = $promotion_model->getPromotionZoneConfig($name, $this->site_id)[ 'data' ][ 'value' ];
        return $this->response($this->success($res));
    }


    public function getApiConfig()
    {
        $api_model = new Api();
        $config_result = $api_model->getApiConfig();
        $api_config = $config_result[ "data" ];
        $key = 'site' . $this->site_id;
        if (!empty($api_config[ 'value' ][ 'private_key' ])) {
            $key = $api_config[ 'value' ][ 'private_key' ] . $key;
        }
        $key = preg_replace("/[^A-Za-z0-9]/", '', $key);
        $res = [
            'time'=>time(),
            'key'=>$key
        ];
        return $this->response($this->success($res));
    }

    public function geMapConfig()
    {
        $map_model = new \app\model\map\QqMap();
        $res = [
            'key'=>$map_model->getKey()
        ];
        return $this->response($this->success($res));
    }

}