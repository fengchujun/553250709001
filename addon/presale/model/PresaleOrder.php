<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\model;

use app\model\BaseModel;

/**
 * 商品预售
 */
class PresaleOrder extends BaseModel
{

    /**
     * 获取预售订单信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleOrderInfo($condition = [], $field = '*')
    {
        $info = model('promotion_presale_order')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取预售订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getPresaleOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = '*')
    {
        $join = [
            [ 'member m', 'ppo.member_id = m.member_id', 'left' ]
        ];
        $field = 'ppo.*,m.nickname';
        $list = model('promotion_presale_order')->pageList($condition, $field, $order, $page, $page_size, 'ppo', $join);
        return $this->success($list);
    }

}