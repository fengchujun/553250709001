<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace addon\supermember\api\controller;

use addon\supermember\model\Config;
use app\api\controller\BaseApi;
use app\model\goods\Goods;
use app\model\member\MemberLevel;
use addon\supermember\model\MemberCard as MemberCardModel;


/**
 * 会员卡
 * @package app\api\controller
 */
class Membercard extends BaseApi
{
    /**
     * 列表信息
     */
    public function lists()
    {
        $member_level_model = new MemberLevel();

        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'level_type', '=', 1 ],
            [ 'status', '=', 1 ]
        ];
        $field = 'level_id,level_name,remark,consume_discount,is_free_shipping,point_feedback,send_point,send_balance,send_coupon,charge_rule,charge_type,bg_color,level_text_color,level_picture';
        $member_level_list = $member_level_model->getMemberLevelList($condition, $field, 'is_recommend desc,level_id desc');
        return $this->response($member_level_list);
    }

    /**
     * 获取会员卡信息
     */
    public function info()
    {
        $level_id = $this->params[ 'level_id' ] ?? 0;

        $member_level_model = new MemberLevel();
        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'level_type', '=', 1 ],
            [ 'level_id', '=', $level_id ]
        ];
        $field = 'level_id,level_name,consume_discount,is_free_shipping,point_feedback,send_point,send_balance,send_coupon';
        $data = $member_level_model->getMemberLevelInfo($condition, $field);

        return $this->response($data);
    }

    /**
     * 查询推荐卡
     * @param int $id
     * @return false|string
     */
    public function recommendCard($id = 0)
    {
        $sku_id = $this->params[ 'sku_id' ] ?? 0;
        if (!empty($id)) {
            $sku_id = $id;
        }

        $card_model = new MemberCardModel();
        $data = $card_model->getRecommendMemberCard($this->site_id);

        if (!empty($data[ 'data' ]) && !empty($sku_id)) {
            $goods_model = new Goods();
            $price_data = $goods_model->getMemberCardGoodsPrice($sku_id, $data[ 'data' ][ 'level_id' ]);
            $data[ 'data' ][ 'member_price' ] = $price_data[ 'data' ][ 'price' ];
        }

        return $this->response($data);
    }

    /**
     * 获取推荐会员卡
     * @return false|string
     */
    public function firstCard()
    {
        $sku_id = $this->params[ 'sku_id' ] ?? 0;

        $member_level_model = new MemberLevel();
        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'level_type', '=', 1 ],
            [ 'status', '=', 1 ],
            [ 'is_recommend', '=', 1 ]
        ];
        $field = 'level_id,level_name,consume_discount,is_free_shipping,point_feedback,send_point,send_balance,send_coupon';
        $data = $member_level_model->getMemberLevelInfo($condition, $field);

        if (empty($data[ 'data' ])) {
            unset($condition[ 3 ]);
            $data = $member_level_model->getFirstMemberLevel($condition, $field, 'consume_discount asc');
        }

        return $this->response($data);
    }

    /**
     * 开卡协议
     */
    public function agreement()
    {
        $config = new Config();
        $data = $config->getMemberCardDocument($this->site_id);
        return $this->response($data);
    }
}