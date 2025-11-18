<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\coupon\storeapi\controller;

use addon\coupon\dict\CouponDict;
use addon\coupon\model\Coupon as CouponModel;
use addon\coupon\model\CouponType;
use addon\coupon\model\CouponType as CouponTypeModel;
use addon\coupon\model\MemberCoupon;
use app\storeapi\controller\BaseStoreApi;
use app\model\goods\GoodsCategory as GoodsCategoryModel;

class Coupon extends BaseStoreApi
{
    /**
     * 添加活动
     */
    public function add()
    {
        $data = [
            'site_id' => $this->site_id,
            'coupon_name' => $this->params[ 'coupon_name' ] ?? '',//优惠券名称
            'type' => $this->params[ 'type' ],//优惠券类型
            'goods_type' => $this->params[ 'goods_type' ] ?? 1,
            'goods_ids' => $this->params[ 'goods_ids' ] ?? '',
            'goods_names' => $this->params[ 'goods_names' ] ?? '',
            'sort' => $this->params[ 'sort' ] ?? 0, //优惠券排序
            'money' => $this->params[ 'money' ] ?? 0,//优惠券面额
            'discount' => $this->params[ 'discount' ] ?? 0,//优惠券折扣
            'discount_limit' => $this->params[ 'discount_limit' ] ?? 0,//最多优惠
            'count' => $this->params[ 'count' ] ?? '',//发放数量
            'max_fetch' => $this->params[ 'max_fetch' ] ?? '',//最大领取数量
            'at_least' => $this->params[ 'at_least' ] ?? '',//满多少元可以使用
            'end_time' => strtotime($this->params[ 'end_time' ] ?? ''),//活动结束时间
            'image' => $this->params[ 'image' ] ?? '',//优惠券图片
            'validity_type' => $this->params[ 'validity_type' ] ?? '',//有效期类型 0固定时间 1领取之日起
            'fixed_term' => $this->params[ 'fixed_term' ] ?? '',//领取之日起N天内有效
            'is_show' => $this->params[ 'is_show' ] ?? 0,//是否允许直接领取 1:是 0：否 允许直接领取，用户才可以在手机端和PC端进行领取，否则只能以活动的形式发放。
            'use_channel' => $this->params[ 'use_channel' ] ?? 0,//适用渠道 all  online  offline
            'use_store' => ',' . $this->store_id . ',',//适用门店 all  门店id组
            'store_id' => $this->store_id,
        ];
        $coupon_type_model = new CouponTypeModel();
        return $this->response($coupon_type_model->addCouponType($data));
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $coupon_type_model = new CouponTypeModel();

        $data = [
            'site_id' => $this->site_id,
            'coupon_name' => $this->params[ 'coupon_name' ] ?? '',//优惠券名称
            'type' => $this->params[ 'type' ],//优惠券类型
            'goods_type' => $this->params[ 'goods_type' ] ?? 1,
            'goods_ids' => $this->params[ 'goods_ids' ] ?? '',
            'goods_names' => $this->params[ 'goods_names' ] ?? '',
            'money' => $this->params[ 'money' ] ?? 0,//优惠券面额
            'sort' => $this->params[ 'sort' ] ?? 0,//优惠券面额
            'discount' => $this->params[ 'discount' ] ?? 0,//优惠券折扣
            'discount_limit' => $this->params[ 'discount_limit' ] ?? 0,//最多优惠
            'count' => $this->params[ 'count' ] ?? '',//发放数量
            'max_fetch' => $this->params[ 'max_fetch' ] ?? '',//最大领取数量
            'at_least' => $this->params[ 'at_least' ] ?? '',//满多少元可以使用
            'end_time' => strtotime($this->params[ 'end_time' ] ?? ''),//活动结束时间
            'image' => $this->params[ 'image' ] ?? '',//优惠券图片
            'validity_type' => $this->params[ 'validity_type' ] ?? '',//有效期类型 0固定时间 1领取之日起
            'fixed_term' => $this->params[ 'fixed_term' ] ?? '',//领取之日起N天内有效
            'is_show' => $this->params[ 'is_show' ] ?? 0,//是否允许直接领取 1:是 0：否 允许直接领取，用户才可以在手机端和PC端进行领取，否则只能以活动的形式发放。
            'use_store' => ',' . $this->store_id . ',',//适用门店 all  门店id组
            'use_channel' => $this->params[ 'use_channel' ] ?? 0,//适用渠道 all  online  offline
        ];
        $coupon_type_id = $this->params[ 'coupon_type_id' ] ?? 0;
        $condition = [
            //仅包含本门店的
            ['store_id', '=', $this->store_id]
        ];
        return $this->response($coupon_type_model->editCouponType($data, $coupon_type_id, $condition));

    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $coupon_type_id = $this->params[ 'coupon_type_id' ] ?? 0;
        $coupon_type_model = new CouponTypeModel();
        $coupon_type_info = $coupon_type_model->getCouponTypeInfo($coupon_type_id, $this->site_id)[ 'data' ][0] ?? null;
        if (empty($coupon_type_info)) return $this->response($this->error('未获取到优惠券数据！'));

        //分类选择返回真实选中的数据（过滤掉部分选中的）
        if(in_array($coupon_type_info['goods_type'], [CouponDict::category_selected, CouponDict::category_selected_out])){
            $goods_category_model = new GoodsCategoryModel();
            $goods_ids_real = $goods_category_model->getGoodsCategoryLeafIds($coupon_type_info['goods_ids'])['data'];
            $coupon_type_info['goods_ids_real'] = join(',', $goods_ids_real);
        }else{
            $coupon_type_info['goods_ids_real'] = $coupon_type_info['goods_ids'];
        }

        return $this->response($this->success([
            'info' => $coupon_type_info,
            'get_type' => (new CouponModel())->getCouponGetType()
        ]));
    }

    /**
     * 活动列表
     */
    public function lists()
    {
        $coupon_type_model = new CouponTypeModel();

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $coupon_name = $this->params[ 'coupon_name' ] ?? '';
        $status = $this->params[ 'status' ] ?? '';
        $use_channel = $this->params['use_channel'] ?? '';

        $is_show = $this->params[ 'is_show' ] ?? ''; // 是否显示

        $condition = [
            ['use_store', 'like', ['%,' . $this->store_id . ',%', '%all%'], 'or'],
            ['site_id', '=', $this->site_id]
        ];
        if ($status !== '') {
            $condition[] = ['status', '=', $status];
        }

        $type = $this->params[ 'type' ];
        if ($type) {
            $condition[] = ['type', '=', $type];
        }

        if ($is_show !== '') {
            $condition[] = ['is_show', '=', $is_show];
        }

        //类型
        $validity_type = $this->params[ 'validity_type' ] ?? '';
        if ($validity_type !== '') {
            $start_time = $this->params[ 'start_time' ] ?? '';
            $end_time = $this->params[ 'end_time' ] ?? '';
            switch ($validity_type) {
                case 0: //固定
                    $condition[] = ['end_time', 'between', [$start_time, $end_time]];
                    break;
                case 1:
                    $condition[] = ['fixed_term', 'between', [$start_time, $end_time]];
                    break;
                case 2:
                    $condition[] = ['validity_type', '=', 2];
                    break;
            }
        }
        if (!empty($coupon_name)) {
            $condition[] = ['coupon_name', 'like', '%' . $coupon_name . '%'];
        }
        if($use_channel){
            $condition[] = ['use_channel', '=', $use_channel];
        }

        $field = '*';

        //排序
        $link_sort = $this->params[ 'order' ] ?? 'create_time';
        $sort = $this->params[ 'sort' ] ?? 'desc';
        if ($link_sort == 'sort') {
            $order_by = $link_sort . ' ' . $sort;
        } else {
            $order_by = $link_sort . ' ' . $sort . ',sort desc';
        }
        $res = $coupon_type_model->getCouponTypePageList($condition, $page, $page_size, $order_by, $field);

        //获取优惠券状态
        $coupon_type_status_arr = $coupon_type_model->getCouponTypeStatus();
        foreach ($res[ 'data' ][ 'list' ] as &$val) {
            $val[ 'status_name' ] = $coupon_type_status_arr[ $val[ 'status' ] ];
            $val = $coupon_type_model->getCouponSubData($val);
        }

        $res['condition'] = $condition;
        return $this->response($res);

    }

    /**
     * 优惠券状态
     * @return false|string
     */
    public function getStatusList()
    {
        $coupon_type_model = new CouponTypeModel();
        $coupon_type_status_arr = $coupon_type_model->getCouponTypeStatus();
        return $this->response($coupon_type_status_arr);
    }

    /**
     * 查询门店优惠券
     * @return false|string
     */
    public function getStoreCouponTypeList()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $name = $this->params[ 'name' ] ?? '';
        $coupon_model = new CouponModel();
        $condition = [
            ['site_id', '=', $this->site_id],
            ['status', '=', 1],
            ['use_store', 'like', ['%,' . $this->store_id . ',%', '%all%'], 'or'],
            ['use_channel', '<>', 'online'],
        ];
        if ($name !== '') {
            $condition[] = ['coupon_name', 'like', '%' . $name . '%'];
        }
        $field = 'coupon_type_id,type,coupon_name,count,lead_count,used_count,goods_type,is_limit,at_least,money,discount,discount_limit,validity_type,end_time,fixed_term, use_channel, use_store';
        $list = $coupon_model->getCouponTypePageList($condition, $page, $page_size, 'coupon_type_id desc', $field);
        $coupon_type_model = new CouponType();
        foreach ($list[ 'data' ][ 'list' ] as &$val) {
            unset($val[ 'use_store' ]);
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->response($list);
    }

    /**
     * 关闭活动
     */
    public function close()
    {
        $coupon_type_id = $this->params[ 'coupon_type_id' ] ?? 0;
        $coupon_type_model = new CouponTypeModel();
        return $this->response($coupon_type_model->closeCouponType($coupon_type_id, $this->site_id, $this->store_id));
    }

    /**
     * 删除活动
     */
    public function delete()
    {
        $coupon_type_id = $this->params[ 'coupon_type_id' ] ?? 0;
        $coupon_type_model = new CouponTypeModel();
        return $this->response($coupon_type_model->deleteCouponType($coupon_type_id, $this->site_id, $this->store_id));
    }

    /**
     * 优惠券回收
     */
    public function recovery()
    {
        $coupon_list = json_decode($this->params[ 'coupon_list' ] ?? '[]', true);
        return $this->response((new MemberCoupon())->recoveryCoupon($coupon_list, $this->site_id, $this->store_id));

    }
}