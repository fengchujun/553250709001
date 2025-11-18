<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\birthdaygift\api\controller;

use app\api\controller\BaseApi;
use addon\birthdaygift\model\BirthdayGift;
use app\model\member\Member;

/**
 * 会员生日奖励
 */
class Config extends BaseApi
{

    /**
     * 计算信息
     */
    public function config()
    {

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        //获取生日有礼信息
        $gift_model = new BirthdayGift();
        $info = $gift_model->getAward($this->site_id);
        $flag = false;
        if (!empty($info[ 'data' ])) {
            //获取会员信息
            $member_model = new Member();
            if (empty($info[ 'data' ][ 'level_id' ])) {
                // 所有会员
                $member_info = $member_model->getMemberInfo([['member_id', '=', $this->member_id], ['site_id', '=', $this->site_id]], 'member_id,nickname,member_level,member_level_name,birthday,reg_time')[ 'data' ];
            } else {
                $member_info = $member_model->getMemberInfo([['member_id', '=', $this->member_id], ['site_id', '=', $this->site_id], ['member_level', 'in', $info[ 'data' ][ 'level_id' ]]], 'member_id,nickname,member_level,member_level_name,birthday,reg_time')[ 'data' ];
            }
            if (!empty($member_info)) {
                // 判断今年有没有领取过
                $start_year = strtotime(date('Y-01-01 00:00:00'));
                $end_year = strtotime('+1 year', $start_year);

                $record_condition[] = ['member_id', '=', $this->member_id];
                $record_condition[] = ['receive_time', '>', $start_year];
                $record_condition[] = ['receive_time', '<', $end_year];
                $recode = $gift_model->getRecordList($record_condition);
                if (empty($recode[ 'data' ])) {
                    $today = date('Y-m-d', time());
                    //以上条件都满足 判断奖励时间 (1生日当天 2生日当周 3生日当月)
                    if ($info[ 'data' ][ 'activity_time_type' ] == 1) {
                        $birthday = date('m-d', $member_info[ 'birthday' ]);
                        if (date('m-d') == $birthday) {
                            $flag = true;
                        }
                    } else if ($info[ 'data' ][ 'activity_time_type' ] == 2) {
                        // 把会员的生日转换为当前的年份
                        $birth_month_and_day = date('m-d', $member_info[ 'birthday' ]);
                        $now_birthday = date('Y', time());
                        $now_birthday_year_month_day = $now_birthday . '-' . $birth_month_and_day;
                        $member_birthday_time = date_to_time($now_birthday_year_month_day);
                        $end_time = date_to_time("$today sunday");
                        $end_time_date = date('Y-m-d', $end_time);
                        $begin_time = date_to_time("$end_time_date -6 days");
                        if ($begin_time <= $member_birthday_time && $member_birthday_time <= $end_time) {
                            $flag = true;
                        }
                    } else if ($info[ 'data' ][ 'activity_time_type' ] == 3) {
                        $birthday = date('m', $member_info[ 'birthday' ]);
                        if (date('m') == $birthday) {
                            $flag = true;
                        }
                    }
                    $info[ 'data' ][ 'nickname' ] = $member_info[ 'nickname' ];
                }
            }
        }
        $info[ 'data' ][ 'flag' ] = $flag;

        if (!empty($info[ 'data' ][ 'coupon_list' ])) {
            foreach ($info[ 'data' ][ 'coupon_list' ] as $kk => $vv) {
                $coupon_flag = false;
                if ($vv[ 'status' ] == 1) {
                    if ($vv[ 'count' ] == -1 || $vv[ 'count' ] - $vv[ 'lead_count' ] > 0) $coupon_flag = true;
                }
                $info[ 'data' ][ 'coupon_list' ][ $kk ][ 'coupon_flag' ] = $coupon_flag;
            }
        } else {
            $info[ 'data' ][ 'coupon_list' ] = [];
        }
        return $this->response($info);
    }

    /**
     * 领取生日奖励
     */
    public function receive()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $activity_id = $this->params[ 'id' ];
        if (empty($activity_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $gift_model = new BirthdayGift();
        $res = $gift_model->receive($this->member_id, $activity_id, $this->site_id);
        return $this->response($res);
    }

    /**
     * 查询领取记录
     */
    public function getRecord()
    {
        $token = $this->checkToken();
        $gift_model = new BirthdayGift();
        $res = $gift_model->verificationRecord($this->member_id);
        return $this->response($res);
    }
}