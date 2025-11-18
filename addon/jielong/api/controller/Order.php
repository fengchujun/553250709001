<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\jielong\api\controller;

use addon\jielong\model\JielongOrderCommon;
use app\api\controller\BaseApi;
use app\model\order\Order as OrderModel;

/**
 * 接龙活动订单
 */
class Order extends BaseApi
{

    /**
     * 订单分页列表
     * @return false|string
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_common_model = new JielongOrderCommon();
        $condition = array (
            [ "member_id", "=", $this->member_id ],
            [ "site_id", "=", $this->site_id ]
        );
        $order_status = $this->params['order_status'] ?? '';
        switch ( $order_status ) {
            case "0"://待付款
                $condition[] = [ "order_status", "=", 0 ];
                break;
            case "1"://已完成
                $condition[] = [ "order_status", "=", 1 ];
                break;
            case "-1"://已关闭
                $condition[] = [ "order_status", "=", -1 ];
                break;
        }

        $page_index = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $res = $order_common_model->getMemberOrderPageList($condition, $page_index, $page_size, "create_time desc");

        return $this->response($res);
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $order_common_model = new JielongOrderCommon();
        $id = $this->params['id'] ?? 0;
        $result = $order_common_model->getMemberOrderDetail($id, $this->member_id, $this->site_id);

        return $this->response($result);
    }

    /**
     * 关闭订单
     */
    public function close()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_JIELONG_ID'));
        }
        $order_model = new OrderModel();

        $log_data = [
            'uid' => $this->member_id,
            'action_way' => 1
        ];

        $order_common_model = new JielongOrderCommon();
        $order_id = $order_common_model->getJielongOrderId($id);

        $result = $order_model->orderClose($order_id, $log_data);
        return $this->response($result);
    }

}