<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\shop\controller;

use app\shop\controller\BaseShop;
use addon\seckill\model\Seckill as SeckillModel;
use think\App;

/**
 * 秒杀控制器
 */
class Seckill extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'SECKILL_CSS' => __ROOT__ . '/addon/seckill/shop/view/public/css',
            'SECKILL_JS' => __ROOT__ . '/addon/seckill/shop/view/public/js',
            'SECKILL_IMG' => __ROOT__ . '/addon/seckill/shop/view/public/img',
        ];
        parent::__construct($app);
    }

    /**
     * 秒杀时间段列表
     */
    public function lists()
    {
        if (request()->isJson()) {
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $order = 'seckill_start_time asc';
            $field = '*';

            $seckill_model = new SeckillModel();
            $res = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            foreach ($res[ 'data' ] as $key => $val) {
                $val = $seckill_model->transformSeckillTime($val);
                $res[ 'data' ][ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $res[ 'data' ][ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            return $res;
        } else {

            return $this->fetch("seckill/lists");
        }
    }

    /**
     * 添加秒杀时间段
     */
    public function add()
    {
        if (request()->isJson()) {
            $start_hour = input('start_hour', 0);
            $start_minute = input('start_minute', 0);
            $start_second = input('start_second', 0);

            $end_hour = input('end_hour', 0);
            $end_minute = input('end_minute', 0);
            $end_second = input('end_second', 0);

            $data = [
                'site_id' => $this->site_id,
                'name' => input('name', ''),
                'seckill_start_time' => $start_hour * 3600 + $start_minute * 60 + $start_second,
                'seckill_end_time' => $end_hour * 3600 + $end_minute * 60 + $end_second,
                'create_time' => time(),
            ];
            $seckill_model = new SeckillModel();
            return $seckill_model->addSeckillTime($data);
        } else {
            return $this->fetch("seckill/add");
        }
    }

    /**
     * 编辑秒杀时间段
     */
    public function edit()
    {
        $seckill_model = new SeckillModel();
        if (request()->isJson()) {
            $start_hour = input('start_hour', 0);
            $start_minute = input('start_minute', 0);
            $start_second = input('start_second', 0);

            $end_hour = input('end_hour', 0);
            $end_minute = input('end_minute', 0);
            $end_second = input('end_second', 0);

            $data = [
                'name' => input('name', ''),
                'seckill_start_time' => $start_hour * 3600 + $start_minute * 60 + $start_second,
                'seckill_end_time' => $end_hour * 3600 + $end_minute * 60 + $end_second,
                'create_time' => time(),
                'id' => input('id', 0),
            ];
            return $seckill_model->editSeckillTime($data, $this->site_id);
        } else {
            $id = input('id', 0);
            $this->assign('id', $id);

            //秒杀详情
            $time_info = $seckill_model->getSeckillTimeInfo([ [ 'id', '=', $id ] ]);
            if (!empty($time_info[ 'data' ])) {
                $time_info[ 'data' ] = $seckill_model->transformSeckillTime($time_info[ 'data' ]);
            }
            $this->assign('time_info', $time_info[ 'data' ]);

            return $this->fetch("seckill/edit");
        }
    }

    /**
     * 删除秒杀时间段
     */
    public function delete()
    {
        if (request()->isJson()) {
            $seckill_time_id = input('id', 0);
            $seckill_model = new SeckillModel();
            return $seckill_model->deleteSeckillTime($seckill_time_id);
        }
    }

    /**
     * 添加秒杀商品
     */
    public function addGoods()
    {
        $seckill_model = new SeckillModel();
        if (request()->isJson()) {
            $data = [
                'site_id' => $this->site_id,
                'seckill_name' => input('seckill_name', ''),
                'remark' => input('remark', ''),
                'seckill_time_id' => input('seckill_time_id', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'goods_data' => input('goods_data', ''),
                'goods_ids' => input('goods_ids', ''),
                'sort' => input('sort', '')
            ];
            $res = $seckill_model->addSeckillGoods($data);
            return $res;
        } else {
            return $this->fetch("seckill/editgoods");
        }
    }

    /**
     * 更新商品（秒杀价格）
     */
    public function updateGoods()
    {
        $seckill_model = new SeckillModel();
        if (request()->isJson()) {
            $data = [
                'site_id' => $this->site_id,
                'id' => input('id', ''),
                'seckill_name' => input('seckill_name', ''),
                'remark' => input('remark', ''),
                'seckill_time_id' => input('seckill_time_id', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'sku_list' => input('sku_list', ''),
                'goods_ids' => input('goods_ids', ''),
                'sort' => input('sort', ''),
            ];
            $res = $seckill_model->editSeckillGoods($data);
            return $res;
        } else {
            $seckill_id = input('id', '');
            if (empty($seckill_id)) {
                $this->error('缺少参数id');
            }
            $seckill_info = $seckill_model->getSeckillDetail([ [ 'id', '=', $seckill_id ] ])[ 'data' ];

            $seckill_time_id = trim($seckill_info[ 'seckill_time_id' ], ',');
            $time_list = $seckill_model->getSeckillTimeList([ [ 'id', 'in', $seckill_time_id ] ])[ 'data' ];

            $this->assign('seckill_info', $seckill_info);
            $this->assign('time_list', $time_list);

            return $this->fetch("seckill/editgoods");
        }
    }

    public function seckillSort()
    {
        $sort = input('sort', 0);
        $id = input('id', 0);
        $seckill_model = new SeckillModel();
        return $seckill_model->seckillSort($id, $sort);
    }

    /**
     * 删除商品
     */
    public function deleteGoods()
    {
        if (request()->isJson()) {
            $seckill_id = input('id', 0);
            $site_id = $this->site_id;

            $seckill_model = new SeckillModel();
            return $seckill_model->deleteSeckillGoods($seckill_id, $site_id);
        }
    }

    /**
     * 秒杀商品
     */
    public function goodslist()
    {
        $seckill_time_id = input('seckill_time_id', '');
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name = input('goods_name', '');
            $status = input('status', '');
            $link_sort = input('order', 'start_time');
            $sort = input('sort', 'desc');

            $condition = [];
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $condition[] = [ 'goods_name', 'like', '%' . $goods_name . '%' ];

            if ($status !== '') $condition[] = [ 'status', '=', $status ];
            //排序
            if ($link_sort == 'sort') {
                $order_by = $link_sort . ' ' . $sort;
            } else {
                $order_by = $link_sort . ' ' . $sort . ',sort desc';
            }

            if (!empty($seckill_time_id)) {
                $condition[] = [ '', 'exp', \think\facade\Db::raw("FIND_IN_SET({$seckill_time_id},seckill_time_id)") ];
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

            $seckill_model = new SeckillModel();
            $seckill_list = $seckill_model->getSeckillPageList($condition, $page, $page_size, $order_by);

            $seckill_condition[] = [ 'site_id', '=', $this->site_id ];

            $time_list = $seckill_model->getSeckillTimeList($seckill_condition);
            foreach ($seckill_list[ 'data' ][ 'list' ] as $k => $v) {
                $seckill_list[ 'data' ][ 'list' ][ $k ][ 'time_list' ] = [];
                foreach ($time_list[ 'data' ] as $index => $item) {
                    if (strpos(',' . $v[ 'seckill_time_id' ] . ',', ',' . $item[ 'id' ] . ',') !== false) {
                        $seckill_list[ 'data' ][ 'list' ][ $k ][ 'time_list' ][] = $item;
                    }
                }
            }

            return $seckill_list;
        } else {

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $order = 'seckill_start_time asc';
            $field = '*';

            $seckill_model = new SeckillModel();
            $res = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            $this->assign('seckill_time_id', $seckill_time_id);
            $this->assign('res', $res[ 'data' ]);
            return $this->fetch("seckill/goodslist");
        }
    }

    /**
     * 秒杀时段
     */
    public function seckilltimeselect()
    {
        if (request()->isJson()) {
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $order = 'seckill_start_time asc';
            $field = '*';

            $seckill_model = new SeckillModel();
            $res = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            foreach ($res[ 'data' ] as $key => $val) {
                $val = $seckill_model->transformSeckillTime($val);
                $res[ 'data' ][ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $res[ 'data' ][ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            return $res;
        } else {

            return $this->fetch("seckill/seckilltimeselect");
        }

    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isJson()) {
            $seckill_model = new SeckillModel();
            $seckill_id = input('seckill_id', '');
            $goods_list = $seckill_model->getSeckillGoodsList($seckill_id);
            return $goods_list;
        }
    }

    /**
     * 手动关闭秒杀
     * @return array
     */
    public function closeSeckill()
    {
        if (request()->isJson()) {
            $seckill_model = new SeckillModel();
            $seckill_id = input('seckill_id', '');
            $goods_list = $seckill_model->closeSeckill($seckill_id);
            return $goods_list;
        }
    }

    /**
     * 秒杀推广
     */
    public function seckillUrl()
    {
        $seckill_id = input('seckill_id', '');
        $app_type = input('app_type', 'all');
        $seckill_model = new SeckillModel();

        $res = $seckill_model->urlQrcode('/pages_promotion/seckill/detail', [ 'id' => $seckill_id ], 'seckill', $app_type, $this->site_id);
        return $res;
    }

    /**
     * 批量删除商品
     */
    public function deleteGoodsAll()
    {
        if (request()->isJson()) {
            $seckill_id = input('seckill_id', 0);

            $seckill_model = new SeckillModel();
            foreach ($seckill_id as $k => $v){
                $res = $seckill_model->deleteSeckillGoods($v, $this->site_id);
            }
            return $res;
        }
    }

    /**
     * 批量关闭秒杀
     * @return array
     */
    public function closeSeckillAll()
    {
        if (request()->isJson()) {
            $seckill_model = new SeckillModel();
            $seckill_id = input('seckill_id', '');

            foreach ($seckill_id as $k => $v){
                $res = $seckill_model->closeSeckill($v);
            }
            return $res;
        }
    }

}