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

use addon\store\model\StoreGoodsSku;
use addon\store\model\StoreOrder;
use app\model\BaseModel;
use app\model\order\LocalOrder;
use app\model\order\Order;
use app\model\order\VirtualOrder;

/**
 * 商品接龙
 */
class JielongOrderCommon extends BaseModel
{
    // 订单待付款
    const ORDER_CREATE = 0;
    //订单完成(尾款已支付)
    const ORDER_PAY = 1;
    // 订单已关闭
    const ORDER_CLOSE = -1;

    /**
     * 基础订单状态(不同类型的订单可以不使用这些状态，但是不能冲突)
     * @var unknown
     */
    public $order_status = [

        self::ORDER_CREATE => [
            'status' => self::ORDER_CREATE,
            'name' => '待付款',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'offlinePayDeposit',
                    'title' => '线下支付定金',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'orderPayDeposit',
                    'title' => '支付定金',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_PAY => [
            'status' => self::ORDER_PAY,
            'name' => '已完成',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [],
            'member_action' => [],
            'color' => ''
        ],
        self::ORDER_CLOSE => [
            'status' => self::ORDER_CLOSE,
            'name' => '已关闭',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-close.png',
            'action' => [
                [
                    'action' => 'deleteOrder',
                    'title' => '删除订单',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'deleteOrder',
                    'title' => '删除订单',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
    ];

    /**
     * 订单状态
     * @return array
     */
    public function getOrderStatus()
    {
        return $this->success($this->order_status);
    }

    /**
     * 会员接龙订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $order_list = model('promotion_jielong_order')->pageList($condition, $field, $order, $page, $page_size);
        if (!empty($order_list[ 'list' ])) {
            foreach ($order_list[ 'list' ] as $k => $v) {
                $order_goods_list = model('order_goods')->getList([
                    'order_id' => $v[ 'relate_order_id' ]
                ]);
                foreach ($order_goods_list as $ck => $cv) {
                    $order_goods_list[ $ck ][ 'num' ] = numberFormat($order_goods_list[ $ck ][ 'num' ]);
                }
                $order_list[ 'list' ][ $k ][ 'order_goods' ] = $order_goods_list;
                $action = empty($v[ "order_status_action" ]) ? [] : json_decode($v[ "order_status_action" ], true);
                $member_action = $action[ "member_action" ] ?? [];
                $order_list[ 'list' ][ $k ][ 'action' ] = $member_action;
            }
        }
        return $this->success($order_list);
    }

    /**
     * 会员订单详情
     * @param $id
     * @param $member_id
     */
    public function getMemberOrderDetail($id, $member_id, $site_id)
    {
        $order_info = model('promotion_jielong_order')->getInfo([ [ 'id', "=", $id ], [ "member_id", "=", $member_id ], [ "site_id", "=", $site_id ] ]);

        if (empty($order_info))
            return $this->error([], "当前订单不是本账号的订单！");

        $action = empty($order_info[ "order_status_action" ]) ? [] : json_decode($order_info[ "order_status_action" ], true);
        $member_action = $action[ "member_action" ] ?? [];
        $order_info[ 'action' ] = $member_action;
        $order_goods_list = model('order_goods')->getList([ [ 'order_id', "=", $order_info[ 'relate_order_id' ] ], [ "member_id", "=", $member_id ] ]);

        foreach ($order_goods_list as $k => $v) {
            $refund_action = empty($v[ "refund_status_action" ]) ? [] : json_decode($v[ "refund_status_action" ], true);
            $refund_action = $refund_action[ "member_action" ] ?? [];
            $order_goods_list[ $k ][ "refund_action" ] = $refund_action;
            $order_goods_list[ $k ][ 'num' ] = numberFormat($order_goods_list[ $k ][ 'num' ]);
        }
        $order_info[ 'order_goods' ] = $order_goods_list;
        $order_info[ 'order_id' ] = $order_info[ 'relate_order_id' ];
        switch ( $order_info[ 'order_type' ] ) {
            case 1:
                $order_model = new Order();
                break;
            case 2:
                $order_model = new StoreOrder();
                break;
            case 3:
                $order_model = new LocalOrder();
                break;
            case 4:
                $order_model = new VirtualOrder();
                break;
        }

        $temp_info = $order_model->orderDetail($order_info);
        $order_info = array_merge($order_info, $temp_info);

        return $this->success($order_info);
    }

    //获取接龙订单id
    public function getJielongOrderId($id)
    {
        $info = model('promotion_jielong_order')->getInfo([ [ 'id', "=", $id ] ], 'relate_order_id');
        return $info[ 'relate_order_id' ];
    }

    /**
     * 订单关闭增加门店商品库存
     * @param $data
     * @return array
     */
    public function incStoreGoodsStock($data)
    {
        $store_goods_sku_model = new StoreGoodsSku();
        $stock_result = $store_goods_sku_model->incStock([ "store_id" => $data[ "delivery_store_id" ], "sku_id" => $data[ "sku_id" ], 'stock' => $data[ "num" ] ]);
        if ($stock_result[ "code" ] < 0) {
            return $stock_result;
        }
    }

    /*********************************************** 订单异步回调 end *****************************************************************/

    /**
     * 订单删除
     * @param $condition
     * @return array
     */
    public function deleteOrder($condition)
    {
        $info = model('promotion_jielong_order')->getInfo($condition, 'order_status');
        if (empty($info)) {
            return $this->error('', '订单不存在');
        }
        if ($info[ 'order_status' ] != self::ORDER_CLOSE) {
            return $this->error('', '抱歉，只有已关闭的订单才可以删除');
        }

        $res = model('promotion_jielong_order')->delete($condition);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error();
        }
    }
}