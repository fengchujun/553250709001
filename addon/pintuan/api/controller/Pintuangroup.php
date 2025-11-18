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

use addon\pintuan\model\PintuanGroup as PintuanGroupModel;
use addon\pintuan\model\PintuanOrder;
use app\api\controller\BaseApi;
use app\model\order\OrderCommon;

/**
 * 拼团组
 */
class Pintuangroup extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $goods_id = $this->params[ 'goods_id' ] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_GOODS_ID'));
        }

        $pintuan_group_model = new PintuanGroupModel();
        $condition = [
            [ 'ppg.goods_id', '=', $goods_id ],
            [ 'ppg.status', '=', 2 ],// 当前状态:0未支付 1拼团失败 2.组团中3.拼团成功
            [ 'ppg.site_id', '=', $this->site_id ]
        ];
        $list = $pintuan_group_model->getPintuanGoodsGroupList($condition);
        return $this->response($list);
    }

    /**
     * 获取开团信息
     * @return false|string
     */
    public function info()
    {
        $group_id = input('group_id', 0);
        $condition = [
            [ 'pg.group_id', '=', $group_id ],
            [ 'pg.site_id', '=', $this->site_id ]
        ];
        $pintuan_group_model = new PintuanGroupModel();
        $info = $pintuan_group_model->getPintuanGroupDetail($condition);
        if (!empty($info)) {
            $info[ 'data' ][ 'is_self' ] = 0;
            $token = $this->checkToken();
            if ($token[ 'code' ] == 0 && $info[ 'data' ][ 'head_id' ] == $this->member_id) $info[ 'data' ][ 'is_self' ] = 1;

            //待支付的参团订单
            $info[ 'data' ][ 'order_id' ] = 0;
            if ($token[ 'code' ] == 0) {
                $pintuan_order = new PintuanOrder();
                $field = 'po.order_id';
                $order_info = $pintuan_order->getPintuanOrderInfo([
                    [ 'po.pintuan_id', '=', $info[ 'data' ][ 'pintuan_id' ] ],
                    [ 'po.site_id', '=', $this->site_id ],
                    [ 'po.group_id', '=', $group_id ],
                    [ 'po.head_id', '<>', $this->member_id ],
                    [ 'o.member_id', '=', $this->member_id ],
                    [ 'o.order_status', '<>', OrderCommon::ORDER_CLOSE ],

                ], $field, 'po', [
                    [ 'order o', 'o.order_id = po.order_id', 'left' ]
                ]);
                $info[ 'data' ][ 'order_id' ] = $order_info[ 'data' ][ 'order_id' ] ?? 0;
            }
        }
        return $this->response($info);
    }
}