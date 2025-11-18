<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\api\controller;

use addon\giftcard\model\card\Card;
use addon\giftcard\model\order\GiftCardOrder;
use addon\giftcard\model\order\GiftCardOrderOperation;
use app\api\controller\BaseApi;

/**
 * 礼品卡订单
 */
class Order extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $card_type = $this->params['card_type'] ?? '';
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $search_text = $this->params['search_text'] ?? '';
        $order_status = $this->params['order_status'] ?? 'all';

        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'member_id', '=', $this->member_id ],
        );
        if (!empty($search_text)) {
            $condition[] = [ 'card_name', 'like', '%' . $search_text . '%' ];
        }

        if (!empty($card_type)) {
            $condition[] = [ 'card_type', '=', $card_type ];
        }
        if ($order_status != 'all') {
            $condition[] = [ 'order_status', '=', $order_status ];
        }
        $order_model = new GiftCardOrder();

        $field = 'order_id,num,order_no,site_id,site_name,order_name,out_trade_no,giftcard_id,card_right_type,card_cover,media_id,order_money,goods_money,pay_money,pay_type,pay_type_name,create_time,pay_time,order_status,member_id,order_from,order_from_name,close_cause,buyer_message,card_price';
        $list = $order_model->getOrderDetailPageList($condition, $page, $page_size, 'order_id desc', $field);

        return $this->response($list);
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_id = $this->params['order_id'] ?? '';
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ORDER_ID'));
        }

        $order_model = new GiftCardOrder();
        $info = $order_model->getOrderDetail([ 'site_id' => $this->site_id, 'member_id' => $this->member_id, 'order_id' => $order_id ])[ 'data' ] ?? [];

        if (!empty($info)) {
            $card_model = new Card();
            $card_list = $card_model->getCardList([ [ 'c.order_id', '=', $order_id ], [ 'c.member_id', '=', $this->member_id ] ], 'c.*,mc.member_card_id', '', 'c', [
                    [ 'giftcard_member_card mc', 'c.card_id=mc.card_id', 'left' ]
                ])[ 'data' ] ?? [];
            $info[ 'card_list' ] = $card_list;
        }
        return $this->response($this->success($info));
    }

    /**
     * 订单关闭
     */
    public function close()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_id = $this->params['order_id'] ?? '';
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ORDER_ID'));
        }

        $order_model = new GiftCardOrderOperation();

        $res = $order_model->close([
            'site_id' => $this->site_id,
            'order_id' => $order_id
        ]);
        return $this->response($res);
    }

}