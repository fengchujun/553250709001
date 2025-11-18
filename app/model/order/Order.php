<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order;

use addon\electronicsheet\model\ElectronicsheetDelivery;
use app\dict\order_refund\OrderRefundDict;
use app\model\express\ExpressDelivery;
use app\model\express\ExpressPackage;
use app\model\message\Message;
use app\model\system\Cron;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Queue;

/**
 * 普通（快递）订单
 *
 * @author Administrator
 *
 */
class Order extends OrderCommon
{

    /*****************************************************************************************订单状态***********************************************/
    // 订单创建
    public const ORDER_CREATE = 0;

    // 订单已支付
    public const ORDER_PAY = 1;

    // 订单备货中
    public const ORDER_PENDING_DELIVERY = 2;

    // 订单已发货（配货）
    public const ORDER_DELIVERY = 3;

    // 订单已收货
    public const ORDER_TAKE_DELIVERY = 4;

    // 订单已结算完成
    public const ORDER_COMPLETE = 10;

    // 订单已关闭
    public const ORDER_CLOSE = -1;

    // 订单类型

    public const DELIVERY_WAIT = 0;


    /***********************************************************************************订单项  配送状态**************************************************/
    // 待发货
    public const DELIVERY_DOING = 1;

    // 已发货
    public const DELIVERY_FINISH = 2;

    // 已收货
    public $order_type = 1;

    // 订单状态
    public $order_status = [
        self::ORDER_CREATE => [
            'status' => self::ORDER_CREATE,
            'name' => '待支付',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon.png',
            'action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'orderAddressUpdate',
                    'title' => '修改地址',
                    'color' => ''
                ],
                [
                    'action' => 'orderAdjustMoney',
                    'title' => '调整价格',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => '',
                ],
                [
                    'action' => 'orderPay',
                    'title' => '支付',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_PAY => [
            'status' => self::ORDER_PAY,
            'name' => '待发货',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [
                [
                    'action' => 'orderDelivery',
                    'title' => '发货',
                    'color' => ''
                ],
                [
                    'action' => 'orderAddressUpdate',
                    'title' => '修改地址',
                    'color' => ''
                ],
            ],
            'member_action' => [],
            'color' => ''
        ],
        self::ORDER_DELIVERY => [
            'status' => self::ORDER_DELIVERY,
            'name' => '已发货',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-receive.png',
            'action' => [
                [
                    'action' => 'takeDelivery',
                    'title' => '确认收货',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'memberTakeDelivery',
                    'title' => '确认收货',
                    'color' => ''
                ],
                [
                    'action' => 'trace',
                    'title' => '查看物流',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_TAKE_DELIVERY => [
            'status' => self::ORDER_TAKE_DELIVERY,
            'name' => '已收货',
            'is_allow_refund' => 1,
            'icon' => 'public/uniapp/order/order-icon-received.png',
            'action' => [],
            'member_action' => [
                [
                    'action' => 'trace',
                    'title' => '查看物流',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_COMPLETE => [
            'status' => self::ORDER_COMPLETE,
            'name' => '已完成',
            'icon' => 'public/uniapp/order/order-icon-received.png',
            'is_allow_refund' => 1,
            'action' => [],
            'member_action' => [
                [
                    'action' => 'trace',
                    'title' => '查看物流',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_CLOSE => [
            'status' => self::ORDER_CLOSE,
            'name' => '已关闭',
            'icon' => 'public/uniapp/order/order-icon-close.png',

            'is_allow_refund' => 0,
            'action' => [],
            'member_action' => [],
            'color' => ''
        ]
    ];

    /**
     * 订单状态（发货列表）
     */
    public $delivery_order_status = [
        self::ORDER_PAY => [
            'status' => self::ORDER_PAY,
            'name' => '待发货',
            'is_allow_refund' => 0,
            'icon' => 'public/uniapp/order/order-icon-send.png',
            'action' => [
                [
                    'action' => 'orderDelivery',
                    'title' => '发货',
                    'color' => ''
                ],
                [
                    'action' => 'orderAddressUpdate',
                    'title' => '修改地址',
                    'color' => ''
                ],
            ],
            'member_action' => [],
            'color' => ''
        ]
    ];

    // 配送状态
    public $delivery_status = [
        self::DELIVERY_WAIT => [
            'status' => self::DELIVERY_WAIT,
            'name' => '待发货',
            'color' => ''
        ],
        self::DELIVERY_DOING => [
            'status' => self::DELIVERY_DOING,
            'name' => '已发货',
            'color' => ''
        ],
        self::DELIVERY_FINISH => [
            'status' => self::DELIVERY_FINISH,
            'name' => '已收货',
            'color' => ''
        ]
    ];


    /**
     * 订单支付
     * @param $data
     * @return array
     */
    public function orderPay($data)
    {
        $order_info = $data['order_info'];
        $member_info = $data['member_info'];
        $pay_type = $data['pay_type'];

        $pay_type_list = $this->getPayType();
        if ($order_info['order_status'] != self::ORDER_CREATE) return $this->error([], '当前订单未处于待付款状态，无法支付！');

        $condition = [
            ['order_id', '=', $order_info['order_id']],
            ['order_status', '=', self::ORDER_CREATE],
        ];
        $data = [
            'order_status' => self::ORDER_PAY,
            'order_status_name' => $this->order_status[self::ORDER_PAY]['name'],
            'pay_status' => 1,
            'order_status_action' => json_encode($this->order_status[self::ORDER_PAY], JSON_UNESCAPED_UNICODE),
            'refund_status_action' => json_encode(OrderRefundDict::getStatus(OrderRefundDict::REFUND_NOT_APPLY), JSON_UNESCAPED_UNICODE),
            'pay_time' => time(),
            'is_enable_refund' => 1,
            'pay_type' => $pay_type,
            'pay_type_name' => $pay_type_list[$pay_type]
        ];
        $result = model('order')->update($data, $condition);
        //记录订单日志 start
        $action = '商家对订单进行了线下支付';
        //获取用户信息
        if (empty($log_data)) {
            $log_data = [
                'uid' => $order_info['member_id'],
                'nick_name' => $member_info['nickname'],
                'action_way' => 1
            ];
            $action = '买家支付了订单';
        }
        $log_data = array_merge($log_data, [
            'order_id' => $order_info['order_id'],
            'action' => $action,
            'order_status' => self::ORDER_PAY,
            'order_status_name' => $this->order_status[self::ORDER_PAY]['name']
        ]);

        OrderLog::addOrderLog($log_data, $this);
        //记录订单日志 end


        return $this->success($result);
    }

    /**
     * 批量订单发货（物流）
     * @param $param
     * @param $order_list
     * @return array|int
     */
    public function orderBatchDelivery($param, $order_list)
    {
        model('express_delivery_package')->startTrans();

        try {

            if (empty($order_list)) {
                return $this->error('', '请先选择要发货的订单！');
            }

            foreach ($order_list as $v) {
                $param['order_id'] = $v['order_id'];
                $param['order_goods_ids'] = '';

                if ($param['type'] == 'electronicsheet') {//电子面单发货

                    $addon_is_exit = addon_is_exit('electronicsheet', $param['site_id']);
                    if ($addon_is_exit != 1) {
                        return $this->error('', '电子面单插件不存在！');
                    }

                    $electronicsheet_model = new ElectronicsheetDelivery();
                    $result = $electronicsheet_model->delivery($param);
                    if ($result['code'] < 0) {
                        return $result;
                    }
                    $param['delivery_no'] = $result['data']['Order']['LogisticCode'];
                } else {
                    $param['delivery_no'] = $v['delivery_no'];
                }
                $result = $this->orderGoodsDelivery($param, 2);
                if ($result['code'] < 0) {
                    model('express_delivery_package')->rollback();
                    return $result;
                }
            }
            model('express_delivery_package')->commit();
            return $this->success();
        } catch ( Exception $e ) {
            model('express_delivery_package')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 订单项发货（物流）
     * @param $param
     * @param int $type 1：订单项发货，2：整体发货
     * @param array $log_data
     * @return array|int
     */
    public function orderGoodsDelivery($param, $type = 1, $log_data = [])
    {
        $param['type'] = $param['type'] ?? 'manual';
        model('order_goods')->startTrans();
        try {
            $order_id = $param['order_id'];
            $delivery_no = $param['delivery_no']; // 物流单号
            $delivery_type = $param['delivery_type'];
            if ($delivery_type == 0) {
                $express_company_id = 0;
            } else {
                $express_company_id = $param['express_company_id'] ?? 0;
            }
            $site_id = $param['site_id'];
            if ($type == 1) {
                if (empty($param['order_goods_ids']))
                    return $this->error('', '发货货物不可为空！');

                $order_goods_id_array = explode(',', $param['order_goods_ids']);

            } else {
                $order_goods_id_array = model('order_goods')->getColumn(
                    [
                        ['order_id', '=', $order_id],
                        ['site_id', '=', $site_id],
                        ['delivery_status', '=', self::DELIVERY_WAIT],
                        ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]
                    ],
                    'order_goods_id'
                );
            }
            if (empty($order_goods_id_array))
                return $this->error('', '发货货物不可为空！');

            $order_id = 0;
            $member_id = 0;
            $goods_id_array = [];
            $order_stock_model = new OrderStock();
            $stock_sku_list = [];
            foreach ($order_goods_id_array as $k => $v) {

                $order_goods_info = model('order_goods')->getInfo([['order_goods_id', '=', $v], ['site_id', '=', $site_id]], '*');
                // 已退款的订单项不可发货
                if ($order_goods_info['refund_status'] == OrderRefundDict::REFUND_COMPLETE) {
                    model('order_goods')->rollback();
                    return $this->error([], 'ORDER_GOODS_IS_REFUND');
                }

                if ($order_goods_info['delivery_status'] == self::DELIVERY_DOING) {
                    model('order_goods')->rollback();
                    return $this->error([], 'ORDER_GOODS_IS_DELIVERYED');
                }
                $order_goods_info['num'] = numberFormat($order_goods_info['num']);
                $member_id = $order_goods_info['member_id'];
                $goods_id_array[] = $order_goods_info['sku_id'] . ':' . $order_goods_info['num'] . ':' . $order_goods_info['sku_name'] . ':' . $order_goods_info['sku_image'];
                $data = ['delivery_status' => self::DELIVERY_DOING, 'delivery_status_name' => $this->delivery_status[self::DELIVERY_DOING]['name']];
                if (!empty($delivery_no)) {
                    $data['delivery_no'] = $delivery_no;
                }
                $res = model('order_goods')->update($data, [
                    ['order_goods_id', '=', $v],
                    ['delivery_status', '=', self::DELIVERY_WAIT]
                ]);
                $order_id = $order_goods_info['order_id'];
                //实际发货扣除库存
                $stock_sku_list[] = $order_goods_info;

            }

            $order_info = model('order')->getInfo([['order_id', '=', $order_id]], 'store_id,site_id');
            $stock_result = $order_stock_model->decOrderStock([
                'store_id' => $order_info['store_id'],
                'site_id' => $order_info['site_id'],
                'goods_sku_list' => $stock_sku_list,
                'user_info' => $param['user_info'] ?? []
            ]);

            if ($stock_result['code'] < 0) {
                model('order_goods')->rollback();
                return $stock_result;
            }

            // 创建包裹
            $order_common_model = new OrderCommon();
            $lock_result = $order_common_model->verifyOrderLock($order_id);
            if ($lock_result['code'] < 0) {
                model('order_goods')->rollback();
                return $lock_result;
            }

            $express_delivery_model = new ExpressDelivery();
            $delivery_data = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array,
                'goods_id_array' => $goods_id_array,
                'goods_array' => $goods_id_array,
                'site_id' => $site_id,
                'delivery_no' => $delivery_no,
                'member_id' => $member_id,
                'express_company_id' => $express_company_id,
                'delivery_type' => $delivery_type,
                'type' => $param['type'],
                'template_id' => $param['template_id']
            ];

            $delivery_id = $express_delivery_model->delivery($delivery_data);

            // 检测整体, 订单中订单项是否全部发放完毕
            $res = $this->orderCommonDelivery($order_id, $log_data);
            if ($res['code'] < 0) {
                model('order_goods')->rollback();
                return $res;
            }

            model('order_goods')->commit();
            return $this->success($delivery_id);
        } catch ( Exception $e ) {
            model('order_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 批量订单发货（导入excel文件发货）
     * @param $param
     * @param $site_id
     * @param $uid
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function orderFileDelivery($param, $site_id, $uid)
    {
        //电子面单插件
        $addon_is_exit = addon_is_exit('electronicsheet', $site_id);

        $PHPReader = IOFactory::createReader('Xlsx');
        //载入文件
        $PHPExcel = $PHPReader->load($param['path']);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        if ($allRow < 2) {
            return $this->error('', '导入了一个空文件！');
        }

        $user_info = model('user')->getInfo([['uid', '=', $uid]], 'username');

        //添加文件上传记录
        $success_num = $allRow - 1;
        $error_num = 0;
        $data = [
            'site_id' => $site_id,
            'filename' => $param['filename'],
            'path' => $param['path'],
            'order_num' => $allRow - 1,
            'success_num' => $success_num,
            'create_time' => time(),
            'uid' => $uid,
            'username' => $user_info['username']
        ];
        $res = model('order_import_file')->add($data);
        if (!$res) {
            return $this->error('', '上传文件失败！');
        }

        model('order_import_file')->startTrans();
        try {

            for ($i = 2; $i <= $allRow; $i++) {

                $delivery_data = [
                    'type' => '',//发货方式（手动发货、电子面单）
                    'express_company_id' => 0,//物流公司
                    'delivery_type' => 1,//是否需要物流
                    'site_id' => $site_id,
                    'template_id' => 0,//电子面单模板id
                    'delivery_no' => ''
                ];

                //订单编号
                $order_no = $PHPExcel->getActiveSheet()->getCell('A' . $i)->getValue();
                $order_no = trim($order_no, ' ');
                $order_no = preg_replace('/\s+/', '', $order_no);
                //订单内容
                $order_name = $PHPExcel->getActiveSheet()->getCell('B' . $i)->getValue();
                $order_name = trim($order_name, ' ');
                //发货方式
                $type = $PHPExcel->getActiveSheet()->getCell('F' . $i)->getValue();
                $type = trim($type, ' ');
                //物流公司名称或电子面单名称
                $name = $PHPExcel->getActiveSheet()->getCell('G' . $i)->getValue();
                $name = trim($name, ' ');
                //物流单号
                $delivery_no = $PHPExcel->getActiveSheet()->getCell('H' . $i)->getValue();
                $delivery_no = trim($delivery_no, ' ');

                if (empty($type)) {
                    $error_num += 1;
                    $success_num -= 1;
                    //修改数量
                    model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                    //添加失败记录
                    model('order_import_file_log')->add(
                        [
                            'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                            'status' => -1, 'reason' => '发货方式为空'
                        ]
                    );
                    continue;
                }
                if ($type == '电子面单' && $addon_is_exit == 1) {

                    if (empty($name)) {
                        $error_num += 1;
                        $success_num -= 1;
                        //修改数量
                        model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                        //添加失败记录
                        model('order_import_file_log')->add(
                            [
                                'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                                'status' => -1, 'reason' => '电子面单模板为空'
                            ]
                        );
                        continue;
                    }
                    $delivery_data['type'] = 'electronicsheet';
                    $template_id = model('express_electronicsheet')->getValue([['template_name', '=', $name], ['site_id', '=', $site_id]], 'id');
                    if (empty($template_id)) {
                        $error_num += 1;
                        $success_num -= 1;
                        //修改数量
                        model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                        //添加失败记录
                        model('order_import_file_log')->add(
                            [
                                'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                                'status' => -1, 'reason' => '电子面单模板不存在'
                            ]
                        );
                        continue;
                    }
                    $delivery_data['template_id'] = $template_id;
                } elseif ($type == '电子面单' && $addon_is_exit != 1) {
                    $error_num += 1;
                    $success_num -= 1;
                    //修改数量
                    model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                    //添加失败记录
                    model('order_import_file_log')->add(
                        [
                            'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                            'status' => -1, 'reason' => '电子面单插件未安装']
                    );
                    continue;
                } else {
                    $delivery_data['type'] = 'manual';

                    if (empty($delivery_no) || empty($name)) {//无需物流
                        $delivery_data['delivery_type'] = 0;
                    } else {
                        $company_id = model('express_company')->getValue([['site_id', '=', $site_id], ['company_name', '=', $name]], 'company_id');
                        if ($company_id == '') {
                            $error_num += 1;
                            $success_num -= 1;
                            //修改数量
                            model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                            //添加失败记录
                            model('order_import_file_log')->add(
                                [
                                    'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                                    'status' => -1, 'reason' => '物流公司不存在'
                                ]
                            );
                            continue;
                        }
                        $delivery_data['express_company_id'] = $company_id;
                        $delivery_data['delivery_no'] = $delivery_no;
                    }
                }
                //获取订单信息
                $order_info = model('order')->getInfo([['order_no', '=', $order_no], ['site_id', '=', $site_id]], 'order_id,order_status');
                if (empty($order_info) || $order_info['order_status'] != self::ORDER_PAY) {
                    $error_num += 1;
                    $success_num -= 1;
                    //修改数量
                    model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                    //添加失败记录
                    model('order_import_file_log')->add(
                        [
                            'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                            'status' => -1, 'reason' => '订单不存在或者已发货'
                        ]
                    );
                    continue;
                }
                $delivery_data['order_id'] = $order_info['order_id'];
                $delivery_data['order_goods_ids'] = '';

                if ($delivery_data['type'] == 'electronicsheet') {//电子面单发货

                    $electronicsheet_model = new ElectronicsheetDelivery();
                    $result = $electronicsheet_model->delivery($delivery_data);
                    if ($result['code'] < 0) {
                        $error_num += 1;
                        $success_num -= 1;
                        //修改数量
                        model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                        //添加失败记录
                        model('order_import_file_log')->add(
                            [
                                'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                                'status' => -1, 'reason' => $result['message']
                            ]
                        );
                        continue;
                    }
                    $delivery_data['delivery_no'] = $result['data']['Order']['LogisticCode'];
                }

                $result = $this->orderGoodsDelivery($delivery_data, 2);
                if ($result['code'] < 0) {
                    $error_num += 1;
                    $success_num -= 1;
                    //修改数量
                    model('order_import_file')->update(['success_num' => $success_num, 'error_num' => $error_num], [['id', '=', $res]]);
                    //添加失败记录
                    model('order_import_file_log')->add(
                        [
                            'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                            'status' => -1, 'reason' => $result['message']
                        ]
                    );
                    continue;
                }

                //添加成功记录
                model('order_import_file_log')->add(
                    [
                        'site_id' => $site_id, 'file_id' => $res, 'order_no' => $order_no, 'order_name' => $order_name,
                        'status' => 0, 'reason' => ''
                    ]
                );
            }

            model('order_import_file')->commit();
            return $this->success();
        } catch ( Exception $e ) {
            model('order_import_file')->rollback();
            //修改数量
            model('order_import_file')->update(['success_num' => 0, 'error_num' => $allRow - 1], [['id', '=', $res]]);
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单收货
     * @param $order_id
     * @return array
     */
    public function orderTakeDelivery($order_id)
    {
        //保存物流轨迹信息
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], 'mobile');
        $express_package_model = new ExpressPackage();
        $express_package_model->package([['order_id', '=', $order_id]], $order_info['mobile'], true);
        return $this->success();
    }

    /**
     * 订单收货地址修改
     * @param $param
     * @param $condition
     * @param array $log_data
     * @return array
     */
    public function orderAddressUpdate($param, $condition, $log_data = [])
    {
        $province_id = $param['province_id'];
        $city_id = $param['city_id'];
        $district_id = $param['district_id'];
        $community_id = $param['community_id'];
        $address = $param['address'];
        $full_address = $param['full_address'];
        $longitude = $param['longitude'];
        $latitude = $param['latitude'];
        $mobile = $param['mobile'];
        $telephone = $param['telephone'];
        $name = $param['name'];
        $data = [
            'province_id' => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'community_id' => $community_id,
            'address' => $address,
            'full_address' => $full_address,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'mobile' => $mobile,
            'telephone' => $telephone,
            'name' => $name,
        ];
        $order_info = model('order')->getInfo($condition, '*');
        $order_status_array = [self::ORDER_PAY, self::ORDER_CREATE];
        if (!in_array($order_info['order_status'], $order_status_array))
            return $this->error('', '当前订单状态不可编辑收货地址！');
        //记录订单日志 start
        if ($log_data) {
            $log_data = array_merge($log_data, [
                'order_status' => $order_info['order_status'],
                'order_status_name' => $order_info['order_status_name']
            ]);

            OrderLog::addOrderLog($log_data, $this);
        }
        //记录订单日志 end
        $result = model('order')->update($data, $condition);
        return $this->success($result);
    }

    /**
     * 退款完成操作
     * @param $order_goods_info
     */
    public function refund($order_goods_info)
    {
        //是否入库
        $order_stock_model = new OrderStock();
        if ($order_goods_info['is_refund_stock'] == 1) {
            $order_stock_model->incOrderStock($order_goods_info);
        }else if($order_goods_info['delivery_status'] == 0){
            $order_stock_model->incOrderSaleStock([
                'store_id' => $order_goods_info['store_id'],
                'goods_sku_data' => [
                    [
                        'sku_id' => $order_goods_info['sku_id'],
                        'num' => $order_goods_info['num'],
                    ],
                ],
            ]);
        }
        //检测订单项是否否全部发放完毕
        $this->orderDelivery($order_goods_info['order_id']);
    }

    /**
     * 订单发货
     * @param $order_id
     * @param array $log_data
     * @return array
     */
    public function orderDelivery($order_id, $log_data = [])
    {
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], '*');
        $site_id = $order_info['site_id'];
        // 待发货状态
        if ($order_info['order_status'] == self::ORDER_PAY) {

            // 统计订单项目
            $count = model('order_goods')->getCount([['order_id', '=', $order_id], ['delivery_status', '=', self::DELIVERY_WAIT], ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');
//            $delivery_count = model('order_goods')->getCount([['order_id', '=', $order_id], ['delivery_status', '=', self::DELIVERY_DOING], ['refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]], 'order_goods_id');
//            if ($count == 0 && $delivery_count > 0) {
            if ($count == 0) {
                // 全部发货
                model('order')->startTrans();
                try {
                    // 修改订单项的配送状态
                    $order_data = [
                        'order_status' => self::ORDER_DELIVERY,
                        'order_status_name' => $this->order_status[self::ORDER_DELIVERY]['name'],
                        'delivery_status' => self::DELIVERY_FINISH,
                        'delivery_status_name' => $this->delivery_status[self::DELIVERY_FINISH]['name'],
                        'order_status_action' => json_encode($this->order_status[self::ORDER_DELIVERY], JSON_UNESCAPED_UNICODE),
                        'delivery_time' => time()

                    ];
                    model('order')->update($order_data, [['order_id', '=', $order_id]]);
                    if ($log_data) {
                        // 记录订单日志 start
                        $log_data = array_merge($log_data, [
                            'order_id' => $order_id,
                            'order_status' => self::ORDER_DELIVERY,
                            'order_status_name' => $this->order_status[self::ORDER_DELIVERY]['name']
                        ]);
                        OrderLog::addOrderLog($log_data, $this);
                        //记录订单日志 end
                    }

                    //订单自动收货
                    OrderCron::takeDelivery(['order_id' => $order_id, 'site_id' => $site_id]);
                    // 订单发货完成，小程序发货信息录入，视频号
                    Queue::push('app\job\order\OrderDeliveryAfter', ['order_id' => $order_id, 'site_id' => $site_id]);

                    model('order')->commit();
                    // 订单发货消息
                    $message_model = new Message();
                    $message_model->sendMessage(['keywords' => 'ORDER_DELIVERY', 'order_id' => $order_id, 'site_id' => $site_id]);
                    return $this->success();
                } catch ( Exception $e ) {
                    model('order')->rollback();
                    return $this->error('', $e->getMessage());
                }
            } else {
                if ($log_data) {
                    $log_data = array_merge($log_data, [
                        'order_id' => $order_id,
                        'order_status' => $order_info['order_status'],
                        'order_status_name' => $this->order_status[$order_info['order_status']]['name']
                    ]);
                    OrderLog::addOrderLog($log_data, $this);
                }
                return $this->success();
            }
        } else {
            return $this->success();
        }

    }

    /**
     * 订单详情
     * @param $order_info
     * @return array
     */
    public function orderDetail($order_info)
    {
        return $this->orderPartDeliveryHandle($order_info);
    }

    /**
     * 订单部分发货处理
     * @param $order_info
     * @return array
     */
    public function orderPartDeliveryHandle($order_info)
    {
        $res = [];
        if(isset($order_info['order_status']) && $order_info['order_status'] == self::ORDER_PAY){
            $is_delivery = false;
            foreach($order_info['order_goods'] as $val){
                if($val['delivery_status'] == 1){
                    $is_delivery = true;
                    break;
                }
            }
            if($is_delivery){
                $res['order_status_name'] = '部分发货';
                $order_status_action = json_decode($order_info['order_status_action'], true);
                $order_status_action['member_action'][] = [
                    'action' => 'trace',
                    'title' => '查看物流',
                    'color' => ''
                ];
                $res['order_status_action'] = json_encode($order_status_action);
            }
        }
        return $res;
    }

    /**
     *  计算订单销售额
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getOrderMoneySum($condition = [], $field = 'order_money')
    {
        $res = model('order')->getSum($condition, $field);
        return $this->success($res);
    }
}
