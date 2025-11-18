<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\api\controller;

use addon\memberrecharge\model\MemberrechargeOrder as MemberRechargeOrderModel;
use app\api\controller\BaseApi;

/**
 * 充值订单
 */
class Order extends BaseApi
{

    /**
     * 计算信息
     */
    public function page()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $field = 'order_id,recharge_id,recharge_name,order_no,cover_img,buy_price,create_time,out_trade_no,face_value,point,growth,coupon_id';
        $member_recharge_order_model = new MemberRechargeOrderModel();
        $condition = array (
            [ 'status', '=', 2 ],
            [ 'member_id', '=', $this->member_id ],
            [ 'site_id', '=', $this->site_id ]
        );
        $list = $member_recharge_order_model->getMemberRechargeOrderPageList($condition, $page, $page_size, 'create_time desc', $field);
        return $this->response($list);
    }
}