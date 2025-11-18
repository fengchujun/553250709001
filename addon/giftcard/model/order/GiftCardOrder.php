<?php


namespace addon\giftcard\model\order;

use app\model\BaseModel;

class GiftCardOrder extends BaseModel
{

    public $order_status_list = [
        'topay' => '待支付',
        'complete' => '已完成',
        'close' => '已关闭',
    ];

    public $pay_type = [
        'ONLINE_PAY' => '在线支付',
        'BALANCE' => '余额支付',
        'offlinepay' => '线下支付'
    ];


    /**
     * 获取支付方式
     */
    public function getPayType()
    {
        //获取订单基础的其他支付方式
        $pay_type = $this->pay_type;
        //获取当前所有在线支付方式
        $onlinepay = event('PayType', []);
        if (!empty($onlinepay)) {
            foreach ($onlinepay as $k => $v) {
                $pay_type[ $v[ 'pay_type' ] ] = $v[ 'pay_type_name' ];
            }
        }
        return $pay_type;
    }

    /**
     * 获取礼品卡订单信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getOrderInfo($condition, $field = '*', $alias = '', $join = [])
    {
        $info = model('giftcard_order')->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取礼品卡订单
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getOrderSum($condition, $field = '*', $alias = '', $join = [])
    {
        $info = model('giftcard_order')->getSum($condition, $field, $alias, $join);
        return $this->success($info);
    }


    /**
     * 获取礼品卡订单列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getOrderList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_order')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [])
    {
        $list = model('giftcard_order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 订单详情分页列表
     * @param $params
     * @return array
     */
    public function getOrderDetailPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [])
    {
        $list = $this->getOrderPageList($condition, $page, $page_size, $order, $field, $alias, $join)[ 'data' ] ?? [];
        if (!empty($list[ 'list' ])) {
            $order_id_array = [];
            foreach ($list['list'] as $k => $v){
                $order_id_array[] = $v['order_id'];
            }
            $order_ids = implode(',', $order_id_array);
            $order_goods_list =  model('giftcard_order_goods')->getList(  [['order_id', 'in', $order_ids]], '*');
            foreach ($list[ 'list' ] as $k => $v) {

                foreach ($order_goods_list as $ck => $cv) {
                    $order_goods_list[ $ck ][ 'num' ] = numberFormat($order_goods_list[ $ck ][ 'num' ]);
                    if($v['order_id'] == $cv['order_id'])
                    {
                        $list[ 'list' ][ $k ][ 'order_goods_list' ][] = $cv;
                    }
                }
                $list[ 'list' ][ $k ] = $this->tran($v);
            }
        }

        return $this->success($list);
    }

    /**
     * 订单详情
     * @param $params
     * @return array
     */
    public function getOrderDetail($params)
    {
        $order_id = $params[ 'order_id' ];
        $site_id = $params[ 'site_id' ];
        $member_id = $params[ 'member_id' ] ?? 0;
        $condition = array (
            [ 'go.order_id', '=', $order_id ]
        );
        if ($site_id > 0) $condition[] = [ 'go.site_id', '=', $site_id ];
        if ($member_id > 0) $condition[] = [ 'go.member_id', '=', $member_id ];

        $order_info = $this->getOrderInfo($condition, 'go.*,m.nickname,m.headimg,m.mobile', 'go', [
                [ 'member m', 'go.member_id=m.member_id', 'left' ]
            ])[ 'data' ] ?? [];
        if (empty($order_info))
            return $this->error();

        $order_goods_model = new GiftCardOrderGoods();
        $condition = array (
            [ 'order_id', '=', $order_id ]
        );
        if ($site_id > 0) $condition[] = [ 'site_id', '=', $site_id ];
        if ($member_id > 0) $condition[] = [ 'member_id', '=', $member_id ];
        $order_goods_list = $order_goods_model->getOrderGoodsList($condition)[ 'data' ] ?? [];
        if (empty($order_goods_list))
            return $this->error();

        $order_info[ 'order_goods_list' ] = $order_goods_list;
        $order_info = $this->tran($order_info);
        return $this->success($order_info);
    }

    /**
     * 数据字段转化翻译
     * @param $data
     * @return mixed
     */
    public function tran($data)
    {
        $order_status = $data[ 'order_status' ] ?? '';
        if (!empty($order_status)) {
            $data[ 'order_status_name' ] = $this->order_status_list[ $order_status ];
        }
        return $data;
    }

}
