<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\member;

use addon\coupon\model\Coupon as CouponModel;
use app\Controller;
use app\model\member\MemberAccount as MemberAccountModel;

/**
 * 会员详情账号明细
 */
class MemberDetail extends Controller
{

    public function handle($param)
    {
        $this->assign("member_id", $param[ 'member_id' ]);

        if ($param[ 'type' ] == 'account') {
            // 账户余额、积分、成长值

            $this->assign("account_type", $param[ 'account_type' ]);

            $id = $param[ 'account_type' ];
            if ($param[ 'account_type' ] == 'balance,balance_money') {
                $id = 'balance';
            }
            $this->assign('table_id', $id);

            //账户类型和来源类型
            $member_account_model = new MemberAccountModel();
            $account_type_arr = $member_account_model->getAccountType();
            $from_type_arr = $member_account_model->getFromType();
            $this->assign('account_type_arr', $account_type_arr);
            $this->assign('from_type_arr', $from_type_arr[ $param[ 'account_type' ] ] ?? []);

            $template = 'app/shop/view/member/account_detail.html';
            return $this->fetch($template);

        } elseif ($param[ 'type' ] == 'order') {

            // 订单列表
            $template = 'app/shop/view/member/order.html';
            return $this->fetch($template);

        } elseif ($param[ 'type' ] == 'address_detail') {

            // 收货地址
            $template = 'app/shop/view/member/address_detail.html';
            return $this->fetch($template);

        } elseif ($param[ 'type' ] == 'member_goods_collect') {

            // 收藏记录
            $template = 'app/shop/view/goods/member_goods_collect.html';
            return $this->fetch($template);

        } elseif ($param[ 'type' ] == 'member_goods_browse') {

            // 浏览记录
            $template = 'app/shop/view/goods/member_goods_browse.html';
            return $this->fetch($template);

        } elseif ($param[ 'type' ] == 'member_coupon') {

            // 优惠券
            $coupon_model = new CouponModel();
            $this->assign('get_type', $coupon_model->getCouponGetType());
            $template = 'app/shop/view/member/member_coupon.html';
            return $this->fetch($template);

        }

    }

}