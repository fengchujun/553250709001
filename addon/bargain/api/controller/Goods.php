<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\api\controller;

use addon\bargain\model\Bargain as BargainModel;
use addon\bargain\model\Poster;
use app\api\controller\BaseApi;
use app\api\controller\Goodssku;

/**
 * 砍价商品
 */
class Goods extends BaseApi
{

    /**
     * 获取砍价活动列表
     */
    public function page()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $id_arr = $this->params[ 'id_arr' ] ?? '';
        $is_exclude_bargaining = $this->params[ 'is_exclude_bargaining' ] ?? 0; // 是否需排除砍价中

        $bargain = new BargainModel();

        $condition = [
            [ 'pb.site_id', '=', $this->site_id ],
            [ 'pb.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        if (!empty($id_arr)) {
            $condition[] = [ 'pb.goods_id', 'in', $id_arr ];
        }
        if ($is_exclude_bargaining) {
            $token = $this->checkToken();
            if ($token[ 'code' ] == 0) {
                $goods_id = $bargain->getBargainingGoodsId($this->member_id);
                if (!empty($goods_id)) $condition[] = [ 'g.goods_id', 'not in', $goods_id ];
            }
        }

        $data = $bargain->getBargainPageList($condition, $page, $page_size, 'pb.bargain_id desc', '');
        return $this->response($data);
    }

    /**
     * 获取砍价活动列表
     */
    public function lists()
    {
        $num = $this->params[ 'num' ] ?? null;
        $id_arr = $this->params[ 'id_arr' ] ?? '';
        $is_exclude_bargaining = $this->params[ 'is_exclude_bargaining' ] ?? 0; // 是否需排除砍价中

        $bargain = new BargainModel();

        $condition = [
            [ 'pb.site_id', '=', $this->site_id ],
            [ 'pb.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        if (!empty($id_arr)) {
            $condition[] = [ 'pb.goods_id', 'in', $id_arr ];
        }
        if ($is_exclude_bargaining) {
            $token = $this->checkToken();
            if ($token[ 'code' ] == 0) {
                $goods_id = $bargain->getBargainingGoodsId($this->member_id);
                if (!empty($goods_id)) $condition[] = [ 'g.goods_id', 'not in', $goods_id ];
            }
        }

        $data = $bargain->getBargainList($condition, '', 'pb.bargain_id desc', $num);
        return $this->response($data);
    }

    /**
     * 获取砍价中的商品列表
     * @return false|string
     */
    public function bargainingList()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $condition = [
            [ 'pbl.site_id', '=', $this->site_id ],
            [ 'pbl.member_id', '=', $this->member_id ],
            [ 'pbl.status', '=', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'pb.status', '=', 1 ],
        ];

        $join = [
            [ 'goods g', 'g.goods_id = pbl.goods_id', 'left' ],
            [ 'promotion_bargain pb', 'pb.goods_id = pbl.goods_id', 'left' ],
        ];
        $field = 'pb.sale_num,pb.join_num,pbl.launch_id,pbl.bargain_id,pbl.sku_id,pbl.goods_id,pbl.site_id,pbl.start_time,pbl.end_time,pbl.member_id,pbl.curr_price,pbl.price,g.goods_name,g.goods_image,g.recommend_way,pbl.floor_price';
        $bargain = new BargainModel();
        $list = $bargain->getBargainLaunchList($condition, $field, 'pbl.start_time desc', 'pbl', $join);
        return $this->response($this->success($list));
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $bargain_id = $this->params[ 'bargain_id' ] ?? 0;
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $bargain = new BargainModel();
        $condition = [
            [ 'pb.bargain_id', '=', $bargain_id ],
            [ 'pbg.site_id', '=', $this->site_id ],
            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $bargain->getBargainGoodsDetail($condition)[ 'data' ];

        if (empty($goods_sku_detail)) return $this->response($this->error());

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $bargain->getGoodsSpecFormat($bargain_id, $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        $token = $this->checkToken();
        if ($token[ 'code' ] == 0) {
            $launch_info = $bargain->getBargainLaunchDetail([
                [ 'bargain_id', '=', $goods_sku_detail[ 'bargain_id' ] ],
                [ 'sku_id', '=', $goods_sku_detail[ 'sku_id' ] ],
                [ 'member_id', '=', $this->member_id ],
                [ 'status', '=', 0 ]
            ], 'launch_id');
            if (!empty($launch_info[ 'data' ])) $res[ 'goods_sku_detail' ][ 'launch_info' ] = $launch_info[ 'data' ];
        }

        // 处理公共数据
        $goods_sku_api = new Goodssku();
        $goods_sku_api->handleGoodsDetailData($res[ 'goods_sku_detail' ]);

        return $this->response($this->success($res));
    }

    /**
     * 查询商品SKU集合
     * @return false|string
     */
    public function goodsSku()
    {
        $goods_id = $this->params[ 'goods_id' ] ?? 0;
        $bargain_id = $this->params[ 'bargain_id' ] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $condition = [
            [ 'pb.bargain_id', '=', $bargain_id ],
            [ 'pbg.site_id', '=', $this->site_id ],
//            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_id', '=', $goods_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];

        $goods = new BargainModel();
        $list = $goods->getBargainGoodsSkuList($condition);
        foreach ($list[ 'data' ] as $k => $v) {
            if (!empty($v[ 'goods_spec_format' ])) {
                $goods_spec_format = $goods->getGoodsSpecFormat($bargain_id, $this->site_id, $v[ 'goods_spec_format' ]);
                $list[ 'data' ][ $k ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
            }
        }

        return $this->response($list);
    }

    /**
     * 商品海报
     * @return false|string
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'bargain';
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