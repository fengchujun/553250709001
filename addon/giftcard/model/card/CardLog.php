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

use addon\giftcard\model\membercard\MemberCard;
use app\model\BaseModel;
use app\model\member\Member;

/**
 * 礼品卡操作日志
 *
 * @author Administrator
 *
 */
class CardLog extends BaseModel
{
    public function add($params)
    {
        $type = $params[ 'type' ];//操作类型
        $card_id = $params[ 'card_id' ];
        $type_id = $params[ 'type_id' ] ?? 0;
        $card_model = new Card();
        $card_condition = array (
            [ 'card_id', '=', $card_id ]
        );
        $card_info = $card_model->getCardInfo($card_condition)[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error();

        $site_id = $card_info[ 'site_id' ];
        $operator_type = $params[ 'operator_type' ] ?? '';
        $operator = '';
        $operator_name = '';

        $member_model = new Member();
        switch ( $operator_type ) {
            case 'member':
                $operator = $params[ 'operator' ] ?? 0;
                $member_condition = array (
                    [ 'member_id', '=', $operator ]
                );
                $operator_name = $member_model->getMemberInfo($member_condition)[ 'data' ][ 'nickname' ] ?? '';
                break;
            case 'shop':
                $operator_info = $params[ 'operator_data' ] ?? [];
                $operator = $operator_info[ 'uid' ] ?? 0;
                $operator_name = $operator_info[ 'username' ] ?? '';
                break;
            case 'system':
                $operator = 0;
                $operator_name = '系统任务';
                break;
        }

        $data = array (
            'card_id' => $card_id,
            'site_id' => $site_id,
            'type' => $type,
            'type_id' => $type_id,
            'giftcard_id' => $card_info[ 'giftcard_id' ],
            'member_id' => $card_info[ 'member_id' ],
            'create_time' => time(),
            'operator_type' => $operator_type,
            'operator' => $operator,
            'operator_name' => $operator_name,
        );
        $remark = '';
        switch ( $type ) {
            case 'create'://制卡
                $remark = '店铺管理员' . $operator_name . '制成礼品卡';
                break;
            case 'buy'://购买卡
                $init_member_id = $card_info[ 'init_member_id' ];
                $member_condition = array (
                    [ 'member_id', '=', $init_member_id ]
                );
                $init_member_name = $member_model->getMemberInfo($member_condition)[ 'data' ][ 'nickname' ] ?? '';
                $remark = '会员' . $init_member_name . '购买礼品卡';
                break;
            case 'transfer'://赠送
                $member_card_id = $type_id;
                $member_card_model = new MemberCard();
                $member_card_condition = array (
                    [ 'member_card_id', '=', $member_card_id ]
                );
                $member_card_info = $member_card_model->getMemberCardInfo($member_card_condition)[ 'data' ] ?? [];
                if (empty($member_card_info)) {
                    return $this->error();
                }
                $member_condition = array (
                    [ 'member_id', '=', $member_card_info[ 'from_member_id' ] ]
                );
                $from_member_name = $member_model->getMemberInfo($member_condition)[ 'data' ][ 'nickname' ] ?? '';
                $member_condition = array (
                    [ 'member_id', '=', $member_card_info[ 'member_id' ] ]
                );
                $member_name = $member_model->getMemberInfo($member_condition)[ 'data' ][ 'nickname' ] ?? '';

                $remark = '会员' . $from_member_name . '将礼品卡赠送给会员' . $member_name;
                break;

            case 'use'://使用
                $records_id = $type_id;
                $card_use_model = new CardUse();
                $use_condition = array (
                    [ 'records_id', '=', $records_id ]
                );
                $card_use_info = $card_use_model->getCardUseRecordsInfo($use_condition)[ 'data' ] ?? [];
                if (empty($card_use_info))
                    return $this->error();

                $use_member_id = $card_use_info[ 'member_id' ];
                $member_condition = array (
                    [ 'member_id', '=', $use_member_id ]
                );
                $use_member_name = $member_model->getMemberInfo($member_condition)[ 'data' ][ 'nickname' ] ?? '';
                $remark = '会员' . $use_member_name . '使用礼品卡购买了';
                $card_use_list = $card_use_model->getCardUseRecordsGoodsList($use_condition)[ 'data' ] ?? [];
                $card_use_goods_array = [];
                foreach ($card_use_list as $v) {
                    $card_use_goods_array[] = $v[ 'sku_name' ] . $v[ 'use_num' ] . '件';
                }
                $remark .= implode('、', $card_use_goods_array);
                break;
            case 'used':
                $remark = '礼品卡次数使用完毕,礼品卡已使用';
                break;
            case 'expire':
                $remark = '礼品卡过期';
                break;
            case 'invalid':
                $remark = '店铺管理员' . $operator_name . '将礼品卡作废';
                break;
        }
        $data[ 'remark' ] = $remark;
        $data[ 'extend' ] = json_encode($extend ?? []);
        model('giftcard_card_log')->add($data);
        return $this->success();
    }

    /**
     * 获取礼品卡日志记录信息
     * @param $condition
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getCardLogInfo($condition, $field = '*', $alias = '', $join = [])
    {
        $info = model('giftcard_card_log')->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取礼品卡日志记录列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $alias
     * @param array $join
     * @param null $limit
     * @return array
     */
    public function getCardLogList($condition = [], $field = '*', $order = '', $alias = '', $join = [], $limit = null)
    {
        $list = model('giftcard_card_log')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡日志记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getCardLogPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = '', $join = [])
    {
        $list = model('giftcard_card_log')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

}
