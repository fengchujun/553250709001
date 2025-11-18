<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\shop\controller;

use app\shop\controller\BaseShop;
use addon\presale\model\Presale as PresaleModel;

class Presale extends BaseShop
{

    /*
     *  预售商品列表
     */
    public function lists()
    {
        $model = new PresaleModel();

        $condition = [
            [ 'p.site_id', '=', $this->site_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];

        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $status = input('status', '');//预售状态

            if ($status !== '') {
                $condition[] = [ 'p.status', '=', $status ];
            }
            //商品名称
            $goods_name = input('goods_name', '');
            if ($goods_name) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && !$end_time) {
                $condition[] = [ 'p.end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'p.start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "p.start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or p.end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (p.start_time <= {$start_timestamp} and p.end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            $order_by = 'p.create_time desc';
            $list = $model->getPresalePageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {
            $presale_status = $model->getPresaleStatus();
            $this->assign('presale_status', $presale_status[ 'data' ]);

            return $this->fetch("presale/lists");
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
                'presale_name' => input('presale_name', ''),//活动名称
                'start_time' => strtotime(input('start_time', '')),//活动时间
                'end_time' => strtotime(input('end_time', '')),//活动时间
                'pay_start_time' => strtotime(input('pay_start_time', '')),//尾款支付时间
                'pay_end_time' => strtotime(input('pay_end_time', '')),//尾款支付时间
                'presale_num' => input('presale_num', ''),//限购
                'deliver_type' => input('deliver_type', ''),//发货方式
                'deliver_time' => input('deliver_time', ''),//发货时间
                'is_fenxiao' => input('is_fenxiao', ''),//是否参与分销
                'is_deposit_back' => input('is_deposit_back', ''),//是否支持退定金
                'deposit_agreement' => input('deposit_agreement', ''),//退定金协议
                'remark' => input('remark', ''),//活动规则说明
            ];
            if ($common_data[ 'deliver_type' ] == 0) {
                $common_data[ 'deliver_time' ] = strtotime($common_data[ 'deliver_time' ]);
            }
            if ($common_data[ 'is_deposit_back' ] == 0) {
                $common_data[ 'deposit_agreement' ] = '';
            }
            $goods = [
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            $sku_list = input('sku_list', '');
            $presale_model = new PresaleModel();
            return $presale_model->addPresale($common_data, $goods, $sku_list);
        } else {
            $presale_name = '预售 ' . date('Y-m-d');
            $this->assign('presale_name', $presale_name);
            return $this->fetch("presale/add");
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $presale_model = new PresaleModel();

        $presale_id = input('presale_id', '');
        if (request()->isJson()) {

            $common_data = [
                'presale_id' => $presale_id,
                'site_id' => $this->site_id,
                'presale_name' => input('presale_name', ''),//活动名称
                'start_time' => strtotime(input('start_time', '')),//活动时间
                'end_time' => strtotime(input('end_time', '')),//活动时间
                'pay_start_time' => strtotime(input('pay_start_time', '')),//尾款支付时间
                'pay_end_time' => strtotime(input('pay_end_time', '')),//尾款支付时间
                'presale_num' => input('presale_num', ''),//限购
                'deliver_type' => input('deliver_type', ''),//发货方式
                'deliver_time' => input('deliver_time', ''),//发货时间
                'is_fenxiao' => input('is_fenxiao', ''),//是否参与分销
                'is_deposit_back' => input('is_deposit_back', ''),//是否支持退定金
                'deposit_agreement' => input('deposit_agreement', ''),//退定金协议
                'remark' => input('remark', ''),//活动规则说明
            ];
            if ($common_data[ 'deliver_type' ] == 0) {
                $common_data[ 'deliver_time' ] = strtotime($common_data[ 'deliver_time' ]);
            }
            if ($common_data[ 'is_deposit_back' ] == 0) {
                $common_data[ 'deposit_agreement' ] = '';
            }

            $sku_list = input('sku_list', '');
            $goods = [
                'goods_id' => input('goods_id', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            return $presale_model->editPresale($common_data, $goods, $sku_list);

        } else {

            //获取预售信息
            $presale_info = $presale_model->getPresaleDetail($presale_id, $this->site_id)[ 'data' ] ?? [];
            if (empty($presale_info)) $this->error('未获取到活动数据', href_url('presale://shop/presale/lists'));
            $this->assign('presale_info', $presale_info);
            return $this->fetch("presale/edit");
        }
    }

    /*
     *  预售详情
     */
    public function detail()
    {
        $presale_model = new PresaleModel();
        $presale_id = input('presale_id', '');
        $presale_info = $presale_model->getPresaleJoinGoodsList($presale_id, $this->site_id)[ 'data' ] ?? [];
        if (empty($presale_info)) $this->error('未获取到活动数据', href_url('presale://shop/presale/lists'));
        $this->assign('info', $presale_info);
        return $this->fetch("presale/detail");
    }

    /*
     *  删除预售活动
     */
    public function delete()
    {
        $presale_id = input('presale_id', '');
        $site_id = $this->site_id;

        $presale_model = new PresaleModel();
        return $presale_model->deletePresale($presale_id, $site_id);
    }

    /*
     *  结束预售活动
     */
    public function finish()
    {
        $presale_id = input('presale_id', '');
        $site_id = $this->site_id;

        $presale_model = new PresaleModel();
        return $presale_model->finishPresale($presale_id, $site_id);
    }


    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isJson()) {
            $presale_model = new PresaleModel();
            $presale_id = input('presale_id', '');
            $presale_info = $presale_model->getPresaleGoodsList($presale_id, $this->site_id);
            return $presale_info;
        }
    }

    /**
     * 预售推广
     */
    public function presaleUrl()
    {
        $presale_id = input('presale_id', '');
        $app_type = input('app_type', 'all');

        $presale_model = new PresaleModel();
        $res = $presale_model->urlQrcode('/pages_promotion/presale/detail', [ 'id' => $presale_id ], 'presale', $app_type, $this->site_id);
        return $res;
    }

    /**
     *  删除预售活动
     */
    public function deleteAll()
    {
        if (request()->isJson()) {
            $presale_id = input('presale_id', '');
            $presale_model = new PresaleModel();
            foreach ($presale_id as $k => $v){
                $res = $presale_model->deletePresale($v, $this->site_id);
            }
            return $res;
        }
    }

    /**
     *  结束预售活动
     */
    public function finishAll()
    {
        if (request()->isJson()) {
            $presale_id = input('presale_id', '');
            $presale_model = new PresaleModel();
            foreach ($presale_id as $k => $v){
                $res = $presale_model->finishPresale($v, $this->site_id);
            }
            return $res;
        }
    }
}