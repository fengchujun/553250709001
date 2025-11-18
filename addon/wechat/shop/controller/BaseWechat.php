<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechat\shop\controller;

use app\shop\controller\BaseShop;
use think\App;

/**
 * 微信控制器基类
 */
class BaseWechat extends BaseShop
{

    public function __construct(App $app = null)
    {
        $this->replace = [
            'WECHAT_CSS' => __ROOT__ . '/addon/wechat/shop/view/public/css',
            'WECHAT_JS' => __ROOT__ . '/addon/wechat/shop/view/public/js',
            'WECHAT_IMG' => __ROOT__ . '/addon/wechat/shop/view/public/img',
        ];
        parent::__construct($app);
    }

}