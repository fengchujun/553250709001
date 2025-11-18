<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\shopapi\controller;

use addon\memberrecharge\model\Memberrecharge as MemberRechargeModel;
use app\shopapi\controller\BaseApi;

class Recharge extends BaseApi
{
    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

        $token = $this->checkToken();
        if ($token['code'] != 0) exit($this->response($token));
    }

    /**
     * 充值套餐
     * @return false|string
     */
    public function activity()
    {
        $member_recharge_model = new MemberRechargeModel();
        $res = $member_recharge_model->getConfig($this->site_id);
        $res['data']['value'] = $member_recharge_model->getMemberRechargeList([ ['site_id', '=', $this->site_id], ['status', '=', 1] ],  'face_value asc', '*')['data'];
        return $this->response($res);
    }
}
