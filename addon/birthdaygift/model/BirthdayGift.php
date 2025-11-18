<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\birthdaygift\model;

use addon\coupon\model\Coupon;
use addon\coupon\model\CouponType;
use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 生日有礼
 */
class BirthdayGift extends BaseModel
{
    public $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        3 => '已关闭',
    ];

    /**
     * 获取订单详细列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @param null $group
     * @param null $limit
     * @return array
     */
    public function birthdayGiftPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [], $group = null, $limit = null)
    {
        $list = model('promotion_birthdaygift')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group, $limit);
        return $this->success($list);
    }

    /**
     * 添加生日有礼
     */
    public function addBirthdayGiftActivity($data)
    {
        //时间检测
        if ($data[ 'end_time' ] < time()) {
            return $this->error('', '结束时间不能早于当前时间');
        }

        $activity_info = model('promotion_birthdaygift')->getInfo([
            ['status', 'in', '0,1'],
            ['site_id', '=', $data[ 'site_id' ]],
            ['', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))')]
        ], 'start_time,end_time');
        if (!empty($activity_info)) {
            return $this->error('', '此时间段已有同类型的活动');
        }
        $data[ 'create_time' ] = time();
        if ($data[ 'start_time' ] <= time()) {
            $data[ 'status' ] = 1;//直接启动
        } else {
            $data[ 'status' ] = 0;
        }

        model('promotion_birthdaygift')->startTrans();
        try {

            $res = model('promotion_birthdaygift')->add($data);

            $cron = new Cron();
            if ($data[ 'start_time' ] <= time()) {
                $cron->addCron(1, 0, '生日有礼活动关闭', 'CloseBirthdayGift', $data[ 'end_time' ], $res);
            } else {
                $cron->addCron(1, 0, '生日有礼活动开启', 'OpenBirthdayGift', $data[ 'start_time' ], $res);
                $cron->addCron(1, 0, '生日有礼活动关闭', 'CloseBirthdayGift', $data[ 'end_time' ], $res);
            }

            model('promotion_birthdaygift')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_birthdaygift')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 编辑生日有礼活动
     * @param array $data 活动数据
     * @param $id
     * @return array
     */
    public function editBirthdayGiftActivity($data, $id)
    {
        //时间检测
        if ($data[ 'end_time' ] < time()) {
            return $this->error('', '结束时间不能早于当前时间');
        }

        $activity_info = model('promotion_birthdaygift')->getInfo([
            ['status', 'in', '0,1'],
            ['site_id', '=', $data[ 'site_id' ]],
            ['', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))')],
            ['id', '<>', $id],
        ], 'id,start_time,end_time');
        if (!empty($activity_info)) {
            return $this->error('', '此时间段已有同类型的活动');
        }
        $data[ 'update_time' ] = time();
        if ($data[ 'start_time' ] <= time()) {
            $data[ 'status' ] = 1;//直接启动
        } else {
            $data[ 'status' ] = 0;
        }

        model('promotion_birthdaygift')->startTrans();
        try {

            model('promotion_birthdaygift')->update($data, [['site_id', '=', $data[ 'site_id' ]], ['id', '=', $id]]);

            $cron = new Cron();

            $cron->deleteCron([['event', '=', 'CloseBirthdayGift'], ['relate_id', '=', $id]]);
            $cron->deleteCron([['event', '=', 'OpenBirthdayGift'], ['relate_id', '=', $id]]);

            if ($data[ 'start_time' ] <= time()) {
                $cron->addCron(1, 0, '生日有礼关闭', 'CloseBirthdayGift', $data[ 'end_time' ], $id);
            } else {
                $cron->addCron(1, 0, '生日有礼开启', 'OpenBirthdayGift', $data[ 'start_time' ], $id);
                $cron->addCron(1, 0, '生日有礼关闭', 'CloseBirthdayGift', $data[ 'end_time' ], $id);
            }

            model('promotion_birthdaygift')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_birthdaygift')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 修改生日有礼活动，用于做状态修改
     * @param $id
     * @param $site_id
     * @param array $data
     * @return array
     */
    public function updateBirthdayGift($id, $site_id, $data = [])
    {
        $res = model('promotion_birthdaygift')->update($data, [['id', '=', $id], ['site_id', '=', $site_id]]);
        return $this->success($res);
    }

    /**
     * 获取生日有礼列表
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getBirthdayGiftList($condition, $field = '*')
    {
        $list = model('promotion_birthdaygift')->getList($condition, $field);
        return $this->success($list);
    }

    /**
     * 获取生日有礼详情
     * @param $condition
     * @param $field
     * @return array
     */
    public function getBirthdayGiftDetail($condition, $field)
    {
        $res = model('promotion_birthdaygift')->getInfo($condition, $field);
        if (!empty($res)) {
            $res[ 'type' ] = explode(',', $res[ 'type' ]);
            //获取优惠券信息
            if (isset($res[ 'coupon' ]) && !empty($res[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([['coupon_type_id', 'in', $res[ 'coupon' ]]], $coupon_field);
                $res[ 'coupon_list' ] = $coupon[ 'data' ];
                $res[ 'coupon_ids' ] = explode(',', $res[ 'coupon' ]);
            } else {
                $res[ 'coupon_ids' ] = [];
            }
        }
        return $this->success($res);
    }

    /**
     * 获取生日有礼的相关奖励
     * @param $site_id
     * @return array
     */
    public function getAward($site_id)
    {
        // 获取进行中的生日有礼
        $award_info = model('promotion_birthdaygift')->getInfoTo([['site_id', '=', $site_id], ['status', '=', '1'], ['is_delete', '=', '0']], 'id,activity_name,activity_time_type,level_id,blessing_content,type,point,balance,balance_type,balance_money,coupon,level_name');
        if (!empty($award_info)) {
            $award_info[ 'type' ] = explode(',', $award_info[ 'type' ]);
            //获取优惠券信息
            if (isset($award_info[ 'coupon' ]) && !empty($award_info[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([['coupon_type_id', 'in', $award_info[ 'coupon' ]]], $coupon_field);
                $award_info[ 'coupon_list' ] = $coupon[ 'data' ];
            }
        }
        return $this->success($award_info);
    }

    public function receive($member_id, $activity_id, $site_id)
    {
        $award_info = model('promotion_birthdaygift')->getInfo([['site_id', '=', $site_id], ['id', '=', $activity_id], ['is_delete', '=', '0'], ['status', '=', 1]], 'id,activity_name,activity_time_type,level_id,blessing_content,type,point,balance,balance_type,balance_money,coupon');
        if (empty($award_info)) return $this->error('', '未获取到活动信息');

        $member_info = model('member')->getInfo([['member_id', '=', $member_id], ['site_id', '=', $site_id], ['status', '=', 1]], 'nickname,member_level,point');
        if (empty($member_info)) return $this->error('', '未获取到会员信息');

        if (!empty($award_info[ 'level_id' ])) {
            $level = explode(',', $award_info[ 'level_id' ]);
            if (!in_array($member_info[ 'member_level' ], $level)) {
                return $this->error('', "只有{$award_info['level_name']}等级的会员可参与该活动");
            }
        }

        // 判断今年有没有领取过
        $start_year = strtotime(date('Y-01-01 00:00:00'));
        $end_year = strtotime('+1 year', $start_year);

        $record_condition[] = ['member_id', '=', $member_id];
        $record_condition[] = ['receive_time', '>', $start_year];
        $record_condition[] = ['receive_time', '<', $end_year];

        $recode = $this->getRecordList($record_condition,'record_id');
        if (!empty($recode[ 'data' ])) {
            return $this->error('', "今年您已经领取过生日有礼啦");
        }

        model('promotion_birthdaygift_record')->startTrans();
        try {
            $member_account = new MemberAccount();

            if (!empty($award_info[ 'type' ])) {
                $type_arr = explode(',', $award_info[ 'type' ]);

                foreach ($type_arr as $v) {
                    switch ($v) {
                        case 'point':
                            // 积分
                            $member_account->addMemberAccount($site_id, $member_id, AccountDict::point, $award_info[ 'point' ], 'birthdaygift', $activity_id, '生日有礼活动奖励发放');
                            break;
                        case 'balance':
                            // 余额
                            if ($award_info[ 'balance_type' ] == 0) {
                                $member_account->addMemberAccount($site_id, $member_id, AccountDict::balance, $award_info[ 'balance' ], 'birthdaygift', $activity_id, '生日有礼活动奖励发放');
                            } else {
                                $member_account->addMemberAccount($site_id, $member_id, 'balance_money', $award_info[ 'balance_money' ], 'birthdaygift', $activity_id, '生日有礼活动奖励发放');
                            }
                            break;
                        case 'coupon':
                            // 优惠券
                            $coupon = new Coupon();
                            $coupon_list = explode(',', $award_info[ 'coupon' ]);
                            $coupon_list = array_map(function($value) {
                                return ['coupon_type_id' => $value, 'num' => 1];
                            }, $coupon_list);
                            $receive_res = $coupon->giveCoupon($coupon_list, $site_id, $member_id, Coupon::GET_TYPE_ACTIVITY_GIVE);
                            break;
                        case 4:
                            // 赠品
                            break;
                    }
                }
                $record[ 'member_id' ] = $member_id;
                $record[ 'member_name' ] = $member_info[ 'nickname' ];
                $record[ 'activity_id' ] = $activity_id;
                $record[ 'receive_time' ] = time();

            }
            $res = model('promotion_birthdaygift_record')->add($record);
            model('promotion_birthdaygift_record')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_games')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 获取生日有礼奖励信息列表
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getRecordList($condition, $field = '*')
    {
        $list = model('promotion_birthdaygift_record')->getList($condition, $field);
        return $this->success($list);
    }

    /**
     * 获取生日有礼奖励分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @param null $group
     * @param null $limit
     * @return array
     */
    public function getRecordPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [], $group = null, $limit = null)
    {
        $list = model('promotion_birthdaygift_record')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group, $limit);
        return $this->success($list);
    }

    /**
     * 验证当前年是否领取
     */
    public function verificationRecord($member_id)
    {
        $list = $this->getRecordList([['member_id', '=', $member_id]]);
        $res = true;
        if ($list[ 'data' ]) {
            foreach ($list[ 'data' ] as $key => $val) {
                if (date('Y', $val[ 'receive_time' ]) == date('Y', time())) {
                    $res = false;
                    break;
                }
            }
        }
        return $this->success($res);
    }

    /**
     * 定时开启活动
     * @param $id
     * @return array
     */
    public function cronOpenBirthdayGift($id)
    {
        $info = model('promotion_birthdaygift')->getInfo([['id', '=', $id]], 'start_time,status');
        if (!empty($info)) {
            if ($info[ 'start_time' ] <= time() && $info[ 'status' ] == 0) {

                model('promotion_birthdaygift')->startTrans();
                try {

                    model('promotion_birthdaygift')->update(['status' => 1], [['id', '=', $id]]);

                    model('promotion_birthdaygift')->commit();
                    return $this->success();
                } catch (\Exception $e) {

                    model('promotion_birthdaygift')->rollback();
                    return $this->error('', $e->getMessage());
                }

            } else {
                return $this->error('', '生日有礼活动已开启或者关闭');
            }

        } else {
            return $this->error('', '生日有礼活动不存在');
        }
    }

    /**
     * 定时关闭活动
     * @param $recommend_id
     * @return array
     */
    public function cronCloseBirthdayGift($id)
    {
        $info = model('promotion_birthdaygift')->getInfo([['id', '=', $id]], 'status');
        if (!empty($info)) {
            if ($info[ 'status' ] == 1) {

                model('promotion_birthdaygift')->startTrans();
                try {

                    model('promotion_birthdaygift')->update(['status' => -1], [['id', '=', $id]]);

                    model('promotion_birthdaygift')->commit();
                    return $this->success();
                } catch (\Exception $e) {

                    model('promotion_birthdaygift')->rollback();
                    return $this->error('', $e->getMessage());
                }

            } else {
                return $this->error('', '生日有礼活动已关闭');
            }
        } else {
            return $this->error('', '生日有礼活动不存在');
        }
    }
}