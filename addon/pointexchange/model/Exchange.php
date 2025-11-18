<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointexchange\model;

use app\model\BaseModel;
use addon\coupon\model\Coupon;
use app\model\goods\Goods;
use app\model\upload\Upload;

/**
 * 积分兑换
 */
class Exchange extends BaseModel
{
    public $type = [
        1 => [
            'name' => 'goods',
            'title' => '兑换商品',
        ],
        2 => [
            'name' => 'coupon',
            'title' => '兑换优惠券',
        ],
        3 => [
            'name' => 'balance',
            'title' => '兑换红包',
        ],
    ];

    /**
     * 添加积分兑换
     * @param array $data
     */
    public function addExchange($data)
    {

        model('promotion_exchange')->startTrans();
        try {
            $exchange_goods_data = [
                'site_id' => $data[ 'site_id' ],
                'type' => $data[ 'type' ],
                'type_name' => $data[ 'type_name' ],
                'state' => $data[ 'state' ],
                'create_time' => time(),
            ];
            if ($data[ 'type' ] == 1) {
                $exchange_goods = $data[ 'goods_data' ];

                foreach ($exchange_goods as $k => $v) {
                    $exist = model('promotion_exchange_goods')->getInfo([ [ 'type', '=', 1 ], [ 'type_id', '=', $v[ 'goods_id' ] ] ], 'id');
                    if (!empty($exist)) {
                        return $this->error('', '商品已存在，请不要重复添加');
                    }
                    $sku_model = new Goods();
                    $sku_info = $sku_model->getGoodsSkuInfo([ [ 'sku_id', '=', $v[ 'sku_list' ][ 0 ][ 'sku_id' ] ] ], 'sku_name,sku_image,price,stock,goods_content')[ 'data' ];
                    $exchange_goods_data[ 'type_id' ] = $v[ 'goods_id' ];
                    $exchange_goods_data[ 'name' ] = $sku_info[ 'sku_name' ];
                    $exchange_goods_data[ 'image' ] = $sku_info[ 'sku_image' ];
                    $exchange_goods_data[ 'point' ] = $v[ 'sku_list' ][ 0 ][ 'point' ];
                    $exchange_goods_data[ 'price' ] = $v[ 'sku_list' ][ 0 ][ 'exchange_price' ];
                    $exchange_goods_data[ 'content' ] = $sku_info[ 'goods_content' ];
                    $exchange_goods_data[ 'rule' ] = $data[ 'rule' ];
                    $exchange_goods_data[ 'pay_type' ] = $v[ 'sku_list' ][ 0 ][ 'exchange_price' ] ? 1 : 0;
                    $exchange_goods_id = model('promotion_exchange_goods')->add($exchange_goods_data);

                    foreach ($v[ 'sku_list' ] as $index => $item) {
                        $sku_info = $sku_model->getGoodsSkuInfo([ [ 'sku_id', '=', $item[ 'sku_id' ] ] ], 'sku_name,sku_image,price,stock,goods_content')[ 'data' ];
                        $exchange_data = [
                            'site_id' => $data[ 'site_id' ],
                            'exchange_goods_id' => $exchange_goods_id,
                            'type' => $data[ 'type' ],
                            'type_name' => $data[ 'type_name' ],
                            'type_id' => $item[ 'sku_id' ],
                            'state' => $data[ 'state' ],
                            'rule' => $data[ 'rule' ],
                            'name' => $sku_info[ 'sku_name' ],
                            'image' => $sku_info[ 'sku_image' ],
                            'stock' => $sku_info[ 'stock' ],
                            'pay_type' => empty($item[ 'exchange_price' ]) ? 0 : 1,
                            'point' => $item[ 'point' ],
                            'market_price' => $sku_info[ 'price' ],
                            'price' => $item[ 'exchange_price' ],
                            'limit_num' => $item[ 'limit_num' ],
                            'create_time' => time(),
                            'content' => $sku_info[ 'goods_content' ],

                            'is_free_shipping' => $data[ 'is_free_shipping' ], //是否免邮（0不免邮  1免邮）
                            'delivery_type' => $data[ 'delivery_type' ] ?? 1, //运费类型（ 0 固定运费  1运费模板  2按照商品）
                            'shipping_template' => $data[ 'shipping_template' ], //运费模板
                            'delivery_price' => $data[ 'delivery_price' ] ?? 0 //运费
                        ];
                        model('promotion_exchange')->add($exchange_data);

                    }
                }
            } elseif ($data[ 'type' ] == 2) {
                $exist = model('promotion_exchange_goods')->getInfo([ [ 'type', '=', 2 ], [ 'type_id', '=', $data[ 'coupon_type_id' ] ] ], 'id');
                if (!empty($exist)) {
                    return $this->error('', '该优惠券已存在，请不要重复添加');
                }

                $coupon = new Coupon();
                $coupon_type_info = $coupon->getCouponTypeInfo([ [ 'coupon_type_id', '=', $data[ 'coupon_type_id' ] ] ], 'coupon_type_id,coupon_name,money,count,image,status,type,discount')[ 'data' ];

                $exchange_goods_data[ 'type_id' ] = $data[ 'coupon_type_id' ];
                $exchange_goods_data[ 'name' ] = $coupon_type_info[ 'coupon_name' ];
                $exchange_goods_data[ 'image' ] = $coupon_type_info[ 'image' ];
                $exchange_goods_data[ 'point' ] = $data[ 'point' ];
                $exchange_goods_data[ 'content' ] = $data[ 'content' ];

                $exchange_goods_id = model('promotion_exchange_goods')->add($exchange_goods_data);

                $exchange_data = [
                    'site_id' => $data[ 'site_id' ],
                    'exchange_goods_id' => $exchange_goods_id,
                    'type' => $data[ 'type' ],
                    'type_name' => $data[ 'type_name' ],
                    'state' => $data[ 'state' ],
                    'type_id' => $data[ 'coupon_type_id' ],
                    'name' => $coupon_type_info[ 'coupon_name' ],
                    'image' => $coupon_type_info[ 'image' ],
                    'stock' => $data[ 'stock' ],
                    'pay_type' => 0,
                    'point' => $data[ 'point' ],
                    'create_time' => time(),
                    'content' => $data[ 'content' ],
                ];
                if ($coupon_type_info[ 'type' ] == 'reward') {
                    $exchange_data[ 'market_price' ] = $coupon_type_info[ 'money' ];
                } elseif ($coupon_type_info[ 'type' ] == 'discount') {
                    $exchange_data[ 'market_price' ] = $coupon_type_info[ 'discount' ];
                }

                model('promotion_exchange')->add($exchange_data);

            } elseif ($data[ 'type' ] == 3) {

                $exchange_goods_data[ 'name' ] = $data[ 'name' ];
                $exchange_goods_data[ 'image' ] = $data[ 'image' ];
                $exchange_goods_data[ 'point' ] = $data[ 'point' ];
                $exchange_goods_data[ 'balance' ] = $data[ 'balance' ];
                $exchange_goods_data[ 'content' ] = $data[ 'content' ];

                $exchange_goods_id = model('promotion_exchange_goods')->add($exchange_goods_data);

                $exchange_data = [
                    'site_id' => $data[ 'site_id' ],
                    'exchange_goods_id' => $exchange_goods_id,
                    'type' => $data[ 'type' ],
                    'type_name' => $data[ 'type_name' ],
                    'state' => $data[ 'state' ],
                    'name' => $data[ 'name' ],
                    'image' => $data[ 'image' ],
                    'stock' => $data[ 'stock' ],
                    'pay_type' => 0,
                    'point' => $data[ 'point' ],
                    'balance' => $data[ 'balance' ],
                    'create_time' => time(),
                    'content' => $data[ 'content' ],
                ];

                model('promotion_exchange')->add($exchange_data);
            }

            model('promotion_exchange')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_exchange')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 编辑积分兑换
     * @param array $data
     */
    public function editExchange($data)
    {
        model('promotion_exchange')->startTrans();
        try {
            $exchange_goods_id = $data[ 'id' ];
            if ($data[ 'type' ] == 1) {
                $goods_data = $data[ 'goods_data' ];

                $sku_model = new Goods();
                $sku_info = $sku_model->getGoodsSkuInfo([ [ 'sku_id', '=', $goods_data[ 0 ][ 'sku_id' ] ] ], 'sku_name,sku_image,price,stock,goods_content')[ 'data' ];
                $exchange_goods_data = [
                    'modify_time' => time(),
                    'pay_type' => $goods_data[ 0 ][ 'exchange_price' ] ? 1 : 0,
                ];
                $exchange_goods_data[ 'name' ] = $sku_info[ 'sku_name' ];
                $exchange_goods_data[ 'image' ] = $sku_info[ 'sku_image' ];
                $exchange_goods_data[ 'point' ] = $goods_data[ 0 ][ 'point' ];
                $exchange_goods_data[ 'price' ] = $goods_data[ 0 ][ 'exchange_price' ];
                $exchange_goods_data[ 'content' ] = $sku_info[ 'goods_content' ];
                $exchange_goods_data[ 'rule' ] = $data[ 'rule' ];
                $exchange_goods_data[ 'state' ] = $data[ 'state' ];
                model('promotion_exchange_goods')->update($exchange_goods_data, [ [ 'id', '=', $exchange_goods_id ] ]);

                model('promotion_exchange')->delete([ [ 'exchange_goods_id', '=', $exchange_goods_id ] ]);

                foreach ($goods_data as $index => $item) {
                    $sku_info = $sku_model->getGoodsSkuInfo([ [ 'sku_id', '=', $item[ 'sku_id' ] ] ], 'sku_name,sku_image,price,stock,goods_content')[ 'data' ];
                    $exchange_data = [
                        'site_id' => $data[ 'site_id' ],
                        'exchange_goods_id' => $exchange_goods_id,
                        'type' => $data[ 'type' ],
                        'type_name' => $data[ 'type_name' ],
                        'type_id' => $item[ 'sku_id' ],
                        'state' => $data[ 'state' ],
                        'rule' => $data[ 'rule' ],
                        'name' => $sku_info[ 'sku_name' ],
                        'image' => $sku_info[ 'sku_image' ],
                        'stock' => $sku_info[ 'stock' ],
                        'pay_type' => empty($item[ 'exchange_price' ]) ? 0 : 1,
                        'point' => $item[ 'point' ],
                        'market_price' => $sku_info[ 'price' ],
                        'price' => $item[ 'exchange_price' ],
                        'limit_num' => $item[ 'limit_num' ],
                        'create_time' => time(),
                        'content' => $sku_info[ 'goods_content' ],

                        'is_free_shipping' => $data[ 'is_free_shipping' ], //是否免邮（0不免邮  1免邮）
                        'delivery_type' => $data[ 'delivery_type' ] ?? 1, //运费类型（ 0 固定运费  1运费模板  2按照商品）
                        'shipping_template' => $data[ 'shipping_template' ], //运费模板
                        'delivery_price' => $data[ 'delivery_price' ] ?? 0 //运费

                    ];
                    model('promotion_exchange')->add($exchange_data);
                }

            } else if ($data[ 'type' ] == 2) {
                $coupon = new Coupon();
                $coupon_type_info = $coupon->getCouponTypeInfo([ [ 'coupon_type_id', '=', $data[ 'coupon_type_id' ] ] ], 'coupon_type_id,coupon_name,money,count,image,status,type,discount')[ 'data' ];
                $exchange_goods_data = [];
                $exchange_goods_data[ 'type_id' ] = $data[ 'coupon_type_id' ];
                $exchange_goods_data[ 'name' ] = $coupon_type_info[ 'coupon_name' ];
                $exchange_goods_data[ 'image' ] = $coupon_type_info[ 'image' ];
                $exchange_goods_data[ 'point' ] = $data[ 'point' ];
                $exchange_goods_data[ 'content' ] = $data[ 'content' ];
                $exchange_goods_data[ 'state' ] = $data[ 'state' ];

                model('promotion_exchange_goods')->update($exchange_goods_data, [ [ 'id', '=', $exchange_goods_id ] ]);
                $exchange_data = [
                    'site_id' => $data[ 'site_id' ],
                    'exchange_goods_id' => $exchange_goods_id,
                    'type' => $data[ 'type' ],
                    'type_name' => $data[ 'type_name' ],
                    'state' => $data[ 'state' ],
                    'type_id' => $data[ 'coupon_type_id' ],
                    'name' => $coupon_type_info[ 'coupon_name' ],
                    'image' => $coupon_type_info[ 'image' ],
                    'stock' => $data[ 'stock' ],
                    'pay_type' => 0,
                    'point' => $data[ 'point' ],
                    'create_time' => time(),
                    'content' => $data[ 'content' ],
                ];
                if ($coupon_type_info[ 'type' ] == 'reward') {
                    $exchange_data[ 'market_price' ] = $coupon_type_info[ 'money' ];
                } elseif ($coupon_type_info[ 'type' ] == 'discount') {
                    $exchange_data[ 'market_price' ] = $coupon_type_info[ 'discount' ];
                }
                model('promotion_exchange')->delete([ [ 'exchange_goods_id', '=', $exchange_goods_id ] ]);
                model('promotion_exchange')->add($exchange_data);

            } else if ($data[ 'type' ] == 3) {
                $exchange_goods_data = [];
                $exchange_goods_data[ 'name' ] = $data[ 'name' ];
                $exchange_goods_data[ 'image' ] = $data[ 'image' ];
                $exchange_goods_data[ 'point' ] = $data[ 'point' ];
                $exchange_goods_data[ 'balance' ] = $data[ 'balance' ];
                $exchange_goods_data[ 'content' ] = $data[ 'content' ];
                $exchange_goods_data[ 'state' ] = $data[ 'state' ];

                $exchange_goods_info = model('promotion_exchange_goods')->getInfo([ [ 'id', '=', $exchange_goods_id ] ]);
                if (!empty($exchange_goods_info[ 'image' ]) && !empty($data[ 'image' ]) && $exchange_goods_info[ 'image' ] != $data[ 'image' ]) {
                    $upload_model = new Upload();
                    $upload_model->deletePic($exchange_goods_info[ 'image' ], $data[ 'site_id' ]);
                }

                model('promotion_exchange_goods')->update($exchange_goods_data, [ [ 'id', '=', $exchange_goods_id ] ]);

                $exchange_data = [
                    'site_id' => $data[ 'site_id' ],
                    'exchange_goods_id' => $exchange_goods_id,
                    'type' => $data[ 'type' ],
                    'type_name' => $data[ 'type_name' ],
                    'state' => $data[ 'state' ],
                    'name' => $data[ 'name' ],
                    'image' => $data[ 'image' ],
                    'stock' => $data[ 'stock' ],
                    'pay_type' => 0,
                    'point' => $data[ 'point' ],
                    'balance' => $data[ 'balance' ],
                    'create_time' => time(),
                    'content' => $data[ 'content' ],
                ];
                model('promotion_exchange')->delete([ [ 'exchange_goods_id', '=', $exchange_goods_id ] ]);
                model('promotion_exchange')->add($exchange_data);

            }

            model('promotion_exchange')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_exchange')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 删除积分兑换
     * @param string $ids
     */
    public function deleteExchange($ids)
    {
        $list = model("promotion_exchange")->getList([ [ 'exchange_goods_id', 'in', $ids ] ]);
        if ($list) {
            foreach ($list as $k => $v) {
                if (!empty($v[ 'image' ])) {
                    $upload_model = new Upload();
                    $upload_model->deletePic($v[ 'image' ], $v[ 'site_id' ]);
                }
            }
        }

        model("promotion_exchange")->delete([ [ 'exchange_goods_id', 'in', $ids ] ]);
        model("promotion_exchange_goods")->delete([ [ 'id', 'in', $ids ] ]);
        return $this->success();
    }

    /**
     * 获取积分兑换信息
     * @param int $id
     */
    public function getExchangeInfo($id, $field = '*', $sku_id = 0)
    {

        $condition = [
            [ 'exchange_goods_id', '=', $id ]
        ];
        if ($sku_id) $condition[] = [ 'type_id', '=', $sku_id ];

        $info = model("promotion_exchange")->getInfo($condition, $field);
        if (!empty($info) && !empty($info[ 'type' ])) {
            switch ( $info[ 'type' ] ) {
                case 1:
                    //商品
                    $goods = new Goods();
                    $goods_sku_info = $goods->getGoodsSkuInfo([ [ 'sku_id', '=', $info[ 'type_id' ], [ 'goods_state', '=', 1 ], [ 'is_delete', '=', 0 ] ] ], 'sku_id,sku_name,stock')[ 'data' ];
                    if (!empty($goods_sku_info)) {
                        $goods_sku_info[ 'stock' ] = numberFormat($goods_sku_info[ 'stock' ]);
                        $info = array_merge($info, $goods_sku_info);
                    } else {
                        $info = [];
                    }
                    break;
                case 2:
                    //优惠券
                    $coupon = new Coupon();
                    $coupon_type_info = $coupon->getCouponTypeInfo([ [ 'coupon_type_id', '=', $info[ 'type_id' ] ] ], 'type as coupon_type,discount_limit,count,lead_count')[ 'data' ];

                    if (!empty($coupon_type_info)) {
                        $info = array_merge($info, $coupon_type_info);
                    } else {
                        $info = [];
                    }
                    break;
                case 3:
                    //余额红包
                    break;
            }
        }
        return $this->success($info);
    }

    /**
     * 获取积分兑换商品详情
     * @param $id
     * @param $site_id
     * @return array
     */
    public function getExchangeGoodsDetail($id, $site_id)
    {
        $info = model("promotion_exchange_goods")->getInfo([ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'id,type,type_name,type_id,name,image,pay_type,point,price,delivery_price,balance,state,content,rule');
        // 兑换类型，1：商品，2：优惠券，3：红包
        switch ( $info[ 'type' ] ) {
            case 1:
                //商品
                $goods_sku = model('goods_sku')->getList([ [ 'goods_id', '=', $info[ 'type_id' ] ], [ 'is_delete', '=', 0 ], [ 'goods_state', '=', 1 ] ], 'stock, price,sku_id,sku_name,discount_price,stock as goods_stock,sku_image,sku_images,goods_id,site_id,goods_content');
                $exchange_list = model("promotion_exchange")->getList([ [ 'exchange_goods_id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'limit_num, id,type,type_name,type_id,name,image,pay_type,point,price,delivery_price,balance,state,content,delivery_type,is_free_shipping,shipping_template,rule');

                foreach ($goods_sku as $k => $v) {
                    $goods_sku[ $k ][ 'is_select' ] = 0;
                    $goods_sku[ $k ][ 'exchange_price' ] = 0;
                    $goods_sku[ $k ][ 'point' ] = 0;
                    $goods_sku[ $k ][ 'limit_num' ] = 0;
                    $goods_sku[ $k ][ 'stock' ] = numberFormat($goods_sku[ $k ][ 'stock' ]);
                    $goods_sku[ $k ][ 'goods_stock' ] = numberFormat($goods_sku[ $k ][ 'goods_stock' ]);
                    foreach ($exchange_list as $key => $val) {
                        if ($val[ 'type_id' ] == $v[ 'sku_id' ]) {
                            $goods_sku[ $k ][ 'is_select' ] = 1;
                            $goods_sku[ $k ][ 'exchange_price' ] = $val[ 'price' ];
                            $goods_sku[ $k ][ 'limit_num' ] = $val[ 'limit_num' ];
                            $goods_sku[ $k ][ 'point' ] = $val[ 'point' ];
                        }
                    }
                }

                $info[ 'goods_sku' ] = $goods_sku;
                $info[ 'exchange_goods' ] = $exchange_list;

                break;
            case 2:
                //优惠券
                $coupon = new Coupon();
                $coupon_type_info = $coupon->getCouponTypeInfo([ [ 'coupon_type_id', '=', $info[ 'type_id' ] ] ], 'coupon_type_id,coupon_name,money,count as stock,status,lead_count,max_fetch,at_least,end_time,validity_type,fixed_term,goods_type,is_limit,type as coupon_type,discount_limit,discount')[ 'data' ];
                $exchange_info = model("promotion_exchange")->getInfo([ [ 'exchange_goods_id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'market_price,stock');
                if (!empty($coupon_type_info)) {
                    $info = array_merge($info, $coupon_type_info);
                } else {
                    $info = [];
                }
                $info = array_merge($info, $exchange_info);

                break;
            case 3:
                //余额红包
                $exchange_info = model("promotion_exchange")->getInfo([ [ 'exchange_goods_id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'stock');
                if (!empty($exchange_info)) {
                    $info = array_merge($info, $exchange_info);
                } else {
                    $info = [];
                }
                break;
        }
        if (!empty($info)) {
            return $this->success($info);
        } else {
            return $this->error('', '该兑换物品已失效');
        }
    }

    /**
     * 获取积分兑换列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getExchangeList($condition = [], $field = '*', $order = '', $limit = null, $alias = '', $join = [])
    {
        $list = model('promotion_exchange')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取积分兑换列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getExchangePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*', $alias = '', $join = [])
    {
        $list = model('promotion_exchange')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取积分兑换列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getExchangeGoodsList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_exchange_goods')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取积分兑换列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getExchangeGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*', $alias = '', $join = [])
    {
        $list = model('promotion_exchange_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        if(!empty($list['list']))
        {
            $goods_id_array = [];
            foreach ($list['list'] as $k => $v)
            {
                if($v['type'] == 1)
                {
                    $goods_id_array[] = $v['type_id'];
                }
            }
            if(!empty($goods_id_array))
            {
                $goods_array = [];
                $goods_ids = implode(",", $goods_id_array);
                $goods_list = model('goods')->getList([ [ 'goods_id', 'in', $goods_ids ], ['is_delete', '=', 0] ], "goods_id, goods_name, is_delete");
                if(!empty($goods_list))
                {
                    $key = array_column($goods_list, 'goods_id');
                    $goods_array = array_combine($key, $goods_list);
                }

            }
            foreach ($list[ 'list' ] as $k => $v) {
                if ($v[ 'type' ] == 1) {
                    $goods_info = $goods_array[$v['type_id']] ?? [];
                    if (empty($goods_info)) {
                        unset($list[ 'list' ][ $k ]);
                    } else {
                        if ($goods_info[ 'is_delete' ] == 1) {
                            unset($list[ 'list' ][ $k ]);
                        } else {
                            $list[ 'list' ][ $k ][ 'g_name' ] = $goods_info[ 'goods_name' ];
                        }
                    }

                }
            }
        }

        return $this->success($list);
    }

    /**
     * 增加库存
     * @param $param
     */
    public function incStock($param)
    {
        $condition = array (
            [ "id", "=", $param[ "id" ] ]
        );
        $num = $param[ "num" ];
        $info = model("promotion_exchange")->getInfo($condition, "stock, name");
        if (empty($info))
            return $this->error(-1, "");

        //编辑sku库存
        $result = model("promotion_exchange")->setInc($condition, "stock", $num);

        return $this->success($result);
    }

    /**
     * 减少库存
     * @param $param
     */
    public function decStock($param)
    {
        $condition = array (
            [ "id", "=", $param[ "id" ] ]
        );
        $num = $param[ "num" ];

        $info = model("promotion_exchange")->getInfo($condition, "stock, name, type");
        if (empty($info))
            return $this->error();

        if ($info[ 'type' ] == 2 && $info[ 'stock' ] == -1) {
            return $this->success();
        }

        if ($info[ "stock" ] < 0) {
            return $this->error('', $info[ "name" ] . "库存不足！");
        }

        //编辑sku库存
        $result = model("promotion_exchange")->setDec($condition, "stock", $num);
        if ($result === false)
            return $this->error();

        return $this->success($result);
    }

    /**
     * 修改标签排序
     * @param $sort
     * @param $id
     * @return array
     */
    public function modifyExchangeSort($sort, $id)
    {
        $res = model('promotion_exchange_goods')->update([ 'sort' => $sort ], [ [ 'id', '=', $id ] ]);
        return $this->success($res);
    }

    /**
     * 兑换商品详情
     * @param array $condition
     * @param int $type
     * @return array
     */
    public function getExchangeDetail($condition = [], $type = 1)
    {
        $alias = 'pe';
        $field = 'peg.type,peg.id as exchange_id, pe.id,pe.type_id as sku_id,peg.type_id as goods_id,pe.pay_type,pe.point, pe.price as exchange_price, pe.limit_num, 
            pe.delivery_price,pe.balance,pe.state,pe.content,pe.exchange_goods_id,pe.rule';
        $join = [
            [ 'promotion_exchange_goods peg', 'pe.exchange_goods_id = peg.id', 'inner' ]
        ];
        if ($type == 1) {
            $condition[] = [ 'g.goods_state', '=', 1 ];
            $condition[] = [ 'g.is_delete', '=', 0 ];

            $field .= ',sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.stock,sku.click_num,
            (sku.sale_num + sku.virtual_sale) as sale_num,sku.collect_num,sku.sku_image,
            sku.sku_images,sku.site_id,sku.goods_content,sku.goods_state,sku.is_virtual,
            sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,
            sku.unit,sku.video_url,sku.evaluate,sku.goods_service_ids,g.goods_image,g.goods_stock,g.goods_name,sku.qr_id,g.stock_show,g.sale_show';

            $join[] = [ 'goods_sku sku', 'pe.type_id = sku.sku_id', 'inner' ];
            $join[] = [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ];
        } else if ($type == 2) {
            $join[] = [ 'promotion_coupon_type pct', 'pct.coupon_type_id = peg.type_id', 'inner' ];
            $field .= ',pe.stock,pct.type as coupon_type,pct.discount_limit,pct.image,pct.coupon_name as name,pct.count, pct.lead_count
            ,pct.money, pct.discount, pct.at_least, pct.validity_type,pct.fixed_term,pct.end_time, pct.image';
//            $condition[] = [ 'pct.is_show', '=', 1 ];
//            $condition[] = [ 'pct.is_forbidden', '=', 0 ];

        } else if ($type == 3) {
            $field .= ',pe.stock,pe.name,pe.image,pe.balance';
        }
        $info = model('promotion_exchange')->getInfo($condition, $field, $alias, $join);
        if (!empty($info)) {
            if (isset($info[ 'stock' ])) {
                $info[ 'stock' ] = numberFormat($info[ 'stock' ]);
            }
            if (isset($info[ 'sale_num' ])) {
                $info[ 'sale_num' ] = numberFormat($info[ 'sale_num' ]);
            }
        }
        return $this->success($info);
    }

    /**
     * 兑换商品详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getExchangeSkuList($condition = [], $type = 1)
    {
        $alias = 'pe';
        $field = 'peg.type,peg.id as exchange_id, pe.id,pe.type_id as sku_id,peg.type_id as goods_id,pe.pay_type,pe.point, pe.price as exchange_price, pe.limit_num, 
            pe.delivery_price,pe.balance,pe.state,pe.exchange_goods_id,pe.rule';
        $join = [
            [ 'promotion_exchange_goods peg', 'pe.exchange_goods_id = peg.id', 'inner' ]
        ];
        if ($type == 1) {
            $condition[] = [ 'g.goods_state', '=', 1 ];
            $condition[] = [ 'g.is_delete', '=', 0 ];

            $field .= ',sku.sku_name,sku.sku_spec_format,sku.price,sku.stock,sku.sku_image,sku.sku_images,sku.goods_spec_format,g.goods_image';

            $join[] = [ 'goods_sku sku', 'pe.type_id = sku.sku_id', 'inner' ];
            $join[] = [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ];
        } else if ($type == 2) {
            $join[] = [ 'promotion_coupon_type pct', 'pct.coupon_type_id = peg.type_id', 'inner' ];
            $field .= ',pe.stock,pct.type as coupon_type,pct.discount_limit,pct.image,pct.coupon_name as name,pct.count, pct.lead_count
            ,pct.money, pct.discount, pct.at_least, pct.validity_type,pct.fixed_term,pct.end_time, pct.image';

        } else if ($type == 3) {
            $field .= ',pe.stock,pe.name,pe.image,pe.balance';
        }
        $list = model('promotion_exchange')->getList($condition, $field, '', $alias, $join);
        foreach ($list as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 判断规格值是否禁用
     * @param $id
     * @param $site_id
     * @param string $goods_spec_format
     * @return int|mixed
     */
    public function getGoodsSpecFormat($id, $site_id, $goods_spec_format = '')
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_exchange')->getColumn([ [ 'exchange_goods_id', '=', $id ], [ 'site_id', '=', $site_id ] ], 'type_id');
        $goods_model = new Goods();
        $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        return $res;
    }

}