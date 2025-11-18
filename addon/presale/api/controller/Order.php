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

namespace addon\presale\api\controller;

use addon\presale\model\PresaleOrder;
use addon\presale\model\PresaleOrderCommon;
use app\api\controller\BaseApi;

/**
 * 预售订单
 */
class Order extends BaseApi
{

    /**
     * 订单列表
     * @return false|string
     */
    public function page()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $condition = [
            [ 'ppo.site_id', '=', $this->site_id ],
            [ 'ppo.member_id', '=', $this->member_id ]
        ];

        $order_status = $this->params['order_status'] ?? '';
        if ($order_status !== '') {
            $condition[] = [ 'ppo.order_status', '=', $order_status ];
        }

        $presale_order_model = new PresaleOrder();
        $list = $presale_order_model->getPresaleOrderPageList($condition, $page, $page_size);
        if (!empty($list[ 'data' ][ 'list' ])) {
            foreach ($list[ 'data' ][ 'list' ] as $k => $v) {
                $action = empty($v[ "order_status_action" ]) ? [] : json_decode($v[ "order_status_action" ], true);
                $member_action = $action[ "member_action" ] ?? [];
                $list[ 'data' ][ 'list' ][ $k ][ 'action' ] = $member_action;
                unset($list[ 'data' ][ 'list' ][ $k ][ 'order_status_action' ]);
            }
        }

        return $this->response($list);
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['order_id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $presale_order_model = new PresaleOrder();
        $condition = [
            [ 'id', '=', $id ],
            [ 'member_id', '=', $this->member_id ],
        ];
        $order_info = $presale_order_model->getPresaleOrderInfo($condition);
        if (!empty($order_info[ 'data' ])) {
            $action = empty($order_info[ 'data' ][ "order_status_action" ]) ? [] : json_decode($order_info[ 'data' ][ "order_status_action" ], true);
            $member_action = $action[ "member_action" ] ?? [];
            $order_info[ 'data' ][ 'action' ] = $member_action;
            unset($order_info[ 'data' ][ 'order_status_action' ]);
        }
        return $this->response($order_info);
    }

    /**
     * 关闭订单
     */
    public function close()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['order_id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $order_common_model = new PresaleOrderCommon();
        $condition = [
            [ 'id', '=', $id ],
            [ 'member_id', '=', $this->member_id ],
        ];

        $res = $order_common_model->depositOrderClose($condition);
        return $this->response($res);
    }

    /**
     * 删除订单
     */
    public function delete()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['order_id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $order_common_model = new PresaleOrderCommon();
        $condition = [
            [ 'id', '=', $id ],
            [ 'member_id', '=', $this->member_id ],
        ];

        $res = $order_common_model->deleteOrder($condition);
        return $this->response($res);
    }

    /**
     * 获取定金或尾款交易流水号
     */
    public function pay()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['id'] ?? ''; //预售订单id

        $order_common_model = new PresaleOrderCommon();
        $res = $order_common_model->getPresaleOrderOutTradeNo($id, $this->member_id, $this->site_id);
        return $this->response($res);
    }
}