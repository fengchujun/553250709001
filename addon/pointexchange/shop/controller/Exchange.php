<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointexchange\shop\controller;

use addon\coupon\model\CouponType as CouponTypeModel;
use addon\pointexchange\model\Exchange as ExchangeModel;
use app\shop\controller\BaseShop;
use app\model\express\ExpressTemplate as ExpressTemplateModel;

/**
 * 积分兑换
 * @author Administrator
 *
 */
class Exchange extends BaseShop
{

    /**
     * 积分兑换列表
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $type = input('type', '');
            $state = input('state', '');
            $sort = input('sort', 'asc');
            $condition[] = [ 'peg.site_id', '=', $this->site_id ];
            if ($search_text) {
                $condition[] = [ 'peg.name', 'like', '%' . $search_text . '%' ];
            }
            if ($type) {
                $condition[] = [ 'peg.type', '=', $type ];
            }
            if ($state != '') {
                $condition[] = [ 'peg.state', '=', $state ];
            }

            $field = 'peg.*';
            $alias = 'peg';
            $join = [];

            //排序
            $order = input('order', 'create_time');
            $sort = input('sort', 'desc');
            if ($order == 'sort') {
                $order_by = 'peg.' . $order . ' ' . $sort;
            } else {
                $order_by = 'peg.' . $order . ' ' . $sort . ',peg.sort desc';
            }

            $exchange_model = new ExchangeModel();
            //兑换名称 兑换图片 兑换库存  兑换价格
            $lists = $exchange_model->getExchangeGoodsPageList($condition, $page, $page_size, $order_by, $field, $alias, $join);
            return $lists;
        }

        return $this->fetch("exchange/lists");
    }

    /**
     * 添加积分兑换
     */
    public function add()
    {
        if (request()->isJson()) {
            $type = input('type', '1');//兑换类型 1 商品 2 优惠券 3 红包

            $data = [
                'site_id' => $this->site_id,
                'type' => $type,//兑换类型 1 商品 2 优惠券 3 红包
                'point' => input('point', ''),//积分
                'state' => input('state', ''),
                'is_free_shipping' => input('is_free_shipping', ''),
                'delivery_type' => input('delivery_type', ''),
                'delivery_price' => input('delivery_price', ''),
                'shipping_template' => input('shipping_template', ''),
            ];
            if ($type == 1) {
                $data[ 'goods_data' ] = input('goods_data', '');
                $data[ 'rule' ] = input('content', '');
                $data[ 'type_name' ] = '商品';
            } elseif ($type == 2) {
                $data[ 'coupon_type_id' ] = input('coupon_type_id', '0');//优惠券id
                $data[ 'content' ] = input('content', '');
                $data[ 'type_name' ] = '优惠券';
                $data[ 'stock' ] = input('stock', '');
            } elseif ($type == 3) {
                $data[ 'name' ] = input('name', '');
                $data[ 'image' ] = input('image', '');
                $data[ 'stock' ] = input('stock', '');
                $data[ 'balance' ] = input('balance', '0');
                $data[ 'content' ] = input('content', '');
                $data[ 'type_name' ] = '红包';
            } else {
                return error(-1, '');
            }
            $exchange_model = new ExchangeModel();
            $res = $exchange_model->addExchange($data);
            return $res;
        } else {
            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->site_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            return $this->fetch("exchange/add");
        }
    }

    /**
     * 编辑积分兑换
     */
    public function edit()
    {
        $id = input("id", 0);
        $exchange_model = new ExchangeModel();
        if (request()->isJson()) {
            $type = input('type', '1');//兑换类型 1 商品 2 优惠券 3 红包
            $data = [
                'site_id' => $this->site_id,
                'type' => $type,//兑换类型 1 商品 2 优惠券 3 红包
                'point' => input('point', ''),//积分
                'state' => input('state', ''),
                'id' => $id,
                'is_free_shipping' => input('is_free_shipping', ''),
                'delivery_type' => input('delivery_type', ''),
                'delivery_price' => input('delivery_price', ''),
                'shipping_template' => input('shipping_template', ''),
            ];
            if ($type == 1) {
                $data[ 'goods_data' ] = input('goods_data', '');
                $data[ 'rule' ] = input('content', '');
                $data[ 'type_name' ] = '商品';

            } elseif ($type == 2) {
                $data[ 'coupon_type_id' ] = input('coupon_type_id', '0');//优惠券id
                $data[ 'content' ] = input('content', '');
                $data[ 'type_name' ] = '优惠券';
                $data[ 'stock' ] = input('stock', '');
            } elseif ($type == 3) {
                $data[ 'name' ] = input('name', '');
                $data[ 'image' ] = input('image', '');
                $data[ 'stock' ] = input('stock', '');
                $data[ 'balance' ] = input('balance', '0');
                $data[ 'content' ] = input('content', '');
                $data[ 'type_name' ] = '红包';
            } else {
                return error(-1, '');
            }

            $res = $exchange_model->editExchange($data);
            return $res;
        } else {
            $exchange_info = $exchange_model->getExchangeGoodsDetail($id, $this->site_id);
            if (empty($exchange_info[ 'data' ][ 'id' ])) {
                $this->error('对应的积分兑换活动商品/优惠券已经不存在了！');
            }
            $this->assign("exchange_info", $exchange_info[ 'data' ]);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->site_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            return $this->fetch("exchange/edit");
        }
    }

    /**
     *关闭积分兑换
     */
    public function delete()
    {
        $id = input("id", 0);
        $exchange_model = new ExchangeModel();
        $res = $exchange_model->deleteExchange($id);
        return $res;

    }

    /**
     * 修改排序
     */
    public function modifySort()
    {
        $sort = input('sort', 0);
        $id = input('id', 0);
        $exchange_model = new ExchangeModel();
        return $exchange_model->modifyExchangeSort($sort, $id);
    }


    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isJson()) {
            $exchange_model = new ExchangeModel();

            $exchange_id = input('exchange_id', '');

            $field = 'pe.*,sku.sku_name,sku.price as market_price,sku.sku_image,sku.stock';
            $alias = 'pe';
            $join = [
                [
                    'promotion_exchange_goods peg',
                    'peg.id = pe.exchange_goods_id',
                    'inner'
                ],
                [
                    'goods_sku sku',
                    'sku.sku_id = pe.type_id',
                    'inner'
                ]
            ];
            $condition = [
                [ 'peg.id', '=', $exchange_id ],
                [ 'sku.is_delete', '=', 0 ],
                [ 'sku.goods_state', '=', 1 ],
                [ 'peg.state', '=', 1 ],
            ];

            $goods_list = $exchange_model->getExchangeList($condition, $field, '', null, $alias, $join);
            foreach ($goods_list[ 'data' ] as $k => $v) {
                $goods_list[ 'data' ][ $k ][ 'stock' ] = numberFormat($goods_list[ 'data' ][ $k ][ 'stock' ]);
            }
            return $goods_list;
        }
    }

    /**
     * 获取优惠券列表
     */
    public function getCouponList()
    {
        $coupon_type_model = new CouponTypeModel();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', '');

            $condition = [];
            if ($status !== "") {
                $condition[] = [ 'status', '=', $status ];
            }

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $order = 'sort asc';
            $field = '*';

            $res = $coupon_type_model->getCouponTypePageList($condition, $page, $page_size, $order, $field);
            $exchange_model = new ExchangeModel();
            //兑换名称 兑换图片 兑换库存  兑换价格
            $exchange_list = $exchange_model->getExchangeList([ [ 'type', '=', 2 ] ], 'type_id')[ 'data' ] ?? [];
            if ($exchange_list) {
                $exchange_list = array_column($exchange_list, 'type_id');
            }

            if ($res[ 'data' ][ 'list' ]) {
                foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
                    if (in_array($val[ 'coupon_type_id' ], $exchange_list)) {
                        $res[ 'data' ][ 'list' ][ $key ][ 'is_exit' ] = 1;
                    } else {
                        $res[ 'data' ][ 'list' ][ $key ][ 'is_exit' ] = 0;
                    }
                }
            }
            return $res;

        }
    }

    /**
     *关闭积分兑换
     */
    public function deleteAll()
    {
        if (request()->isJson()) {
            $id = input("exchange_id", '');
            $exchange_model = new ExchangeModel();
            foreach ($id as $k => $v){
                $res = $exchange_model->deleteExchange($v);
            }
            return $res;
        }
    }
}