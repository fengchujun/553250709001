<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\coupon\model;

use addon\coupon\dict\CouponDict;
use app\model\BaseModel;

/**
 * 优惠券
 */
class MemberCoupon extends BaseModel
{

    /**
     * 获取会员已领取优惠券
     * @param $member_id
     * @param $state
     * @param int $site_id
     * @param string $order
     * @return array
     */
    public function getMemberCouponList($member_id, $state, $site_id = 0, $order = 'fetch_time desc')
    {
        $condition = [
            ['member_id', '=', $member_id ],
            ['state', '=', $state ],
        ];
        if ($site_id > 0) {
            $condition[] = ['site_id', '=', $site_id ];
        }
        $list = model('promotion_coupon')->getList($condition, '*', $order, '', '', '', 0);
        return $this->success($list);
    }

    /**
     * 使用优惠券
     * @param $coupon_id
     * @param $member_id
     * @param int $order_id
     * @return array
     */
    public function useMemberCoupon($coupon_id, $member_id, $order_id = 0)
    {
        //优惠券处理方案
        $result = model('promotion_coupon')->update([ 'use_order_id' => $order_id, 'state' => 2, 'use_time' => time() ], [ [ 'coupon_id', '=', $coupon_id ], ['member_id', '=', $member_id ], [ 'state', '=', 1 ] ]);
        if ($result === false) {
            return $this->error();
        }
        //累赠使用数
        $info = model('promotion_coupon')->getInfo([ [ 'coupon_id', '=', $coupon_id ]]);
        model('promotion_coupon_type')->setInc([['coupon_type_id', '=', $info['coupon_type_id']]], 'used_count');
        return $this->success();
    }

    /**
     * 获取会员已领取优惠券数量
     * @param $member_id
     * @param $state
     * @param int $site_id
     * @return array
     */
    public function getMemberCouponNum($member_id, $state, $site_id = 0)
    {
        $condition = [
            ['member_id', '=', $member_id ],
            ['state', '=', $state ],
        ];
        if ($site_id > 0) {
            $condition[] = ['site_id', '=', $site_id ];
        }
        $num = model('promotion_coupon')->getCount($condition);
        return $this->success($num);
    }

    /**
     * 会员是否可领取该优惠券
     * @param $coupon_type_id
     * @param $member_id
     * @return array
     */
    public function receivedNum($coupon_type_id, $member_id)
    {
        $received_num = model('promotion_coupon')->getCount([ [ 'coupon_type_id', '=', $coupon_type_id ], [ 'member_id', '=', $member_id ] ]);
        return $this->success($received_num);
    }

    /**
     * 获取编码
     */
    public function getCode()
    {
        return random_keys(8);
    }

    /**
     * 回收优惠券
     * @param array $coupon_list
     * @param $site_id
     * @return array
     */
    public function recoveryCoupon(array $coupon_list, $site_id, $store_id = 0)
    {
        //检测
        $coupon_type_ids = array_column($coupon_list, 'coupon_type_id');
        $condition = [
            ['coupon_type_id', 'in', $coupon_type_ids],
            ['site_id', '=', $site_id],
        ];
        if($store_id > 0){
            $condition[] = ['store_id', '=', $store_id];
        }
        $coupon_type_list = model('promotion_coupon_type')->getList($condition);
        if(count(array_unique($coupon_type_ids)) != count($coupon_type_list)) return $this->error('', '回收失败');

        $coupon_ids = array_column($coupon_list, 'coupon_id');
        $coupon_list = model('promotion_coupon')->getList([['coupon_id', 'in', $coupon_ids]], 'coupon_id,coupon_type_id,get_type');
        if (!count($coupon_list)) return $this->error(null, '没有要回收的数据');

        $coupon_data = [];
        foreach ($coupon_list as $coupon_item) {
            if(!isset($coupon_data[$coupon_item['coupon_type_id']])){
                $coupon_data[$coupon_item['coupon_type_id']] = [
                    'lead_num' => 0,
                    'give_num' => 0,
                ];
            }
            if($coupon_item['get_type'] == Coupon::GET_TYPE_MEMBER_LEAD){
                $coupon_data[$coupon_item['coupon_type_id']]['lead_num']++;
            }else{
                $coupon_data[$coupon_item['coupon_type_id']]['give_num']++;
            }
        }

        model('promotion_coupon')->startTrans();
        try {
            foreach ($coupon_data as $coupon_type_id => $coupon_data_item) {
                model('promotion_coupon_type')->update([
                    'lead_count' => \think\facade\Db::raw("IF(lead_count - {$coupon_data_item['lead_num']} > 0, lead_count - {$coupon_data_item['lead_num']}, 0)"),
                    'give_count' => \think\facade\Db::raw("IF(give_count - {$coupon_data_item['give_num']} > 0, give_count - {$coupon_data_item['give_num']}, 0)"),
                ], [['coupon_type_id', '=', $coupon_type_id]]);
            }
            model('promotion_coupon')->delete([['coupon_id', 'in', array_column($coupon_list, 'coupon_id')]]);
            model('promotion_coupon')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_coupon')->rollback();
            return $this->error('', '回收失败');
        }
    }

    /**
     * 专用于撤回活动赠送的优惠券
     * @return void
     */
    public function cancelByPromotion($data){
        $member_id = $data['member_id'];
        $coupon_data = $data['coupon_data'];//优惠券id相关项
        $coupon_ids = array_column($coupon_data, 'coupon_type_id');
        $member_coupon_list = model('promotion_coupon')->getList([
            ['member_id', '=', $member_id],
            ['coupon_type_id', 'in', $coupon_ids],
            ['state', '=', CouponDict::normal]
        ], '*');
        $member_coupon_type_group_list = [];
        foreach($member_coupon_list as $v){
            $member_coupon_type_group_list[$v['coupon_type_id']][] = $v['coupon_id'];
        }
        $cancel_ids = [];
        foreach ($coupon_data as $item) {
            $coupon_type_id = $item['coupon_type_id'];
            $num = $item['num'];
            $item_coupon_type_group = $member_coupon_type_group_list[$coupon_type_id] ?? [];
            if($item_coupon_type_group){
                if(count($item_coupon_type_group) > $num){
                    $cancel_ids = array_merge($cancel_ids, array_slice($item_coupon_type_group, 0, $num));
                }else{
                    $cancel_ids = array_merge($cancel_ids, $item_coupon_type_group);
                }
            }
        }
        model('promotion_coupon')->update(['state' => CouponDict::close], [['coupon_id', 'in', $cancel_ids]]);
        return $this->success();

    }
}