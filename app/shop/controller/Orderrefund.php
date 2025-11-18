<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use app\dict\order_refund\OrderRefundDict;
use app\model\order\multitype;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund as OrderRefundModel;
use app\model\order\OrderExport;
use app\model\member\Member;

/**
 * 订单维权
 * Class Orderrefund
 * @package app\shop\controller
 */
class Orderrefund extends BaseShop
{

    /**
     * 维权订单列表
     * @return mixed
     */
    public function lists()
    {
        $refund_status = input('refund_status', '');//退款状态
        $sku_name = input('sku_name', '');//商品名称
        $refund_type = input('refund_type', '');//退款方式
        $start_time = input('start_time', '');//开始时间
        $end_time = input('end_time', '');//结束时间
        $order_no = input('order_no', '');//订单编号
        $delivery_status = input('delivery_status', '');//物流状态
        $refund_no = input('refund_no', '');//退款编号

        $delivery_no = input('delivery_no', '');//物流编号
        $refund_delivery_no = input('refund_delivery_no', '');//退款物流编号
        $refund_mode = input('refund_mode', '');//退款类型

        $order_refund_model = new OrderRefundModel();
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                ['nop.site_id', '=', $this->site_id ]
            ];
            //退款状态
            if ($refund_status != '') {
                $condition[] = ['nop.refund_status', '=', $refund_status ];
            } else {
                $condition[] = ['nop.refund_status', '<>', OrderRefundDict::REFUND_NOT_APPLY ];
            }
            //物流状态
            if ($delivery_status != '') {
                $condition[] = ['nop.delivery_status', '=', $delivery_status ];
            }
            //商品名称
            if ($sku_name != '') {
                $condition[] = ['nop.sku_name', 'like', "%$sku_name%" ];
            }
            //退款方式
            if ($refund_type != '') {
                $condition[] = ['nop.refund_type', '=', $refund_type ];
            }
            //退款编号
            if ($refund_no != '') {
                $condition[] = ['nop.refund_no', 'like', "%$refund_no%" ];
            }
            //订单编号
            if ($order_no != '') {
                $condition[] = ['nop.order_no', 'like', "%$order_no%" ];
            }
            //物流编号
            if ($delivery_no != '') {
                $condition[] = ['nop.delivery_no', 'like', "%$delivery_no%" ];
            }
            //退款物流编号
            if ($refund_delivery_no != '') {
                $condition[] = ['nop.refund_delivery_no', 'like', "%$refund_delivery_no%" ];
            }
            //退款类型
            if ($refund_mode == 1) {
                $condition[] = ['nop.refund_mode', 'in', [ 0, 1 ] ];
            } else if ($refund_mode == 2) {
                $condition[] = ['nop.refund_mode', '=', 2 ];
            }

            if (!empty($start_time) && empty($end_time)) {
                $condition[] = ['nop.refund_time', '>=', date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = ['nop.refund_time', '<=', date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'nop.refund_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $res = $order_refund_model->getRefundOrderGoodsPageList($condition, $page_index, $page_size, 'nop.refund_action_time desc');

            //查询退款总金额
            $join = [
                [
                    'order no',
                    'nop.order_id = no.order_id',
                    'left'
                ],
                [
                    'member m',
                    'm.member_id = no.member_id',
                    'left'
                ],
            ];
            $total_refund_pay_money = $order_refund_model->getRefundSum($condition, 'nop.refund_pay_money', 'nop', $join)['data'];
            $res['data']['total_refund_pay_money'] = $total_refund_pay_money;
            return $res;
        } else {
            $this->assign('refund_status_list', OrderRefundDict::getStatus());//退款状态
            $this->assign('refund_type_list', OrderRefundDict::getRefundType());//退款方式
            return $this->fetch('orderrefund/lists');
        }
    }

    /**
     * 维权订单详情
     * @return mixed
     */
    public function detail()
    {
        $order_goods_id = input('order_goods_id', 0);
        //维权订单项信息
        $order_refund_model = new OrderRefundModel();
        $detail = $order_refund_model->getRefundDetail($order_goods_id)[ 'data' ];

        if (empty($detail))
            $this->error('未获取到维权信息', href_url('shop/orderrefund/lists'));

        $order_common_model = new OrderCommon();
//        $order_info_result  = $order_common_model->getOrderInfo([["order_id", "=", $detail["order_id"]]]);
        $order_info_result = $order_common_model->getOrderDetail($detail['order_id']);
        $order_info = $order_info_result['data'];
        if (empty($order_info))
            $this->error('未获取到维权信息', href_url('shop/orderrefund/lists'));

        $template = 'orderrefund/detail';
        if ($order_info['order_type'] == 4) {
            $template = 'orderrefund/virtualdetail';
        }

        //添加会员昵称
        $member = new Member();
        $member_info = $member->getMemberInfo([ ['member_id', '=', $order_info[ 'member_id' ] ] ], 'nickname')[ 'data' ] ?? [];
        $order_info[ 'nickname' ] = $member_info[ 'nickname' ] ?? '';
        $this->assign('detail', $detail);
        $this->assign('order_info', $order_info);
        return $this->fetch($template);
    }

    /**
     * 维权拒绝
     * @return array
     */
    public function refuse()
    {
        $order_goods_id = input('order_goods_id', 0);
        $refund_refuse_reason = input('refund_refuse_reason', '');
        $order_refund_model = new OrderRefundModel();
        $data = [
            'order_goods_id' => $order_goods_id,
            'refund_refuse_reason' => $refund_refuse_reason
        ];
        $log_data = [
            'uid' => $this->user_info[ 'uid' ],
            'nick_name' => $this->user_info[ 'username' ],
            'action' => '商家拒绝了维权',
            'action_way' => 2
        ];
        return $order_refund_model->orderRefundRefuse($data, $this->user_info, $refund_refuse_reason, $log_data);
    }

    /**
     * 维权同意
     * @return array
     */
    public function agree()
    {
        $order_goods_id = input('order_goods_id', 0);
        $order_refund_model = new OrderRefundModel();
        $data = [
            'order_goods_id' => $order_goods_id
        ];
        return $order_refund_model->orderRefundConfirm($data, $this->user_info);
    }

    /**
     * 维权收货
     * @return array
     */
    public function receive()
    {
        $order_goods_id = input('order_goods_id', 0);
        $is_refund_stock = input('is_refund_stock', 0);//是否入库

        $order_refund_model = new OrderRefundModel();
        $data = [
            'order_goods_id' => $order_goods_id,
            'is_refund_stock' => $is_refund_stock
        ];
        return $order_refund_model->orderRefundTakeDelivery($data, $this->user_info);
    }

    /**
     * 维权通过
     * @return array|null
     */
    public function complete()
    {
        $order_goods_id = input('order_goods_id', 0);
        $refund_money_type = input('refund_money_type', '');
        $shop_refund_remark = input('shop_refund_remark', '');
        $refund_real_money = input('refund_real_money', 0);
        $is_deposit_back = input('is_deposit_back', 1);

        $order_refund_model = new OrderRefundModel();
        $data = [
            'order_goods_id' => $order_goods_id,
            'refund_money_type' => $refund_money_type,
            'shop_refund_remark' => $shop_refund_remark,
            'refund_real_money' => $refund_real_money,
            'is_deposit_back' => $is_deposit_back
        ];
        $log_data = [
            'uid' => $this->user_info[ 'uid' ],
            'nick_name' => $this->user_info[ 'username' ],
            'action' => '商家对维权进行了转账，维权结束',
            'action_way' => 2
        ];
        return $order_refund_model->orderRefundFinish($data, $this->user_info, $log_data);
    }


    /**
     * 订单导出（维权订单）
     */
    public function exportRefundOrder()
    {
        $refund_status = input('refund_status', '');//退款状态
        $sku_name = input('sku_name', '');//商品名称
        $refund_type = input('refund_type', '');//退款方式
        $start_time = input('start_time', '');//开始时间
        $end_time = input('end_time', '');//结束时间
        $order_no = input('order_no', '');//订单编号
        $delivery_status = input('delivery_status', '');//物流状态
        $refund_no = input('refund_no', '');//退款编号

        $order_refund_model = new OrderRefundModel();
        $delivery_no = input('delivery_no', '');//物流编号
        $refund_delivery_no = input('refund_delivery_no', '');//退款物流编号
        $condition_desc = [];

        $condition[] = [ 'og.site_id', '=', $this->site_id ];
        //退款状态
        $refund_status_list = OrderRefundDict::getStatus();
        $refund_status_name = '全部';
        if ($refund_status != '') {
            $condition[] = ['og.refund_status', '=', $refund_status ];
            $refund_status_name = $refund_status_list[ $refund_status ][ 'name' ] ?? '';
        } else {
            $condition[] = ['og.refund_status', '<>', OrderRefundDict::REFUND_NOT_APPLY ];
        }
        $condition_desc[] = [ 'name' => '维权状态', 'value' => $refund_status_name ];

        //物流状态
        if ($delivery_status != '') {
            $condition[] = ['og.delivery_status', '=', $delivery_status ];
        }

        //商品名称
        $sku_name_value = '';
        if ($sku_name != '') {
            $condition[] = ['og.sku_name', 'like', "%$sku_name%" ];
            $sku_name_value = $sku_name;
        }
        $condition_desc[] = [ 'name' => '商品名称', 'value' => $sku_name_value ];

        //退款方式
        $refund_type_name = '全部';
        if ($refund_type != '') {
            $condition[] = ['og.refund_type', '=', $refund_type ];
            $refund_type_name = $order_refund_model->refund_type[ $refund_type ];
        }
        $condition_desc[] = [ 'name' => '退款方式', 'value' => $refund_type_name ];

        //退款编号
        if ($refund_no != '') {
            $condition[] = [ 'og.refund_no', 'like', "%$refund_no%" ];
        }
        $condition_desc[] = [ 'name' => '退款编号', 'value' => $refund_no ];

        //订单编号
        if ($order_no != '') {
            $condition[] = [ 'og.order_no', 'like', "%$order_no%" ];
        }
        $condition_desc[] = [ 'name' => '订单编号', 'value' => $order_no ];

        //物流编号
        if ($delivery_no != '') {
            $condition[] = [ 'og.delivery_no', 'like', "%$delivery_no%" ];
        }
        //退款物流编号
        if ($refund_delivery_no != '') {
            $condition[] = [ 'og.refund_delivery_no', 'like', "%$refund_delivery_no%" ];
        }
        $time_name = '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ 'og.refund_action_time', '>=', date_to_time($start_time) ];
            $time_name = $start_time . '起';
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'og.refund_action_time', '<=', date_to_time($end_time) ];
            $time_name = '至' . $end_time;
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'og.refund_action_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            $time_name = $start_time . ' 至 ' . $end_time;
        }
        $condition_desc[] = [ 'name' => '申请时间', 'value' => $time_name ];

        $order_export_model = new OrderExport();
        return $order_export_model->orderRefundExport($condition, $condition_desc, $this->site_id);
    }

    /**
     * 订单导出记录
     * @return mixed
     */
    public function export()
    {
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $export_model = new OrderExport();
            $condition = [
                [ 'site_id', '=', $this->site_id ]
            ];
            return $export_model->getRefundExportPageList($condition, $page_index, $page_size, 'create_time desc', '*');
        } else {
            return $this->fetch('orderrefund/export');

        }
    }

    /**
     * 删除订单导出记录
     */
    public function deleteExport()
    {

        if (request()->isJson()) {
            $export_ids = input('export_ids', '');

            $export_model = new OrderExport();
            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'export_id', 'in', (string) $export_ids ]
            ];
            return $export_model->deleteRefundExport($condition);
        }
    }

    /**
     * 关闭维权
     * @return array|void
     */
    public function close()
    {
        if (request()->isJson()) {
            $order_goods_id = input('order_goods_id', 0);
            $order_refund_model = new OrderRefundModel();
            return $order_refund_model->orderRefundClose($order_goods_id, $this->site_id, $this->user_info);
        }
    }

    /**
     * 获取订单项退款信息
     */
    public function getOrderGoodsRefundInfo()
    {
        if (request()->isJson()) {
            $order_goods_id = input('order_goods_id', '');
            $order_refund_model = new OrderRefundModel();
            return $order_refund_model->getOrderGoodsRefundInfo($order_goods_id, $this->site_id);
        }
    }

    /**
     * 主动退款
     * @return array|null
     */
    public function shopActiveRefund()
    {
        $order_goods_id = input('order_goods_id', 0);
        $shop_active_refund_money_type = input('shop_active_refund_money_type', '');
        $shop_active_refund_remark = input('shop_active_refund_remark', '');
        $shop_active_refund_money = input('shop_active_refund_money', '');
        $refund_status = input('refund_status', '');

        $order_refund_model = new OrderRefundModel();
        $params = [
            'site_id' => $this->site_id,
            'app_module' => $this->app_module,
            'shop_active_refund_money_type' => $shop_active_refund_money_type,
            'shop_active_refund_remark' => $shop_active_refund_remark,
            'user_info' => $this->user_info,
            'order_goods_id' => $order_goods_id,
            'shop_active_refund_money' => $shop_active_refund_money,
            'refund_status' => $refund_status,
        ];
        return $order_refund_model->shopActiveRefund($params);
    }
}