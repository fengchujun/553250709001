<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\goodscircle\shop\controller;

use app\shop\controller\BaseShop;
use addon\goodscircle\model\Config as ConfigModel;
use think\App;

/**
 * 满减控制器
 */
class Config extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'CIRCLE_CSS' => __ROOT__ . '/addon/goodscircle/shop/view/public/css',
            'CIRCLE_JS' => __ROOT__ . '/addon/goodscircle/shop/view/public/js',
            'CIRCLE_IMG' => __ROOT__ . '/addon/goodscircle/shop/view/public/img',
        ];
        parent::__construct($app);
    }

    public function index()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $data = [];
            $is_use = input('is_use', 0);
            return $config_model->setGoodscircleConfig($data, $is_use, $this->site_id);
        } else {
            $config = $config_model->getGoodscircleConfig($this->site_id);
            $this->assign('config', $config[ 'data' ]);
            return $this->fetch('config/index');
        }
    }
}