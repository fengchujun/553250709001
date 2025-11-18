<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\shop\controller;

use addon\giftcard\model\card\Card as CardModel;
use addon\giftcard\model\order\GiftCardOrder;
use think\App;

/**
 * 礼品卡订单控制器
 */
class Order extends Giftcard
{

    /**
     * 订单列表
     * @return mixed
     */
    public function order()
    {
        $giftcard_id = input('giftcard_id', 0);
        $order_model = new GiftCardOrder();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', 'all');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $nickname = input('nickname', '');
            $order_no = input('order_no', '');
            $card_right_type = input('card_right_type', '');

            $condition = array (
                [ 'o.site_id', '=', $this->site_id ],
                [ 'o.is_delete', '=', 0 ],
                [ 'o.order_status', '=', 'complete' ],
            );
            if ($giftcard_id > 0) {
                $condition[] = [ 'o.giftcard_id', '=', $giftcard_id ];
            }
            if (!empty($nickname)) {
                $condition[] = [ 'm.nickname', 'like', '%' . $nickname . '%' ];
            }
            if (!empty($card_right_type)) {
                $condition[] = [ 'o.card_right_type', '=', $card_right_type ];
            }
            if (!empty($order_no)) {
                $condition[] = [ 'o.order_no', 'like', '%'. $order_no . '%' ];
            }
            //支付时间
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "o.pay_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "o.pay_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'o.pay_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $order = 'o.create_time desc';
            $field = 'o.*, m.nickname,m.headimg,m.mobile';
            $join = [
                [ 'member m', 'o.member_id = m.member_id', 'left' ]
            ];
            $list = $order_model->getOrderDetailPageList($condition, $page, $page_size, $order, $field, 'o', $join);
            return $list;
        }
        $this->assign('giftcard_id', $giftcard_id);
        return $this->fetch("order/order");
    }

    /**
     * 详情
     * @return mixed|void
     */
    public function detail()
    {
        $order_id = input('order_id', '');
        $order_model = new GiftCardOrder();
        $order_detail = $order_model->getOrderDetail([ 'site_id' => $this->site_id, 'order_id' => $order_id ])[ 'data' ] ?? [];
        $card_model = new CardModel();
        $card_list = $card_model->getCardList([ [ 'site_id', '=', $this->site_id ], [ 'order_id', '=', $order_detail[ 'order_id' ] ] ])[ 'data' ];
        foreach ($card_list as $k => $v) {
            $card_list[ $k ] = $card_model->tran($v);
        }
        $this->assign('order_detail', $order_detail);
        $this->assign('card_list', $card_list);
        return $this->fetch("order/detail");
    }
}