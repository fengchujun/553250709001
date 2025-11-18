<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\coupon\shopapi\controller;

use addon\coupon\model\Coupon as CouponModel;
use addon\coupon\model\CouponType as CouponTypeModel;
use addon\coupon\model\MemberCoupon;
use app\shopapi\controller\BaseApi;

/**
 * 优惠券
 */
class Coupon extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 活动列表
     */
    public function lists()
    {
        $coupon_type_model = new CouponTypeModel();

        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $coupon_name = $this->params['coupon_name'] ?? '';
        $status = $this->params['status'] ?? '';

        $condition = [];
        if ($status !== '') {
            $condition[] = [ 'status', '=', $status ];
        }
        $type = $this->params['type'] ?? '';
        if ($type) {
            $condition[] = [ 'type', '=', $type ];
        }
        //类型
        $validity_type = $this->params['validity_type'] ?? '';
        if ($validity_type) {
            $start_time = $this->params['start_time'] ?? '';
            $end_time = $this->params['end_time'] ?? '';
            switch ( $validity_type ) {
                case 1: //固定
                    $condition[] = [ 'end_time', 'between', [ $start_time, $end_time ] ];
                    break;
                case 2:
                    $condition[] = [ 'fixed_term', 'between', [ $start_time, $end_time ] ];
                    break;
            }
        }

        $condition[] = [ 'site_id', '=', $this->site_id ];
        $condition[] = [ 'coupon_name', 'like', '%' . $coupon_name . '%' ];
        $order = 'create_time desc';
        $field = '*';

        $res = $coupon_type_model->getCouponTypePageList($condition, $page, $page_size, $order, $field);
        //获取优惠券状态
        $coupon_type_status_arr = $coupon_type_model->getCouponTypeStatus();
        foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
            $res[ 'data' ][ 'list' ][ $key ][ 'status_name' ] = $coupon_type_status_arr[ $val[ 'status' ] ];
        }

        return $this->response($res);
    }

    /**
     * 发放优惠券
     */
    public function send()
    {
        $member_id = $this->params['member_id'] ?? 0;
        $coupon_type_ids = $this->params['parent'] ?? '';
        $site_id = $this->site_id;
        $coupon_type_ids = explode(',', $coupon_type_ids);
        $coupon_data = [];
        foreach($coupon_type_ids as $coupon_type_id){
            $coupon_data[] = ['coupon_type_id' => $coupon_type_id, 'num' => 1];
        }
        $coupon_model = new CouponModel();
        $res = $coupon_model->giveCoupon($coupon_data, $site_id, $member_id, CouponModel::GET_TYPE_MERCHANT_GIVE);
        return $this->response($res);
    }

}