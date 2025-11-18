<?php

namespace app\api\controller;

use app\model\goods\GoodsBrowse as GoodsBrowseModel;
use app\model\goods\Goods as GoodsModel;

/**
 * 商品浏览历史
 * @package app\api\controller
 */
class Goodsbrowse extends BaseApi
{
    /**
     * 添加信息
     */
    public function add()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $goods_id = $this->params['goods_id'] ?? 0;
        $sku_id = $this->params['sku_id'] ?? 0;

        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_GOODS_ID'));
        }

        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }
        $goods_browse_model = new GoodsBrowseModel();
        $data = [
            'member_id' => $token[ 'data' ][ 'member_id' ],
            'goods_id' => $goods_id,
            'sku_id' => $sku_id,
            'site_id' => $this->site_id,
            'app_module' => $this->params[ 'app_type' ]
        ];
        $res = $goods_browse_model->addBrowse($data);
        return $this->response($res);
    }

    /**
     * 删除信息
     */
    public function delete()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['id'] ?? '';
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $goods_browse_model = new GoodsBrowseModel();
        $res = $goods_browse_model->deleteBrowse($id, $token[ 'data' ][ 'member_id' ]);
        return $this->response($res);
    }

    /**
     * 分页列表
     */
    public function page()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $this->initStoreData();

        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;

        $goods_browse_model = new GoodsBrowseModel();
        $condition = [
            [ 'ngb.member_id', '=', $token[ 'data' ][ 'member_id' ] ]
        ];

        $alias = 'ngb';
        $field = 'ngb.id,ngb.member_id,ngb.browse_time,ngb.sku_id,ngs.sku_image,ngs.discount_price,ngs.sku_name,ng.goods_id,ng.goods_name,ng.goods_image,(ngs.sale_num + ngs.virtual_sale) as sale_num,ngs.is_free_shipping,ngs.promotion_type,ngs.member_price,ngs.price,ngs.market_price,ngs.is_virtual,ng.goods_image,ng.sale_show,ng.market_price_show,ngs.unit';
        $join = [
            [
                'goods ng',
                'ngb.goods_id = ng.goods_id',
                'inner'
            ],
            [
                'goods_sku ngs',
                'ngb.sku_id = ngs.sku_id',
                'inner'
            ]
        ];
        if ($this->store_data[ 'config' ][ 'store_business' ] == 'store') {
            $join[] = [ 'store_goods_sku sgs', 'ngs.sku_id = sgs.sku_id and sgs.store_id=' . $this->store_id, 'left' ];
            $field = str_replace('ngs.price', 'IFNULL(IF(ngs.is_unify_price = 1,ngs.price,sgs.price), ngs.price) as price', $field);
            $field = str_replace('ngs.discount_price', 'IFNULL(IF(ngs.is_unify_price = 1,ngs.discount_price,sgs.price), ngs.discount_price) as discount_price', $field);
        }

        $res = $goods_browse_model->getBrowsePageList($condition, $page, $page_size, 'ngb.browse_time desc', $field, $alias, $join);

        $goods = new GoodsModel();
        if (!empty($res[ 'data' ][ 'list' ])) {
            foreach ($res[ 'data' ][ 'list' ] as $k => $v) {
                $res[ 'data' ][ 'list' ][ $k ][ 'sale_num' ] = numberFormat($res[ 'data' ][ 'list' ][ $k ][ 'sale_num' ]);
                if ($token[ 'code' ] >= 0) {
                    // 是否参与会员等级折扣
                    $goods_member_price = $goods->getGoodsPrice($v[ 'sku_id' ], $this->member_id)[ 'data' ];
                    if (!empty($goods_member_price[ 'member_price' ])) {
                        $res[ 'data' ][ 'list' ][ $k ][ 'member_price' ] = $goods_member_price[ 'price' ];
                    } else {
                        unset($res[ 'data' ][ 'list' ][ $k ][ 'member_price' ]);
                    }
                } else {
                    unset($res[ 'data' ][ 'list' ][ $k ][ 'member_price' ]);
                }
            }
        }
        return $this->response($res);
    }

}