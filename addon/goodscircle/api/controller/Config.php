<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\goodscircle\api\controller;

use app\api\controller\BaseApi;
use addon\goodscircle\model\Config as ConfigModel;

/**
 * 好物圈
 */
class Config extends BaseApi
{
    /**
     * 获取好物圈配置
     */
    public function info()
    {
        $config = new ConfigModel();
        $res    = $config->getGoodscircleConfig($this->site_id);
        return $this->response($this->success($res['data']));
    }

}