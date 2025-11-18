<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\shopcomponent\model;

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\order\OrderRefund;

class Order extends BaseModel
{
    /**
     * 订单发货之后
     */
    public function delivery($order_id)
    {
        $order = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'site_id,is_video_number,pay_type,member_id,order_type');
        if ($order[ 'is_video_number' ] && $order[ 'pay_type' ] == 'wechatpay') {
            $member = model('member')->getInfo([ [ 'member_id', '=', $order[ 'member_id' ] ] ], 'weapp_openid');
            $weapp = new Weapp($order[ 'site_id' ]);
            $data = [
                'out_order_id' => $order_id,
                'openid' => $member[ 'weapp_openid' ],
                'finish_all_delivery' => 1,
                'delivery_list' => []
            ];
            if ($order[ 'order_type' ] == 1) {
                $package_list = model('express_delivery_package')->getList([ [ 'order_id', '=', $order_id ] ], 'express_company_name,delivery_no,order_goods_id_array');
                if (!empty($package_list)) {
                    $company_list = $weapp->getCompanyList();
                    foreach ($package_list as $item) {
                        $delivery_id = 'OTHERS';
                        if ($company_list[ 'code' ] == 0) {
                            $index = array_search($item[ 'express_company_name' ], array_column($company_list[ 'data' ], 'delivery_name'));
                            if ($index !== false && isset($company_list[ 'data' ][ $index ])) {
                                $delivery_id = $company_list[ 'data' ][ $index ][ 'delivery_id' ];
                            }
                        }

                        $order_goods_model = model('order_goods')->getList([ [ 'order_goods_id', "in", $item[ 'order_goods_id_array' ] ] ], "goods_id,sku_id,num");
                        $goods_id_arr = [];
                        $sku_id_arr = [];
                        $number = 0;
                        foreach ($order_goods_model as $order_iem) {
                            $goods_id_arr[] = $order_iem[ 'goods_id' ];
                            $sku_id_arr[] = $order_iem[ 'sku_id' ];
                            $number += $order_iem[ 'num' ];
                        }

                        $data['delivery_list'][] = [
                            'delivery_id' => $delivery_id,
                            'waybill_id' => $item['delivery_no'],
                            'product_info_list' => [
                                [
                                    'out_product_id' => implode(',', $goods_id_arr),
                                    'out_sku_id' => implode(',', $sku_id_arr),
                                    'product_cnt' => $number
                                ]
                            ]
                        ];
                    }
                }
            }
            $res = $weapp->sendDelivery($data);
            return $res;
        }
        return $this->success();
    }

    /**
     * 订单收货
     * @param $order_id
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function takeDelivery($order_id)
    {
        $order = model('order')->getInfo([ [ 'order_id', '=', $order_id ] ], 'site_id,is_video_number,pay_type,member_id,order_type');
        if ($order[ 'is_video_number' ] && $order[ 'pay_type' ] == 'wechatpay') {
            $member = model('member')->getInfo([ [ 'member_id', '=', $order[ 'member_id' ] ] ], 'weapp_openid');
            $weapp = new Weapp($order[ 'site_id' ]);
            $res = $weapp->recieveDelivery([ 'out_order_id' => $order_id, 'openid' => $member[ 'weapp_openid' ] ]);
            if ($res[ 'code' ] < 0) {
                return $this->error('', $res[ 'message' ]);
            }
        }
        return $this->success();
    }

    /**
     * 发起退款申请
     * @param $param
     */
    public function refundApply($param)
    {
        $join = [
            [ 'order o', 'o.order_id = og.order_id', 'left' ]
        ];
        $info = model('order_goods')->getInfo([ [ 'og.order_goods_id', '=', $param[ 'order_goods_id' ] ] ], 'og.out_aftersale_id,og.order_goods_id,og.goods_id,og.sku_id,og.num,o.order_id,o.site_id,o.is_video_number,o.pay_type,o.member_id', 'og', $join);

        if ($info[ 'is_video_number' ] && $info[ 'pay_type' ] == 'wechatpay') {
            $member = model('member')->getInfo([ [ 'member_id', '=', $info[ 'member_id' ] ] ], 'weapp_openid');
            $data = [
                'out_order_id' => (string) $info[ 'order_id' ],
                'out_aftersale_id' => $info[ 'out_aftersale_id' ],
                'openid' => $member[ 'weapp_openid' ],
                'type' => (int) $param[ 'refund_type' ],
                'product_info' => [
                    'out_product_id' => (string) $info[ 'goods_id' ],
                    'out_sku_id' => (string) $info[ 'sku_id' ],
                    'product_cnt' => (int) $info[ 'num' ],
                ],
                'refund_reason' => !empty($param[ 'refund_remark' ]) ? $param[ 'refund_remark' ] : "无",
                'refund_reason_type' => 12,
                'orderamt' => round($param[ 'refund_apply_money' ] * 100)
            ];
            $weapp = new Weapp($info[ 'site_id' ]);
            $weapp->addAftersale($data);
        }
    }

    /**
     * 维权状态变更
     */
    public function refundStatusChange($param)
    {
        if ($param[ 'refund_status' ] != OrderRefundDict::REFUND_APPLY) {
            $join = [
                [ 'order o', 'o.order_id = og.order_id', 'left' ]
            ];
            $info = model('order_goods')->getInfo([ [ 'og.order_goods_id', '=', $param[ 'order_goods_id' ] ] ], 'og.order_goods_id,og.refund_type,o.order_id,o.site_id,o.is_video_number,o.pay_type,o.member_id', 'og', $join);

            if ($info[ 'is_video_number' ] && $info[ 'pay_type' ] == 'wechatpay') {
                $data = [
                    'out_order_id' => $info[ 'order_id' ],
                    'out_aftersale_id' => $info[ 'order_goods_id' ],
                    'finish_all_aftersale' => 0
                ];

                // status 0:未受理,1:用户取消,2:商家受理中,3:商家逾期未处理,4:商家拒绝退款,5:商家拒绝退货退款,6:待买家退货,7:退货退款关闭,8:待商家收货,11:商家退款中,12:商家逾期未退款,13:退款完成,14:退货退款完成
                switch ( $param[ 'refund_status' ] ) {
                    case 0: // 会员取消或商家关闭
                        $data[ 'status' ] = $param[ 'action_way' ] == 1 ? 1 : 0;
                        break;
                    case OrderRefundDict::REFUND_CONFIRM: // 同意退款
                        $data[ 'status' ] = 2;
                        break;
                    case OrderRefundDict::REFUND_DIEAGREE: // 拒绝退款
                        $data[ 'status' ] = $info[ 'refund_type' ] == 1 ? 4 : 5;
                        break;
                    case OrderRefundDict::REFUND_COMPLETE: // 退款完成
                        $data[ 'status' ] = $info[ 'refund_type' ] == 1 ? 13 : 14;
                        $order_goods_count = model('order_goods')->getCount([ [ "order_id", "=", $info[ 'order_id' ] ] ], "order_goods_id");
                        $refund_count = model('order_goods')->getCount([ [ "order_id", "=", $info[ 'order_id' ] ], [ "refund_status", "=", OrderRefundDict::REFUND_COMPLETE ] ], "order_goods_id");
                        if ($order_goods_count == $refund_count) $data[ 'finish_all_aftersale' ] = 1;
                        break;
                }

//                if (isset($data['status'])) {
//                    $weapp = new Weapp($info['site_id']);
//                    $weapp->updateAftersale($data);
//                }
            }
        }
    }
}