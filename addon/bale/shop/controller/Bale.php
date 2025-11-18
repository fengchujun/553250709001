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

namespace addon\bale\shop\controller;

use app\shop\controller\BaseShop;
use addon\bale\model\Bale as BaleModel;

/**
 * 打包一口价
 * @author Administrator
 *
 */
class Bale extends BaseShop
{
    /**
     * 活动列表
     * @return array|mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', '');
            $name = input('name', '');

            $condition = [
                [ 'site_id', '=', $this->site_id ]
            ];
            if ($status !== '') {
                $condition[] = [ 'status', '=', $status ];
            }
            if (!empty($name)) {
                $condition[] = [ 'name', 'like', '%' . $name . '%' ];
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

            $bale = new BaleModel();
            $list = $bale->getBalePageList($condition, $page, $page_size);
            return $list;
        } else {
            return $this->fetch('bale/lists');
        }
    }

    /**
     * 添加活动
     * @return mixed
     */
    public function add()
    {
        if (request()->isJson()) {
            $bale = new BaleModel();
            $res = $bale->addBale([
                'site_id' => $this->site_id,
                'name' => input('name', ''),
                'num' => input('num', 0),
                'price' => input('price', 0.00),
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
                'start_time' => strtotime(input('start_time', 0)),
                'end_time' => strtotime(input('end_time', 0)),
                'shipping_fee_type' => input('shipping_fee_type', 0),
            ]);
            return $res;
        }
        return $this->fetch('bale/add');
    }

    /**
     * 编辑活动
     * @return mixed
     */
    public function edit()
    {
        $bale = new BaleModel();
        if (request()->isJson()) {
            $res = $bale->editBale([
                'bale_id' => input('bale_id'),
                'site_id' => $this->site_id,
                'name' => input('name', ''),
                'num' => input('num', 0),
                'price' => input('price', 0.00),
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
                'start_time' => strtotime(input('start_time', 0)),
                'end_time' => strtotime(input('end_time', 0)),
                'shipping_fee_type' => input('shipping_fee_type', 0),
            ]);
            return $res;
        }
        $bale_id = input('bale_id', '');
        $info = $bale->getEditBaleData($bale_id, $this->site_id);
        if (empty($info[ 'data' ])) $this->error('未获取到活动信息');

        $this->assign('bale_info', $info[ 'data' ]);
        return $this->fetch('bale/edit');
    }

    /**
     * 详情
     */
    public function detail()
    {
        $bale_id = input('bale_id', '');

        $bale = new BaleModel();

        $info = $bale->getEditBaleData($bale_id, $this->site_id)[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到活动信息');
        $this->assign('info', $info);
        return $this->fetch('bale/detail');
    }

    /**
     * 删除活动
     * @return array
     */
    public function delete()
    {
        if (request()->isJson()) {
            $id = input('id', 0);
            $bale = new BaleModel();
            $res = $bale->deleteBale($id, $this->site_id);
            return $res;
        }
    }

    /**
     * 商品推广
     * return
     */
    public function baleUrl()
    {
        $bale_id = input('bale_id', '');
        $app_type = input('app_type', 'all');
        $bale = new BaleModel();
        $res = $bale->urlQrcode('/pages_promotion/bale/detail', [ 'id' => $bale_id ], 'bale', $app_type, $this->site_id);
        return $res;
    }

    public function closeBale()
    {
        $bale_id = input('id', '');
        $bale = new BaleModel();
        $res = $bale->closeBale($bale_id);
        return $res;
    }

    /**
     * 删除活动(批量)
     * @return array
     */
    public function deleteAll()
    {
        if (request()->isJson()) {
            $bale_id = input('bale_id', '');
            $bale = new BaleModel();
            foreach ($bale_id as $k => $v){
                $res = $bale->deleteBale($v, $this->site_id);
            }
            return $res;
        }
    }

    /**
     * 关闭活动(批量)
     * @return array
     */
    public function closeBaleAll()
    {
        if (request()->isJson()) {
            $bale_id = input('bale_id', '');
            $bale = new BaleModel();
            foreach ($bale_id as $k => $v){
                $res = $bale->closeBale($v);
            }
            return $res;
        }
    }
}