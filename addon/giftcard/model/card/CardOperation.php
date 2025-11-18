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
use addon\giftcard\model\membercard\MemberCard;
use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use think\facade\Db;

/**
 * 礼品卡工具类
 *
 * @author Administrator
 *
 */
class CardOperation extends BaseModel
{

    /**
     * 礼品卡使用吧公共函数
     * @param $params
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function cardUseOperation($params)
    {
        $card_id = $params[ 'card_id' ];
        $card_goods_list = $params[ 'goods_list' ];
        $condition = array (
            [ 'card_id', '=', $card_id ]
        );
        $card_right_type = $params[ 'card_right_type' ];
        $card_right_goods_type = $params[ 'card_right_goods_type' ];
        $card_right_goods_count = $params[ 'card_right_goods_count' ];
        $card_use_count = $params[ 'use_count' ];
        $card_right_goods_count -= $card_use_count;
        foreach ($card_goods_list as $k => $v) {
            $use_num = $v[ 'use_num' ];
            $total_num = $v[ 'total_num' ];
            $temp_use_num = $v[ 'temp_use_num' ];
            if ($card_right_goods_type != 'all') {
                if (( $use_num + $temp_use_num ) > $total_num) {
                    return $this->error([], '使用次数超出可使用次数');
                }
            } else {
                if ($temp_use_num > $card_right_goods_count) {
                    return $this->error([], '使用次数超出可使用次数');
                }
            }
            $item_condition = array (
                [ 'id', '=', $v[ 'id' ] ]
            );

            model('giftcard_card_goods')->setInc($item_condition, 'use_num', $temp_use_num);
            model('giftcard_card')->setInc($condition, 'use_count', $temp_use_num);//卡整体使用次数
            $card_right_goods_count -= $temp_use_num;
        }
        //写入使用记录
        $card_use_records_model = new CardUse();
        $result = $card_use_records_model->addCardUseRecords($params);
        $records_id = $result[ 'data' ];
        //核验礼品卡还是否存在可使用次数
        if ($card_right_goods_type != 'all') {
            $surplus_num = model('giftcard_card_goods')->getSum($condition, Db::raw('(total_num - use_num)'));
        } else {
            $card_model = new Card();
            $card_info = $card_model->getCardInfo($condition)[ 'data' ] ?? [];
            $surplus_num = $card_info[ 'card_right_goods_count' ] - $card_info[ 'use_count' ];
        }
        ( new CardLog() )->add([
            'card_id' => $card_id,
            'type_id' => $records_id,
            'type' => 'use',
            'operator_type' => 'member',//todo  暂时是确定的
            'operator' => $params[ 'member_id' ],
        ]);

        if ($surplus_num == 0) {
            //使用
            $this->used($params);
        }

        return $this->success();
    }

    /**
     * 储值礼品卡使用
     * @param $params
     */
    public function cardUse($params)
    {
        $member_id = $params[ 'member_id' ] ?? 0;
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_card_id = $params[ 'member_card_id' ];
        $order_id = $params[ 'order_id' ] ?? 0;
        $member_card_model = new MemberCard();
        $member_card_condition = array (
            [ 'member_card_id', '=', $member_card_id ],
            [ 'member_id', '=', $member_id ],
            [ 'is_transfer', '=', 0 ]
        );
        if ($site_id > 0) {
            $member_card_condition[] = [ 'site_id', '=', $site_id ];
        }
        $member_card_info = $member_card_model->getMemberCardInfo($member_card_condition)[ 'data' ] ?? [];
        if (empty($member_card_info))
            return $this->error([], '礼品卡不存在或已转赠');

        $member_id = $member_card_info[ 'member_id' ];
        $card_id = $member_card_info[ 'card_id' ];
        $card_model = new Card();
        $card_condition = array (
            [ 'card_id', '=', $card_id ],
        );
        $card_info = $card_model->getCardInfo($card_condition)[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error([], '礼品卡不存在或已转赠');

        $card_info[ 'use_order_id' ] = $order_id;
        $status = $card_info[ 'status' ];
        //todo  加入队列概念,队列
        if ($status != 'to_use')
            return $this->error([], '当前礼品卡不可以使用');

        $card_right_type = $card_info[ 'card_right_type' ];
        if ($card_right_type == 'balance') {
            $card_goods_list = $card_model->getCardGoodsList($card_condition)[ 'data' ] ?? [];
            foreach ($card_goods_list as $k => $v) {
                $card_goods_list[ $k ][ 'temp_use_num' ] = $v[ 'total_num' ];
            }
        } else {
            $card_goods_array = $params[ 'card_goods_json' ];//[{'card_goods_id':15,'order_goods_id':1, 'num': 2}]
            if (empty($card_goods_array))
                return $this->error([], '礼品卡使用参数有误');

            $card_goods_ids = array_column($card_goods_array, 'card_goods_id');
            $card_goods_column = array_column($card_goods_array, null, 'card_goods_id');
            $temp_card_condition = $card_condition;
            $temp_card_condition[] = [ 'id', 'in', $card_goods_ids ];
            $card_goods_list = $card_model->getCardGoodsList($temp_card_condition)[ 'data' ] ?? [];
            foreach ($card_goods_list as $k => $v) {
                $item_column = $card_goods_column[ $v[ 'id' ] ];
                $card_goods_list[ $k ][ 'temp_use_num' ] = $item_column[ 'num' ];
                $card_goods_list[ $k ][ 'use_order_goods_id' ] = $item_column[ 'order_goods_id' ];
            }
        }
        model('giftcard_card')->startTrans();
        try {

            $user_params = array_merge($member_card_info, $card_info);
            $user_params[ 'goods_list' ] = $card_goods_list;
            $result = $this->cardUseOperation($user_params);
            if ($result[ 'code' ] < 0) {
                model('giftcard_card')->rollback();
                return $result;
            }

            $result = $this->balanceCardUse($user_params);
            if ($result[ 'code' ] < 0) {
                model('giftcard_card')->rollback();
                return $result;
            }
            model('giftcard_card')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('giftcard_card')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 礼品卡兑换
     * @param $params
     * @return array
     */
    public function balanceCardUse($params)
    {
        $card_goods_list = $params[ 'goods_list' ];
        //将余额给会员
        $member_account_model = new MemberAccount();
        foreach ($card_goods_list as $k => $v) {
            $item_balance = $v[ 'total_balance' ];//总的余额
            if ($item_balance > 0) {
                $member_account_model->addMemberAccount($params[ 'site_id' ], $params[ 'member_id' ], AccountDict::balance, $item_balance, 'giftcard', '礼品卡兑换' . $item_balance, '储值礼品卡兑换');
            }
            $card_goods_list[ $k ][ 'temp_use_num' ] = $v[ 'total_num' ];
        }
        return $this->success();
    }

    public function cardExpire($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $card_ids = $params[ 'card_ids' ];
        $condition = array (
            [ 'card_id', 'in', $card_ids ],
            [ 'status', 'in', [ 'to_activate', 'to_use' ] ]//暂时认为只有待使用和待激活的卡项会过期
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $card_model = new Card();
        $list = $card_model->getCardList($condition)[ 'data' ] ?? [];
        if (empty($list)){
            return $this->success();
        }

        $data = array (
            'status' => 'expire'
        );
        model('giftcard_card')->update($data, $condition);

        $this->cardExpireOperation($list);
        return $this->success();
    }

    /**
     * 卡过期后续任务
     * @param $list
     * @return array
     */
    public function cardExpireOperation($list)
    {
        $card_log_model = new CardLog();
        foreach ($list as $k => $v) {
            //将贺卡全部作废
            $card_blessing_condition = array (
                [ 'card_id', '=', $v[ 'card_id' ] ],
                [ 'status', '=', 0 ]
            );
            model('giftcard_card_blessing')->update([ 'status' => 1 ], $card_blessing_condition);
            $card_log_model->add([
                'card_id' => $v[ 'card_id' ],
                'type' => 'expire',
                'operator_type' => 'system',//todo  暂时是确定的
            ]);

        }
        return $this->success();
    }

    /**
     * 卡使用
     * @param $params
     */
    public function used($params)
    {
        $condition = array (
            [ 'card_id', '=', $params[ 'card_id' ] ]
        );
        $data = array (
            'status' => 'used',
            'use_time' => time(),
        );
        model('giftcard_card')->update($data, $condition);

        //数据统计
        ( new CardStat() )->stat(array_merge($params, [ 'stat_type' => 'use' ]));

        ( new CardLog() )->add([
            'card_id' => $params[ 'card_id' ],
            'type' => 'used',
            'operator_type' => 'member',//todo  暂时是确定的
            'operator' => $params[ 'member_id' ],
        ]);
        return $this->success();
    }

    /**
     * 批量作废
     * @param $params
     * @return array
     */
    public function cardInvalid($params)
    {

        $site_id = $params[ 'site_id' ] ?? 0;
        $card_import_id = $params[ 'card_import_id' ] ?? 0;
        $card_id = $params[ 'card_id' ] ?? 0;
        $condition = [
            [ 'status', '=', 'to_activate' ]
        ];
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($card_import_id > 0) {
            $condition[] = [ 'card_import_id', '=', $card_import_id ];
        }
        if ($card_id > 0) {
            $condition[] = [ 'card_id', '=', $card_id ];
        }
        $data = array (
            'status' => 'invalid',
            'invalid_time' => time(),
        );
        $card_model = new Card();
        $list = $card_model->getCardList($condition)[ 'data' ] ?? [];
        if (empty($list))
            return $this->error();

        model('giftcard_card')->update($data, $condition);
        $params[ 'list' ] = $list;
        //作废后的操作
        $this->cardInvalidOperation($params);
        return $this->success();
    }

    /**
     * 作废后操作
     * @param $params
     * @return array
     */
    public function cardInvalidOperation($params)
    {
        $list = $params[ 'list' ];
        $card_log_model = new CardLog();
        foreach ($list as $k => $v) {
            //添加日志
            $card_log_model->add([
                'card_id' => $v[ 'card_id' ],
                'type' => 'used',
                'operator_type' => 'member',//todo  暂时是确定的
                'operator_data' => $params[ 'operator_data' ],
            ]);
        }
        ( new CardStat() )->stat(array_merge($params, [ 'stat_type' => 'invalid', 'num' => count($list) ]));
        return $this->success();
    }
}
