<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\scenefestival\model;


use addon\coupon\model\Coupon;
use addon\coupon\model\CouponType;
use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\system\Cron;
use app\model\system\Config as ConfigModel;
use think\facade\Cache;
use think\facade\Db;

/**
 * 活动
 */
class SceneFestival extends BaseModel
{
    public $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        3 => '已关闭',
    ];

    /**
     * 添加活动
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function addFestival($festival_data, $award_arr)
    {
        Cache::clear();
        model('promotion_festival')->startTrans();
        try {
            $condition = [
                [ 'status', 'in', '0,1' ],
                [ 'site_id', '=', $festival_data[ 'site_id' ] ],
                [ '', 'exp', Db::raw('not ( (`push_time` > ' . $festival_data[ 'end_time' ] . ' and `push_time` > ' . $festival_data[ 'push_time' ] . ' )  or (`end_time` < ' . $festival_data[ 'push_time' ] . ' and `end_time` < ' . $festival_data[ 'end_time' ] . '))') ]
            ];
            $res = model('promotion_festival')->getList($condition, 'festival_id');
            if (!empty($res)) {
                return $this->error('', "当前时间段已有相同的活动");
            }

            $time = time();
            $festival_data[ 'create_time' ] = $time;

            if ($time > $festival_data[ 'push_time' ] && $time < $festival_data[ 'end_time' ]) {
                $festival_data[ 'status' ] = 1;
            } else {
                $festival_data[ 'status' ] = 0;
            }

            $festival_id = model('promotion_festival')->add($festival_data);
            $award_arr[ 'site_id' ] = $festival_data[ 'site_id' ];
            $award_arr[ 'festival_id' ] = $festival_id;
            model('promotion_festival_award')->add($award_arr);

            $cron = new Cron();
            if ($festival_data[ 'status' ] == 1) {//进行中

                $cron->addCron(1, 0, "节日有礼活动关闭", "cronCloseFestival", $festival_data[ 'end_time' ], $festival_id);
            } else {//未进行
                $cron->addCron(1, 0, "节日有礼活动开启", "cronOpenFestival", $festival_data[ 'push_time' ], $festival_id);
                $cron->addCron(1, 0, "节日有礼活动关闭", "cronCloseFestival", $festival_data[ 'end_time' ], $festival_id);
            }

            model('promotion_festival')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_festival')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 编辑活动
     * @param $condition
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function editGames($condition, $festival_data, $award_data)
    {

        $festival_info = model('promotion_festival')->getInfo($condition, 'festival_id,status');
        if (in_array($festival_info[ 'status' ], [ 2, 3 ])) {
            return $this->error('', '已关闭或已结束的活动不能编辑');
        }
        $festival_id = $festival_info[ 'festival_id' ];

        $verify_condition = [
            [ 'festival_id', '<>', $festival_id ],
            [ 'status', 'in', '1,2' ],
            [ 'site_id', '=', $festival_data[ 'site_id' ] ],
            [ '', 'exp', Db::raw('not ( (`push_time` > ' . $festival_data[ 'end_time' ] . ' and `push_time` > ' . $festival_data[ 'push_time' ] . ' )  or (`end_time` < ' . $festival_data[ 'push_time' ] . ' and `end_time` < ' . $festival_data[ 'end_time' ] . '))') ]
        ];
        $res = model('promotion_festival')->getList($verify_condition, 'festival_id');
        if (!empty($res)) {
            return $this->error('', "当前时间段已有相同的活动");
        }

        $time = time();
        $festival_data[ 'update_time' ] = $time;

        if ($time > $festival_data[ 'push_time' ] && $time < $festival_data[ 'end_time' ]) {
            $festival_data[ 'status' ] = 1;
        } else {
            $festival_data[ 'status' ] = 0;
        }
        model('promotion_festival')->startTrans();
        try {

            model('promotion_festival')->update($festival_data, $condition);

            model('promotion_festival_award')->update($award_data, [ [ 'festival_id', '=', $festival_id ] ]);

            $cron = new Cron();

            $cron->deleteCron([ [ 'event', '=', 'cronOpenFestival' ], [ 'relate_id', '=', $festival_id ] ]);
            $cron->deleteCron([ [ 'event', '=', 'cronCloseFestival' ], [ 'relate_id', '=', $festival_id ] ]);

            if ($festival_data[ 'status' ] == 1) {//进行中

                $cron->addCron(1, 0, "节日有礼活动关闭", "cronCloseFestival", $festival_data[ 'end_time' ], $festival_id);
            } else {
                //未进行
                $cron->addCron(1, 0, "节日有礼活动开启", "cronOpenFestival", $festival_data[ 'push_time' ], $festival_id);
                $cron->addCron(1, 0, "节日有礼活动关闭", "cronCloseFestival", $festival_data[ 'end_time' ], $festival_id);
            }

            model('promotion_festival')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_festival')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取活动奖励
     * @param array $condition
     * @param string $field
     */
    public function getGameAward($condition = [], $field = '*')
    {
        $award_info = model('promotion_festival_award')->getInfo($condition, $field);
        if (!empty($award_info)) {
            $award_info[ 'award_type' ] = explode(',', $award_info[ 'award_type' ]);
            //获取优惠券信息
            if (isset($award_info[ 'coupon' ]) && !empty($award_info[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $award_info[ 'coupon' ] ] ], $coupon_field);
                $award_info[ 'coupon_list' ] = $coupon[ 'data' ];
            }
        }
        return $this->success($award_info);
    }

    /**
     * 获取活动信息
     * @param array $condition
     * @param string $field
     */
    public function getGamesInfo($condition, $field = '*')
    {
        $res = model('promotion_festival')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 活动有礼详情
     */
    public function getFestivalDetail($condition, $field, $alias = 'a', $join = [])
    {

        $res = model('promotion_festival')->getInfo($condition, $field, $alias, $join);
        if (!empty($res)) {
            $res[ 'award_type' ] = explode(',', $res[ 'award_type' ]);
            //获取优惠券信息
            if (isset($res[ 'coupon' ]) && !empty($res[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $res[ 'coupon' ] ] ], $coupon_field);
                $res[ 'coupon_list' ] = $coupon[ 'data' ];
                $res[ 'coupon_ids' ] = explode(',', $res[ 'coupon' ]);
            } else {
                $res[ 'coupon_ids' ] = [];
            }
        }
        return $this->success($res);
    }

    /**
     * 删除活动
     * @param $site_id
     * @param $game_id
     * @return array
     */
    public function deleteGames($site_id, $festival_id)
    {
        model('promotion_festival')->startTrans();
        try {
            model('promotion_festival')->delete([ [ 'site_id', '=', $site_id ], [ 'festival_id', '=', $festival_id ] ]);
            model('promotion_festival_award')->delete([ [ 'site_id', '=', $site_id ], [ 'festival_id', '=', $festival_id ] ]);
            model('promotion_festival')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_festival')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取活动列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getFestivalList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_festival')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取活动分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGamesPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('promotion_festival')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 关闭活动
     * @param $site_id
     * @param $game_id
     * @return array
     */
    public function finishGames($site_id, $festival_id)
    {
        $game_info = model('promotion_festival')->getInfo([ [ 'festival_id', '=', $festival_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if (!empty($game_info)) {

            if ($game_info[ 'status' ] != 3) {
                $res = model('promotion_festival')->update([ 'status' => 3 ], [ [ 'festival_id', '=', $festival_id ] ]);
//                if ($res) {
//                    $cron = new Cron();
//                    $cron->deleteCron([ [ 'event', '=', 'OpenGame' ], [ 'relate_id', '=', $game_id ] ]);
//                    $cron->deleteCron([ [ 'event', '=', 'CloseGame' ], [ 'relate_id', '=', $game_id ] ]);
//                }
                return $this->success($res);
            } else {
                $this->error('', '该活动已关闭');
            }

        } else {
            $this->error('', '该活动不存在');
        }
    }

    /**
     * 定时任务开启活动
     * @param $game_id
     * @return array|\multitype
     */
    public function cronOpenGames($game_id)
    {
        $game_info = model('promotion_festival')->getInfo([ [ 'festival_id', '=', $game_id ] ], 'start_time,push_time,status');
        if (!empty($game_info)) {

            if ($game_info[ 'start_time' ] <= time() && $game_info[ 'status' ] == 0) {
                $res = model('promotion_festival')->update([ 'status' => 1 ], [ [ 'festival_id', '=', $game_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "活动已开启或者关闭");
            }

        } else {
            return $this->error("", "活动不存在");
        }

    }

    /**
     * 开启活动
     * @param $game_id
     * @return array|\multitype
     */
    public function startGames($festival_id)
    {
        $game_info = model('promotion_festival')->getInfo([ [ 'festival_id', '=', $festival_id ] ], 'end_time,status');
        if (!empty($game_info)) {

            if ($game_info[ 'end_time' ] >= time()) {
                $res = model('promotion_festival')->update([ 'status' => 1 ], [ [ 'festival_id', '=', $festival_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "活动已结束");
            }

        } else {
            return $this->error("", "活动不存在");
        }

    }

    /**
     * 定时任务关闭活动
     * @param $game_id
     * @return array|\multitype
     */
    public function cronCloseGames($festival_id)
    {
        $game_info = model('promotion_festival')->getInfo([ [ 'festival_id', '=', $festival_id ] ], 'start_time,status');
        if (!empty($game_info)) {
            if ($game_info[ 'status' ] != 2) {
                $res = model('promotion_festival')->update([ 'status' => 2 ], [ [ 'festival_id', '=', $festival_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "活动不存在");
        }
    }

    /**
     * 节日有礼领取
     * @param $game_id
     * @param $member_id
     * @param $site_id
     */
    public function receive($festival_id, $member_id, $site_id)
    {
        $game_info = model('promotion_festival')->getInfo([ [ 'festival_id', '=', $festival_id ], [ 'site_id', '=', $site_id ] ]);
        if (empty($game_info)) return $this->error("", "未获取到活动信息");

        if ($game_info[ 'status' ] == 2 || $game_info[ 'status' ] == 3) return $this->error("", "活动已经结束");

        $member_info = model('member')->getInfo([ [ 'member_id', '=', $member_id ], [ 'site_id', '=', $site_id ], [ 'status', '=', 1 ] ], 'nickname,member_level,point');
        if (empty($member_info)) return $this->error("", "未获取到会员信息");

        if (!empty($game_info[ 'level_id' ])) {
            $level = explode(',', $game_info[ 'level_id' ]);
            if (!in_array($member_info[ 'member_level' ], $level)) {
                return $this->error("", "只有{$game_info['level_name']}等级的会员可参与该活动");
            }
        }

        // 判断今年有没有领取过
        $record_model = new Record();
        $start_year = date_to_time(date('Y-01-01 00:00:00'));
        $end_year = $start_year + 31535999;
        $record_condition[] = [ 'member_id', '=', $member_id];
        $record_condition[] = [ 'festival_id', '=', $festival_id ];
        $record_condition[] = [ 'receive_time', '>', $start_year ];
        $record_condition[] = [ 'receive_time', '<', $end_year ];
        $record_data = $record_model->getFestivalDrawRecordList($record_condition, 'record_id', 'receive_time desc')[ 'data' ];
        if (!empty($record_data)) {
            return $this->error('', "今年您已经领取过节日有礼奖励啦");
        }

        model('promotion_festival_draw_record')->startTrans();
        try {
            $member_account = new MemberAccount();
            $award_info = model('promotion_festival_award')->getInfo([ [ 'festival_id', '=', $festival_id ] ], 'award_id,festival_id,award_type,coupon,point,balance,balance_type,balance_money');

            if (!empty($award_info)) {
                $type_arr = explode(',', $award_info[ 'award_type' ]);
                foreach ($type_arr as $v) {
                    switch ( $v ) {
                        case 'point':
                            // 积分
                            $member_account->addMemberAccount($site_id, $member_id, 'point', $award_info[ 'point' ], $game_info[ 'festival_type' ], $festival_id, '节日有礼活动奖励发放');
                            break;
                        case 'balance':
                            // 余额
                            if ($award_info[ 'balance_type' ] == 0) {
                                $member_account->addMemberAccount($site_id, $member_id, AccountDict::balance, $award_info[ 'balance' ], $game_info[ 'festival_type' ], $festival_id, '节日有礼活动奖励发放');
                            } else {
                                $member_account->addMemberAccount($site_id, $member_id, 'balance_money', $award_info[ 'balance_money' ], $game_info[ 'festival_type' ], $festival_id, '节日有礼活动奖励发放');
                            }

                            break;
                        case 'coupon':
                            // 优惠券
                            $coupon = new Coupon();
                            $coupon_array = explode(',', $award_info[ 'coupon' ]);
                            $coupon_array = array_map(function($value) {
                                return [ 'coupon_type_id' => $value, 'num' => 1 ];
                            }, $coupon_array);
                            $receive_res = $coupon->giveCoupon($coupon_array, $site_id, $member_id, Coupon::GET_TYPE_ACTIVITY_GIVE);
                            break;
                        case 4:
                            // 赠品
                            break;
                    }

                }
                $record[ 'award_id' ] = $award_info[ 'award_id' ];
                $record[ 'festival_id' ] = $award_info[ 'festival_id' ];
                $record[ 'festival_type' ] = $game_info[ 'festival_type' ];
                $record[ 'member_id' ] = $member_id;
                $record[ 'site_id' ] = $site_id;
                $record[ 'receive_time' ] = time();
                $record[ 'member_nick_name' ] = $member_info[ 'nickname' ];

                model('promotion_festival_draw_record')->add($record);
            }

            model('promotion_festival_draw_record')->commit();
            return $this->success([ 'status' => 1 ]);
        } catch (\Exception $e) {
            model('promotion_festival_draw_record')->rollback();
            return $this->error("", $e->getMessage());
        }
    }

    /**
     * 推广二维码
     * @param $game_id
     * @param $game_name
     * @param $url
     * @param $site_id
     * @param string $type
     * @return array
     */
    public function qrcode($festival_id, $game_name, $url, $site_id, $type = "create")
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                "id" => $festival_id
            ],
            'page' => $url,
            'qrcode_path' => 'upload/qrcode/games',
            'qrcode_name' => "games_qrcode_" . $festival_id
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ( $k ) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[ $k ][ 'status' ] = 1;
                    $path[ $k ][ 'url' ] = $wap_domain . $data[ 'page' ] . '?id=' . $festival_id;
                    $path[ $k ][ 'img' ] = "upload/qrcode/games/games_qrcode_" . $festival_id . "_" . $k . ".png";
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信小程序';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }

                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信小程序';
                    }
                    break;

                case 'wechat' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WECHAT_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信公众号';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }
                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信公众号';
                    }
                    break;
            }

        }

        $return = [
            'path' => $path,
            'game_name' => $game_name,
        ];

        return $this->success($return);
    }

}