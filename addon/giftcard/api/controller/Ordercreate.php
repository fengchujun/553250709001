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

namespace addon\giftcard\api\controller;

use app\api\controller\BaseApi;
use addon\giftcard\model\order\GiftCardOrderCreate as OrderCreateModel;

/**
 * 订单创建
 * @author Administrator
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
            'giftcard_id' => $this->params[ 'giftcard_id' ] ?? 0,//礼包id
            'num' => $this->params[ 'num' ] ?? 1,//商品数量(买几套)
            'media_id' => $this->params[ 'media_id' ] ?? 0,
            'card_cover' => $this->params[ 'card_cover' ] ?? '',
            'site_id' => $this->site_id,//站点id
            'member_id' => $this->member_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],
            'buyer_message' => $this->params[ 'buyer_message' ] ?? '',
        ];
        if (empty($data[ 'giftcard_id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->create($data);
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
            'giftcard_id' => $this->params[ 'giftcard_id' ] ?? 0,//礼包id
            'num' => $this->params[ 'num' ] ?? 1,//商品数量(买几套)
            'media_id' => $this->params[ 'media_id' ] ?? 0,
            'card_cover' => $this->params[ 'card_cover' ] ?? '',
            'site_id' => $this->site_id,//站点id
            'member_id' => $this->member_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],
            'buyer_message' => $this->params[ 'buyer_message' ] ?? '',
        ];
        if (empty($data[ 'giftcard_id' ])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->calculate($data);
        return $this->response($res);
    }

}