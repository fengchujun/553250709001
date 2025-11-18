<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\coupon\storeapi\controller;

use addon\coupon\model\Coupon as CouponModel;
use app\storeapi\controller\BaseStoreApi;

class Membercoupon extends BaseStoreApi
{
    /**
     * 获取已领取的优惠券
     * @return false|string
     */
    public function getReceiveCouponPageList(){
        $coupon_model = new CouponModel();
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $coupon_type_id = $this->params['coupon_type_id'] ?? 0;
        $state = $this->params['state'] ?? '';
        $condition = [];
        $condition[] = [ 'npc.coupon_type_id', '=', $coupon_type_id ];
        $condition[] = [ 'npc.site_id', '=', $this->site_id ];
        if ($state !== '') {
            $condition[] = [ 'npc.state', '=', $state ];
        }
        $res = $coupon_model->getMemberCouponPageList($condition, $page, $page_size);
        $get_type_list = $coupon_model->getCouponGetType();
        foreach ($res['data']['list'] as &$val) {
            $val['get_type_name'] = $get_type_list[$val['get_type']] ?? '';
        }
        return $this->response($res);
    }
}