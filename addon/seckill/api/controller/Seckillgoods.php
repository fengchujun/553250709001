<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\api\controller;

use addon\seckill\model\Poster;
use addon\seckill\model\Seckill as SeckillModel;
use addon\seckill\model\SeckillOrder;
use app\api\controller\BaseApi;
use app\model\goods\GoodsApi;

/**
 * 秒杀商品
 */
class Seckillgoods extends BaseApi
{

    /**
     * 【PC端在用】基础信息
     */
    public function info()
    {
        $token = $this->checkToken();

        $seckill_id = $this->params[ 'seckill_id' ] ?? 0;
        $sku_id = $this->params[ 'sku_id' ] ?? 0;
        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $seckill_model = new SeckillModel();
        $order_model = new SeckillOrder();
        $condition = [
            [ 'ps.id', '=', $seckill_id ],
            [ 'psg.sku_id', '=', $sku_id ],
            [ 'psg.site_id', '=', $this->site_id ],
            [ 'psg.status', '=', 1 ],
            [ 'ps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $seckill_model->getSeckillGoodsInfo($condition, '')[ 'data' ];

        // 限购
        if ($token[ 'code' ] >= 0 && $goods_sku_detail[ 'max_buy' ] > 0) {
            $goods_sku_detail[ 'purchased_num' ] = $seckill_model->getGoodsPurchasedNum($goods_sku_detail[ 'sku_id' ], $this->member_id);
            $goods_sku_detail[ 'is_limit' ] = 1;
            $goods_sku_detail[ 'limit_type' ] = 2;
        }

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (!empty($goods_sku_detail)) {
            $num = $order_model->getGoodsSeckillNum($seckill_id);
            $time_data = $seckill_model->getSeckillInfo($seckill_id)[ 'data' ];
            $goods_sku_detail[ 'sale_num' ] = $num;
            $goods_sku_detail[ 'seckill_start_time' ] = $time_data[ 'seckill_start_time' ];
            $goods_sku_detail[ 'seckill_end_time' ] = $time_data[ 'seckill_end_time' ];
            //判断商品规格项
            $goods_spec_format = $seckill_model->getGoodsSpecFormat($seckill_id, $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        } else {
            $sku_id = $seckill_model->getGoodsSpecFormat($seckill_id, $this->site_id, '');
            $res = [ 'type' => 'again', 'sku_id' => $sku_id ];
        }
        return $this->response($this->success($res));
    }

    /**
     * 详情信息
     */
    public function detail()
    {
        $token = $this->checkToken();
        $seckill_id = $this->params[ 'seckill_id' ] ?? 0;
        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $seckill_model = new SeckillModel();
        $condition = [
            [ 'ps.id', '=', $seckill_id ],
            [ 'psg.site_id', '=', $this->site_id ],
            [ 'psg.status', '=', 1 ],
            [ 'ps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $seckill_model->getSeckillGoodsInfo($condition)[ 'data' ];
        if (empty($goods_sku_detail)) return $this->response($this->error());

        // 限购
        if ($token[ 'code' ] >= 0 && $goods_sku_detail[ 'max_buy' ] > 0) {
            $goods_sku_detail[ 'purchased_num' ] = $seckill_model->getGoodsPurchasedNum($goods_sku_detail[ 'sku_id' ], $this->member_id);
            $goods_sku_detail[ 'is_limit' ] = 1;
            $goods_sku_detail[ 'limit_type' ] = 2;
        }

        $time_data = $seckill_model->getSeckillInfo($seckill_id)[ 'data' ];
        $goods_sku_detail[ 'seckill_start_time' ] = $time_data[ 'seckill_start_time' ];
        $goods_sku_detail[ 'seckill_end_time' ] = $time_data[ 'seckill_end_time' ];
        $goods_sku_detail[ 'time_list' ] = $time_data[ 'time_list' ];

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $seckill_model->getGoodsSpecFormat($seckill_id, $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        // 处理公共数据
        $goods_sku_api = new GoodsApi();
        $goods_sku_api->handleGoodsDetailData($res[ 'goods_sku_detail' ], $this->member_id, $this->site_id);

        return $this->response($this->success($res));
    }

    /**
     * 查询商品SKU集合
     * @return false|string
     */
    public function goodsSku()
    {
        $token = $this->checkToken();

        $goods_id = $this->params[ 'goods_id' ] ?? 0;
        $seckill_id = $this->params[ 'seckill_id' ] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $seckill_model = new SeckillModel();
        $condition = [
            [ 'ps.id', '=', $seckill_id ],
            [ 'psg.goods_id', '=', $goods_id ],
            [ 'psg.site_id', '=', $this->site_id ],
            [ 'psg.status', '=', 1 ],
            [ 'ps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $list = $seckill_model->getSeckillGoodsSkuList($condition);
        if (!empty($list[ 'data' ])) {
            foreach ($list[ 'data' ] as $k => $v) {
                if (!empty($v[ 'goods_spec_format' ])) {
                    //判断商品规格项
                    $goods_spec_format = $seckill_model->getGoodsSpecFormat($seckill_id, $this->site_id, $v[ 'goods_spec_format' ]);
                    $list[ 'data' ][ $k ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
                }

                // 限购
                if ($token[ 'code' ] >= 0 && $v[ 'max_buy' ] > 0) {
                    $list[ 'data' ][ $k ][ 'purchased_num' ] = $seckill_model->getGoodsPurchasedNum($v[ 'sku_id' ], $this->member_id);
                    $list[ 'data' ][ $k ][ 'is_limit' ] = 1;
                    $list[ 'data' ][ $k ][ 'limit_type' ] = 2;
                }
            }
        }
        return $this->response($list);
    }

    public function page()
    {
        $seckill_time_id = $this->params[ 'seckill_time_id' ] ?? 0;
        $seckill_time_type = $this->params[ 'seckill_time_type' ] ?? 'today';
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        if (empty($seckill_time_id)) {
            return $this->response($this->error('', 'REQUEST_SECKILL_ID'));
        }

        $seckill_model = new SeckillModel();
        $res = $seckill_model->getSeckillGoodsPageList($seckill_time_id, $this->site_id, $page, $page_size, $seckill_time_type);
        foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
            if ($val[ 'price' ] != 0) {
                $discount_rate = floor($val[ 'seckill_price' ] / $val[ 'price' ] * 100);
            } else {
                $discount_rate = 100;
            }
            $res[ 'data' ][ 'list' ][ $key ][ 'discount_rate' ] = $discount_rate;
        }
        return $this->response($res);
    }

    public function lists()
    {
        $seckill_time_id = $this->params[ 'seckill_time_id' ] ?? 0;
        $num = $this->params[ 'num' ] ?? null;

        if (empty($seckill_time_id)) {
            return $this->response($this->error('', 'REQUEST_SECKILL_ID'));
        }

        $seckill_model = new SeckillModel();
        $res = $seckill_model->getSeckillList($seckill_time_id, $this->site_id, $num);
        $list = $res[ 'data' ];
        foreach ($list as $key => $val) {
            if ($val[ 'price' ] != 0) {
                $discount_rate = floor($val[ 'seckill_price' ] / $val[ 'price' ] * 100);
            } else {
                $discount_rate = 100;
            }
            $list[ $key ][ 'discount_rate' ] = $discount_rate;
        }

        return $this->response($this->success($list));
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'seckill';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $this->member_id;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }

    /**
     * 分享图片
     * @return false|string
     */
    public function shareImg()
    {
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);

        $poster = new Poster();
        $res = $poster->shareImg($this->params[ 'page' ] ?? '', $qrcode_param, $this->site_id);
        return $this->response($res);
    }
}