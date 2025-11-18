<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supermember\model;

use app\model\BaseModel;

/**
 * 会员卡订单
 */
class MemberCard extends BaseModel
{
    /**
     * 获取站点推荐会员卡
     * @param $site_id
     */
    public function getRecommendMemberCard($site_id)
    {
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'level_type', '=', 1 ],
            [ 'status', '=', 1 ],
            [ 'is_recommend', '=', 1 ]
        ];
        $field = 'level_id,level_name,consume_discount,is_free_shipping,point_feedback,send_point,send_balance,send_coupon,charge_rule';
        $data = model('member_level')->getInfo($condition, $field);
        return $this->success($data);
    }
}