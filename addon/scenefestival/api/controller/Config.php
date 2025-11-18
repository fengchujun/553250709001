<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\scenefestival\api\controller;

use addon\scenefestival\model\Record;
use addon\scenefestival\model\SceneFestival as Festival;
use app\api\controller\BaseApi;
use app\model\member\Member;

/**
 * 会员节日有礼奖励
 */
class Config extends BaseApi
{

    /**
     * 计算信息
     */
    public function Config()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        //获取当前时间
        $time = time();

        //获取满足条件的节日有礼活动
        $game_condition = [
            ['status', 'in', '0,1'],
            ['push_time', '<=', $time],
            ['festival_type', '=', 'scenefestival'],
            ['site_id', '=', $this->site_id]
        ];

        $festival_model = new Festival();
        $member_model = new Member();
        $field = 'festival_id,festival_type,site_id,activity_name,festival_type_name,remark,status,start_time,end_time,level_id,level_name,join_type,join_frequency,push_time';
        $festival_list = $festival_model->getFestivalList($game_condition, $field);

        if (!empty($festival_list)) {
            foreach ($festival_list[ 'data' ] as $k => $v) {
                $flag = false;
                //获取会员信息
                if ($v[ 'level_id' ] == 0) {//所有会员
                    $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $this->member_id ], [ 'site_id', '=', $this->site_id ] ], 'member_id,nickname,member_level,member_level_name')[ 'data' ];
                } else {
                    $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $this->member_id ], [ 'site_id', '=', $this->site_id ], [ 'member_level', 'in', $v[ 'level_id' ] ] ], 'member_id,nickname,member_level,member_level_name')[ 'data' ];
                }
                // 判断今年有没有领取过
                $record = new Record();
                $start_year = date_to_time(date('Y-01-01 00:00:00'));
                $end_year = $start_year + 31535999;
                $record_condition[] = [ 'member_id', '=', $this->member_id ];
                $record_condition[] = [ 'festival_id', '=', $v[ 'festival_id' ] ];
                $record_condition[] = [ 'receive_time', '>', $start_year ];
                $record_condition[] = [ 'receive_time', '<', $end_year ];
                $record_data = $record->getFestivalDrawRecordList($record_condition, '*', 'receive_time desc')[ 'data' ];
                // 奖项
                $game_ward = $festival_model->getGameAward([ [ 'festival_id', '=', $v[ 'festival_id' ] ] ], 'award_id,award_type,coupon,point,balance,balance_type,balance_money')[ 'data' ];
                if (!empty($game_ward[ 'coupon_list' ])) {
                    foreach ($game_ward[ 'coupon_list' ] as $coupon_k => &$coupon_v) {
                        $coupon_flag = false;
                        if ($coupon_v[ 'status' ] == 1) {
                            if ($coupon_v[ 'count' ] == -1 || $coupon_v[ 'count' ] - $coupon_v[ 'lead_count' ] > 0) $coupon_flag = true;
                        }
                        $coupon_v[ 'coupon_flag' ] = $coupon_flag;
                    }
                } else {
                    $game_ward[ 'coupon_list' ] = [];
                }
                if (( isset($member_info) && !empty($member_info) ) && empty($record_data) && !empty($game_ward)) {
                    $flag = true;
                }
                $festival_list[ 'data' ][ $k ][ 'award_list' ] = $game_ward;
                $festival_list[ 'data' ][ $k ][ 'nickname' ] = $member_info[ 'nickname' ];
                $festival_list[ 'data' ][ $k ][ 'flag' ] = $flag;
            }
        }
        return $this->response($festival_list);
    }

    public function getFestivalDrawRecordList()
    {

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        //获取当前时间
        $time = time();
        //获取满足条件的节日有礼活动
        $game_condition = [
            [
                'status', 'in', '0,1'
            ],
            [
                'push_time', '<=', $time
            ],
            [
                'festival_type', '=', 'scenefestival'
            ],
            [
                'site_id', '=', $this->site_id
            ]
        ];

        $festival_model = new Festival();
        $field = 'festival_id,festival_type,site_id,activity_name,festival_type_name,remark,status,start_time,end_time,level_id,level_name,join_type,join_frequency,push_time';
        $festival_list = $festival_model->getFestivalList($game_condition, $field);

        if (!empty($festival_list)) {
            foreach ($festival_list[ 'data' ] as $k => $v) {
                $flag = false;
                $record = new Record();
                $start_year = date_to_time(date('Y-01-01 00:00:00'));
                $end_year = $start_year + 31535999;
                $record_condition[] = [ 'member_id', '=', $this->member_id ];
                $record_condition[] = [ 'festival_id', '=', $v[ 'festival_id' ] ];
                $record_condition[] = [ 'receive_time', '>', $start_year ];
                $record_condition[] = [ 'receive_time', '<', $end_year ];
                $record_data = $record->getFestivalDrawRecordList($record_condition, '*', 'receive_time desc')[ 'data' ];
                if (empty($record_data)) {
                    $flag = true;
                }
                $festival_list[ 'data' ][ $k ][ 'flag' ] = $flag;
            }
        }
        return $this->response($festival_list);
    }

    /**
     * 领取节日有礼奖励
     */
    public function receive()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $festival_id = $this->params[ 'festival_id' ];
        if (empty($festival_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $game = new Festival();
        $res = $game->receive($festival_id, $this->member_id, $this->site_id);
        return $this->response($res);
    }
}