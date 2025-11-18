<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\pointexchange\api\controller;

use app\api\controller\BaseApi;
use addon\pointexchange\model\Order as OrderModel;

/**
 * 积分兑换订单
 */
class Order extends BaseApi
{
	/**
	 * 基础信息
	 */
	public function info()
	{
		$token = $this->checkToken();
		if ($token['code'] < 0) return $this->response($token);
		
		$order_id = $this->params['order_id'] ?? 0;
		if (empty($order_id)) {
			return $this->response($this->error('', 'REQUEST_ORDER_ID'));
		}

		$condition = [
			[ 'order_id', '=', $order_id ],
			[ 'member_id', '=', $this->member_id ],
			[ 'site_id', '=', $this->site_id ],
		];
		$field = '*';
		$exchange_model = new OrderModel();
		
		$info = $exchange_model->getOrderInfo($condition, $field);
		return $this->response($info);
	}
	
	public function page()
	{
		$token = $this->checkToken();
		if ($token['code'] < 0) return $this->response($token);
		
		$page = $this->params['page'] ?? 1;
		$page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $order_status = $this->params['order_status'] ?? 'all';
		$condition = [
			[ 'member_id', '=', $this->member_id ],
			[ 'site_id', '=', $this->site_id ],
		];
		if ($order_status != 'all') $condition[] = ['order_status', '=', $order_status];
		$order = 'create_time desc';
		$field = '*';
		
		$exchange_model = new OrderModel();
		$list = $exchange_model->getExchangePageList($condition, $page, $page_size, $order, $field);
		return $this->response($list);
	}

    /**
     * 关闭订单
     * @return false|string
     */
	public function close(){
          $token = $this->checkToken();
          if ($token['code'] < 0) return $this->response($token);

          $order_id = $this->params['order_id'] ?? 0;

          $exchange_model = new OrderModel();
          $result =  $exchange_model->closeOrder($order_id);
          return $this->response($result);
      }
	
}