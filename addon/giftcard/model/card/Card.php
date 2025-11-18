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

use addon\giftcard\model\giftcard\CardStat;
use addon\giftcard\model\giftcard\GiftCard;
use app\model\BaseModel;
use app\model\member\Member;
use think\facade\Cache;

/**
 * 礼品卡工具类
 *
 * @author Administrator
 *
 */
class Card extends BaseModel
{
    public $source_list = array (
        'order' => '购买',
        'gift' => '赠送'
    );

    public function getStatusList($card_type)
    {
        switch ( $card_type ) {
            case 'virtual'://电子卡
                return array (
                    'to_use' => '待使用',
                    'used' => '已使用',
                    'expire' => '已过期'
                );
                break;
            case 'real'://实体卡
                return array (
                    'to_activate' => '待激活',
                    'to_use' => '待使用',
                    'used' => '已使用',
                    'expire' => '已过期',
                    'invalid' => '已失效'
                );
                break;
        }
    }

    /**
     * 生成礼品卡记录
     * @param $params
     * @return array
     */
    public function addCardItem($params)
    {
        $insert_data = $params[ 'insert_data' ];//可能包含订单id
        $site_id = $params[ 'site_id' ];
        $member_id = $params[ 'member_id' ] ?? 0;
        $giftcard_id = $params[ 'giftcard_id' ];

        $card_type = $params[ 'card_type' ] ?? '';

        $card_right_type = $params[ 'card_right_type' ];

        //批量生成卡号
        $giftcard_model = new Giftcard();
        $card_no_res = $giftcard_model->createCardNo($params['giftcard_id'], 1);
        if($card_no_res['code'] < 0) return $card_no_res;
        $card_no = $card_no_res['data'][0];
        if ($card_type == 'real') {
            $count = model('giftcard_card')->getCount([ [ 'card_no', '=', $card_no ] ]);
            if ($count > 0) {
                return $this->error([], '当前卡密和编号已存在');
            }
        }

        $data = array (
            'site_id' => $params[ 'site_id' ],
            'card_no' => $card_no,
            'card_type' => $card_type,
            'giftcard_id' => $giftcard_id,
            'member_id' => $member_id,
            'create_time' => time(),
            'card_right_type' => $card_right_type,
            'valid_time' => $this->getValidityTime($params),
            'balance' => $params[ 'balance' ] ?? 0,
            'card_right_goods_type' => $params[ 'card_right_goods_type' ] ?? '',
            'card_right_goods_count' => $params[ 'card_right_goods_count' ] ?? '',
        );
        $card_id = model('giftcard_card')->add(array_merge($data, $insert_data));
        if ($card_type == 'virtual') {
            $goods_list = $params[ 'goods_list' ];
            foreach ($goods_list as $k => $v) {
                $v[ 'card_id' ] = $card_id;
                $v[ 'giftcard_id' ] = $giftcard_id;
                $v[ 'card_right_type' ] = $card_right_type;
                $this->addCardItemGoods($v);
            }
        }
        return $this->success($card_id);
    }

    /**
     * 礼品卡项
     * @param $params
     * @return array
     */
    public function addCardItemGoods($params)
    {
        $data = array (
            'site_id' => $params[ 'site_id' ],
            'giftcard_id' => $params[ 'giftcard_id' ],
            'card_id' => $params[ 'card_id' ],
            'sku_id' => $params[ 'sku_id' ] ?? 0,
            'sku_name' => $params[ 'sku_name' ] ?? '',
            'sku_image' => $params[ 'sku_image' ] ?? '',
            'sku_no' => $params[ 'sku_no' ] ?? '',
            'goods_id' => $params[ 'goods_id' ] ?? 0,
            'goods_name' => $params[ 'goods_name' ] ?? '',
            'balance' => $params[ 'balance' ] ?? 0,//储值余额
            'total_balance' => $params[ 'total_balance' ] ?? 0,
            'price' => $params[ 'price' ] ?? 0,
            'total_num' => $params[ 'num' ] ?? 1,//购买数量
            'order_id' => $params[ 'order_id' ] ?? 0,
            'order_goods_id' => $params[ 'order_goods_id' ] ?? 0,
            'card_right_type' => $params[ 'card_right_type' ] ?? '',
        );

        model('giftcard_card_goods')->add($data);
        return $this->success();
    }

    /**
     * 计算礼品卡有效期
     * @param $params
     * @return float|int
     */
    public function getValidityTime($params)
    {
        $validity_type = $params[ 'validity_type' ];
        $validity_time = $params[ 'validity_time' ];
        $validity_day = $params[ 'validity_day' ];
        switch ( $validity_type ) {
            case 'forever':
                $temp_time = 0;
                break;
            case 'day':
                $temp_time = time() + 86400 * $validity_day;
                break;
            case 'date':
                $temp_time = $validity_time;
                break;
        }
        return $temp_time;
    }

    /**
     * 一般认为这是针对单项的删除
     * @param $params
     * @return array
     */
    public function delete($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $card_id = $params[ 'card_id' ] ?? 0;
        $card_ids = $params[ 'card_ids' ] ?? '';
        $card_import_id = $params[ 'card_import_id' ] ?? 0;
        $card_import_ids = $params[ 'card_import_ids' ] ?? 0;
        $condition = array (
            [ 'status', '=', 'to_activate' ]
        );
        if ($card_id > 0) {
            $condition[] = [ 'card_id', '=', $card_id ];
        }
        if (!empty($card_ids)) {
            $condition[] = [ 'card_id', 'in', $card_ids ];
        }
        if ($card_id > 0) {
            $condition[] = [ 'card_id', '=', $card_id ];
        }
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($card_import_id > 0) {
            $condition[] = [ 'card_import_id', '=', $card_import_id ];
        }
        if (!empty($card_import_ids)) {
            $condition[] = [ 'card_import_id', 'in', $card_import_ids ];
        }
        $card_list = $this->getCardList($condition)[ 'data' ] ?? [];
        if (empty($card_list))
            return $this->error();

//        if($card_info['status'] == 'used')
//            return $this->error('', '删除失败，不可删除已使用的礼品卡');

        $result = model('giftcard_card')->delete($condition);
        if ($result === false)
            return $this->error();

        $params[ 'list' ] = $card_list;
        $this->deleteOperation($params);
        return $this->success();
    }

    /**
     * 删除卡项的后续事件
     * @param $params
     * @return array
     */
    public function deleteOperation($params)
    {
        $list = $params[ 'list' ];
        foreach ($list as $v) {
            //数据统计
            ( new CardStat() )->stat(array_merge([ 'card_info' => $v, 'stat_type' => 'del' ]));
        }
        return $this->success();
    }

    /**
     * 获取礼品卡记录信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCardInfo($condition, $field = '*', $alias = '', $join = [])
    {
        $info = model('giftcard_card')->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取礼品卡记录列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCardList($condition = [], $field = '*', $order = '', $alias = '', $join = [], $limit = null)
    {
        $list = model('giftcard_card')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [])
    {
        $list = model('giftcard_card')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取礼品卡记录项信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCardGoodsInfo($condition, $field = '*')
    {
        $info = model('giftcard_card_goods')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡记录项列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCardGoodsList($condition = [], $field = '*', $order = '', $limit = null, $alias = 'a', $join = [])
    {
        $list = model('giftcard_card_goods')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡记录项分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCardGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('giftcard_card_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 卡券详情
     * @param $params
     * @return array
     */
    public function getCardDetail($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_id = $params[ 'member_id' ] ?? 0;
        $card_id = $params[ 'card_id' ];

        $condition = array (
            [ 'c.card_id', '=', $card_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'c.site_id', '=', $site_id ];
        }
        if ($member_id > 0) {
            $condition[] = [ 'c.member_id', '=', $member_id ];
        }
        $info = $this->getCardInfo($condition, 'c.*,go.order_no', 'c', [
                [ 'giftcard_order go', 'c.order_id=go.order_id', 'left' ]
            ])[ 'data' ] ?? [];
        if (empty($info))
            return $this->error();

        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $info[ 'member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
        $info[ 'member_nickname' ] = $member_info[ 'nickname' ] ?? '';
        $info[ 'member_headimg' ] = $member_info[ 'headimg' ] ?? '';

        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $info[ 'init_member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
        $info[ 'init_member_nickname' ] = $member_info[ 'nickname' ] ?? '';
        $info[ 'init_member_headimg' ] = $member_info[ 'headimg' ] ?? '';

        $condition = array (
            [ 'card_id', '=', $card_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($member_id > 0) {
            $condition[] = [ 'member_id', '=', $member_id ];
        }
        $list = $this->getCardGoodsList($condition)[ 'data' ] ?? [];
        $info[ 'card_goods_list' ] = $list;
        return $this->success($info);
    }

    public function tran($data)
    {
        $status = $data[ 'status' ] ?? '';
        if (!empty($status)) {
            $data[ 'status_name' ] = $this->getStatusList($data[ 'card_type' ])[ $status ] ?? '';
        }
        $source = $data[ 'source' ] ?? '';
        if (!empty($source)) {
            $data[ 'source_name' ] = $this->source_list[ $source ];
        }
        $member_id = $data[ 'member_id' ] ?? 0;
        if ($member_id > 0) {
            $member_model = new Member();
            $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $member_id ] ])[ 'data' ] ?? [];
            $data[ 'member_nickname' ] = $member_info[ 'nickname' ] ?? '';
        }
        return $data;
    }
}
