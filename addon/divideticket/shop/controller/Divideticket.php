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

namespace addon\divideticket\shop\controller;

use app\shop\controller\BaseShop;
use addon\divideticket\model\Divideticket as DivideticketModel;
use addon\divideticket\model\DivideticketFriendsGroup;

/**
 * 好友瓜分券
 * Class DivideTicket
 * @package addon\divideticket\shop\controller
 */
class Divideticket extends BaseShop
{
    /**
     * 活动列表
     * @return array|mixed
     */
    public function lists()
    {
        $divideticket_model = new DivideticketModel();

        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $name = input('name', '');
            $status = input('status', '');

            $condition = [];
            if ($status !== "") {
                $condition[] = [ 'status', '=', $status ];
            }
            //类型
            $validity_type = input('validity_type', '');
            if ($validity_type) {
                $validity_start_time = input('validity_start_time', '');
                $validity_end_time = input('validity_end_time', '');
                switch ( $validity_type ) {
                    case 1: //固定
                        $condition[] = [ 'validity_end_time', 'between', [ $validity_start_time, $validity_end_time ] ];
                        break;
                    case 2:
                        $condition[] = [ 'fixed_term', 'between', [ $validity_start_time, $validity_end_time ] ];
                        break;
                }
            }

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $condition[] = [ 'name', 'like', '%' . $name . '%' ];
            $data = $divideticket_model->getDivideticketPageList($condition, $page, $page_size);
            return $data;
        } else {
            $divideticket_status = $divideticket_model->getDivideticketStatus();
            $this->assign('divideticket_status', $divideticket_status);
            return $this->fetch("divideticket/lists");
        }
    }

    /**
     * 添加活动
     * @return mixed
     */
    public function add()
    {
        if (request()->isJson()) {
            $data = [
                'site_id' => $this->site_id,
                'name' => input('name', ''),//活动名称
                'goods_type' => input('goods_type', 1),
                'goods_ids' => input('goods_ids', ''),
                'money' => input('money', 0),//瓜分金额
                'start_time' => strtotime(input('start_time', '')), // 活动开始时间
                'end_time' => strtotime(input('end_time', '')), // 活动结束时间
                'divide_num' => input('divide_num', 0),//瓜分人数
                'image' => input('image', 0),//优惠券图片
                'inventory' => input('inventory', ''),//库存
                'count' => input('inventory', ''),//发放数量
                'is_limit' => input('at_least', 0) > 0 ? 1 : 0,//使用门槛
                'at_least' => input('at_least', 0),//满多少元可以使用
                'divide_time' => input('divide_time', 1),//瓜分有效期
                'validity_end_time' => strtotime(input('validity_end_time', '')),//有效期结束时间
                'validity_type' => input('validity_type', ''),//有效期类型 0固定时间 1领取之日起
                'fixed_term' => input('fixed_term', ''),//领取之日起N天内有效
                'is_simulation' => input('is_simulation', 0),//是否模拟好友
                'is_new' => input('is_new', 0),//仅新人限制
                'divide_type' => input('divide_type', ''),//瓜分方式
                'create_time' => time(),//创建时间
                'remark' => input('remark', ''),//活动规则
            ];
            $divideticket_model = new DivideticketModel();
            $res = $divideticket_model->addDivideticket($data);
            return $res;
        } else {
            return $this->fetch("divideticket/add");
        }
    }

    /**
     * 编辑活动
     * @return mixed
     */
    public function edit()
    {
        $divideticket_model = new DivideticketModel();
        if (request()->isJson()) {
            $data = [
                'coupon_id' => input('coupon_id', ''),
                'site_id' => $this->site_id,
                'name' => input('name', ''),//活动名称
                'goods_type' => input('goods_type', 1),
                'goods_ids' => input('goods_ids', ''),
                'money' => input('money', 0),//瓜分金额
                'divide_time' => input('divide_time', 1),//瓜分有效期
                'start_time' => strtotime(input('start_time', '')), // 活动开始时间
                'end_time' => strtotime(input('end_time', '')), // 活动结束时间
                'divide_num' => input('divide_num', 0),//瓜分人数
                'image' => input('image', 0),//优惠券图片
                'inventory' => input('inventory', ''),//发放数量
                'is_limit' => input('at_least', 0) > 0 ? 1 : 0,//使用门槛
                'at_least' => input('at_least', 0),//满多少元可以使用
                'validity_end_time' => strtotime(input('validity_end_time', '')),//有效期结束时间
                'validity_type' => input('validity_type', ''),//有效期类型 0固定时间 1领取之日起
                'fixed_term' => input('fixed_term', ''),//领取之日起N天内有效
                'is_simulation' => input('is_simulation', 0),//是否模拟好友
                'is_new' => input('is_new', 0),//仅新人限制
                'divide_type' => input('divide_type', ''),//瓜分方式
                'remark' => input('remark', ''),//活动规则
            ];
            $res = $divideticket_model->editDivideticket($data);
            return $res;
        } else {
            $coupon_id = input('coupon_id', 0);
            $this->assign('coupon_id', $coupon_id);
            $condition = [
                [ 'coupon_id', '=', $coupon_id ],
                [ 'site_id', '=', $this->site_id ],
            ];
            $coupon_info = $divideticket_model->getDivideticketInfo($condition);
            if (empty($coupon_info[ 'data' ])) $this->error('未获取到优惠券数据', href_url('divideticket://shop/divideticket/lists'));
            $this->assign('coupon_info', $coupon_info[ 'data' ]);
            return $this->fetch("divideticket/edit");
        }
    }

    /**
     * 详情
     * @return mixed|void
     */
    public function detail()
    {
        $coupon_id = input('coupon_id', 0);
        $this->assign('coupon_id', $coupon_id);
        $condition = [
            [ 'coupon_id', '=', $coupon_id ],
            [ 'site_id', '=', $this->site_id ],
        ];
        $divideticket_model = new DivideticketModel();
        $info = $divideticket_model->getDivideticketInfo($condition)[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到优惠券数据', href_url('divideticket://shop/divideticket/lists'));
        $info[ 'status_name' ] = $divideticket_model->getDivideticketStatus()[ $info[ 'status' ] ] ?? '';
        $this->assign('info', $info);
        return $this->fetch("divideticket/detail");
    }

    /**
     * 活动推广
     */
    public function spreadDivideticket()
    {
        $coupon_id = input('coupon_id', '');
        $app_type = input('app_type', 'all');
        $divideticket_model = new DivideticketModel();

        $res = $divideticket_model->urlQrcode('/pages_promotion/divideticket/index', [ 'cid' => $coupon_id ], 'divideticket', $app_type, $this->site_id);
        return $res;
    }

    /**
     * 关闭活动
     */
    public function close()
    {
        if (request()->isJson()) {
            $coupon_id = input('coupon_id', 0);
            $data = [
                'coupon_id' => $coupon_id,
                'site_id' => $this->site_id,
            ];
            $divideticket_model = new DivideticketModel();
            return $divideticket_model->closeDividetocket($data);
        }
    }

    /**
     * 删除活动
     */
    public function delete()
    {
        if (request()->isJson()) {
            $coupon_id = input('coupon_id', 0);
            $data = [
                'coupon_id' => $coupon_id,
                'site_id' => $this->site_id,
            ];
            $divideticket_model = new DivideticketModel();
            return $divideticket_model->deleteDividetocket($data);
        }
    }

    /**
     * 运营
     */
    public function operate()
    {
        $coupon_id = input('coupon_id', '0');
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [];
            $condition[] = [ 'a.site_id', '=', $this->site_id ];

            if ($coupon_id) {
                $condition[] = [ 'a.promotion_id', '=', $coupon_id ];
            }

            $alias = 'a';
            $join = [
                [ 'promotion_friends_coupon p', 'a.promotion_id = p.coupon_id', 'left' ],
                [ 'member m', 'a.header_id = m.member_id', 'left' ],
            ];
            $field = 'a.*,p.name,m.username,m.nickname,m.headimg';

            $divideticket_group_model = new DivideticketFriendsGroup();
            $data = $divideticket_group_model->getDivideticketFriendsGroupPageList($condition, $page, $page_size, '', $field, $alias, $join);

            return $data;
        } else {
            $this->assign('coupon_id', $coupon_id);
            return $this->fetch("divideticket/operate");
        }
    }

    /**
     * 邀请人
     */
    public function groupMember()
    {
        $group_id = input('group_id', '0');
        if (request()->isJson()) {
            $divideticket_group_model = new DivideticketFriendsGroup();
            $condition = [];
            $condition[] = [ 'a.group_id', '=', $group_id ];
            $condition[] = [ 'a.site_id', '=', $this->site_id ];
            $field = 'a.*,p.divide_num,p.money';
            $join = [
                [ 'promotion_friends_coupon p', 'a.promotion_id = p.coupon_id', 'left' ],
            ];
            $data = $divideticket_group_model->getDivideticketFriendsGroupInfo($condition, $field, $alias = 'a', $join);
            $member_arr[ 'code' ] = 0;
            $member_arr[ 'data' ][ 'list' ] = $data[ 'data' ][ 'member_list' ] ?? [];
            if ($member_arr[ 'data' ][ 'list' ]) {
                foreach ($member_arr[ 'data' ][ 'list' ] as $k => $v) {
                    $member_arr[ 'data' ][ 'list' ][ $k ][ 'divide_num' ] = $data[ 'data' ][ 'divide_num' ];
                    $member_arr[ 'data' ][ 'list' ][ $k ][ 'money' ] = $data[ 'data' ][ 'money' ];
                }
            }
            return $member_arr;
        } else {
            $this->assign('group_id', $group_id);
            return $this->fetch("divideticket/group_member");
        }
    }
}