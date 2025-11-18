<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\shop\controller;

use app\shop\controller\BaseShop;
use addon\jielong\model\Jielong as JielongModel;
use addon\jielong\model\Poster;

class Jielong extends BaseShop
{

    /*
     *  接龙活动列表
     */
    public function lists()
    {
        $model = new JielongModel();
        $condition = [
            [ 'is_delete', '=', 0 ],
            [ 'site_id', '=', $this->site_id ]
        ];

        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', '');//活动状态
            if ($status !== '') {
                $condition[] = [ 'status', '=', $status ];
            }
            //商品名称
            $jielong_name = input('jielong_name', '');
            if ($jielong_name) {
                $condition[] = [ 'jielong_name', 'like', '%' . $jielong_name . '%' ];
            }
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

            $list = $model->getJielongPageList($condition, $page, $page_size, 'create_time desc', $this->site_id);
            return $list;
        } else {
            $status_list = $model->getJielongStatus()[ 'data' ] ?? [];

            $this->assign('jielong_status', $status_list);
            return $this->fetch("jielong/lists");
        }
    }

    /**
     * 添加接龙活动
     */
    public function add()
    {
        if (request()->isJson()) {

            $common_data = [
                'site_id' => $this->site_id,
                'jielong_name' => input('jielong_name', ''),//活动名称
                'start_time' => strtotime(input('start_time', '')),//活动开始时间
                'end_time' => strtotime(input('end_time', '')),//活动结束时间
                'take_start_time' => strtotime(input('take_start_time', '')),//自提开始时间
                'take_end_time' => strtotime(input('take_end_time', '')),//自提结束时间
                'desc' => input('desc', ''),//活动说明
            ];

            $goods = [
                'goods_ids' => input('goods_ids', '')
            ];

            $model = new JielongModel();
            return $model->addJielong($common_data, $goods);
        } else {
            return $this->fetch("jielong/add");
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $jielong_model = new JielongModel();

        $jielong_id = input('jielong_id', '');
        if (request()->isJson()) {

            $common_data = [
                'jielong_id' => $jielong_id,
                'site_id' => $this->site_id,
                'jielong_name' => input('jielong_name', ''),//活动名称
                'start_time' => strtotime(input('start_time', '')),//活动开始时间
                'end_time' => strtotime(input('end_time', '')),//活动结束时间
                'desc' => input('desc', ''),//活动说明
                'take_start_time' => strtotime(input('take_start_time', '')),//自提开始时间
                'take_end_time' => strtotime(input('take_end_time', '')),//自提结束时间
            ];

            $sku_list = input('sku_list', '');
            $goods = input('goods_ids', []);

            return $jielong_model->editJielong($common_data, $goods, $sku_list);
        } else {

            //获取接龙信息
            $jielong_info = $jielong_model->getJielongDetail($jielong_id, $this->site_id);
            $this->assign('jielong_info', $jielong_info[ 'data' ]);

            return $this->fetch("jielong/edit");
        }
    }

    /*
     *  接龙详情
     */
    public function detail()
    {
        $jielong_model = new JielongModel();
        $jielong_id = input('jielong_id', '');
        $jielong_info = $jielong_model->getJielongDetail($jielong_id, $this->site_id)[ 'data' ] ?? [];
        $this->assign('info', $jielong_info);
        return $this->fetch("jielong/detail");
    }

    /*
     *  删除接龙活动
     */
    public function delete()
    {
        $jielong_id = input('jielong_id', '');
        $jielong_model = new JielongModel();
        return $jielong_model->deleteJielong($jielong_id, $this->site_id);
    }

    /*
     *  结束接龙活动
     */
    public function finish()
    {
        $jielong_id = input('jielong_id', '');
        $model = new JielongModel();
        return $model->finishJielong($jielong_id, $this->site_id);
    }

    /**
     * 接龙活动海报
     */
    public function poster()
    {
        $qrcode_param[ 'jielong_id' ] = input('jielong_id', '');
        $app_type = input('app_type', '');

        $poster = new Poster();
        $res = $poster->getSolitaireQrcode('/pages_promotion/jielong/jielong', $qrcode_param, 'jielong', $app_type, $this->site_id);
//        $res = $poster->goodsShop('', '/promotionpages/jielong/jielong', $qrcode_param, 'jielong', $this->site_id);
        return $res;
    }

    /*
     *  删除接龙活动
     */
    public function deleteAll()
    {
        if (request()->isJson()) {
            $jielong_id = input('jielong_id', '');
            $jielong_model = new JielongModel();
            foreach ($jielong_id as $k => $v){
                $res = $jielong_model->deleteJielong($v, $this->site_id);
            }
            return $res;
        }
    }

    /*
     *  结束接龙活动
     */
    public function finishAll()
    {
        if (request()->isJson()) {
            $jielong_id = input('jielong_id', '');
            $model = new JielongModel();
            foreach ($jielong_id as $k => $v){
                $res = $model->finishJielong($v, $this->site_id);
            }
            return $res;
        }
    }

}