<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\api\controller;

use addon\presale\model\Presale as PresaleModel;
use addon\presale\model\PresaleOrderCommon;
use addon\presale\model\PresaleOrderRefund;
use app\api\controller\BaseApi;
use addon\presale\model\Poster;
use app\model\goods\GoodsApi;

/**
 * 预售商品
 */
class Goods extends BaseApi
{

    /**
     * 预售商品详情信息
     */
    public function detail()
    {
        $presale_id = $this->params['id'] ?? 0;
        if (empty($presale_id)) {
            return $this->response($this->error('', 'REQUEST_PRESALE_ID'));
        }
        $sku_id = $this->params['sku_id'] ?? 0;

        $presale_model = new PresaleModel();
        $condition = [
            [ 'pp.presale_id', '=', $presale_id ],
            [ 'pp.site_id', '=', $this->site_id ],
            [ 'pp.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        if ($sku_id > 0) {
            $condition[] = [ 'ppg.sku_id', '=', $sku_id ];
        }
        $goods_sku_detail = $presale_model->getPresaleGoodsDetail($condition)[ 'data' ];
        if (empty($goods_sku_detail)) {
            return $this->response($this->error());
        }

        $res = [];
        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        $res[ 'goods_sku_detail' ][ 'purchased_num' ] = 0;

        $token = $this->checkToken();
        if ($token[ 'code' ] >= 0) {
            $res[ 'goods_sku_detail' ][ 'purchased_num' ] = $presale_model->getGoodsPurchasedNum($goods_sku_detail[ 'presale_id' ], $this->member_id);
            $res[ 'goods_sku_detail' ][ 'buying_num' ] = $presale_model->getPresaleOrderCount([
                [ 'member_id', '=', $this->member_id ],
                [ 'presale_id', '=', $goods_sku_detail[ 'presale_id' ] ],
                [ 'order_status', 'not in', [ PresaleOrderCommon::ORDER_CLOSE, PresaleOrderCommon::ORDER_PAY ] ],
                [ 'refund_status', '<>', PresaleOrderRefund::REFUND_COMPLETE ]
            ])[ 'data' ];
        }
        // 预约人数
        $res[ 'goods_sku_detail' ][ 'sale_num' ] = $presale_model->getPresaleOrderCount([
            [ 'presale_id', '=', $goods_sku_detail[ 'presale_id' ] ],
            [ 'order_status', '<>', PresaleOrderCommon::ORDER_CLOSE ]
        ])[ 'data' ];

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
        $presale_id = $this->params['presale_id'] ?? 0;
        if (empty($presale_id)) {
            return $this->response($this->error('', 'REQUEST_PRESALE_ID'));
        }
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $presale_model = new PresaleModel();

        $condition = [
            [ 'pp.presale_id', '=', $presale_id ],
            [ 'g.goods_id', '=', $goods_id ],
            [ 'pp.status', '=', 1 ],
            [ 'pp.site_id', '=', $this->site_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
        ];
        $list = $presale_model->getPresaleGoodsSkuList($condition);
        return $this->response($list);
    }

    public function page()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $goods_id_arr = $this->params['goods_id_arr'] ?? '';//goods_id数组

        $condition = [
            [ 'pp.status', '=', 1 ],// 状态（0未开始 1进行中 2已结束）
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $this->site_id ]
        ];

        if (!empty($goods_id_arr)) {
            $condition[] = [ 'g.goods_id', 'in', $goods_id_arr ];
        }

        $presale_model = new PresaleModel();
        $list = $presale_model->getPresaleGoodsPageList($condition, $page, $page_size, 'pp.presale_id desc', '');

        return $this->response($list);
    }

    public function lists()
    {
        $num = $this->params['num'] ?? 0;

        $condition = [
            [ 'pp.status', '=', 1 ],// 状态（0未开始 1进行中 2已结束）
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $this->site_id ]
        ];

        if (!empty($goods_id_arr)) {
            $condition[] = [ 'g.goods_id', 'in', $goods_id_arr ];
        }

        $presale_model = new PresaleModel();
        $list = $presale_model->getPresaleList($condition, '', 'pp.presale_id desc', $num);

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'presale';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $this->member_id;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }
}