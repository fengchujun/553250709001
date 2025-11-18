<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bundling\model;

use app\model\BaseModel;

/**
 * 优惠套餐
 */
class Bundling extends BaseModel
{
    /**
     * 添加优惠套餐
     * @param $data
     * @param $sku_ids
     * @return array
     */
    public function addBundling($data, $sku_ids)
    {
        if ($data['bl_price'] <= 0) {
            return $this->error([], '优惠套餐价格不能小于或等与0');
        }
        model('promotion_bundling')->startTrans();
        try {
            $sku_id_array = explode(',', $sku_ids);
            $goods_money = 0;
            $sku_array = [];
            foreach ($sku_id_array as $k => $v) {
                $sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $v ] ], 'sku_id,sku_name,price,sku_image,is_virtual');
                if ($sku_info['is_virtual'] == 1) {
                    model('promotion_bundling')->rollback();
                    return $this->error([], '优惠套餐中不能包含虚拟商品');
                }
                unset($sku_info['is_virtual']);
                $goods_money += $sku_info[ 'price' ];
                $sku_array[] = $sku_info;
            }

            $data['goods_money'] = $goods_money;
            $data['update_time'] = time();
            $bundling_id = model('promotion_bundling')->add($data);
            foreach ($sku_array as $k => $v) {
                $v[ 'bl_id' ] = $bundling_id;
                $v[ 'site_id' ] = $data['site_id'];
                $v[ 'promotion_price' ] = $v[ 'price' ] / $goods_money * $data[ 'bl_price' ];
                model('promotion_bundling_goods')->add($v);
            }
            model('promotion_bundling')->commit();
            return $this->success($bundling_id);
        } catch (\Exception $e) {
            model('promotion_bundling')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 编辑优惠套餐
     * @param $data
     * @param $sku_ids
     * @param $condition
     * @return array
     */
    public function editBundling($data, $sku_ids, $condition)
    {
        if ($data['bl_price'] <= 0) {
            return $this->error([], '优惠套餐价格不能小于或等与0');
        }
        $check_condition = array_column($condition, 2, 0);
        model('promotion_bundling')->startTrans();
        try {
            model('promotion_bundling_goods')->delete($condition);
            $sku_id_array = explode(',', $sku_ids);
            $goods_money = 0;
            $sku_array = [];
            foreach ($sku_id_array as $k => $v) {
                $sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $v ] ], 'sku_id,sku_name,price,sku_image,is_virtual');
                if ($sku_info['is_virtual'] == 1) {
                    model('promotion_bundling')->rollback();
                    return $this->error([], '优惠套餐中不能包含虚拟商品');
                }
                unset($sku_info['is_virtual']);
                $sku_info[ 'bl_id' ] = $check_condition[ 'bl_id' ];
                $goods_money += $sku_info[ 'price' ];
                $sku_array[] = $sku_info;
            }
            $data['goods_money'] = $goods_money;
            $data['update_time'] = time();
            $res = model('promotion_bundling')->update($data, $condition);
            foreach ($sku_array as $k => $v) {
                $v[ 'promotion_price' ] = $v[ 'price' ] / $goods_money * $data[ 'bl_price' ];
                $v['site_id'] = $check_condition['site_id'];
                model('promotion_bundling_goods')->add($v);
            }
            model('promotion_bundling')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_bundling')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除优惠套餐
     * @param number $bl_id
     * @param number $site_id
     */
    public function deleteBundling($bl_id, $site_id)
    {
        $condition = array (
            [ 'bl_id', '=', $bl_id ],
            ['site_id', '=', $site_id ]
        );
        $res = model('promotion_bundling')->delete($condition);
        if ($res) {
            model('promotion_bundling_goods')->delete([ 'bl_id' => $bl_id ]);
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 获取优惠套餐详情
     * @param $condition
     * @return array
     */
    public function getBundlingInfo($condition)
    {
        $data = model('promotion_bundling')->getInfo($condition, 'bl_id,bl_name, site_id, site_name, bl_price, goods_money, shipping_fee_type,status');
        return $this->success($data);
    }

    /**
     * 获取优惠套餐详情
     * @param $condition
     * @return array
     */
    public function getBundlingDetail($condition)
    {
        $check_condition = array_column($condition, 2, 0);
        $bl_id = $check_condition['bl_id'] ?? '';
        $data = model('promotion_bundling')->getInfo($condition, 'bl_id,bl_name, site_id, site_name, bl_price, goods_money, shipping_fee_type,status');
        if (!empty($data)) {
            $order = '';
            $alias = 'pbg';
            $condition = [
                [ 'pbg.bl_id', '=', $bl_id ],
                [ 'ngs.is_delete', '=', 0 ]
            ];

            $field = 'ngs.sku_id,ngs.goods_id, ngs.sku_name, ngs.price, ngs.sku_image, ngs.stock,ngs.unit,pbg.promotion_price,g.sale_store';
            $join = [
                [
                    'goods_sku ngs',
                    'pbg.sku_id = ngs.sku_id',
                    'inner'
                ],
                [
                    'goods g',
                    'g.goods_id = ngs.goods_id',
                    'left'
                ],
            ];
            $bundling_goods = model('promotion_bundling_goods')->getList($condition, $field, $order, $alias, $join);
            foreach ($bundling_goods as $k => $v) {
                $bundling_goods[ $k ][ 'stock' ] = numberFormat($bundling_goods[ $k ][ 'stock' ]);
            }

            $data[ 'bundling_goods' ] = $bundling_goods;
            $data[ 'bundling_goods_count' ] = count($data[ 'bundling_goods' ]);
        }
        return $this->success($data);
    }

    /**
     * 获取商品优惠套餐
     * @param $sku_id
     * @return array
     */
    public function getBundlingGoods($sku_id)
    {
        $bundling_ids = model('promotion_bundling_goods')->getList([ [ 'sku_id', '=', $sku_id ] ], 'bl_id');
        $bundling_array = [];
        foreach ($bundling_ids as $k => $v) {
            $temp_result = $this->getBundlingDetail([ [ 'bl_id', '=', $v['bl_id'] ], [ 'status', '=', 1 ] ]);
            if (!empty($temp_result['data'])) $bundling_array[] = $temp_result['data'];
        }
        return $this->success($bundling_array);
    }

    /**
     * 获取优惠餐列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getBundlingPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('promotion_bundling')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 删除商品（需判断套餐是否存在该商品，存在活动关闭）
     * @param $param
     * @return array
     */
    public function cronDeleteGoods($param)
    {
        //获取商品sku_id
        $sku_ids = model('goods_sku')->getColumn([ [ 'goods_id', 'in', (array) $param[ 'goods_id' ] ], [ 'site_id', '=', $param[ 'site_id' ] ] ], 'sku_id');
        if (!empty($sku_ids)) {

            //获取组合套餐id
            $bl_ids = model('promotion_bundling_goods')->getColumn([ [ 'sku_id', 'in', $sku_ids ], [ 'site_id', '=', $param[ 'site_id' ] ] ], 'bl_id');
            if (!empty($bl_ids)) {
                $bl_ids = array_unique($bl_ids);
                //将组合套餐活动下架
                $res = model('promotion_bundling')->update([ 'status' => 0 ], [ [ 'bl_id', 'in', $bl_ids ], [ 'site_id', '=', $param[ 'site_id' ] ] ]);
                return $this->success($res);
            }
        }
    }

    /**
     * 获取组合
     *
     * @param $sku_id
     * @return array
     */
    public function getBundlingGoodsNew($sku_id)
    {
        $goods_id = model('goods_sku')->getInfo([ [ 'sku_id', '=', $sku_id ] ], 'goods_id');
        $sku_list_id = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id[ 'goods_id' ] ] ], 'sku_id');
        $sku_id_arr = [];
        foreach ($sku_list_id as $key => $val) {
            if ($val[ 'sku_id' ] != $sku_id) {
                $sku_id_arr[] = $val[ 'sku_id' ];
            }
        }
        $bundling_list1 = model('promotion_bundling_goods')->getList([ [ 'sku_id', '=', $sku_id ] ], 'bl_id');
        $bundling_list = model('promotion_bundling_goods')->getList([ [ 'sku_id', 'in', $sku_id_arr ] ], 'bl_id');
        $bl_id_arr1 = [];
        if ($bundling_list1) {
            foreach ($bundling_list1 as $kes => $vas) {
                $bl_id_arr1[] = $vas[ 'bl_id' ];
            }
        }

        $bl_id_arr = [];
        if ($bundling_list) {
            foreach ($bundling_list as $ke => $va) {
                $bl_id_arr[] = $va[ 'bl_id' ];
            }
        }

        $bl_id_arr = array_unique(array_merge($bl_id_arr1, $bl_id_arr));
        $bundling_array = [];
        foreach ($bl_id_arr as $k => $v) {
            $temp_result = $this->getBundlingDetail([ [ 'bl_id', '=', $v ], [ 'status', '=', 1 ] ]);
            if (!empty($temp_result['data'])) $bundling_array[] = $temp_result['data'];
        }
        return $this->success($bundling_array);
    }

    /**
     * 获取组合套餐
     * @param $goods_sku_detail_array
     * @return array
     */
    public function getBundlingGoodsInApi($goods_sku_detail_array)
    {
        $goods_sku_detail_array[ 'goods_sku_detail' ][ 'bundling_list' ] = [];
        $goods_sku_detail = $goods_sku_detail_array['goods_sku_detail'];
        //查询商品对应skuid组
        $sku_list_ids = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_sku_detail[ 'goods_id' ] ] ], 'sku_id');
        $sku_ids = array_column($sku_list_ids, 'sku_id');
        $sku_ids = implode(',', $sku_ids);
        $bundling_goods_ids = model('promotion_bundling_goods')->getList([['sku_id', 'in', $sku_ids]], 'bl_id');
        if(empty($bundling_goods_ids)) return $goods_sku_detail_array;
        $bl_ids = [];
        foreach ($bundling_goods_ids as $k => $v)
        {
            if(!in_array($v['bl_id'], $bl_ids))
            {
                $bl_ids[] = $v['bl_id'];
            }
        }
        $bl_ids = implode(',', $bl_ids);
        $bundling_list =   $data = model('promotion_bundling')->getList([['bl_id', 'in', $bl_ids], ['status', '=', 1]], 'bl_id,bl_name, site_id, site_name, bl_price, goods_money, shipping_fee_type,status');
        if(empty($bundling_list))
        {
            $goods_sku_detail_array[ 'goods_sku_detail' ][ 'bundling_list' ] = [];
            return $goods_sku_detail_array;
        }

        $order = '';
        $alias = 'pbg';
        $condition = [
            [ 'pbg.bl_id', 'in', $bl_ids ],
        ];

        $field = 'ngs.sku_id,ngs.goods_id, ngs.sku_name, ngs.price, ngs.sku_image, ngs.stock,ngs.unit,pbg.promotion_price,g.sale_store,pbg.bl_id';
        $join = [
            [
                'goods_sku ngs',
                'pbg.sku_id = ngs.sku_id',
                'inner'
            ],
            [
                'goods g',
                'g.goods_id = ngs.goods_id',
                'left'
            ],
        ];
        $bundling_goods = model('promotion_bundling_goods')->getList($condition, $field, $order, $alias, $join);

        foreach ($bundling_list as $k => $v)
        {
            $bundling_list[$k]['bundling_goods_count'] = 0;
            foreach ($bundling_goods as $k_goods => $v_goods)
            {
                if($v['bl_id'] == $v_goods['bl_id'])
                {
                    $v_goods[ 'stock' ] = numberFormat($v_goods[ 'stock' ]);
                    $bundling_list[$k]['bundling_goods'][] = $v_goods;
                    $bundling_list[$k]['bundling_goods_count'] += 1;
                }
            }
        }
        $goods_sku_detail_array[ 'goods_sku_detail' ][ 'bundling_list' ] = $bundling_list;
        return $goods_sku_detail_array;
    }

    /**
     * 获取组合套餐列表
     * @param $condition
     * @param $field
     * @param $order
     * @param $alias
     * @param $join
     * @param $group
     * @param $limit
     * @return array
     */
    public function getBundlingGoodsList($condition = [], $field = '', $order = '', $alias = '', $join = [], $group = null, $limit = null): array
    {
        $result = model('promotion_bundling')->getList($condition, $field, $order, $alias, $join, $group, $limit);
        return $this->success($result);
    }
}