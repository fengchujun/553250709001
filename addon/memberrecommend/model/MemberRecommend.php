<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecommend\model;

use app\dict\member_account\AccountDict;
use app\model\system\Cron;
use app\model\BaseModel;
use think\facade\Db;
use app\model\member\MemberAccount;
use addon\coupon\model\Coupon;
use addon\coupon\model\CouponType;

/**
 * 邀请奖励
 */
class MemberRecommend extends BaseModel
{
    //状态
    private $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        -1 => '已关闭',
    ];

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 添加邀请奖励
     * @param $data
     * @return array
     */
    public function addRecommend($data)
    {
        //时间检测
        if ($data[ 'end_time' ] < time()) {
            return $this->error('', '结束时间不能早于当前时间');
        }

        $activity_info = model('member_recommend')->getInfo([
            [ 'status', 'in', "0,1" ],
            [ 'site_id', '=', $data[ 'site_id' ] ],
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))') ]
        ], 'recommend_name,start_time,end_time');
        if (!empty($activity_info)) {
            return $this->error('', '此时间段已有同类型的活动');
        }

        $data[ 'create_time' ] = time();
        if ($data[ 'start_time' ] <= time()) {
            $data[ 'status' ] = 1;//直接启动
        } else {
            $data[ 'status' ] = 0;
        }

        model('member_recommend')->startTrans();
        try {

            $recommend_id = model('member_recommend')->add($data);

            $cron = new Cron();
            if ($data[ 'start_time' ] <= time()) {
                $cron->addCron(1, 0, "邀请奖励关闭", "CloseRecommend", $data[ 'end_time' ], $recommend_id);
            } else {
                $cron->addCron(1, 0, "邀请奖励开启", "OpenRecommend", $data[ 'start_time' ], $recommend_id);
                $cron->addCron(1, 0, "邀请奖励关闭", "CloseRecommend", $data[ 'end_time' ], $recommend_id);
            }

            model('member_recommend')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_recommend')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 修改邀请奖励
     * @param $data
     * @return array
     */
    public function editRecommend($data)
    {
        $recommend_id = $data[ 'recommend_id' ];
        unset($data[ 'recommend_id' ]);
        $recommend_status = model('member_recommend')->getInfo([ [ 'site_id', '=', $data[ 'site_id' ] ], [ 'recommend_id', '=', $recommend_id ] ], 'status');
        if ($recommend_status[ 'status' ] != 0) {
            return $this->error('', '只有未开始的活动才能进行修改');
        }

        //时间检测
        if ($data[ 'end_time' ] < time()) {
            return $this->error('', '结束时间不能早于当前时间');
        }

        $activity_info = model('member_recommend')->getInfo([
            [ 'status', 'in', "0,1" ],
            [ 'site_id', '=', $data[ 'site_id' ] ],
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))') ],
            [ 'recommend_id', '<>', $recommend_id ],
        ], 'recommend_name,start_time,end_time');
        if (!empty($activity_info)) {
            return $this->error('', '此时间段已有同类型的活动');
        }

        $data[ 'update_time' ] = time();
        if ($data[ 'start_time' ] <= time()) {
            $data[ 'status' ] = 1;//直接启动
        } else {
            $data[ 'status' ] = 0;
        }

        model('member_recommend')->startTrans();
        try {

            model('member_recommend')->update($data, [ [ 'site_id', '=', $data[ 'site_id' ] ], [ 'recommend_id', '=', $recommend_id ] ]);

            $cron = new Cron();

            $cron->deleteCron([ [ 'event', '=', 'CloseRecommend' ], [ 'relate_id', '=', $recommend_id ] ]);
            $cron->deleteCron([ [ 'event', '=', 'OpenRecommend' ], [ 'relate_id', '=', $recommend_id ] ]);

            if ($data[ 'start_time' ] <= time()) {
                $cron->addCron(1, 0, "邀请奖励关闭", "CloseRecommend", $data[ 'end_time' ], $recommend_id);
            } else {
                $cron->addCron(1, 0, "邀请奖励开启", "OpenRecommend", $data[ 'start_time' ], $recommend_id);
                $cron->addCron(1, 0, "邀请奖励关闭", "CloseRecommend", $data[ 'end_time' ], $recommend_id);
            }

            model('member_recommend')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_recommend')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 删除邀请奖励
     * @param $recommend_id
     * @param $site_id
     * @return array
     */
    public function deleteRecommend($recommend_id, $site_id)
    {
        $condition = [
            [ 'recommend_id', '=', $recommend_id ],
            [ 'site_id', '=', $site_id ]
        ];
        $res = model('member_recommend')->delete($condition);

        $cron = new Cron();
        $cron->deleteCron([ [ 'event', '=', 'OpenRecommend' ], [ 'relate_id', '=', $recommend_id ] ]);
        $cron->deleteCron([ [ 'event', '=', 'CloseRecommend' ], [ 'relate_id', '=', $recommend_id ] ]);
        return $this->success($res);
    }

    /**
     * 活动信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getRecommendInfo($condition, $field = '*')
    {
        $res = model('member_recommend')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 活动详情
     * @param $recommend_id
     * @param $site_id
     * @param string $field
     * @return array
     */
    public function getRecommendDetail($recommend_id, $site_id, $field = '*')
    {
        $res = model('member_recommend')->getInfo([ [ 'recommend_id', '=', $recommend_id ], [ 'site_id', '=', $site_id ] ], $field);

        if (!empty($res)) {
            $res[ 'type' ] = explode(',', $res[ 'type' ]);
            //获取优惠券信息
            if (isset($res[ 'coupon' ]) && !empty($res[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time,goods_type,max_fetch';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $res[ 'coupon' ] ] ], $coupon_field);
                $res[ 'coupon_list' ] = $coupon[ 'data' ];
            }
        }

        return $this->success($res);
    }

    /**
     * 活动列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getRecommendList($condition = [], $field = '*', $order = 'recommend_id desc', $limit = null)
    {
        $list = model('member_recommend')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 活动分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getRecommendPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_recommend')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 定时开启活动
     * @param $recommend_id
     * @return array
     */
    public function cronOpenRecommend($recommend_id)
    {
        $info = model('member_recommend')->getInfo([ [ 'recommend_id', '=', $recommend_id ] ], 'start_time,status');
        if (!empty($info)) {
            if ($info[ 'start_time' ] <= time() && $info[ 'status' ] == 0) {

                model('member_recommend')->startTrans();
                try {

                    model('member_recommend')->update([ 'status' => 1 ], [ [ 'recommend_id', '=', $recommend_id ] ]);

                    model('member_recommend')->commit();
                    return $this->success();
                } catch (\Exception $e) {

                    model('member_recommend')->rollback();
                    return $this->error('', $e->getMessage());
                }

            } else {
                return $this->error("", "邀请奖励活动已开启或者关闭");
            }

        } else {
            return $this->error("", "邀请奖励活动不存在");
        }
    }

    /**
     * 定时关闭活动
     * @param $recommend_id
     * @return array
     */
    public function cronCloseRecommend($recommend_id)
    {
        $info = model('member_recommend')->getInfo([ [ 'recommend_id', '=', $recommend_id ] ], 'status');
        if (!empty($info)) {
            if ($info[ 'status' ] == 1) {

                model('member_recommend')->startTrans();
                try {

                    model('member_recommend')->update([ 'status' => 2 ], [ [ 'recommend_id', '=', $recommend_id ] ]);

                    model('member_recommend')->commit();
                    return $this->success();
                } catch (\Exception $e) {

                    model('member_recommend')->rollback();
                    return $this->error('', $e->getMessage());
                }

            } else {
                return $this->error("", "邀请奖励活动已关闭");
            }
        } else {
            return $this->error("", "邀请奖励活动不存在");
        }
    }

    /**
     * 关闭活动
     * @param $recommend_id
     * @param $site_id
     * @return array
     */
    public function closeRecommend($recommend_id, $site_id)
    {
        $condition = array (
            [ 'recommend_id', '=', $recommend_id ],
            [ 'site_id', "=", $site_id ]
        );
        $info = model('member_recommend')->getInfo($condition, 'start_time,end_time,status');
        if (!empty($info)) {

            if ($info[ 'status' ] == 1) {

                model('member_recommend')->startTrans();
                try {

                    model('member_recommend')->update([ 'status' => -1 ], [ [ 'recommend_id', '=', $recommend_id ], [ 'site_id', "=", $site_id ] ]);

                    model('member_recommend')->commit();
                    return $this->success();
                } catch (\Exception $e) {

                    model('member_recommend')->rollback();
                    return $this->error('', $e->getMessage());
                }

            } else {
                return $this->error("", "邀请奖励活动已关闭");
            }

        } else {
            return $this->error("", "邀请奖励活动不存在");
        }
    }

    /**
     * 活动奖励信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getRecommendAwardInfo($condition, $field = '*')
    {
        $res = model('member_recommend_award')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 查询数量
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getRecommendAwardCount($condition, $field = 'award_id')
    {
        $count = model('member_recommend_award')->getCount($condition, $field);
        return $this->success($count);
    }

    /**
     * 活动奖励详情
     * @param $award_id
     * @param $site_id
     * @param string $field
     * @return array
     */
    public function getRecommendAwardDetail($award_id, $site_id, $field = '*')
    {
        $res = model('member_recommend_award')->getInfo([ [ 'award_id', '=', $award_id ], [ 'site_id', '=', $site_id ] ], $field);

        if (!empty($res)) {
            if (!empty($res[ 'coupon' ])) {
                $coupon_list = model('promotion_coupon_type')->getList([ [ 'coupon_type_id', 'in', $res[ 'coupon' ] ] ]);
                $res[ 'coupon_list' ] = $coupon_list;
            }
        }

        return $this->success($res);
    }

    /**
     * 活动奖励列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getRecommendAwardList($condition = [], $field = '*', $order = 'award_id desc', $limit = null)
    {
        $list = model('member_recommend_award')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 活动奖励分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getRecommendAwardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('member_recommend_award')->pageList($condition, $field, $order, $page, $page_size);

        if (!empty($list[ 'list' ])) {
            foreach ($list[ 'list' ] as $key => $value) {
                $list[ 'list' ][ $key ][ 'coupon_list' ] = [];
                if (!empty($value[ 'coupon' ])) {
                    $coupon_list = model('promotion_coupon_type')->getList([ [ 'coupon_type_id', 'in', $value[ 'coupon' ] ] ]);
                    $list[ 'list' ][ $key ][ 'coupon_list' ] = $coupon_list;
                }
                $member_info = model('member')->getInfo([ 'member_id' => $value[ 'source_member' ] ], 'headimg,nickname');
                $list[ 'list' ][ $key ][ 'headimg' ] = $member_info[ 'headimg' ];
                $list[ 'list' ][ $key ][ 'source_member_nickname' ] = $member_info[ 'nickname' ];
            }

        }

        return $this->success($list);
    }

    /**
     * 发放奖励
     * @param $data
     * @return array
     */
    public function receiveAward($data)
    {

        $member_info = model('member')->getInfo([ 'member_id' => $data[ 'member_id' ], 'site_id' => $data[ 'site_id' ] ], 'source_member,nickname');

        if (!empty($member_info[ 'source_member' ])) {
            $info = model('member_recommend')->getInfo([
                [ 'status', '=', "1" ],
                [ 'site_id', '=', $data[ 'site_id' ] ]
            ], '*');

            if (!empty($info)) {

                //判断领取次数
                $count = model('member_recommend_award')->getCount([ 'recommend_id' => $info[ 'recommend_id' ], 'member_id' => $member_info[ 'source_member' ] ], 'award_id');
                if ($info[ 'max_fetch' ] == 0 || $count < $info[ 'max_fetch' ]) {

                    $type = explode(',', $info[ 'type' ]);
                    //邀请人信息
                    $source_member_info = model('member')->getInfo([ 'member_id' => $member_info[ 'source_member' ], 'site_id' => $data[ 'site_id' ] ], 'nickname');

                    $member_account_model = new MemberAccount();
                    model('member_recommend_award')->startTrans();
                    try {
                        $balance = 0;
                        $point = 0;
                        $coupon = '';
                        $coupon_num = 0;
                        //赠送红包
                        if ($info[ 'balance' ] > 0 && in_array('balance', $type)) {
                            $balance = $info[ 'balance' ];
                            $member_account_model->addMemberAccount($data[ 'site_id' ], $member_info[ 'source_member' ], AccountDict::balance, $balance, 'memberrecommend', '邀请得红包' . $balance, '活动奖励发放');
                        }
                        //赠送积分
                        if ($info[ 'point' ] > 0 && in_array('point', $type)) {
                            $point = $info[ 'point' ];
                            $member_account_model->addMemberAccount($data[ 'site_id' ], $member_info[ 'source_member' ], 'point', $point, 'memberrecommend', '邀请得积分' . $point, '活动奖励发放');
                        }

                        if (!empty($info[ 'coupon' ]) && in_array('coupon', $type)) {
                            //给用户发放优惠券
                            $coupon_model = new Coupon();
                            $coupon_array = explode(',', $info[ 'coupon' ]);
                            $coupon_array = array_map(function($value) {
                                return [ 'coupon_type_id' => $value, 'num' => 1 ];
                            }, $coupon_array);
                            $res = $coupon_model->giveCoupon($coupon_array, $data[ 'site_id' ], $member_info[ 'source_member' ], Coupon::GET_TYPE_ACTIVITY_GIVE);
                            //更新已得优惠券数量
                            if ($res[ 'code' ] >= 0) {
                                $coupon_num += $res[ 'data' ];
                                $coupon = $info[ 'coupon' ];
                            }
                        }
                        //有一项不为0 添加邀请奖励
                        if (!empty($point) || !empty($balance) || !empty($coupon)) {
                            $data = [
                                "site_id" => $data[ 'site_id' ],
                                "recommend_id" => $info[ 'recommend_id' ],
                                "recommend_name" => $info[ 'recommend_name' ],
                                "member_id" => $member_info[ 'source_member' ],
                                "member_nickname" => $source_member_info[ 'nickname' ],
                                "source_member" => $data[ 'member_id' ],
                                "source_member_nickname" => $member_info[ 'nickname' ],
                                "create_time" => time(),
                                "remark" => '邀请奖励',
                                "point" => $point,
                                "balance" => $balance,
                                "coupon" => $coupon,
                                "coupon_num" => $coupon_num,
                            ];

                            model('member_recommend_award')->add($data);
                        }

                        model('member_recommend_award')->commit();
                        return $this->success();
                    } catch (\Exception $e) {

                        model('member_recommend_award')->rollback();
                        return $this->error('', $e->getMessage());
                    }

                }

            }

        }

    }

    /**
     * 获取最新一条信息
     * @param $site_id
     * @return array
     */
    public function getRecommendFirstData($site_id)
    {
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'status', '=', 1 ],
            [ 'end_time', '>', time() ]
        ];
        $res = model('member_recommend')->getFirstData($condition, '*', 'create_time desc');

        if (!empty($res)) {
            //获取优惠券信息
            if (isset($res[ 'coupon' ]) && !empty($res[ 'coupon' ])) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $res[ 'coupon' ] ] ], $coupon_field);
                $res[ 'coupon_list' ] = $coupon[ 'data' ];
            }
        }

        return $this->success($res);
    }
}