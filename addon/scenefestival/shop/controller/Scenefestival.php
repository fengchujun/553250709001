<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\scenefestival\shop\controller;

use addon\coupon\model\CouponType;
use addon\scenefestival\model\SceneFestival as Festival;
use app\model\member\MemberLevel;
use app\shop\controller\BaseShop;
use think\facade\Cache;

/**
 * 节日有礼控制器
 */
class Scenefestival extends BaseShop
{
    //活动类型
    private $game_type = 'scenefestival';
    private $game_type_name = '节日有礼';
    private $festival_url = '/pages_promotion/game/turntable';

    /*
     *  节日有礼活动列表
     */
    public function lists()
    {
        $model = new Festival();
        if (request()->isJson()) {

            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'festival_type', '=', $this->game_type ]
            ];

            $status = input('status', '');//节日有礼状态
            if ($status !== '') {
                $condition[] = [ 'status', '=', $status ];
            }
            //活动名称
            $activity_name = input('activity_name', '');
            if ($activity_name) {
                $condition[] = [ 'activity_name', 'like', '%' . $activity_name . '%' ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            if ($start_time && !$end_time) {
                $condition[] = [ 'end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (start_time <= {$start_timestamp} and end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            $list = $model->getGamesPageList($condition, $page, $page_size, 'festival_id desc');
            return $list;
        } else {
            return $this->fetch("scenefestival/lists");
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        $model = new Festival();
        if (request()->isJson()) {

            $holiday_time = input('holiday_time', '');
            $days = input('join_frequency', '1');
            $join_type = input('join_type', 1);
            $join_time = input('join_time', '0'); //1传统节日 0自定义节日
            if ($join_time == 1) {
                $holiday_name = input('holiday_name', '');
                $date = substr($holiday_time, '0', '4') . '-' . substr($holiday_time, '4', '2') . '-' . substr($holiday_time, '6', '2');
                $end_time = strtotime($date . ' ' . '23:59:59');
                if ($join_type == 1) {
                    $start_time = strtotime($date . ' ' . '00:00:00');
                    $push_time = $start_time;
                } else {
                    $start_time = strtotime($date . ' ' . '00:00:00');
                    $push_time = $start_time - ( 86400 * $days );
                }
            } else {
                $holiday_name = input('festival_name', '');
                if (input('join_type') == 1) {
                    $start_time = strtotime(input('start_time', ''));
                    $push_time = $start_time;
                } else if (input('join_type') == 0) {
                    $start_time = strtotime(input('start_time', ''));
                    $push_time = $start_time - ( 86400 * $days );
                }
                $end_time = strtotime(input('end_time', ''));
            }

            //获取节日信息
            $festival_data = [
                'site_id' => $this->site_id,
                'activity_name' => input('activity_name', ''),
                'festival_type' => $this->game_type,
                'festival_type_name' => $this->game_type_name,
                'festival_name' => $holiday_name,
                'level_id' => input('level_id', ''),
                'level_name' => input('level_name', ''),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'remark' => input('remark', ''),
                'join_time' => input('join_time', ''),//0自定义节日 1传统节日
                'join_type' => input('join_type', ''), //奖励发放时间类型(1节日当天 0节日前某天)
                'join_frequency' => $days,//天数
                'push_time' => $push_time//活动推送时间
            ];

            if (strpos(input('type', ''), 'point') !== false) {
                $point = input('point', 0);
            } else {
                $point = 0;
            }
            if (strpos(input('type', ''), 'balance') !== false) {
                if (input('balance_type') == 0) {
                    $balance = input('balance', '0.00');
                    $balance_money = '0.00';
                } else {
                    $balance_money = input('balance_money', '0.00');
                    $balance = '0.00';
                }
            } else {
                $balance = '0.00';
                $balance_money = '0.00';

            }
            if (strpos(input('type', ''), 'coupon') !== false) {
                $coupon = input('coupon', '');
            } else {
                $coupon = 0;
            }

            $award_arr = [
                'award_type' => input('type', ''),
                'point' => $point,
                'balance' => $balance,
                'balance_type' => input('balance_type', '0'),
                'balance_money' => $balance_money,
                'coupon' => $coupon
            ];
            return $model->addFestival($festival_data, $award_arr);
        } else {
            $jieri_list = Cache::get('jieri_list');
            if (empty($jieri_list)) {
                $jieri_data = file_get_contents('https://api.apihubs.cn/holiday/get?field=year,month,date,lunar_year,lunar_month,lunar_date,holiday&year=' . date('Y') . '&holiday=22,15,11,16,17,19,44,55,58,62,66,70,77,88,89,97&holiday_today=1&order_by=1&cn=1&size=31');
                $jieri_data = json_decode($jieri_data, true);
                $jieri_list = $jieri_data[ 'data' ][ 'list' ];
                Cache::set('jieri_list', $jieri_list);
            }
            $this->assign('jieri_list', $jieri_list);

            //会员等级
            $member_level_model = new MemberLevel();
            $member_level_list = $member_level_model->getMemberLevelList([ [ 'site_id', '=', $this->site_id ] ], 'level_id, level_name', 'growth asc');
            $this->assign('member_level_list', $member_level_list[ 'data' ]);

            return $this->fetch("scenefestival/add");
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $model = new Festival();
        $festival_id = input('festival_id');
        if (request()->isJson()) {
            $holiday_time = input('holiday_time', '');
            $days = input('join_frequency', '1');
            $join_type = input('join_type', 1);
            $join_time = input('join_time', '0'); //1传统节日 0自定义节日
            if ($join_time == 1) {
                $holiday_name = input('holiday_name', '');
                $date = substr($holiday_time, '0', '4') . '-' . substr($holiday_time, '4', '2') . '-' . substr($holiday_time, '6', '2');
                $end_time = strtotime($date . ' ' . '23:59:59');
                if ($join_type == 1) {
                    $start_time = strtotime($date . ' ' . '00:00:00');
                    $push_time = $start_time;
                } else {
                    $start_time = strtotime($date . ' ' . '00:00:00');
                    $push_time = $start_time - ( 86400 * $days );
                }

            } else {
                $holiday_name = input('festival_name', '');
                if (input('join_type') == 1) {
                    $start_time = strtotime(input('start_time', ''));
                    $push_time = $start_time;
                } else if (input('join_type') == 0) {
                    $start_time = strtotime(input('start_time', ''));
                    $push_time = $start_time - ( 86400 * $days );
                }
                $end_time = strtotime(input('end_time', ''));
            }
            //获取活动信息
            $festival_data = [
                'site_id' => $this->site_id,
                'activity_name' => input('activity_name', ''),
                'festival_type' => $this->game_type,
                'festival_type_name' => $this->game_type_name,
                'festival_name' => $holiday_name,
                'level_id' => input('level_id', ''),
                'level_name' => input('level_name', ''),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'remark' => input('remark', ''),
                'join_time' => input('join_time', ''),//0自定义节日 1传统节日
                'join_type' => input('join_type', ''), //奖励发放时间类型(1节日当天 0节日前某天)
                'join_frequency' => $days,//天数
                'push_time' => $push_time//活动推送时间
            ];

            if (strpos(input('type', ''), 'point') !== false) {
                $point = input('point', 0);
            } else {
                $point = 0;
            }
            if (strpos(input('type', ''), 'balance') !== false) {
                if (input('balance_type') == 0) {
                    $balance = input('balance', '0.00');
                    $balance_money = '0.00';
                } else {
                    $balance_money = input('balance_money', '0.00');
                    $balance = '0.00';
                }
            } else {
                $balance = '0.00';
                $balance_money = '0.00';

            }
            if (strpos(input('type', ''), 'coupon') !== false) {
                $coupon = input('coupon', '');
            } else {
                $coupon = 0;
            }

            $award_arr = [
                'award_type' => input('type', ''),
                'point' => $point,
                'balance' => $balance,
                'balance_type' => input('balance_type', '0'),
                'balance_money' => $balance_money,
                'coupon' => $coupon
            ];

            return $model->editGames([ [ 'site_id', '=', $this->site_id ], [ 'festival_id', '=', $festival_id ] ], $festival_data, $award_arr);
        } else {
            //会员等级
            $member_level_model = new MemberLevel();
            $member_level_list = $member_level_model->getMemberLevelList([ [ 'site_id', '=', $this->site_id ] ], 'level_id, level_name', 'growth asc');
            $this->assign('member_level_list', $member_level_list[ 'data' ]);

            $jieri_list = Cache::get('jieri_list');
            if (empty($jieri_list)) {
                $jieri_data = file_get_contents('https://api.apihubs.cn/holiday/get?field=year,month,date,lunar_year,lunar_month,lunar_date,holiday&year=' . date('Y') . '&holiday=22,15,11,16,17,19,44,55,58,62,66,70,77,88,89,97&holiday_today=1&order_by=1&cn=1&size=31');
                $jieri_data = json_decode($jieri_data, true);
                $jieri_list = $jieri_data[ 'data' ][ 'list' ];
                Cache::set('jieri_list', $jieri_list);
            }
            $this->assign('jieri_list', $jieri_list);

            //获取详情
            $info = $model->getFestivalDetail([ [ 'a.festival_id', '=', $festival_id ], [ 'a.site_id', '=', $this->site_id ] ], 'a.*,pfa.award_type,pfa.coupon,pfa.point,pfa.balance,pfa.balance_type,pfa.balance_money', 'a', [ [ 'promotion_festival_award pfa', 'pfa.festival_id = a.festival_id', 'left' ] ]);
            $info[ 'data' ][ 'time' ] = time();
            $this->assign('info', $info[ 'data' ]);
            if (empty($info[ 'data' ])) $this->error('未获取到活动数据', href_url('scenefestival://shop/scenefestival/lists'));
            return $this->fetch("scenefestival/edit");
        }
    }

    /*
     *  节日有礼详情
     */
    public function detail()
    {
        $festival_model = new Festival();

        $festival_id = input('festival_id', '');
        //获取详情
        $info = $festival_model->getFestivalDetail([ [ 'a.festival_id', '=', $festival_id ], [ 'a.site_id', '=', $this->site_id ] ], 'a.*,pfa.award_type,pfa.coupon,pfa.point,pfa.balance,pfa.balance_type,pfa.balance_money', 'a', [ [ 'promotion_festival_award pfa', 'pfa.festival_id = a.festival_id', 'left' ] ])[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到活动数据', href_url('scenefestival://shop/scenefestival/lists'));
        $info[ 'status_name' ] = $festival_model->status[ $info[ 'status' ] ] ?? '';
        $this->assign('info', $info);

        return $this->fetch("scenefestival/detail");
    }

    /*
     *  删除节日有礼活动
     */
    public function delete()
    {
        $festival_id = input('festival_id', '0');
        $site_id = $this->site_id;

        $festival_model = new Festival();
        return $festival_model->deleteGames($site_id, $festival_id);
    }

    /*
     *  结束节日有礼活动
     */
    public function finish()
    {
        $festival_id = input('festival_id', '0');
        $site_id = $this->site_id;

        $festival_model = new Festival();
        return $festival_model->finishGames($site_id, $festival_id);
    }


    /*
     *  重启节日有礼活动
     */
    public function start()
    {
        $festival_id = input('festival_id', '0');

        $festival_model = new Festival();
        return $festival_model->startGames($festival_id);
    }

    /**
     * 推广
     * return
     */
    public function gameUrl()
    {
        $festival_id = input('festival_id', '0');
        $model = new Festival();
        $festival_info_data = $model->getGamesInfo([ [ 'festival_id', '=', $festival_id ] ], 'festival_id,activity_name');
        $festival_info = $festival_info_data[ 'data' ];
        $res = $model->qrcode($festival_info[ 'festival_id' ], $festival_info[ 'activity_name' ], $this->festival_url, $this->site_id);
        return $res;
    }

}