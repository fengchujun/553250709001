<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\api\controller;

use addon\jielong\model\Jielong as JielongModel;
use app\api\controller\BaseApi;
use addon\jielong\model\Poster;
use app\model\goods\Goods as GoodsModel;

/**
 * 社群接龙
 */
class Goods extends BaseApi
{

    //社群接龙活动列表
    public function jielongPage()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $condition = [
            [ 'status', 'in', '0,1' ],// 状态（0未开始 1进行中 2已结束）
            [ 'is_delete', '=', 0 ],
            [ 'site_id', '=', $this->site_id ]
        ];
        $jielong_model = new JielongModel();
        $list = $jielong_model->getJielongActivityPageList($condition, $page, $page_size);
        return $this->response($list);
    }

    //社群接龙活动详情
    public function jielongDetail()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $jielong_id = $this->params[ 'jielong_id' ] ?? 0;

        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $condition = [
            [ 'pjg.jielong_id', '=', $jielong_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.site_id', '=', $this->site_id ]
        ];

        $jielong_model = new JielongModel();
        $list = $jielong_model->getJielongActivityDetail($condition, $page, 0, '', '', $jielong_id);

        $token = $this->checkToken();
        if ($token[ 'code' ] >= 0) {
            if (!empty($list[ 'data' ][ 'list' ])) {
                $goods = new GoodsModel();
                foreach ($list[ 'data' ][ 'list' ] as $k => $v) {
                    // 是否参与会员等级折扣
                    $goods_member_price = $goods->getGoodsPrice($v[ 'sku_id' ], $this->member_id)[ 'data' ];
                    if (!empty($goods_member_price[ 'member_price' ])) {
                        $list[ 'data' ][ 'list' ][ $k ][ 'member_price' ] = $goods_member_price[ 'member_price' ];
                    }
                    //购物车数量
                    $list[ 'data' ][ 'list' ][ $k ][ 'cart_num' ] = $jielong_model->getCartNum($jielong_id, $v[ 'goods_id' ], $this->member_id, $this->site_id);

                    if ($v[ 'is_limit' ] && $v[ 'limit_type' ] == 2 && $v[ 'max_buy' ] > 0) $list[ 'data' ][ 'list' ][ $k ][ 'purchased_num' ] = $goods->getGoodsPurchasedNum($v[ 'goods_id' ], $this->member_id);
                }
            }
        }
        return $this->response($list);
    }

    //社群接龙购买列表
    public function jielongBuyPage()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $jielong_id = $this->params[ 'jielong_id' ] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $condition = [
            [ 'o.pay_status', '=', '1' ],
            [ 'pjo.jielong_id', '=', $jielong_id ],
            [ 'pjo.order_status', 'not in', [ 0, -1 ] ],
            [ 'pjo.site_id', '=', $this->site_id ]
        ];
        $jielong_model = new JielongModel();
        $list = $jielong_model->getJielongBuyPageList($condition, $page, $page_size);
        //list 中的count就是接龙人数

        return $this->response($list);
    }

    /**
     * 接龙活动海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'jielong';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $qrcode_param[ 'source_member' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }
}