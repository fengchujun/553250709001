<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\model\card;

use app\model\BaseModel;

/**
 * 礼品卡使用记录工具类
 * Class CardUse
 * @package addon\giftcard\model\card
 */
class CardUse extends BaseModel
{

    /**
     * 生成礼品卡记录
     * @param $params
     * @return array
     */
    public function addCardUseRecords($params)
    {
        $member_id = $params[ 'member_id' ] ?? 0;
        $giftcard_id = $params[ 'giftcard_id' ];
        $card_right_type = $params[ 'card_right_type' ];
        $use_order_id = $params[ 'use_order_id' ] ?? 0;
        $data = array (
            'site_id' => $params[ 'site_id' ],
            'card_id' => $params[ 'card_id' ],
            'member_card_id' => $params[ 'member_card_id' ],
            'giftcard_id' => $giftcard_id,
            'member_id' => $member_id,
            'use_time' => time(),
            'card_right_type' => $card_right_type,
            'order_id' => $use_order_id
        );
        $records_id = model('giftcard_card_use_records')->add($data);

        $goods_list = $params[ 'goods_list' ];
        foreach ($goods_list as $k => $v) {
            $v[ 'order_id' ] = $use_order_id;
            $v[ 'records_id' ] = $records_id;
            $this->addCardUseRecordsGoods($v);
        }
        return $this->success($records_id);
    }

    /**
     * 礼品卡项使用记录
     * @param $params
     * @return array
     */
    public function addCardUseRecordsGoods($params)
    {
        $data = array (
            'site_id' => $params[ 'site_id' ],
            'records_id' => $params[ 'records_id' ],//使用记录id
            'card_goods_id' => $params[ 'id' ],
            'sku_id' => $params[ 'sku_id' ] ?? 0,
            'sku_name' => $params[ 'sku_name' ] ?? '',
            'sku_image' => $params[ 'sku_image' ] ?? '',
            'sku_no' => $params[ 'sku_no' ] ?? '',
            'goods_id' => $params[ 'goods_id' ] ?? 0,
            'goods_name' => $params[ 'goods_name' ] ?? '',
            'balance' => $params[ 'balance' ] ?? 0,//储值余额
            'use_num' => $params[ 'temp_use_num' ] ?? '',
            'order_id' => $params[ 'order_id' ] ?? 0,
            'order_goods_id' => $params[ 'use_order_goods_id' ] ?? 0,
        );

        model('giftcard_card_use_records_goods')->add($data);
        return $this->success();
    }

    /**
     * 获取礼品卡使用记录信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCardUseRecordsInfo($condition, $field = '*')
    {
        $info = model('giftcard_card_use_records')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡使用记录列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCardUseRecordsList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_card_use_records')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡使用记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCardUseRecordsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_card_use_records')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取礼品卡使用记录项信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCardUseRecordsGoodsInfo($condition, $field = '*')
    {
        $info = model('giftcard_card_use_records_goods')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡使用记录项列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCardUseRecordsGoodsList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_card_use_records_goods')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡使用记录项分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCardUseRecordsGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_card_use_records_goods')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

}
