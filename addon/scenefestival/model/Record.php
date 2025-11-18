<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\scenefestival\model;

use app\model\BaseModel;

/**
 * 领取记录
 */
class Record extends BaseModel
{

    /**
     * 获取领取记录列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getFestivalDrawRecordList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_festival_draw_record')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取领取记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGamesDrawRecordPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('promotion_festival_draw_record')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);

        if (!empty($list[ 'list' ])) {
            foreach ($list[ 'list' ] as $awark_k => $awark_v) {
                $list[ 'list' ][ $awark_k ][ 'coupon_name' ] = '';
                if (!empty($awark_v[ 'coupon' ])) {
                    $coupon_info = model('promotion_coupon_type')->getList([ [ 'coupon_type_id', 'in', $awark_v[ 'coupon' ] ] ], 'coupon_type_id,coupon_name,count,lead_count');
                    if (!empty($coupon_info)) {
                        $coupon_name = '';
                        foreach ($coupon_info as $k => $v) {
                            if ($v[ 'count' ] < 0 || $v[ 'count' ] - $v[ 'lead_count' ] > 0) {
                                $coupon_name .= $v[ 'coupon_name' ];
                            }
                        }
                        $coupon_name = ltrim($coupon_name, ',');
                        $list[ 'list' ][ $awark_k ][ 'coupon_name' ] = $coupon_name;
                    }
                }
            }
        }

        return $this->success($list);
    }


}