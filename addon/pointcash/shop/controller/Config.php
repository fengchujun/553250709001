<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointcash\shop\controller;

use app\shop\controller\BaseShop;
use addon\pointcash\model\Config as ConfigModel;

/**
 * 积分抵现
 * @author Administrator
 *
 */
class Config extends BaseShop
{
    public function index()
    {
        $config = new ConfigModel();
        if (request()->isJson()) {
            $data = [
                'cash_rate' => input('cash_rate', 0), // 抵现比率
                'is_limit' => input('is_limit', 0), // 是否限制订单金额门槛
                'limit' => input('limit', 0.00), // 订单金额门槛
                'is_limit_use' => input('is_limit_use', 0), // 是否限制使用上限
                'type' => input('type', 0), // 限制类型 0：固定金额 1：订单百分比
                'max_use' => input('max_use', 0) // 最大可用
            ];
            $is_use = input('is_use', 0);
            $res = $config->setPointCashConfig($data, $is_use, $this->site_id);
            return $res;
        } else {
            $info = $config->getPointCashConfig($this->site_id);
            $this->assign('config', $info[ 'data' ][ 'value' ]);
            $this->assign('is_use', $info['data']['is_use']);
            return $this->fetch('config/index');
        }
    }
}