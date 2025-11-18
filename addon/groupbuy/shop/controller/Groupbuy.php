<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\shop\controller;

use app\model\goods\Goods as GoodsModel;
use app\shop\controller\BaseShop;
use addon\groupbuy\model\Groupbuy as GroupbuyModel;

/**
 * 团购控制器
 */
class Groupbuy extends BaseShop
{

    /*
     *  团购活动列表
     */
    public function lists()
    {
        $model = new GroupbuyModel();
        //获取续签信息
        if (request()->isJson()) {

            $condition = [
                [ 'pg.site_id', '=', $this->site_id ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];

            $goods_name = input('goods_name', '');
            $status = input('status', '');//团购状态
            if ($status) {
                $condition[] = [ 'pg.status', '=', $status ];
            }
            if (!empty($goods_name)) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            if ($start_time && !$end_time) {
                $condition[] = [ 'pg.end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'pg.start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "pg.start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or pg.end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (pg.start_time <= {$start_timestamp} and pg.end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            //排序
            $order = input('order', 'create_time');
            $sort = input('sort', 'desc');
            if ($order == 'create_time') {
                $order_by = 'pg.' . $order . ' ' . $sort;
            } else {
                $order_by = 'pg.' . $order . ' ' . $sort . ',pg.create_time desc';
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = $model->getGroupbuyPageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {
            return $this->fetch('groupbuy/lists');
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        if (request()->isJson()) {
            //获取商品信息
            $groupbuy_data = [
                'site_id' => $this->site_id,
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'rule' => input('rule', '')
            ];
            $goods_list = input('goods_list', '');
            $goods_ids = input('goods_ids', '');
            $groupbuy_model = new GroupbuyModel();
            return $groupbuy_model->addGroupbuy($groupbuy_data, $goods_list, $goods_ids);
        } else {
            return $this->fetch('groupbuy/add');
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $groupbuy_id = input('groupbuy_id', '');
        $groupbuy_model = new GroupbuyModel();

        if (request()->isJson()) {
            //获取商品信息
            $goods_id = input('goods_id', '');
            $goods_model = new GoodsModel();
            $goods = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $goods_id ] ], 'goods_name,goods_image,price');
            $goods_info = $goods[ 'data' ];
            $groupbuy_data = [
                'goods_id' => $goods_id,
                'goods_name' => $goods_info[ 'goods_name' ],
                'goods_image' => $goods_info[ 'goods_image' ],
                'goods_price' => $goods_info[ 'price' ],
                'sku_id' => input('sku_id', ''),
                'groupbuy_price' => input('groupbuy_price', ''),
                'buy_num' => input('buy_num', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'rule' => input('rule', '')
            ];
            return $groupbuy_model->editGroupbuy($groupbuy_id, $this->site_id, $groupbuy_data);
        } else {
            //获取团购信息
            $condition = [
                [ 'pg.groupbuy_id', '=', $groupbuy_id ],
                [ 'pg.site_id', '=', $this->site_id ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];
            $groupbuy_info = $groupbuy_model->getGroupbuyInfo($condition)[ 'data' ];
            if (empty($groupbuy_info)) $this->error('未获取到活动数据', href_url('groupbuy://shop/groupbuy/lists'));
            $this->assign('groupbuy_info', $groupbuy_info);
            return $this->fetch('groupbuy/edit');
        }
    }

    /*
     *  删除团购活动
     */
    public function delete()
    {
        $groupbuy_id = input('groupbuy_id', '');
        $groupbuy_model = new GroupbuyModel();
        return $groupbuy_model->deleteGroupbuy($groupbuy_id, $this->site_id);
    }

    /*
     *  结束团购活动
     */
    public function finish()
    {
        $groupbuy_id = input('groupbuy_id', '');
        $groupbuy_model = new GroupbuyModel();
        return $groupbuy_model->finishGroupbuy($groupbuy_id, $this->site_id);
    }

    /*
     *  删除团购活动(批量)
     */
    public function deleteAll()
    {
        if (request()->isJson()) {
            $groupbuy_id = input('groupbuy_id', '');
            $groupbuy_model = new GroupbuyModel();
            foreach ($groupbuy_id as $k => $v){
                $res = $groupbuy_model->deleteGroupbuy($v, $this->site_id);
            }
            return $res;
        }
    }

    /*
     *  结束团购活动(批量)
     */
    public function finishAll()
    {
        if (request()->isJson()) {
            $groupbuy_id = input('groupbuy_id', '');
            $groupbuy_model = new GroupbuyModel();
            foreach ($groupbuy_id as $k => $v){
                $res = $groupbuy_model->finishGroupbuy($v, $this->site_id);
            }
            return $res;
        }
    }

    /**
     * 团购推广
     */
    public function groupbuyUrl()
    {
        $groupbuy_id = input('groupbuy_id', '');
        $app_type = input('app_type', 'all');
        $groupbuy_model = new GroupbuyModel();
        $res = $groupbuy_model->urlQrcode('/pages_promotion/groupbuy/detail', [ 'id' => $groupbuy_id ], 'groupbuy', $app_type, $this->site_id);
        return $res;
    }


}