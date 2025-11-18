<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\shop\controller;

use addon\bargain\model\Bargain as BargainModel;

class Bargain extends BaseController
{

    /*
     *  砍价商品列表
     */
    public function lists()
    {

        $model = new BargainModel();

        $condition = [
            [ 'pb.site_id', '=', $this->site_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];

        //获取续签信息
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $status = input('status', '');//砍价状态

            if ($status !== '') {
                $condition[] = [ 'pb.status', '=', $status ];
            }
            //商品名称
            $goods_name = input('goods_name', '');
            if ($goods_name) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && !$end_time) {
                $condition[] = [ 'pb.end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'pb.start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "pb.start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or pb.end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (pb.start_time <= {$start_timestamp} and pb.end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            //排序
            $order = input('order', 'create_time');
            $sort = input('sort', 'desc');
            if (!empty($sort)) {
                $order_by = 'pb.' . $order . ' ' . $sort;
            } else {
                $order_by = 'pb.create_time desc';
            }
            $list = $model->getBargainPageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {
            $bargain_status = $model->getBargainStatus();
            $this->assign('bargain_status', $bargain_status[ 'data' ]);

            return $this->fetch('bargain/lists');
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        if (request()->isJson()) {

            $common_data = [
                'site_id' => $this->site_id,
                'bargain_name' => input('bargain_name', ''),
                'is_fenxiao' => input('is_fenxiao', ''),
                'buy_type' => input('buy_type', ''),
                'bargain_type' => input('bargain_type', ''),
                'bargain_num' => input('bargain_num', ''),
                'bargain_time' => input('bargain_time', ''),
                'remark' => input('remark', ''),
                'is_own' => input('is_own', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'is_differ_new_user' => input('is_differ_new_user', 0),
                'distinguish' => input('distinguish', 1),
                'new_low' => input('new_low', ''),
                'aged_tall' => input('aged_tall'),
                'aged_fixation' => input('aged_fixation'),
                'bargain_max_num' => input('bargain_max_num', 0),
                'help_bargain_num' => input('help_bargain_num', 0),
            ];

            $goods = [
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            $sku_list = input('sku_list', '');
            $bargain_model = new BargainModel();
            return $bargain_model->addBargain($common_data, $goods, $sku_list);
        } else {
            $bargain_name = '砍价 ' . date('Y-m-d');
            $this->assign('bargain_name', $bargain_name);
            return $this->fetch('bargain/add');
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $bargain_model = new BargainModel();

        $bargain_id = input('bargain_id', '');
        if (request()->isJson()) {

            $common_data = [
                'bargain_id' => $bargain_id,
                'site_id' => $this->site_id,
                'bargain_name' => input('bargain_name', ''),
                'is_fenxiao' => input('is_fenxiao', ''),
                'buy_type' => input('buy_type', ''),
                'bargain_type' => input('bargain_type', ''),
                'bargain_num' => input('bargain_num', ''),
                'bargain_time' => input('bargain_time', ''),
                'remark' => input('remark', ''),
                'is_own' => input('is_own', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'is_differ_new_user' => input('is_differ_new_user', 0),
                'distinguish' => input('distinguish', 1),
                'new_low' => input('new_low', ''),
                'aged_tall' => input('aged_tall'),
                'aged_fixation' => input('aged_fixation'),
                'bargain_max_num' => input('bargain_max_num', 0),
                'help_bargain_num' => input('help_bargain_num', 0),
            ];
            $sku_list = input('sku_list', '');
            $goods = [
                'goods_id' => input('goods_id', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            return $bargain_model->editBargain($common_data, $goods, $sku_list);
        } else {

            //获取砍价信息
            $condition = [
                [ 'pb.bargain_id', '=', $bargain_id ],
                [ 'pb.site_id', '=', $this->site_id ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];
            $bargain_info = $bargain_model->getBargainInfo($condition);
            if (empty($bargain_info[ 'data' ])) $this->error('未获取到活动数据', href_url('bargain://shop/bargain/lists'));
            $this->assign('bargain_info', $bargain_info[ 'data' ]);
            return $this->fetch('bargain/edit');
        }
    }

    /*
     *  砍价详情
     */
    public function detail()
    {
        $bargain_model = new BargainModel();

        $bargain_id = input('bargain_id', '');
        //获取砍价信息
        $condition = [
            [ 'pb.bargain_id', '=', $bargain_id ],
            [ 'pb.site_id', '=', $this->site_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $bargain_info = $bargain_model->getBargainJoinGoodsList($condition);

        if (empty($bargain_info[ 'data' ])) $this->error('未获取到活动数据', href_url('bargain://shop/bargain/lists'));

        $this->assign('bargain_info', $bargain_info[ 'data' ]);
        return $this->fetch('bargain/detail');
    }

    /*
     *  删除砍价活动
     */
    public function delete()
    {
        $bargain_id = input('bargain_id', '');
        $bargain_model = new BargainModel();
        return $bargain_model->deleteBargain($bargain_id, $this->site_id);
    }

    /*
     *  结束砍价活动
     */
    public function finish()
    {
        $bargain_id = input('bargain_id', '');
        $bargain_model = new BargainModel();
        return $bargain_model->finishBargain($bargain_id, $this->site_id);
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isJson()) {
            $bargain_model = new BargainModel();
            $bargain_id = input('bargain_id', '');
            $bargain_info = $bargain_model->getBargainGoodsList($bargain_id, $this->site_id);
            return $bargain_info;
        }
    }

    /**
     * 砍价列表
     */
    public function launchList()
    {
        $bargain_id = input('bargain_id', '');
        if (request()->isJson()) {
            $goods_name = input('goods_name', '');
            $nickname = input('nickname', '');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $status = input('status', '');//砍价状态
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $bargain_model = new BargainModel();

            $condition = [
                [ 'site_id', '=', $this->site_id ]
            ];
            if ($bargain_id) {
                $condition[] = [ 'bargain_id', '=', $bargain_id ];
            }
            if ($status !== '') {
                $condition[] = [ 'status', '=', $status ];
            }
            if ($goods_name) {
                $condition[] = [ 'sku_name', 'like', '%' . $goods_name . '%' ];
            }
            if ($nickname) {
                $condition[] = [ 'nickname', 'like', '%' . $nickname . '%' ];
            }
            if ($start_time && !$end_time) {
                $condition[] = [ 'start_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'start_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $list = $bargain_model->getBargainLaunchPageListInAdmin($condition, '', 'launch_id desc', $page, $page_size);
            return $list;
        } else {

            $this->assign('bargain_id', $bargain_id);

            return $this->fetch('bargain/launch_list');
        }
    }

    /**
     * 帮砍记录
     * @return mixed
     */
    public function launchDetail()
    {
        $launch_id = input('launch_id', '');
        $bargain_model = new BargainModel();
        if (request()->isJson()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [ [ 'launch_id', '=', $launch_id ] ];
            $list = $bargain_model->getBargainRecordPageList($condition, '', 'id desc', $page, $page_size);
            return $list;
        } else {

            $this->assign('launch_id', $launch_id);
            //获取发起人信息
            $info = $bargain_model->getBargainLaunchDetail([ [ 'launch_id', '=', $launch_id ], [ 'site_id', '=', $this->site_id ] ]);

            if (empty($info[ 'data' ])) $this->error('未获取到活动数据', href_url('bargain://shop/bargain/launchlist'));

            $this->assign('info', $info[ 'data' ]);

            return $this->fetch('bargain/launch_detail');
        }
    }

    /**
     * 砍价推广
     */
    public function bargainUrl()
    {
        $bargain_id = input('bargain_id', '');
        $app_type = input('app_type', 'all');
        $bargain_model = new BargainModel();
        $res = $bargain_model->urlQrcode('/pages_promotion/bargain/detail', [ 'b_id' => $bargain_id ], 'bargain', $app_type, $this->site_id);
        return $res;
    }

    /**
     * 批量删除
     * @return array
     */
    public function deleteAll()
    {
        $bargain_id = input('bargain_id', '');
        $bargain_model = new BargainModel();
        foreach ($bargain_id as $k => $v){
            $res = $bargain_model->deleteBargain($v, $this->site_id);
        }
        return $res;
    }

    /**
     * 批量关闭
     * @return array
     */
    public function finishAll()
    {
        $bargain_id = input('bargain_id', '');
        $bargain_model = new BargainModel();
        foreach ($bargain_id as $k => $v){
            $res = $bargain_model->finishBargain($v, $this->site_id);
        }
        return $res;
    }

}