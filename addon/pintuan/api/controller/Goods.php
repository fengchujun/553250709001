<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\api\controller;

use addon\pintuan\model\Pintuan as PintuanModel;
use addon\pintuan\model\Poster;
use app\api\controller\BaseApi;
use app\model\goods\GoodsApi;

/**
 * 拼团商品
 */
class Goods extends BaseApi
{

    /**
     * 拼团商品详情信息
     */
    public function detail()
    {
        $pintuan_id = $this->params['pintuan_id'] ?? 0;
        if (empty($pintuan_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $pintuan_model = new PintuanModel();
        $condition = [
            [ 'ppg.pintuan_id', '=', $pintuan_id ],
            [ 'ppg.site_id', '=', $this->site_id ],
            [ 'pp.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $pintuan_model->getPintuanGoodsDetail($condition)[ 'data' ];
        if (empty($goods_sku_detail)) return $this->response($this->error());

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $pintuan_model->getGoodsSpecFormat($pintuan_id, $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
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
        $goods_id = $this->params['goods_id'] ?? 0;
        $pintuan_id = $this->params['pintuan_id'] ?? 0;
        $pintuan_num = $this->params[ 'pintuan_num' ] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($pintuan_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $pintuan_model = new PintuanModel();

        $condition = [
            [ 'ppg.pintuan_id', '=', $pintuan_id ],
            [ 'ppg.site_id', '=', $this->site_id ],
            [ 'pp.status', '=', 1 ],
            [ 'g.goods_id', '=', $goods_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $list = $pintuan_model->getPintuanGoodsSkuList($condition);

        foreach ($list[ 'data' ] as $k => $v) {
            if ($v[ 'pintuan_type' ] == 'ladder') {
                $v[ 'pintuan_ladder' ] = $pintuan_num;
                $price = $pintuan_model->getPintuanPrice($v);
                $list[ 'data' ][ $k ][ 'pintuan_price' ] = $price;
            }

            if (!empty($v[ 'goods_spec_format' ])) {
                $goods_spec_format = $pintuan_model->getGoodsSpecFormat($pintuan_id, $this->site_id, $v[ 'goods_spec_format' ]);
                $list[ 'data' ][ $k ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
            }
        }
        return $this->response($list);
    }

    public function page()
    {
        $site_id = $this->site_id;
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $goods_id_arr = $this->params['goods_id_arr'] ?? '';//goods_id数组

        $condition = [
            [ 'pp.status', '=', 1 ],// 状态（0正常 1活动进行中  2活动已结束  3失效  4删除）
            [ 'g.goods_stock', '>', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $site_id ]
        ];

        if (!empty($goods_id_arr)) {
            $condition[] = [ 'g.goods_id', 'in', $goods_id_arr ];
        }

        $pintuan_model = new PintuanModel();
        $list = $pintuan_model->getPintuanGoodsPageList($condition, $page, $page_size, 'pp.pintuan_id desc');

        return $this->response($list);
    }

    public function lists()
    {
        $site_id = $this->site_id;
        $num = $this->params['num'] ?? null;
        $goods_id_arr = $this->params['goods_id_arr'] ?? '';//goods_id数组

        $condition = [
            [ 'pp.status', '=', 1 ],// 状态（0正常 1活动进行中  2活动已结束  3失效  4删除）
            [ 'g.goods_stock', '>', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $site_id ]
        ];

        if (!empty($goods_id_arr)) {
            $condition[] = [ 'g.goods_id', 'in', $goods_id_arr ];
        }

        $pintuan_model = new PintuanModel();
        $list = $pintuan_model->getPintuanList($condition, '', 'pp.pintuan_id desc', $num);

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'pintuan';
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