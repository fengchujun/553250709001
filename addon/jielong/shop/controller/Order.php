<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\jielong\shop\controller;

use addon\jielong\model\JielongOrder;
use addon\jielong\model\JielongOrderCommon;
use app\shop\controller\BaseShop;
use app\model\order\OrderCommon as OrderCommonModel;
use think\facade\Config;

/**
 * 接龙订单
 */
class Order extends BaseShop
{
    /*
     *  订单列表
     */
    public function lists()
    {
        $jielong_id = input('jielong_id', 0);
        $jielong_order_model = new JielongOrder();
        $condition = [
            [ 'pjo.site_id', '=', $this->site_id ],
        ];
        if ($jielong_id > 0) {
            $condition[] = [ 'jielong_id', '=', $jielong_id ];
        }

        if (request()->isJson()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            //搜索类型（订单号，收货人姓名，收货人手机号码）
            $order_label = input('order_label', 'order_no');
            $search = input('search', '');
            if ($search) {
                $condition[] = [ 'pjo.' . $order_label, 'like', '%' . $search . '%' ];
            }
            //订单状态
            $order_status = input('order_status', '');
            if ($order_status !== '') {
                $condition[] = [ 'o.order_status', '=', $order_status ];
            }
            //订单来源
            $order_from = input("order_from", '');
            if ($order_from) {
                $condition[] = [ "pjo.order_from", "=", $order_from ];
            }
            //支付方式
            $pay_type = input("pay_type", '');
            if ($pay_type) {
                $condition[] = [ "pjo.pay_type", "=", $pay_type ];
            }

            //创建时间
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = [ 'pjo.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'pjo.create_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && !$end_time) {
                $condition[] = [ 'pjo.create_time', '>=', date_to_time($start_time) ];
            }

            $list = $jielong_order_model->getJielongOrderPageList($condition, $page, $page_size, 'id desc');
            return $list;
        } else {

            //搜索方式
            $order_label_list = array (
                "order_no" => "订单号",
                "name" => "收货人姓名",
                "mobile" => "收货人手机号",
            );
            $this->assign('order_label_list', $order_label_list);

            //订单状态
            $order_model = new JielongOrderCommon();
            $order_status_list = $order_model->order_status;
            $this->assign("order_status_list", $order_status_list);

            //订单来源 (支持端口)
            $order_from = Config::get("app_type");
            $this->assign('order_from_list', $order_from);

            $order_common_model = new OrderCommonModel();
            //付款方式
            $pay_type = $order_common_model->getPayType();
            $this->assign("pay_type_list", $pay_type);

            $this->assign('jielong_id', $jielong_id);
            return $this->fetch("order/lists");
        }
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function detail()
    {
        $jielong_order_model = new JielongOrder();

        $id = input('id', '');

        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'id', '=', $id ]
        ];

        $info = $jielong_order_model->getJielongOrderInfo($condition);

        $this->assign('order_detail', $info[ 'data' ]);

        return $this->fetch("order/detail");
    }


    /**
     * 删除订单
     */
    public function deleteOrder()
    {
        if (request()->isJson()) {

            $id = input('order_id');

            $order_common_model = new JielongOrderCommon();
            $condition = [
                [ 'id', '=', $id ],
                [ 'site_id', '=', $this->site_id ]
            ];

            $res = $order_common_model->deleteOrder($condition);
            return $res;
        }
    }

}