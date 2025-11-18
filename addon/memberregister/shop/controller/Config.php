<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberregister\shop\controller;

use addon\memberregister\model\Register;
use app\shop\controller\BaseShop;

/**
 * 会员注册
 */
class Config extends BaseShop
{

    public function index()
    {
        $config_model = new Register();
        if (request()->isJson()) {
            $data = [
                'point' => input('point', 0),//注册送积分
                'balance' => input('balance', 0),//注册送余额
                'growth' => input('growth', ''),//注册赠送成长值
                'coupon' => input('coupon', ''),//注册送优惠券
                'coupon_new' => input('coupon_new', ''),//注册送优惠券
            ];
            $is_use = input('is_use', 0);//是否启用
            $res = $config_model->setConfig($data, $is_use, $this->site_id);
            $this->addLog('设置会员注册奖励');
            return $res;
        } else {
            //注册后奖励
            $config_result = $config_model->getConfig($this->site_id)[ 'data' ];
            $this->assign('config', $config_result);
            return $this->fetch('config/index');
        }
    }
}