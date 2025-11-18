<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecommend\shop\controller;

use app\shop\controller\BaseShop;
use addon\memberrecommend\model\MemberRecommend as MemberRecommendModel;
use addon\coupon\model\CouponType;

/**
 * 邀请奖励控制器
 */
class Memberrecommend extends BaseShop
{
    /**
     * 活动列表
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $recommend_name = input('recommend_name', '');
            $status = input('status', '');
            $condition = [];
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $condition[] = [ 'recommend_name', 'like', '%' . $recommend_name . '%' ];
            if ($status != null) {
                $condition[] = [ 'status', '=', $status ];
            }
            $order = 'create_time desc';
            $field = 'recommend_id,recommend_name,start_time,end_time,create_time,status';
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            if ($start_time && !$end_time) {
                $condition[] = [ 'end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (start_time <= {$start_timestamp} and end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            $memberRecommend_model = new MemberRecommendModel();
            $res = $memberRecommend_model->getRecommendPageList($condition, $page, $page_size, $order, $field);

            //获取状态名称
            $recommend_status_arr = $memberRecommend_model->getStatus();
            foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
                $res[ 'data' ][ 'list' ][ $key ][ 'status_name' ] = $recommend_status_arr[ $val[ 'status' ] ];
                //统计查询邀请人数
                $item_condition = array (
                    [ 'recommend_id', '=', $val[ 'recommend_id' ] ],
                    [ 'site_id', '=', $this->site_id ]
                );
                $res[ 'data' ][ 'list' ][ $key ][ 'count' ] = $memberRecommend_model->getRecommendAwardCount($item_condition)[ 'data' ] ?? 0;
            }
            return $res;

        } else {
            //状态
            $memberRecommend_model = new MemberRecommendModel();
            $recommend_status_arr = $memberRecommend_model->getStatus();
            $this->assign('recommend_status_arr', $recommend_status_arr);

            return $this->fetch("recommend/lists");
        }
    }

    /**
     * 活动添加
     */
    public function add()
    {
        if (request()->isJson()) {
            $data = [
                'site_id' => $this->site_id,
                'recommend_name' => input('recommend_name', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'point' => input('point', ''),
                'balance' => input('balance', ''),
                'coupon' => input('coupon', ''),
                'max_point' => input('max_point', ''),
                'max_balance' => input('max_balance', ''),
                'max_coupon' => input('max_coupon', ''),
                'remark' => input('remark', ''),
                'type' => input('type', ''),
                'max_fetch' => input('max_fetch', 0)
            ];

            $memberRecommend_model = new MemberRecommendModel();
            return $memberRecommend_model->addRecommend($data);
        } else {
            return $this->fetch("recommend/add");
        }
    }

    /**
     * 活动编辑
     */
    public function edit()
    {
        $memberRecommend_model = new MemberRecommendModel();
        if (request()->isJson()) {

            $data = [
                'recommend_id' => input('recommend_id', 0),
                'site_id' => $this->site_id,
                'recommend_name' => input('recommend_name', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'point' => input('point', ''),
                'balance' => input('balance', ''),
                'coupon' => input('coupon', ''),
                'max_point' => input('max_point', ''),
                'max_balance' => input('max_balance', ''),
                'max_coupon' => input('max_coupon', ''),
                'remark' => input('remark', ''),
                'type' => input('type', ''),
                'max_fetch' => input('max_fetch', 0)
            ];
            return $memberRecommend_model->editRecommend($data);
        } else {

            $recommend_id = input('recommend_id', 0);
            $this->assign('recommend_id', $recommend_id);

            $recommend_info = $memberRecommend_model->getRecommendDetail($recommend_id, $this->site_id);
            if (empty($recommend_info[ 'data' ])) $this->error('未获取到活动数据', href_url('memberrecommend://shop/memberrecommend/lists'));
            $this->assign('recommend_info', $recommend_info[ 'data' ]);

            return $this->fetch("recommend/edit");
        }
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $recommend_id = input('recommend_id', 0);
        $memberRecommend_model = new MemberRecommendModel();
        $recommend_info = $memberRecommend_model->getRecommendDetail($recommend_id, $this->site_id)[ 'data' ] ?? [];
        if (empty($recommend_info)) $this->error('未获取到活动数据', href_url('memberrecommend://shop/memberrecommend/lists'));

        //获取状态名称
        $recommend_status_arr = $memberRecommend_model->getStatus();
        $recommend_info[ 'status_name' ] = $recommend_status_arr[ $recommend_info[ 'status' ] ];

        $this->assign('info', $recommend_info);

        //获取优惠券列表
        $coupon_model = new CouponType();
        $condition = [
            [ 'status', '=', 1 ],
            [ 'site_id', '=', $this->site_id ],
        ];
        //优惠券字段
        $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';
        $coupon_list = $coupon_model->getCouponTypeList($condition, $coupon_field);
        $this->assign('coupon_list', $coupon_list);
        return $this->fetch('recommend/detail');

    }

    /**
     * 活动关闭
     */
    public function close()
    {
        if (request()->isJson()) {
            $recommend_id = input('recommend_id', 0);
            $memberRecommend_model = new MemberRecommendModel();
            return $memberRecommend_model->closeRecommend($recommend_id, $this->site_id);
        }
    }

    /**
     * 活动删除
     */
    public function delete()
    {
        if (request()->isJson()) {
            $recommend_id = input('recommend_id', 0);
            $memberRecommend_model = new MemberRecommendModel();
            return $memberRecommend_model->deleteRecommend($recommend_id, $this->site_id);
        }
    }

    /**
     * 优惠券领取记录
     * */
    public function receive()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $recommend_id = input('recommend_id', 0);
            $recommend_name = input("recommend_name", "");
            $condition = [];
            $condition[] = [ 'recommend_id', '=', $recommend_id ];
            $condition[] = [ 'site_id', '=', $this->site_id ];

            if (!empty($recommend_name)) {
                $condition[] = [ 'member_nickname', 'like', '%' . $recommend_name . '%' ];
            }

            $memberRecommend_model = new MemberRecommendModel();
            $res = $memberRecommend_model->getRecommendAwardPageList($condition, $page, $page_size);
            return $res;
        } else {
            $recommend_id = input('recommend_id', 0);
            $this->assign('recommend_id', $recommend_id);
            return $this->fetch("recommend/receive");
        }
    }
}