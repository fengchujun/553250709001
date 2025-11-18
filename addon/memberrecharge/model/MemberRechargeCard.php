<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\model;

use addon\coupon\model\Coupon;
use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use addon\coupon\model\CouponType;
use app\model\member\MemberAccount;
use app\model\system\Site;
use think\facade\Cache;

/**
 * 开卡
 */
class MemberRechargeCard extends BaseModel
{

    /**
     * 开卡
     * @param $data
     * @return array
     */
    public function addMemberRechargeCard($data)
    {
        $site_model = new Site();
        $site_info = $site_model->getSiteInfo([ [ 'site_id', '=', $data[ 'site_id' ] ] ]);
        $card_account = substr(md5(date('YmdHis') . mt_rand(100, 999)), 8, 16);
        $card_data = [
            'recharge_id' => $data[ 'recharge_id' ],
            'site_id' => $data[ 'site_id' ],
            'site_name' => $site_info[ 'data' ][ 'site_name' ],
            'recharge_name' => $data[ 'recharge_name' ],
            'card_account' => $card_account,
            'cover_img' => $data[ 'cover_img' ],
            'face_value' => $data[ 'face_value' ],
            'point' => $data[ 'point' ],
            'growth' => $data[ 'growth' ],
            'coupon_id' => $data[ 'coupon_id' ],
            'buy_price' => $data[ 'buy_price' ],
            'member_id' => $data[ 'member_id' ],
            'member_img' => $data[ 'member_img' ],
            'nickname' => $data[ 'nickname' ],
            'order_id' => $data[ 'order_id' ],
            'order_no' => $data[ 'order_no' ],
            'use_status' => $data[ 'use_status' ],
            'create_time' => time(),
            'use_time' => $data[ 'use_time' ]
        ];
        $res = model('member_recharge_card')->add($card_data);
        Cache::tag("member_recharge_card")->clear();
        return $this->success($res);
    }

    /**
     *  开卡发放礼包
     * @param $order_info
     */
    public function addMemberAccount($order_info)
    {
        $member_account = new MemberAccount();
        //修改用户的余额
        $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], AccountDict::balance, $order_info[ 'face_value' ], 'memberrecharge', '0', '会员充值');

        //积分
        if ($order_info[ 'point' ] > 0) {
            $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'point', $order_info[ 'point' ], 'memberrecharge', '0', '会员充值奖励');
        }

        //成长值
        if ($order_info[ 'growth' ] > 0) {
            $member_account->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'growth', $order_info[ 'growth' ], 'memberrecharge', '0', '会员充值奖励');
        }
        //添加优惠券
        if (!empty($order_info[ 'coupon_id' ])) {
            $coupon_model = new Coupon();
            $coupon_id = explode(',', $order_info[ 'coupon_id' ]);
            $coupon_list = array_map(function($value) {
                return [ 'coupon_type_id' => $value, 'num' => 1 ];
            }, $coupon_id);
            $coupon_model->giveCoupon($coupon_list, $order_info[ 'site_id' ], $order_info[ 'member_id' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
        }
    }

    /**
     * 套餐详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberRechargeCardInfo($condition = [], $field = '*')
    {
        $info = model('member_recharge_card')->getInfo($condition, $field);
        if ($info) {
            //获取优惠券信息
            if ($info[ 'coupon_id' ]) {
                //优惠券字段
                $coupon_field = '*';
                $model = new CouponType();
                $coupon_list = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $info[ 'coupon_id' ] ] ], $coupon_field);
                $info[ 'coupon_list' ] = $coupon_list[ 'data' ];
            }
        }
        Cache::tag("member_recharge_card")->clear();
        return $this->success($info);
    }

    /**
     * 开卡列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberRechargeCardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_recharge_card')->pageList($condition, $field, $order, $page, $page_size);

        Cache::tag("member_recharge_card")->clear();
        return $this->success($list);
    }

}