<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\model;

use app\model\BaseModel;

/**
 * 商品接龙
 */
class JielongOrder extends BaseModel
{

    /**
     * 获取接龙订单信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getJielongOrderInfo($condition = [], $field = '*')
    {
        $info = model('promotion_jielong_order')->getInfo($condition, $field);

        $order_goods_list = model('order_goods')->getList([ 'order_id' => $info[ 'relate_order_id' ] ], 'sku_name,price,num,goods_money');
        foreach ($order_goods_list as $k => $v) {
            $order_goods_list[ $k ][ 'num' ] = numberFormat($order_goods_list[ $k ][ 'num' ]);
        }
        $info[ 'order_goods_list' ] = $order_goods_list;

        return $this->success($info);
    }

    /**
     * 获取接龙订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getJielongOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = '*')
    {
        $join = [
            [
                'order o',
                'o.order_id = pjo.relate_order_id',
                'left'
            ]
        ];
        $field = 'pjo.*,o.order_status_name';
        $list = model('promotion_jielong_order')->pageList($condition, $field, $order, $page, $page_size, 'pjo', $join);

        //获取关联订单商品
        foreach ($list[ 'list' ] as $k => $v) {
            $order_goods_list = model('order_goods')->getList([ 'order_id' => $v[ 'relate_order_id' ] ], 'sku_image,sku_name,price,num');
            foreach ($order_goods_list as $ck => $cv) {
                $order_goods_list[ $ck ][ 'num' ] = numberFormat($order_goods_list[ $ck ][ 'num' ]);
            }
            $list[ 'list' ][ $k ][ 'order_goods_list' ] = $order_goods_list;
        }

        return $this->success($list);
    }

}