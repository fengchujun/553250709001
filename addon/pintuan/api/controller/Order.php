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

namespace addon\pintuan\api\controller;

use addon\pintuan\model\Pintuan;
use addon\pintuan\model\PintuanOrder as PintuanOrderModel;
use app\api\controller\BaseApi;

/**
 * 拼团订单
 * @author Administrator
 *
 */
class Order extends BaseApi
{

    /**
     * 拼团订单详情
     * @return string
     */
    public function detail()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params['id'] ?? 0;//拼团订单主键id
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $pintuan_order_model = new PintuanOrderModel();
        $res = $pintuan_order_model->getPintuanOrderDetail($id, $this->member_id, $this->site_id);
        return $this->response($res);
    }

    /**
     * 拼团列表
     * @return string
     */
    public function page()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $pintuan_order_model = new PintuanOrderModel();
        $condition = array (
            [ "ppo.member_id", "=", $this->member_id ],
            [ "ppo.site_id", "=", $this->site_id ]
        );
        $pintuan_status = $this->params['pintuan_status'] ?? 'all';
        if ($pintuan_status != "all") {
            $condition[] = [ "ppo.pintuan_status", "=", $pintuan_status ];
        } else {
            $condition[] = [ "ppo.pintuan_status", "<>", "0" ];//不查询未支付的拼团
        }

        $page_index = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $res = $pintuan_order_model->getPintuanOrderPageList($condition, $page_index, $page_size, "o.pay_time desc");
        if (!empty($res[ 'data' ][ 'list' ])) {
            foreach ($res[ 'data' ][ 'list' ] as $k => $v) {
                $member_list = $pintuan_order_model->getPintuanOrderList([ [ "group_id", "=", $v[ "group_id" ] ], [ 'pintuan_status', 'in', '2,3' ] ], "member_img,nickname");
                $res[ 'data' ][ 'list' ][ $k ][ 'member_list' ] = $member_list[ 'data' ];
            }

        }
        return $this->response($res);
    }

    /**
     * 拼团会员
     * @return string
     */
    public function pintuanMember()
    {
        $token = $this->checkToken();

        $pintuan_order_model = new PintuanOrderModel();
        $pintuan = new Pintuan();
        $condition = array (
            [ "site_id", "=", $this->site_id ],
            [ "pintuan_status", "=", '3' ],
        );

        $limit = $this->params['num'] ?? 5;
        $field = 'head_id,member_id,member_img,nickname';
        $pintuna_member_list = $pintuan_order_model->getPintuanOrderList($condition, $field, 'id desc', $limit, 'member_id')[ 'data' ] ?? [];

        $pintuna_member_count = $pintuan->getPintuanInfo([
                [ 'pp.site_id', '=', $this->site_id ],
                [ 'pp.status', '=', 1 ],
            ], '(sum(g.sale_num) + sum(g.virtual_sale)) as sale_num', 'pp', [
                [ 'goods g', 'g.goods_id=pp.goods_id', 'inner' ],
            ])[ 'data' ][ 'sale_num' ] ?? 0;

        $data = [
            'member_list' => $pintuna_member_list,
            'pintuan_count' => numberFormat($pintuna_member_count)
        ];
        return $this->response($this->success($data));
    }
}