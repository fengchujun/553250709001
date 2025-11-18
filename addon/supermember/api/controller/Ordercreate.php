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

namespace addon\supermember\api\controller;

use addon\supermember\model\MemberLevelOrder;
use app\api\controller\BaseApi;


/**
 * 会员卡订单
 * @package app\api\controller
 */
class Ordercreate extends BaseApi
{
    /**
     * 订单创建
     * @return false|string
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $data = [
            'level_id' => $this->params[ 'level_id' ],
            'period_unit' => $this->params[ 'period_unit' ],
            'member_id' => $this->member_id,
            'site_id' => $this->site_id
        ];

        $order = new MemberLevelOrder();
        $res = $order->create($data);

        return $this->response($res);
    }
}