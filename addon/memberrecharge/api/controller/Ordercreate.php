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

namespace addon\memberrecharge\api\controller;

use app\api\controller\BaseApi;
use addon\memberrecharge\model\MemberrechargeOrderCreate as OrderCreateModel;

/**
 * 订单创建
 * @author Administrator
 *
 */
class Ordercreate extends BaseApi
{
    /**
     * 创建订单
     * @return string
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $order_create = new OrderCreateModel();
        $data = [
            'recharge_id' => $this->params['recharge_id'] ?? 0,//套餐id
            'face_value' => $this->params['face_value'] ?? 0,//自定义充值面额
            'member_id' => $this->member_id,
            'order_from' => $this->params[ 'app_type' ],
            'order_from_name' => $this->params[ 'app_type_name' ],
            'site_id' => $this->site_id,
            'store_id' => $this->store_id
        ];

        if ($data[ 'recharge_id' ] == 0 && $data[ 'face_value' ] == 0) {
            return $this->response($this->error('', '缺少必填参数数据'));
        }
        $res = $order_create->create($data);
        return $this->response($res);
    }

}