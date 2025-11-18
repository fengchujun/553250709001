<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\Pointexchange\shop\controller;

use addon\pointexchange\model\Order as ExchangeOrderModel;
use app\shop\controller\BaseShop;
use think\App;

/**
 * 兑换发放订单
 */
class Pointexchange extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'POINTEXCHANGE_CSS' => __ROOT__ . '/addon/pointexchange/shop/view/public/css',
            'POINTEXCHANGE_JS' => __ROOT__ . '/addon/pointexchange/shop/view/public/js',
            'POINTEXCHANGE_IMG' => __ROOT__ . '/addon/pointexchange/shop/view/public/img',
        ];
        parent::__construct($app);

    }

    /**
     * 兑换订单列表
     * @return mixed
     */
    public function lists()
    {

        $exchange_id = input('exchange_id', '');
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $condition = [];
            if ($search_text) {
                $condition[] = [ 'eo.exchange_name', 'like', '%' . $search_text . '%' ];
            }

            $name = input('name', '');
            if (!empty($name)) {
                $condition[] = [ 'm.nickname', 'like', '%' . $name . '%' ];
            }

            $mobile = input('mobile', '');
            if (!empty($mobile)) {
                $condition[] = [ 'eo.mobile', 'like', '%' . $mobile . '%' ];
            }

            $type = input('type', '');
            if ($type) {
                $condition[] = [ 'eo.type', '=', $type ];
            }

            if ($exchange_id) {
                $condition[] = [ 'eo.exchange_goods_id', '=', $exchange_id ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            if ($start_time && !$end_time) {
                $condition[] = [ 'eo.pay_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'eo.pay_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'eo.pay_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $order = 'eo.create_time desc';
            $field = 'eo.*,m.nickname';

            $exchange_order_model = new ExchangeOrderModel();
            return $exchange_order_model->getExchangePageList($condition, $page, $page_size, $order, $field, 'eo', [
                [ 'member m', 'm.member_id=eo.member_id', 'left' ]
            ]);
        } else {
            $this->assign('exchange_id', $exchange_id);
            return $this->fetch("exchange_order/lists");
        }

    }

    /**订单详情
     * @return mixed
     */
    public function detail()
    {
        $order_id = input('order_id', 0);
        $order_model = new ExchangeOrderModel();
        $order_info = $order_model->getOrderInfo([ [ 'site_id', '=', $this->site_id ], [ 'order_id', '=', $order_id ] ]);
        $order_info = $order_info[ "data" ];
        if (empty($order_info)) $this->error('未获取到订单数据', href_url('pointexchange://shop/pointexchange/lists'));
        $this->assign("order_info", $order_info);
        return $this->fetch('exchange_order/detail');
    }

}