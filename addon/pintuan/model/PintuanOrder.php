<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\model;

use app\model\BaseModel;
use app\model\order\OrderCommon;
use app\model\order\VirtualOrder;

/**
 * 拼团订单
 */
class PintuanOrder extends BaseModel
{

    /**
     * 开团/参团
     * @param $order
     * @param int $group_id
     * @param $pintuan_id
     * @return array|\multitype
     */
    public function addPintuanOrder($order, $group_id, $pintuan_id)
    {
        $site_id = $order['site_id'];
        $order_id = $order['order_id'];
        $order_no = $order['order_no'];
        $order_type = $order['order_type']['order_type_id'];
        $member_id = $order['member_id'];
        $member_info = $order['member_account'];
        //获取拼团信息
        $pintuan_info = $order['pintuan_info'];
        //判断拼团活动状态
        if ($pintuan_info['status'] != 1) {
            return $this->error('', '该拼团活动已结束');//该拼团活动已结束
        }
        $order_extend = $order['extend'] ?? [];
        if (!empty($order_extend)) {
            $pintuan_num = $order_extend['pintuan_num'];
        }
        //判断是开团还是拼团
        $pintuan_order_data = array(
            'pintuan_id' => $pintuan_id,
            'order_id' => $order_id,
            'order_no' => $order_no,
            'order_type' => $order_type,
            'pintuan_status' => 0,
            'site_id' => $site_id,
            'member_id' => $member_id,
            'member_img' => $member_info['headimg'],
            'nickname' => $member_info['nickname'],
            'pintuan_num' => $pintuan_num ?? 0//阶梯规格
        );
        if ($group_id) {//拼团
            //拼团组信息
            $pintuan_group_info = $order['pintuan_group_info'];
            $result = $this->isCanJoinGroup($group_id, $member_id);
            if ($result['code'] < 0) {
                return $result;
            }
            $pintuan_order_data['group_id'] = $group_id;
            $pintuan_order_data['head_id'] = $pintuan_group_info['head_id'];
        } else {//开团
            $pintuan_order_data['group_id'] = 0;
            $pintuan_order_data['head_id'] = $member_id;
        }
        $res = model('promotion_pintuan_order')->add($pintuan_order_data);
        return $this->success($res);
    }

    /**
     * 判断是否可以参团
     * @param $group_id
     * @param $member_id
     * @return array
     */
    public function isCanJoinGroup($group_id, $member_id)
    {
        if ($group_id > 0) {
            $pintuan_group_model = new PintuanGroup();
            $pintuan_group = $pintuan_group_model->getPintuanGroupInfo(
                [['group_id', '=', $group_id]], 'group_id,head_id,pintuan_num,pintuan_count,status'
            );
            $pintuan_group_info = $pintuan_group['data'];

            if ($pintuan_group_info['head_id'] == $member_id) {
                return $this->error('', '抱歉，您不能参与自己的团');
            }
            if ($pintuan_group_info['status'] != 2) {
                return $this->error('', '该拼团组已失效');
            }
            if ($pintuan_group_info['pintuan_num'] == $pintuan_group_info['pintuan_count']) {
                return $this->error('', '该拼团组已满员，请参加别的拼团或自己开团');
            }
            //判断是否已参团
            $count = model('promotion_pintuan_order')->getCount(
                [
                    ['po.group_id', '=', $group_id],
                    ['po.pintuan_status', 'in', '0,2'],
                    ['po.member_id', '=', $member_id],
                    [' o.order_status', '<>', OrderCommon::ORDER_CLOSE],
                ],
                'po.pintuan_id', 'po',
                [
                    ['order o', 'o.order_id = po.order_id', 'left']
                ]
            );

            if ($count > 0) {
                return $this->error('', '请不要重复参团');
            }
        }

        return $this->success();
    }

    /**
     * @param $order
     * @return array
     */
    public function orderPay($order)
    {
        model('promotion_pintuan_order')->startTrans();
        try {
            //禁止拼团订单在未成团中申请退款
            model('order')->update(['is_enable_refund' => 0], [['order_id', '=', $order['order_id']]]);
            //支付操作查询拼团订单，如果group_id=0,创建组，else，检测成团
            //获取拼团订单信息
            $pintuan_order = $this->getPintuanOrderInfo([['order_id', '=', $order['order_id']]]);
            $pintuan_order_info = $pintuan_order['data'];

            $order_common_model = new OrderCommon();
            $local_result = $order_common_model->orderLock($order['order_id']);
            if (!$local_result) return $this->error();

            $pintuan_group_model = new PintuanGroup();
            if ($pintuan_order_info['group_id'] == 0) {
                //开团
                //创建组
                $group_id = $pintuan_group_model->addPintuanGroup($pintuan_order_info);

                //更新拼团订单组信息
                $pintuan_order_data['group_id'] = $group_id['data'];
                $pintuan_order_data['pintuan_status'] = 2;
                $res = model('promotion_pintuan_order')->update($pintuan_order_data, [['order_id', '=', $order['order_id']]]);
                //更新订单营销状态名称
                model('order')->update(['promotion_status_name' => '拼团中'], [['order_id', '=', $order['order_id']]]);

            } else {//参团

                //更新拼团订单信息
                $pintuan_order_data['pintuan_status'] = 2;
                $res = model('promotion_pintuan_order')->update($pintuan_order_data, [['order_id', '=', $order['order_id']]]);
                //更新订单营销状态名称
                model('order')->update(['promotion_status_name' => '拼团中'], [['order_id', '=', $order['order_id']]]);

                //加入组
                $pintuan_group_model->joinPintuanGroup($pintuan_order_info);
            }

            model('promotion_pintuan_order')->commit();
            return $this->success($res);
        } catch ( \Exception $e ) {
            model('promotion_pintuan_order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 获取拼团订单信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPintuanOrderInfo($condition = [], $field = '*', $alias = '', $join = '')
    {
        $order_info = model('promotion_pintuan_order')->getInfo($condition, $field, $alias, $join);
        return $this->success($order_info);
    }

    /**
     * 获取订单信息
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getPintuanOrderList($condition = [], $field = '*', $order = '', $limit = null, $group_by = '')
    {
        $list = model('promotion_pintuan_order')->getList($condition, $field, $order, '', '', $group_by, $limit);
        return $this->success($list);
    }

    /**
     * 获取订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @return array
     */
    public function getPintuanOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $field = 'ppo.*,ppgs.id as pintuan_goods_id,
        ppg.pintuan_num,ppg.pintuan_count,ppg.is_promotion,ppg.end_time as group_end_time,
        o.site_name,o.pay_time,o.pay_money,o.order_status_name,o.name,o.order_money,o.mobile,o.address,o.full_address,o.order_from_name,o.pay_type_name,
        og.sku_name,og.sku_image';
        $alias = 'ppo';
        $join = [
            ['order o', 'o.order_id = ppo.order_id', 'left'],
            ['order_goods og', 'og.order_id = ppo.order_id', 'left'],
            ['promotion_pintuan_group ppg', 'ppo.group_id = ppg.group_id', 'left'],
            ['promotion_pintuan_goods ppgs', 'og.sku_id = ppgs.sku_id and ppgs.pintuan_id=ppo.pintuan_id', 'inner']
        ];
        $list = model('promotion_pintuan_order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 拼团订单详情
     * @param $order_id
     * @param $member_id
     */
    public function getPintuanOrderDetail($id, $member_id, $site_id)
    {
        $field = 'ppo.*,ppgs.id as pintuan_goods_id,
        ppg.pintuan_num,ppg.pintuan_count,ppg.is_promotion,ppg.end_time as group_end_time,ppgs.pintuan_price,
        pp.group_num,pp.order_num,pp.status,
        gs.discount_price,
        o.site_name,o.pay_time,o.pay_money,o.order_status_name,o.name,o.mobile,o.address,o.full_address,o.order_from_name,o.pay_type_name,o.order_type,o.order_money,
        og.sku_name,og.sku_image';
        $alias = 'ppo';
        $join = [
            ['order o', 'o.order_id = ppo.order_id', 'left'],
            ['order_goods og', 'og.order_id = ppo.order_id', 'left'],
            ['promotion_pintuan_group ppg', 'ppo.group_id = ppg.group_id', 'left'],
            ['promotion_pintuan pp', 'pp.pintuan_id = ppo.pintuan_id', 'left'],
            ['goods_sku gs', 'gs.sku_id = og.sku_id', 'left'],
            ['promotion_pintuan_goods ppgs', 'og.sku_id = ppgs.sku_id and ppgs.pintuan_id=ppo.pintuan_id', 'left']
        ];
        $condition = array(
            ["ppo.id", "=", $id],
            ["ppo.member_id", "=", $member_id],
            ["ppo.site_id", "=", $site_id],
        );
        $info = model('promotion_pintuan_order')->getInfo($condition, $field, $alias, $join);
        //查询参与拼单的会员
        if (!empty($info)) {
            $member_list = model('promotion_pintuan_order')->getList([["group_id", "=", $info["group_id"]], ['pintuan_status', 'in', '2,3']], "member_img,nickname,member_id");
            $info["member_list"] = $member_list;
        }
        return $this->success($info);
    }

    /**
     * 获取拼团订单数量
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPintuanOrderCount($condition = [], $field = '*', $alias = '', $join = '', $group = '')
    {
        $order_info = model('promotion_pintuan_order')->getCount($condition, $field, $alias, $join, $group);
        return $this->success($order_info);
    }

    /**
     * 拼团成功
     * @param $params
     */
    public function pintuanOrderSuccess($params)
    {

    }

    /**
     * 拼团订单关闭
     * @param $condition
     * @return array
     */
    public function pintuanOrderClose($condition)
    {
        //将未支付的修改为失败
        model('promotion_pintuan_order')->update(['pintuan_status' => 1], $condition);
        return $this->success();
    }

    /**
     * 虚拟商品成功后操作
     * @param $order_id
     */
    public function virtualSuccessAction($order_id)
    {
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]]);
        $order_type = $order_info['order_type'];
        if ($order_type == 4) {//虚拟订单
            $virtual_order_model = new VirtualOrder();
            $virtual_order_model->toSend(['order_id' => $order_id]);
        }
    }
}