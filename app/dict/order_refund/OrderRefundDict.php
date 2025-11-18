<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\dict\order_refund;



/**
 * 订单公共属性
 */
class OrderRefundDict
{
    /********************************************************************************* 订单退款状态 *****************************************************/
    //未申请退款
    const REFUND_NOT_APPLY = 0;

    //已申请退款
    const REFUND_APPLY = 1;

    // 已确认
    const REFUND_CONFIRM = 2;

    //已完成
    const REFUND_COMPLETE = 3;

    //等待买家发货
    const REFUND_WAIT_DELIVERY = 4;

    //等待卖家收货
    const REFUND_WAIT_TAKEDELIVERY = 5;
    //卖家确认收货
    const REFUND_TAKEDELIVERY = 6;

    // 卖家拒绝退款
    const REFUND_DIEAGREE = -1;

    // 卖家关闭退款
    const REFUND_CLOSE = -2;

    //部分退款
    const PARTIAL_REFUND = -3;

    //退款方式
    const ONLY_REFUNDS = 1;//仅退款
    const A_REFUND_RETURN = 2;//退款退货

    public static function getRefundType($type = ''){
        $list = [
            self::ONLY_REFUNDS => '仅退款',
            self::A_REFUND_RETURN => '退款退货',
        ];
        if($type !== '') return $list[$type] ?? '';
        return $list;
    }

    /********************  退款模式  ****************/
    const refund = 1;
    const after_sales = 2;
    /**
     * 退款类型
     * @return void
     */
    public static function getRefundMode($type = ''){
        $list = [
            self::refund => '退款',
            self::after_sales => '售后',
        ];
        if($type !== '') return $list[$type] ?? '';
        return $list;
    }

    /**
     * 维权状态以及操作
     * @param $status
     * @return string|string[]
     */
    public static function getStatus($status = 'all'){
        $list = [
            self::REFUND_NOT_APPLY => [
                'status' => self::REFUND_NOT_APPLY,
                'name' => '',
                'action' => [

                ],
                'member_action' => [
                    [
                        'event' => 'orderRefundApply',
                        'title' => '申请售后',
                        'color' => ''
                    ],
                ]
            ],
            self::REFUND_APPLY => [
                'status' => self::REFUND_APPLY,
                'name' => '申请售后',
                'action' => [
                    [
                        'event' => 'orderRefundAgree',
                        'title' => '同意',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundRefuse',
                        'title' => '拒绝',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭维权',
                        'color' => ''
                    ]
                ],
                'member_action' => [
                    [
                        'event' => 'orderRefundCancel',
                        'title' => '撤销售后',
                        'color' => ''
                    ],
                ]
            ],
            self::REFUND_CONFIRM => [
                'status' => self::REFUND_CONFIRM,
                'name' => '待转账',
                'action' => [
                    [
                        'event' => 'orderRefundTransfer',
                        'title' => '转账',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭售后',
                        'color' => ''
                    ]
                ],
                'member_action' => [

                ]
            ],
            self::REFUND_COMPLETE => [
                'status' => self::REFUND_COMPLETE,
                'name' => '售后结束',
                'action' => [

                ],
                'member_action' => [

                ]
            ],
            self::REFUND_WAIT_DELIVERY => [
                'status' => self::REFUND_WAIT_DELIVERY,
                'name' => '买家待退货',
                'action' => [
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭售后',
                        'color' => ''
                    ]
                ],
                'member_action' => [
                    [
                        'event' => 'orderRefundDelivery',
                        'title' => '填写发货物流',
                        'color' => ''
                    ],
                ]
            ],
            self::REFUND_WAIT_TAKEDELIVERY => [
                'status' => self::REFUND_WAIT_TAKEDELIVERY,
                'name' => '卖家待收货',
                'action' => [
                    [
                        'event' => 'orderRefundTakeDelivery',
                        'title' => '收货',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundRefuse',
                        'title' => '拒绝',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭售后',
                        'color' => ''
                    ]
                ],
                'member_action' => [

                ]
            ],
            self::REFUND_TAKEDELIVERY => [
                'status' => self::REFUND_TAKEDELIVERY,
                'name' => '卖家已收货',
                'action' => [
                    [
                        'event' => 'orderRefundTransfer',
                        'title' => '转账',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭售后',
                        'color' => ''
                    ]
                ],
                'member_action' => [

                ]
            ],
            self::REFUND_DIEAGREE => [
                'status' => self::REFUND_DIEAGREE,
                'name' => '卖家拒绝',
                'action' => [
                    [
                        'event' => 'orderRefundClose',
                        'title' => '关闭售后',
                        'color' => ''
                    ]
                ],
                'member_action' => [
                    [
                        'event' => 'orderRefundCancel',
                        'title' => '撤销售后',
                        'color' => ''
                    ],
                    [
                        'event' => 'orderRefundAsk',
                        'title' => '修改申请',
                        'color' => ''
                    ],
                ]
            ],
            self::PARTIAL_REFUND => [
                'status' => self::PARTIAL_REFUND,
                'name' => '部分退款',
                'action' => [

                ],
                'member_action' => [
                    [
                        'event' => 'orderRefundApply',
                        'title' => '申请售后',
                        'color' => ''
                    ],
                ]
            ],
        ];

        if((string)$status != 'all') {
            return $list[$status] ?? [];
        }
        return $list;
    }

    /**
     * 售后原因
     * @return string[]
     */
    public static function getRefundReasonType($site_id = 1){
        /*$list = [
            '未按约定时间发货',
            '拍错/多拍/不喜欢',
            '协商一致退款',
            '其他',
        ];*/
        $config_model = new \app\model\order\Config();
        $config_info = $config_model->getOrderRefundConfig($site_id)['data']['value'];
        $reason_type = explode("\n", $config_info['reason_type']);
        return $reason_type;
    }

    const back = 1;
    const offline = 2;
    const balance = 3;
    public static function getRefundMoneyType($type = ''){
        $list = [
            self::back => '原路退款',
            self::offline => '线下退款',
            self::balance => '退款到余额',
        ];
        if($type !== '') return $list[$type] ?? '';
        return $list;
    }
}
