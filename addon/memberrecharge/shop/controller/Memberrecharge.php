<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\shop\controller;

use addon\memberrecharge\model\MemberRechargeCard as MemberRechargeCardModel;
use addon\memberrecharge\model\Memberrecharge as MemberRechargeModel;
use addon\memberrecharge\model\MemberrechargeOrder as MemberRechargeOrderModel;
use addon\memberrecharge\model\MemberrechargeOrder;
use addon\printer\model\PrinterOrder;
use app\model\store\Store as StoreModel;
use app\shop\controller\BaseShop;
use think\App;

/**
 * 会员充值
 */
class Memberrecharge extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'MEMBERRECHARGE_CSS' => __ROOT__ . '/addon/memberrecharge/shop/view/public/css',
            'MEMBERRECHARGE_JS' => __ROOT__ . '/addon/memberrecharge/shop/view/public/js',
            'MEMBERRECHARGE_IMG' => __ROOT__ . '/addon/memberrecharge/shop/view/public/img',
        ];
        parent::__construct($app);
    }

    //套餐字段
    protected $field = 'recharge_id,recharge_name,cover_img,face_value,buy_price,point,growth,coupon_id,sale_num,create_time,status';

    //开卡字段
    protected $card_field = 'card_id,recharge_id,card_account,cover_img,face_value,point,growth,coupon_id,buy_price,member_img,nickname,order_id,order_no,from_type,use_status,create_time,use_time';

    //订单字段
    protected $order_field = 'a.order_id,a.recharge_name,a.recharge_id,a.order_no,a.out_trade_no,a.member_id,a.cover_img,a.face_value,a.buy_price,a.point,a.growth,a.coupon_id,a.price,a.pay_type,a.pay_type_name,a.status,a.create_time,a.pay_time,a.member_img,a.nickname,a.order_from_name,a.order_from,IFNULL(s.store_name,"") as store_name';

    //优惠券字段
    protected $coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term';

    /**
     * 充值会员套餐列表
     * @return array|mixed
     */
    public function lists()
    {
        $model = new MemberRechargeModel();
        //获取续签信息
        if (request()->isJson()) {
            $status = input('status', '');//套餐状态
            $condition = [];
            if ($status) {
                $condition[] = [ 'status', '=', $status ];
            }
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list = $model->getMemberRechargePageList($condition, $page, $page_size, 'recharge_id desc', $this->field);
            return $list;
        } else {
            $config = $model->getConfig($this->site_id);
            $config = $config[ 'data' ];
            $this->assign("config", $config);
            return $this->fetch('memberrecharge/lists');
        }

    }

    /**
     * 添加充值套餐
     * @return array|mixed
     */
    public function add()
    {
        if (request()->isJson()) {

            $data = [
                'site_id' => $this->site_id,
                'recharge_name' => input('recharge_name', ''),//套餐名称
                'cover_img' => input('cover_img', ''),//封面
                'face_value' => input('face_value', ''),//面值
                'buy_price' => input('buy_price', ''),//价格
                'point' => input('point', ''),//赠送积分
                'growth' => input('growth', ''),//赠送成长值
                'coupon_id' => input('coupon_id', '')//优惠券id
            ];

            $model = new MemberRechargeModel();
            return $model->addMemberRecharge($data);

        } else {
            return $this->fetch('memberrecharge/add');
        }
    }

    /**
     * 编辑充值套餐
     * @return array|mixed
     */
    public function edit()
    {
        $rechargeModel = new MemberRechargeModel();

        $recharge_id = input('recharge_id', '');
        if (request()->isJson()) {

            $data = [
                'recharge_name' => input('recharge_name', ''),//套餐名称
                'cover_img' => input('cover_img', ''),//封面
                'face_value' => input('face_value', ''),//面值
                'buy_price' => input('buy_price', ''),//价格
                'point' => input('point', ''),//赠送积分
                'growth' => input('growth', ''),//赠送成长值
                'coupon_id' => input('coupon_id', '')//优惠券id
            ];

            return $rechargeModel->editMemberRecharge(
                [
                    [ 'recharge_id', '=', $recharge_id ],
                    [ 'site_id', '=', $this->site_id ]
                ], $data);

        } else {
            //获取套餐详情
            $recharge = $rechargeModel->getMemberRechargeInfo(
                [
                    [ 'recharge_id', '=', $recharge_id ],
                    [ 'site_id', '=', $this->site_id ]
                ],
                $this->field
            );
            if (empty($recharge[ 'data' ])) $this->error('未获取到套餐数据', href_url('memberrecharge://shop/memberrecharge/lists'));
            $this->assign('recharge', $recharge);
            return $this->fetch('memberrecharge/edit');
        }

    }

    /**
     * 充值套餐详情
     * @return mixed
     */
    public function detail()
    {
        $recharge_model = new MemberRechargeModel();

        $recharge_id = input('recharge_id', '');

        //获取套餐详情
        $info = $recharge_model->getMemberRechargeInfo(
                [
                    [ 'recharge_id', '=', $recharge_id ],
                    [ 'site_id', '=', $this->site_id ]
                ],
                $this->field
            )[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到套餐数据', href_url('memberrecharge://shop/memberrecharge/lists'));
        $this->assign('info', $info);

        return $this->fetch('memberrecharge/detail');
    }

    /**
     * 停用充值套餐
     * @return array
     */
    public function invalid()
    {
        $model = new MemberRechargeModel();

        $recharge_id = input('recharge_id', '');

        $data = [ 'status' => 2 ];
        $condition = [ [ 'recharge_id', '=', $recharge_id ] ];

        $res = $model->editMemberRecharge($condition, $data);
        return $res;
    }

    /**
     * 开启充值套餐
     * @return array
     */
    public function open()
    {
        $model = new MemberRechargeModel();

        $recharge_id = input('recharge_id', '');

        $data = [ 'status' => 1 ];
        $condition = [ [ 'recharge_id', '=', $recharge_id ] ];

        $res = $model->editMemberRecharge($condition, $data);
        return $res;
    }

    /**
     * 删除充值套餐
     * @return mixed
     */
    public function delete()
    {
        $model = new MemberRechargeModel();

        $recharge_id = input('recharge_id', '');

        return $model->deleteMemberRecharge([ [ 'recharge_id', '=', $recharge_id ] ]);
    }

    /**
     * 开卡列表
     * @return array|mixed
     */
    public function cardLists()
    {
        $recharge_id = input('recharge_id', '');
        $page_size = input('page_size', PAGE_LIST_ROWS);

        $model = new MemberRechargeCardModel();
        $condition[] = [ 'site_id', '=', $this->site_id ];
        $condition[] = [ 'recharge_id', '=', $recharge_id ];
        //获取续签信息
        if (request()->isJson()) {
            $status = input('use_status', '');//使用状态
            if ($status) {
                $condition[] = [ 'use_status', '=', $status ];
            }

            $page = input('page', 1);
            $list = $model->getMemberRechargeCardPageList($condition, $page, $page_size, 'card_id desc', $this->card_field);
            return $list;
        } else {

            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = $model->getMemberRechargeCardPageList($condition, 1, $page_size, 'card_id desc', $this->card_field);
            $this->assign('list', $list);

            $this->assign('recharge_id', $recharge_id);
            return $this->fetch('memberrecharge/card_lists');
        }

    }

    /**
     * 开卡详情
     * @return mixed
     */
    public function cardDetail()
    {
        $model = new MemberRechargeCardModel();

        $card_id = input('card_id', '');

        //获取详情
        $info = $model->getMemberRechargeCardInfo(
                [ [ 'card_id', '=', $card_id ] ],
                $this->card_field
            )[ 'data' ] ?? [];
        $this->assign('info', $info);

        return $this->fetch('memberrecharge/card_detail');
    }

    /**
     * 订单列表
     * @return array|mixed
     */
    public function orderLists()
    {
        $recharge_id = input('recharge_id', 0);
        $model = new MemberRechargeOrderModel();
        //获取续签信息
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $nickname = input('nickname', '');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $order_no = input('order_no', '');
            $store_id = input('store_id', '');

            $condition = [
                [ 'a.status', '=', 2 ],
                [ 'a.site_id', '=', $this->site_id ]
            ];

            if ($recharge_id > 0) {
                $condition[] = [ 'recharge_id', '=', $recharge_id ];
            }

            if ($nickname) {
                $condition[] = [ 'a.nickname', 'like', '%' . $nickname . '%' ];
            }
            if ($order_no) {
                $condition[] = [ 'a.order_no', '=', $order_no ];
            }
            if ($store_id != '') {
                $condition[] = [ 'a.store_id', '=', $store_id ];
            }
            //支付时间
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "a.pay_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "a.pay_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'a.pay_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            $alias = 'a';
            $join[] = [
                'store s',
                's.store_id = a.store_id',
                'left'
            ];
            $order = 'a.create_time desc';
            $list = $model->getMemberRechargeOrderPageList($condition, $page, $page_size, $order, $this->order_field, $alias, $join);
            return $list;
        } else {
            $this->assign('recharge_id', $recharge_id);

            $order_num = $model->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'status', '=', 2 ] ], 'order_id')[ 'data' ];
            $this->assign('order_num', $order_num);

            $order_money = $model->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'status', '=', 2 ] ], 'price')[ 'data' ];
            $this->assign('order_money', $order_money);

            if (addon_is_exit('store') == 1) {
                $store_model = new StoreModel();
                $store_list = $store_model->getStoreList([
                    [ 'site_id', '=', $this->site_id ]
                ], 'store_id,store_name')[ 'data' ];
                $this->assign('store_list', $store_list);
            }

            $this->assign('printer_addon_is_exit',addon_is_exit('printer'));

            return $this->fetch('memberrecharge/order_lists');
        }
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function orderDetail()
    {
        $order_id = input('order_id', '');

        $condition = [
            [ 'a.order_id', '=', $order_id ],
            [ 'a.site_id', '=', $this->site_id ]
        ];

        $alias = 'a';
        $join[] = [
            'store s',
            's.store_id = a.store_id',
            'left'
        ];

        $model = new MemberRechargeOrderModel();
        $info = $model->getMemberRechargeOrderInfo($condition, $this->order_field, $alias, $join)[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到订单数据', href_url('memberrecharge://shop/memberrecharge/order_lists'));
        $this->assign('info', $info);

        return $this->fetch('memberrecharge/order_detail');
    }

    /**
     * 是否开启充值
     * @return mixed
     */
    public function setConfig()
    {
        $model = new MemberRechargeModel();
        $is_use = input('is_use', 0);
        $data = [];
        return $model->setConfig($data, $is_use, $this->site_id);
    }

    public function exportRecharge()
    {
        $recharge_id = input("recharge_id", "");//订单状态

        $condition[] = [ "recharge_id", "=", $recharge_id ];
        $model = new MemberRechargeCardModel();

        $list = $model->getMemberRechargeCardPageList($condition, 1, 0, 'card_id desc', "*");
        if (empty($list[ 'data' ][ 'list' ])) {
            return $this->error("未查询到数据");
        }

        // 实例化excel
        $phpExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $phpExcel->getProperties()->setTitle("充值记录");
        $phpExcel->getProperties()->setSubject("充值记录");
        // 对单元格设置居中效果
        $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //单独添加列名称
        $phpExcel->setActiveSheetIndex(0);
        $phpExcel->getActiveSheet()->setCellValue('A1', '店铺名称');//可以指定位置
        $phpExcel->getActiveSheet()->setCellValue('B1', '充值卡号');
        $phpExcel->getActiveSheet()->setCellValue('C1', '套餐名称');
        $phpExcel->getActiveSheet()->setCellValue('D1', '面值');
        $phpExcel->getActiveSheet()->setCellValue('E1', '积分');
        $phpExcel->getActiveSheet()->setCellValue('F1', '成长值');
        $phpExcel->getActiveSheet()->setCellValue('G1', '购买金额');
        $phpExcel->getActiveSheet()->setCellValue('H1', '会员昵称');
        $phpExcel->getActiveSheet()->setCellValue('I1', '订单编号');
        $phpExcel->getActiveSheet()->setCellValue('J1', '使用状态');
        $phpExcel->getActiveSheet()->setCellValue('K1', '创建时间');
        $phpExcel->getActiveSheet()->setCellValue('L1', '使用时间');
        //循环添加数据（根据自己的逻辑）
        foreach ($list[ 'data' ][ 'list' ] as $k => $v) {
            $i = $k + 2;
            if ($v[ 'use_status' ] == 1) {
                $status_name = "未使用";
            } else {
                $status_name = "已使用";
            }

            $phpExcel->getActiveSheet()->setCellValue('A' . $i, $v[ 'site_name' ]);
            $phpExcel->getActiveSheet()->setCellValue('B' . $i, $v[ 'card_account' ]);
            $phpExcel->getActiveSheet()->setCellValue('C' . $i, $v[ 'recharge_name' ]);
            $phpExcel->getActiveSheet()->setCellValue('D' . $i, $v[ 'face_value' ]);
            $phpExcel->getActiveSheet()->setCellValue('E' . $i, $v[ 'point' ]);
            $phpExcel->getActiveSheet()->setCellValue('F' . $i, $v[ 'growth' ]);
            $phpExcel->getActiveSheet()->setCellValue('G' . $i, $v[ 'buy_price' ]);
            $phpExcel->getActiveSheet()->setCellValue('H' . $i, $v[ 'nickname' ]);
            $phpExcel->getActiveSheet()->setCellValue('I' . $i, ' ' . (string) $v[ 'order_no' ]);
            $phpExcel->getActiveSheet()->setCellValue('J' . $i, $status_name);
            $phpExcel->getActiveSheet()->setCellValue('K' . $i, date('Y-m-d', $v[ 'create_time' ]));
            $phpExcel->getActiveSheet()->setCellValue('L' . $i, date('Y-m-d', $v[ 'use_time' ]));
        }

        // 重命名工作sheet
        $phpExcel->getActiveSheet()->setTitle('充值记录');
        // 设置第一个sheet为工作的sheet
        $phpExcel->setActiveSheetIndex(0);
        // 保存Excel 2007格式文件，保存路径为当前路径，名字为export.xlsx
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpExcel, 'Xlsx');
        $file = date('Y年m月d日-充值记录表', time()) . '.xlsx';
        $objWriter->save($file);

        header("Content-type:application/octet-stream");

        $filename = basename($file);
        header("Content-Disposition:attachment;filename = " . $filename);
        header("Accept-ranges:bytes");
        header("Accept-length:" . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    /**
     * 打印订单小票
     * @return array|mixed|void
     */
    public function printTicket()
    {
        if (addon_is_exit('printer') == 0) return error('', '未安装打印小票插件');

        if (request()->isJson()) {
            $order_id = input('order_id', 0);
            $printer_order_model = new PrinterOrder();
            $recharge_order = ( new MemberrechargeOrder() )->getMemberRechargeOrderInfo([ [ 'relate_type', '=', 'order' ], [ 'order_id', '=', $order_id ] ], 'order_id')[ 'data' ];
            if (empty($recharge_order)) return error('', '未获取到充值订单信息');
            $res = $printer_order_model->printer([
                'order_id' => $recharge_order[ 'order_id' ],
                'type' => 'recharge',
            ]);
            return $res;
        }
    }

}