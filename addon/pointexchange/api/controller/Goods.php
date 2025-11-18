<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointexchange\api\controller;

use app\api\controller\BaseApi;
use addon\pointexchange\model\Exchange as ExchangeModel;
use app\model\goods\GoodsService;
use app\model\web\Config as ConfigModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;

/**
 * 积分兑换
 */
class Goods extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {

        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $exchange_model = new ExchangeModel();

        $exchange_info = $exchange_model->getExchangeInfo($id, 'type, type_id')[ 'data' ];
        $condition = [
            [ 'peg.id', '=', $id ],
            [ 'peg.site_id', '=', $this->site_id ],
            [ 'peg.state', '=', 1 ],
        ];

        if (empty($exchange_info)) return $this->response($this->error('', '商品未找到'));
        $info = $exchange_model->getExchangeDetail($condition, $exchange_info[ 'type' ])[ 'data' ];

        if (empty($info)) return $this->response($this->error($info));

        if ($exchange_info[ 'type' ] == 1) {

            //判断商品规格项
            $goods_spec_format = $exchange_model->getGoodsSpecFormat($info[ 'exchange_id' ], $this->site_id, $info[ 'goods_spec_format' ]);
            $info[ 'goods_spec_format' ] = json_encode($goods_spec_format);

            $goods_service = new GoodsService();
            $info[ 'goods_service' ] = $goods_service->getServiceList([ [ 'site_id', '=', $this->site_id ], [ 'id', 'in', $info[ 'goods_service_ids' ] ] ], 'service_name,desc,icon')[ 'data' ];
        }

        return $this->response($this->success($info));
    }

    /**
     * 查询商品SKU集合
     * @return false|string
     */
    public function goodsSku()
    {
        $goods_id = $this->params['goods_id'] ?? 0;
        $exchange_id = $this->params['exchange_id'] ?? 0;
        $type = $this->params['type'] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($exchange_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $exchange_model = new ExchangeModel();
        $condition = [
            [ 'peg.id', '=', $exchange_id ],
            [ 'peg.site_id', '=', $this->site_id ],
            [ 'peg.state', '=', 1 ],
        ];
        $list = $exchange_model->getExchangeSkuList($condition, $type);
        if (!empty($list[ 'data' ])) {
            foreach ($list[ 'data' ] as $k => $v) {
                if (!empty($v[ 'goods_spec_format' ])) {
                    $goods_spec_format = $exchange_model->getGoodsSpecFormat($v[ 'exchange_id' ], $this->site_id, $v[ 'goods_spec_format' ]);
                    $list[ 'data' ][ $k ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
                }
            }
        }

        return $this->response($list);
    }

    public function page()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $type = $this->params['type'] ?? 1;//兑换类型，1：商品，2：优惠券，3：红包

        //商品类型条件查询
        $keyword = $this->params['keyword'] ?? ''; //关键词
        $order = $this->params['order'] ?? '';//排序（综合、上新时间、价格）
        $sort = $this->params['sort'] ?? '';//升序、降序
        $min_point = $this->params['min_point'] ?? 0;//积分区间，小
        $max_point = $this->params['max_point'] ?? 0;//积分区间，大
        $category_id = $this->params['category_id'] ?? 0;//分类
        $condition = [
            [ 'peg.state', '=', 1 ],
            [ 'peg.type', '=', $type ],
            [ 'peg.site_id', '=', $this->site_id ]
        ];

        if (!empty($keyword)) {
            $condition[] = [ 'g.goods_name|peg.name', 'like', '%' . $keyword . '%' ];
        }

        // 非法参数进行过滤
        if ($sort != 'desc' && $sort != 'asc') {
            $sort = '';
        }
        // 非法参数进行过滤
        if ($order != '') {
            if ($order != 'create_time' && $order != 'point') {
                $order = 'peg.sort';
            } elseif ($order == 'create_time') {
                $order = 'peg.create_time';
            } else {
                $order = 'peg.' . $order;
            }
            $order_by = $order . ' ' . $sort;
        } else {
            $config_model = new ConfigModel();
            $sort_config = $config_model->getGoodsSort($this->site_id)[ 'data' ][ 'value' ];
            $order_by = 'peg.sort ' . $sort_config[ 'type' ] . ',peg.create_time desc';
        }

        if ($min_point != '' && $max_point != '') {
            $condition[] = [ 'peg.point', 'between', [ $min_point, $max_point ] ];
        } elseif ($min_point != '') {
            $condition[] = [ 'peg.point', '>=', $min_point ];
        } elseif ($max_point != '') {
            $condition[] = [ 'peg.point', '<=', $max_point ];
        }

        if (!empty($category_id)) {
            $goods_category_model = new GoodsCategoryModel();

            // 查询当前
            $category_list = $goods_category_model->getCategoryList([ [ 'category_id', '=', $category_id ], [ 'site_id', '=', $this->site_id ] ], 'category_id,pid,level')[ 'data' ];

            // 查询子级
            $category_child_list = $goods_category_model->getCategoryList([ [ 'pid', '=', $category_id ], [ 'site_id', '=', $this->site_id ] ], 'category_id,pid,level')[ 'data' ];

            $temp_category_list = [];
            if (!empty($category_list)) {
                $temp_category_list = $category_list;
            } elseif (!empty($category_child_list)) {
                $temp_category_list = $category_child_list;
            }

            if (!empty($temp_category_list)) {
                $category_id_arr = [];
                foreach ($temp_category_list as $k => $v) {
                    // 三级分类，并且都能查询到
                    if ($v[ 'level' ] == 3 && !empty($category_list) && !empty($category_child_list)) {
                        $category_id_arr[] = $v['pid'];
                    } else {
                        $category_id_arr[] = $v['category_id'];
                    }
                }
                $category_id_arr = array_unique($category_id_arr);
                $temp_condition = [];
                foreach ($category_id_arr as $ck => $cv) {
                    $temp_condition[] = '%,' . $cv . ',%';
                }
                $category_condition = $temp_condition;
                $condition[] = [ 'g.category_id', 'like', $category_condition, 'or' ];
            }
        }

        $field = 'peg.*';

        $alias = 'peg';
        $join = [];
        if ($type == 1) {
            $condition[] = [ 'g.is_delete', '=', 0 ];
            $condition[] = [ 'g.goods_state', '=', 1 ];

            $join = [
                [ 'goods g', 'peg.type_id = g.goods_id', 'inner' ]
            ];
            $field .= ',g.goods_name as name,g.goods_image as image, g.goods_stock as stock, g.stock_show,g.sale_show,(g.sale_num + g.virtual_sale) as sale_num';

        } elseif ($type == 2) {
            $field .= ',pct.type as coupon_type,pct.goods_type,pct.at_least,pct.money,pct.discount';
            $join = [
                [ 'promotion_coupon_type pct', 'peg.type_id = pct.coupon_type_id', 'inner' ]
            ];
        }
        $exchange_model = new ExchangeModel();

        $list = $exchange_model->getExchangeGoodsPageList($condition, $page, $page_size, $order_by, $field, $alias, $join);

        return $this->response($list);
    }

}