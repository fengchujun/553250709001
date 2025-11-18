<?php

namespace addon\jielong\api\controller;

use addon\jielong\model\Cart as CartModel;
use app\model\goods\Goods;
use app\api\controller\BaseApi;

class Cart extends BaseApi
{
    /**
     * 添加信息
     */
    public function add()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $sku_id = $this->params['sku_id'] ?? 0;
        $num = $this->params['num'] ?? 0;
        $jielong_id = $this->params['jielong_id'] ?? 0;

        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }
        if (empty($num)) {
            return $this->response($this->error('', 'REQUEST_NUM'));
        }
        $sku_model = new Goods();
        $condition = [ [ 'sku_id', '=', $sku_id ] ];
        $skuInfo = $sku_model->getGoodsSkuInfo($condition, 'sku_id,stock');
        if (!empty($skuInfo[ 'data' ])) {
            if ($skuInfo[ 'data' ][ 'stock' ] < $num) return $this->response($this->error('', '库存不足！'));
        } else {
            return $this->response($this->error('', '商品不存在！'));
        }

        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $cart = new CartModel();
        $data = [
            'site_id' => $this->site_id,
            'member_id' => $token[ 'data' ][ 'member_id' ],
            'sku_id' => $sku_id,
            'num' => $num,
            'jielong_id' => $jielong_id
        ];
        $res = $cart->addCart($data);
        return $this->response($res);
    }

    /**
     * 编辑信息
     */
    public function edit()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $cart_id = $this->params['cart_id'] ?? 0;
        $num = $this->params['num'] ?? 0;
        if (empty($cart_id)) {
            return $this->response($this->error('', 'REQUEST_CART_ID'));
        }
        if (empty($num)) {
            return $this->response($this->error('', 'REQUEST_NUM'));
        }
        $cart = new CartModel();
        $alias = 'pjc';
        $join = [ [ 'goods_sku ngs', 'ngs.sku_id = pjc.sku_id', 'left' ] ];
        $fields = 'ngs.stock,ngs.sku_id';
        $condition = [ [ 'pjc.cart_id', '=', $cart_id ] ];
        $cartSkuInfo = $cart->getCartSkuInfo($condition, $fields, $alias, $join);
        if (!empty($cartSkuInfo[ 'data' ])) {
            if ($cartSkuInfo[ 'data' ][ 'stock' ] < $num) return $this->response($this->error('', '库存不足！'));
        } else {
            return $this->response($this->error('', '商品不存在！'));
        }

        $data = [
            'cart_id' => $cart_id,
            'member_id' => $token[ 'data' ][ 'member_id' ],
            'num' => $num
        ];
        $res = $cart->editCart($data);
        return $this->response($res);
    }

    /**
     * 删除信息
     */
    public function delete()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $cart_id = $this->params['cart_id'] ?? 0;
        if (empty($cart_id)) {
            return $this->response($this->error('', 'REQUEST_CART_ID'));
        }
        $cart = new CartModel();
        $data = [
            'cart_id' => $cart_id,
            'member_id' => $token[ 'data' ][ 'member_id' ]
        ];
        $res = $cart->deleteCart($data);
        return $this->response($res);
    }

    /**
     * 清空购物车
     */
    public function clear()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $cart = new CartModel();
        $data = [
            'member_id' => $token[ 'data' ][ 'member_id' ],
            'jielong_id' => $jielong_id
        ];
        $res = $cart->clearCart($data);
        return $this->response($res);
    }

    /**
     * 商品购物车列表
     */
    public function goodsLists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }
        $cart = new CartModel();
        $list = $cart->getCart($token[ 'data' ][ 'member_id' ], $this->site_id, $jielong_id);
        $goods = new Goods();
        if (!empty($list[ 'data' ])) {
            foreach ($list[ 'data' ] as $k => $v) {
                // 是否参与会员等级折扣
                $goods_member_price = $goods->getGoodsPrice($v[ 'sku_id' ], $this->member_id)[ 'data' ];
                if (!empty($goods_member_price[ 'member_price' ])) {
                    $list[ 'data' ][ $k ][ 'member_price' ] = $goods_member_price[ 'price' ];
                }
            }
        }
        return $this->response($list);
    }

    /**
     * 获取购物车数量
     * @return string
     */
    public function count()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $cart = new CartModel();
//        $list = $cart->getCartCount($token['data']['member_id'],$jielong_id);
        $condition = [
            [ 'gc.jielong_id', '=', $jielong_id ],
            [ 'gc.member_id', '=', $token[ 'data' ][ 'member_id' ] ],
            [ 'gc.site_id', '=', $this->site_id ],
            [ 'gs.goods_state', '=', 1 ],
            [ 'gs.is_delete', '=', 0 ]
        ];
        $list = $cart->getCartList($condition, 'gc.num');
        $list = $list[ 'data' ];
        $count = 0;
        foreach ($list as $k => $v) {
            $count += $v[ 'num' ];
        }
        return $this->response($this->success($count));
    }

    /**
     * 购物车关联列表
     * @return false|string
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $cart = new CartModel();
        $condition = [
            [ 'gc.member_id', '=', $token[ 'data' ][ 'member_id' ] ],
            [ 'gc.site_id', '=', $this->site_id ],
            [ 'gc.jielong_id', '=', $jielong_id ]
        ];
        $list = $cart->getCartList($condition, 'gc.cart_id,gc.sku_id,gc.num');
        return $this->response($list);
    }

    /**
     * 获取会员购物车中商品数量
     * @return false|string
     */
    public function goodsNumCopy()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $goods_id = $this->params[ 'goods_id' ] ?? 0;

        $condition = [
            [ 'gc.jielong_id', '=', $jielong_id ],
            [ 'gc.member_id', '=', $this->member_id ],
            [ 'gc.site_id', '=', $this->site_id ],
            [ 'gs.goods_id', '=', $goods_id ]
        ];

        $join = [
            [
                'goods_sku gs',
                'gc.sku_id = gs.sku_id',
                'left'
            ]
        ];

        $cart = new CartModel();
        $data = $cart->getCartSumCopy($condition, 'gc.num', 'gc', $join);
        return $this->response($data);
    }

    public function goodsNum()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $jielong_id = $this->params['jielong_id'] ?? 0;
        if (empty($jielong_id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }

        $condition = [
            [ 'jielong_id', '=', $jielong_id ],
            [ 'member_id', '=', $this->member_id ],
            [ 'site_id', '=', $this->site_id ],
        ];

        $cart = new CartModel();
        $data = $cart->getCartSum($condition);
        return $this->response($data);
    }
}