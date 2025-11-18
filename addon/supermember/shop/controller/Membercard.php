<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supermember\shop\controller;

use addon\coupon\model\CouponType;
use addon\supermember\model\Config;
use addon\supermember\model\MemberLevelOrder;
use app\model\member\Member;
use app\model\member\MemberLevel;
use app\shop\controller\BaseShop;
use Carbon\Carbon;
use think\App;

/**
 * 会员卡
 */
class Membercard extends BaseShop
{

    public function __construct(App $app = null)
    {
        $this->replace = [
            'SUPERMEMBER_IMG' => __ROOT__ . '/addon/supermember/shop/view/public/img',
            'SUPERMEMBER_CSS' => __ROOT__ . '/addon/supermember/shop/view/public/css',
            'SUPERMEMBER_JS' => __ROOT__ . '/addon/supermember/shop/view/public/js',
        ];
        parent::__construct($app);
    }

    /**
     * 概况
     * @return mixed
     */
    public function index()
    {
        return $this->fetch('membercard/index');
    }

    /**
     * 销售数据统计
     */
    public function salesStatistics()
    {
        if (request()->isJson()) {
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            $order = new MemberLevelOrder();

            $data = [
                'sale_num' => $order->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ strtotime($start_time), strtotime($end_time) ] ] ])[ 'data' ],
                'sale_money' => $order->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'pay_type', '<>', 'BALANCE' ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ strtotime($start_time), strtotime($end_time) ] ] ], 'order_money')[ 'data' ],
            ];

            return success(0, '', $data);
        }
    }

    /**
     * 会员卡列表
     * @return mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $charge_type = input('charge_type', '');
            $status = input('status', '');

            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'level_type', '=', 1 ]
            ];
            if (!empty($search_text)) $condition[] = [ 'level_name', 'like', "%" . $search_text . "%" ];
            if ($charge_type != '') $condition[] = [ 'charge_type', '=', $charge_type ];
            if ($status != '') $condition[] = [ 'status', '=', $status ];
            $order = 'growth asc,level_id desc';
            $field = '*';

            $member_level_model = new MemberLevel();
            $list = $member_level_model->getMemberLevelPageList($condition, $page, $page_size, $order, $field);
            if (!empty($list[ 'data' ][ 'list' ])) {
                $member_level_array = $member_level_model->getMemberCountGroupByLevel();
                foreach ($list[ 'data' ][ 'list' ] as $k => $item) {

                    $list[ 'data' ][ 'list' ][ $k ][ 'member_num' ] = $member_level_array[ $item[ 'level_id' ] ][ 'count' ] ?? 0;
                }
            }
            return $list;
        } else {
            return $this->fetch('membercard/lists');
        }
    }

    /**
     * 添加会员卡
     * @return mixed
     */
    public function add()
    {
        $member_level_model = new MemberLevel();
        if (request()->isJson()) {
            $data = [
                'site_id' => $this->site_id,
                'level_name' => input('level_name', ''),
                'growth' => input('growth', 0),
                'remark' => input('remark', ''),
                'is_free_shipping' => input('is_free_shipping', 0),
                'consume_discount' => input('consume_discount', 100),
                'point_feedback' => input('point_feedback', 0),
                'send_point' => input('send_point', 0),
                'send_balance' => input('send_balance', 0),
                'send_coupon' => input('send_coupon', ''),
                'charge_rule' => input('charge_rule', ''),
                'charge_type' => input('charge_type', 0),
                'level_type' => 1,
                'bg_color' => input('bg_color', '#333333'),
                'level_text_color' => input('level_text_color', '#ffffff'),
                'level_picture' => input('level_picture', ''),
            ];
            $this->addLog("添加会员卡:" . $data[ 'level_name' ]);
            $res = $member_level_model->addMemberLevel($data);
            return $res;
        } else {
            $this->assign('level_time', $member_level_model->level_time);
            return $this->fetch('membercard/add');
        }
    }

    /**
     * 编辑会员卡
     * @return mixed
     */
    public function edit()
    {
        $member_level_model = new MemberLevel();
        if (request()->isJson()) {
            $data = [
                'level_name' => input('level_name', ''),
                'remark' => input('remark', ''),
                'is_free_shipping' => input('is_free_shipping', 0),
                'consume_discount' => input('consume_discount', 100),
                'point_feedback' => input('point_feedback', 0),
                'send_point' => input('send_point', 0),
                'send_balance' => input('send_balance', 0),
                'send_coupon' => input('send_coupon', ''),
                'charge_rule' => input('charge_rule', ''),
                'charge_type' => input('charge_type', 0),
                'bg_color' => input('bg_color', '#333333'),
                'level_text_color' => input('level_text_color', '#ffffff'),
                'level_picture' => input('level_picture', ''),
            ];
            $level_id = input('level_id', 0);
            $this->addLog("编辑会员卡:" . $data[ 'level_name' ]);
            if ($data[ 'charge_type' ]) $data[ 'is_recommend' ] = 0;
            return $member_level_model->editMemberLevel($data, [ [ 'level_id', '=', $level_id ], [ 'site_id', '=', $this->site_id ] ]);
        } else {
            $level_id = input('id', 0);
            $level_info = $member_level_model->getMemberLevelInfo([ [ 'level_id', '=', $level_id ], [ 'site_id', '=', $this->site_id ] ]);
            $this->assign('level_info', $level_info[ 'data' ]);

            if (empty($level_info[ 'data' ]))
                $this->error('未获取到会员卡数据', href_url('supermember://shop/membercard/lists'));

            $this->assign('level_time', $member_level_model->level_time);
            return $this->fetch('membercard/edit');
        }
    }

    /**
     * 会员等级删除
     */
    public function delete()
    {
        if (request()->isJson()) {
            $level_id = input('level_id', '');
            $member_level_model = new MemberLevel();
            $this->addLog("会员卡删除id:" . $level_id);
            return $member_level_model->deleteMemberLevel($level_id, $this->site_id);
        }
    }

    /**
     * 会员卡状态变更
     */
    public function status()
    {
        if (request()->isJson()) {
            $member_level_model = new MemberLevel();
            $level_id = input('level_id', '');
            $status = input('status', 0);
            $res = $member_level_model->editMemberLevel([ 'status' => $status ], [ [ 'level_id', '=', $level_id ], [ 'site_id', '=', $this->site_id ] ]);
            return $res;
        }
    }

    /**
     * 推荐
     * @return array
     */
    public function recommend()
    {
        if (request()->isJson()) {
            $member_level_model = new MemberLevel();
            $level_id = input('level_id', '');
            $recommend = input('recommend', 1);
            $member_level_model->editMemberLevel([ 'is_recommend' => 0 ], [ [ 'level_id', '<>', $level_id ], [ 'is_recommend', '=', 1 ], [ 'site_id', '=', $this->site_id ] ]);
            $member_level_model->editMemberLevel([ 'is_recommend' => $recommend ], [ [ 'level_id', '=', $level_id ], [ 'site_id', '=', $this->site_id ] ]);
            return success();
        }
    }

    /**
     * 会员卡订单
     * @return mixed
     */
    public function order()
    {
        $level_order = new MemberLevelOrder();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $order_no = input('order_no', '');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $nickname = input('nickname', '');

            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'order_status', '=', 1 ]
            ];
            if ($order_no) {
                $condition[] = [ 'order_no', 'like', '%' . $order_no . '%' ];
            }
            if ($nickname) {
                $condition[] = [ 'nickname', 'like', '%' . $nickname . '%' ];
            }
            if ($start_time && !$end_time) {
                $condition[] = [ 'pay_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'pay_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'pay_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            $data = $level_order->getLevelOrderPageList($condition, $page, $page_size);
            return $data;
        } else {
            $buyer_num = $level_order->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', 1 ] ], 'order_id', 'a', null, 'buyer_id')[ 'data' ];
            $this->assign('buyer_num', $buyer_num);

            $order_money = $level_order->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', 1 ] ], 'order_money')[ 'data' ];
            $this->assign('order_money', sprintf("%.2f", $order_money));

            return $this->fetch('membercard/order');
        }
    }

    /**
     * 会员卡订单详情
     * @return mixed
     */
    public function orderDetail()
    {
        $order_id = input('order_id', '');
        $level_order = new MemberLevelOrder();
        $condition = [
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ]
        ];
        $order = $level_order->getLevelOrderInfo($condition);
        if (empty($order[ 'data' ]))
            $this->error('未获取到订单数据', href_url('supermember://shop/membercard/order'));

        $this->assign('order', $order);
        return $this->fetch('membercard/order_detail');
    }

    /**
     * 线下支付
     * @return array
     */
    public function offlinepay()
    {
        if (request()->isJson()) {
            $out_trade_no = input('out_trade_no', 0);
            $level_order = new MemberLevelOrder();
            $res = $level_order->offlinePay($out_trade_no);
            return $res;
        }
    }

    /**
     * 开卡协议
     * @return mixed
     */
    public function agreement()
    {
        if (request()->isJson()) {
            //设置注销协议
            $title = input('title', '');
            $content = input('content', '');
            $config_model = new Config();
            return $config_model->setMemberCardDocument($title, $content, $this->site_id, 'shop');
        } else {
            //获取注销协议
            $config_model = new Config();
            $document_info = $config_model->getMemberCardDocument($this->site_id, 'shop');
            $this->assign('document_info', $document_info);
            return $this->fetch('membercard/agreement');
        }
    }

    /**
     * 会员卡统计
     */
    public function stat()
    {
        $order = new MemberLevelOrder();
        $yesterday = Carbon::yesterday();

        $data = [
            'yesterday_num' => $order->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ date_to_time("{$yesterday->year}-{$yesterday->month}-{$yesterday->day} 00:00:00"), date_to_time("{$yesterday->year}-{$yesterday->month}-{$yesterday->day} 23:59:59") ] ] ])[ 'data' ],
            'today_num' => $order->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), time() ] ] ])[ 'data' ],
            'total_num' => $order->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ] ])[ 'data' ],
            'yesterday_money' => $order->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'pay_type', '<>', 'BALANCE' ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ date_to_time("{$yesterday->year}-{$yesterday->month}-{$yesterday->day} 00:00:00"), date_to_time("{$yesterday->year}-{$yesterday->month}-{$yesterday->day} 23:59:59") ] ] ], 'order_money')[ 'data' ],
            'today_money' => $order->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'pay_type', '<>', 'BALANCE' ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ], [ 'pay_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), time() ] ] ], 'order_money')[ 'data' ],
            'total_money' => $order->getOrderSum([ [ 'site_id', '=', $this->site_id ], [ 'pay_type', '<>', 'BALANCE' ], [ 'order_status', '=', MemberLevelOrder::ORDER_PAY ] ], 'order_money')[ 'data' ],
            'has_card_member' => ( new Member() )->getMemberCount([ [ 'site_id', '=', $this->site_id ], [ 'member_level_type', '=', 1 ], [ 'is_delete', '=', 0 ] ])[ 'data' ], // 持有超级会员卡的人数
            'no_has_card_member' => ( new Member() )->getMemberCount([ [ 'site_id', '=', $this->site_id ], [ 'member_level_type', '=', 0 ], [ 'is_delete', '=', 0 ] ])[ 'data' ], // 未持有超级会员卡的人数
            'card_list' => []
        ];

        $member_level_model = new MemberLevel();
        $card_list = $member_level_model->getMemberLevelList([ [ 'site_id', '=', $this->site_id ], [ 'level_type', '=', 1 ] ], 'level_name,level_id');
        if (!empty($card_list[ 'data' ])) {
            $member_level_array = $member_level_model->getMemberCountGroupByLevel();
            foreach ($card_list[ 'data' ] as $k => $item) {

                $card_list[ 'data' ][ $k ][ 'member_num' ] = $member_level_array[ $item[ 'level_id' ] ][ 'count' ] ?? 0;
            }
            array_multisort(array_column($card_list[ 'data' ], 'member_num'), SORT_DESC, $card_list[ 'data' ]);
            $data[ 'card_list' ] = $card_list[ 'data' ];
        }

        return $data;
    }
}