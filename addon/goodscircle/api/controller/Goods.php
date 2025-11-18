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

use addon\goodscircle\model\GoodsCircle;
use app\api\controller\BaseApi;
use addon\goodscircle\model\Config as ConfigModel;

/**
 * 好物圈
 */
class Goods extends BaseApi
{

    /**
     * 将商品同步到好物圈
     */
    public function sync()
    {
        $config_model = new ConfigModel();
        $config = $config_model->getGoodscircleConfig($this->site_id);
        $config = $config[ 'data' ];
        if (isset($config[ 'is_use' ]) && $config[ 'is_use' ]) {
            $goods_circle = new GoodsCircle($this->site_id);
            $res = $goods_circle->importProduct($this->params[ 'goods_id' ]);
            return $this->response($res);
        } else {
            return $this->response($this->error());
        }
    }

}