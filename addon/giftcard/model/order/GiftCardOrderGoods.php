<?php


namespace addon\giftcard\model\order;

use app\model\BaseModel;


class GiftCardOrderGoods extends BaseModel
{

    /**
     * 获取礼品卡订单项信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getOrderGoodsInfo($condition, $field = '*')
    {
        $info = model('giftcard_order_goods')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡订单项列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getOrderGoodsList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_order_goods')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡订单项分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getOrderGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_order_goods')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }


}
