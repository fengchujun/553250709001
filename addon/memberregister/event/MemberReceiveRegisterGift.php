<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * 4.0.1升级测试
 */

namespace addon\memberregister\event;

use addon\memberregister\model\Register as RegisterModel;
use app\model\member\MemberAccount as MemberAccountModel;
use addon\coupon\model\CouponType;
use addon\coupon\model\Coupon;

/**
 * 后台添加会员领取新人礼
 */
class MemberReceiveRegisterGift
{
    /**
     * @param $param
     * @return array|\multitype
     */
    public function handle($param)
    {
        $register_model = new RegisterModel();
        $member_account_model = new MemberAccountModel();

        $register_config = $register_model->getConfig($param[ 'site_id' ])[ 'data' ];

        $res = [];
        if ($register_config[ 'is_use' ]) {
            $register_config = $register_config[ 'value' ];
            unset($register_config[ 'coupon_list' ]);
            foreach ($register_config as $k => $v) {
                if ($k == 'coupon') {
                    $coupon_type = new CouponType();
                    $coupon_list = $coupon_type->getCouponTypeList([ [ 'site_id', '=', $param[ 'site_id' ] ], [ 'status', '=', 1 ], [ 'coupon_type_id', 'in', $v ] ])[ 'data' ];
                    $coupon = new Coupon();

                    foreach ($coupon_list as $key => $val) {
                        //修改:新增发放数量
                        $send_res = array_column($v['coupon_new'] ?? [],'num','id');
                        $send_num = $send_res[$val['coupon_type_id']] ?? 1;
                        $coupon->giveCoupon([['coupon_type_id' => $val[ 'coupon_type_id' ], 'num' => $send_num]], $param[ 'site_id' ], $param[ 'member_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
                    }

                } else if (!empty($v)) {
                    $adjust_num = $v;
                    $account_type = $k;
                    $remark = '注册奖励';
                    $res = $member_account_model->addMemberAccount($param[ 'site_id' ], $param[ 'member_id' ], $account_type, $adjust_num, 'register', 0, $remark);
                }
            }
        }

        return $res;

    }

}