<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace addon\pointexchange\api\controller;

use app\api\controller\BaseApi;
use addon\pointexchange\model\OrderCreate as OrderCreateModel;

/**
 * 订单创建
 *
 */
class Ordercreate extends BaseApi
{
    /**
     * 创建订单
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '',
            'member_id' => $this->member_id,
            'site_id' => $this->site_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],

            'delivery' => isset($this->params[ 'delivery' ]) && !empty($this->params[ 'delivery' ]) ? json_decode($this->params[ 'delivery' ], true) : [],
            'member_address' => isset($this->params[ 'member_address' ]) && !empty($this->params[ 'member_address' ]) ? json_decode($this->params[ 'member_address' ], true) : [],
            'latitude' => $this->params[ 'latitude' ] ?? '',
            'longitude' => $this->params[ 'longitude' ] ?? '',
//            'buyer_ask_delivery_time' => !empty($this->params[ 'buyer_ask_delivery_time' ]) ? $this->params[ 'buyer_ask_delivery_time' ] : '',

            'buyer_message' => $this->params[ 'buyer_message' ],
        ];

        $res = $order_create->setParam($data)->create();
        return $this->response($res);
    }

    /**
     * 计算信息
     */
    public function calculate()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'order_key' => $this->params[ 'order_key' ] ?? '',
            'member_id' => $this->member_id,
            'site_id' => $this->site_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],

            'delivery' => isset($this->params[ 'delivery' ]) && !empty($this->params[ 'delivery' ]) ? json_decode($this->params[ 'delivery' ], true) : [],
            'member_address' => isset($this->params[ 'member_address' ]) && !empty($this->params[ 'member_address' ]) ? json_decode($this->params[ 'member_address' ], true) : [],
            'latitude' => $this->params[ 'latitude' ] ?? '',
            'longitude' => $this->params[ 'longitude' ] ?? '',
//            'buyer_ask_delivery_time' => !empty($this->params[ 'buyer_ask_delivery_time' ]) ? $this->params[ 'buyer_ask_delivery_time' ] : '',
        ];
        $res = $order_create->setParam($data)->confirm();
        return $this->response($this->success($res));

    }

    /**
     * 待支付订单 数据初始化
     * @return string
     */
    public function payment()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'id' => $this->params[ 'id' ] ?? '',//兑换id
            'num' => $this->params[ 'num' ] ?? 1,//兑换数量(买几套)
            'sku_id' => $this->params[ 'sku_id' ] ?? 0,
            'site_id' => $this->site_id,//站点id
            'member_id' => $this->member_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],
            'latitude' => $this->params[ 'latitude' ] ?? '',
            'longitude' => $this->params[ 'longitude' ] ?? '',

        ];
        if (empty($data[ 'id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        if($data['num'] < 1){
            return $this->response($this->error('', '兑换数量不能小于1'));
        }
        $res = $order_create->setParam($data)->payment();
        return $this->response($this->success($res));
    }

}