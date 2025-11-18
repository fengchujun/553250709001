<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\divideticket\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use extend\Poster as PosterExtend;
use app\model\upload\Upload;

/**
 * 好友瓜分券活动表
 * Class Divideticket
 * @package addon\divideticket\model
 */
class Divideticket extends BaseModel
{
    private $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        -1 => '已关闭'
    ];

    /**
     * 获取活动状态
     * @return array
     */
    public function getDivideticketStatus()
    {
        return $this->status;
    }

    /**
     * 获取分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getDivideticketPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('promotion_friends_coupon')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取优惠券活动信息
     * @param array $condition
     * @param bool $field
     * @param string $alias
     * @param null $join
     * @param null $data
     * @return array
     */
    public function getDivideticketInfo($condition = [], $field = true, $alias = 'a', $join = null, $data = null)
    {
        $res = model('promotion_friends_coupon')->getInfo($condition, $field, $alias, $join, $data);

        if ($res) {
            if (isset($res[ 'goods_type' ]) && $res[ 'goods_type' ] == 2) {
                $field = 'goods_id,goods_name,FLOOR(goods_stock) as goods_stock,price,sort,goods_image';
                $goods_ids = substr($res[ 'goods_ids' ], '1', '-1');
                $goods_list = model('goods')->getList([ [ 'goods_id', 'in', $goods_ids ] ], $field);
                $res[ 'goods_list' ] = $goods_list ?? [];
            } else {
                $res[ 'goods_list' ] = [];
            }
            $res[ 'goods_list_count' ] = count($res[ 'goods_list' ]);
        }
        return $this->success($res);
    }


    /**
     * 新增瓜分优惠券活动
     * @param $data
     * @return array
     */
    public function addDivideticket($data)
    {
        if ($data[ 'start_time' ] > time()) {
            $data[ 'status' ] = 0;
            $data[ 'status_name' ] = $this->status[ 0 ];
        } else {
            $data[ 'status' ] = 1;
            $data[ 'status_name' ] = $this->status[ 1 ];
        }

        //获取商品id
        if ($data[ 'goods_type' ] == 1) {//全部商品参与
            $data[ 'goods_ids' ] = '';
        }
        $data[ 'goods_ids' ] = ',' . $data[ 'goods_ids' ] . ',';
        model('promotion_friends_coupon')->startTrans();
        try {
            $coupon_type_data = [
                'promotion_type' => 1,
                'promotion_name' => 'divideticket',
                'at_least' => $data[ 'at_least' ],
                'is_limit' => $data[ 'is_limit' ],
                'goods_type' => $data[ 'goods_type' ],
                'goods_ids' => $data[ 'goods_ids' ]
            ];

            $coupon_type_id = model('promotion_coupon_type')->add($coupon_type_data);

            $data[ 'coupon_type_id' ] = $coupon_type_id;

            $res = model("promotion_friends_coupon")->add($data);

            $cron = new Cron();

            //增加定时更改活动状态自动事件
            if (!empty($data[ 'start_time' ])) {
                $cron->addCron(1, 0, "瓜分优惠券变更活动状态", "CronChangeDivideticketStatus", $data[ 'start_time' ], $res);
            }
            if (!empty($data[ 'end_time' ])) {
                $cron->addCron(1, 0, "瓜分优惠券变更活动状态", "CronChangeDivideticketStatus", $data[ 'end_time' ], $res);
            }

            model('promotion_friends_coupon')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_friends_coupon')->rollback();
            return $this->error($e->getMessage());
        }
    }

    public function editDivideticket($data)
    {
        $data[ 'update_time' ] = time();

        if ($data[ 'start_time' ] > time()) {
            $data[ 'status' ] = 0;
            $data[ 'status_name' ] = $this->status[ 0 ];
        } else {
            $data[ 'status' ] = 1;
            $data[ 'status_name' ] = $this->status[ 1 ];
        }

        //获取商品id
        if ($data[ 'goods_type' ] == 1) {//全部商品参与
            $data[ 'goods_ids' ] = '';
        }
        $data[ 'goods_ids' ] = ',' . $data[ 'goods_ids' ] . ',';
        $coupon_id = $data[ 'coupon_id' ];
        unset($data[ 'coupon_id' ]);

        #(修改发放量与库存)
        $old_info = model("promotion_friends_coupon")->getInfo([ [ 'coupon_id', '=', $coupon_id ] ], 'inventory,count,coupon_type_id,image');
        $data[ 'count' ] = $old_info[ 'count' ] + $data[ 'inventory' ] - $old_info[ 'inventory' ];
        if (!empty($old_info[ 'image' ]) && !empty($data[ 'image' ]) && $old_info[ 'image' ] != $data[ 'image' ]) {
            $upload_model = new Upload();
            $upload_model->deletePic($old_info[ 'image' ], $data[ 'site_id' ]);
        }

        model('promotion_coupon_type')->update([ 'goods_type' => $data[ 'goods_type' ], 'goods_ids' => $data[ 'goods_ids' ] ], [ [ 'coupon_type_id', '=', $old_info[ 'coupon_type_id' ] ] ]);

        // 通过瓜分领取的优惠券，如果没有使用，那就要更新优惠券的适用商品状态
        model('promotion_coupon')->update([ 'goods_type' => $data[ 'goods_type' ], 'goods_ids' => $data[ 'goods_ids' ] ], [ [ 'coupon_type_id', '=', $old_info[ 'coupon_type_id' ] ], [ 'state', '=', 1 ] ]);

        $res = model("promotion_friends_coupon")->update($data, [ [ 'coupon_id', '=', $coupon_id ] ]);
        $cron = new Cron();
        $cron->deleteCron([ [ 'event', '=', 'CronChangeDivideticketStatus' ], [ 'relate_id', '=', $coupon_id ] ]);
        if ($data[ 'status' ] == 0) {
            $cron->addCron(1, 0, "变更活动状态", "CronChangeDivideticketStatus", $data[ 'start_time' ], $coupon_id);
            $cron->addCron(1, 0, "变更活动状态", "CronChangeDivideticketStatus", $data[ 'end_time' ], $coupon_id);
        } else if ($data[ 'status' ] == 1) {
            $cron->addCron(1, 0, "变更活动状态", "CronChangeDivideticketStatus", $data[ 'end_time' ], $coupon_id);
        }
        return $this->success($res);
    }

    /**
     * 更改活动状态
     * @param $coupon_id
     * @return array
     */
    public function changeDivideticketStatus($coupon_id)
    {
        $info = model('promotion_friends_coupon')->getInfo([ [ 'coupon_id', '=', $coupon_id ] ]);
        if (empty($info)) $this->success();

        if ($info[ 'end_time' ] <= time()) {
            $status = 2;
            $status_name = $this->status[ 2 ];
            model('promotion_friends_coupon_group')->update([ 'is_look' => 1 ], [ [ 'promotion_id', '=', $coupon_id ], [ 'status', '=', 2 ] ]);
        } else if ($info[ 'start_time' ] <= time() && $info[ 'end_time' ] > time()) {
            $status = 1;
            $status_name = $this->status[ 1 ];
        } else {
            $status = 0;
            $status_name = $this->status[ 0 ];
        }
        $res = model('promotion_friends_coupon')->update([ 'status' => $status, 'status_name' => $status_name ], [ [ 'coupon_id', '=', $coupon_id ] ]);
        return $this->success($res);
    }

    /**
     * 关闭活动
     * @param $data
     * @return array
     */
    public function closeDividetocket($data)
    {
        $coupon_id = $data[ 'coupon_id' ];
        $site_id = $data[ 'site_id' ];
        $condition = [
            [ 'coupon_id', '=', $coupon_id ],
            [ 'site_id', '=', $site_id ],
        ];
        model('promotion_friends_coupon')->startTrans();
        try {
            $res = model('promotion_friends_coupon')->update([ 'status' => -1, 'status_name' => '已关闭' ], $condition);
            model('promotion_friends_coupon_group')->update([ 'status' => 2 ], [ [ 'promotion_id', '=', $coupon_id ], [ 'status', '=', 0 ] ]);
            model('promotion_friends_coupon_group')->update([ 'is_look' => 1 ], [ [ 'promotion_id', '=', $coupon_id ], [ 'status', '=', 2 ] ]);
            $cron = new Cron();
            $cron->deleteCron([ [ 'event', '=', 'CronChangeDivideticketStatus' ], [ 'relate_id', '=', $coupon_id ] ]);
            model('promotion_friends_coupon')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_friends_coupon')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除活动
     * @param $data
     * @return array
     */
    public function deleteDividetocket($data)
    {
        $coupon_id = $data[ 'coupon_id' ];
        $site_id = $data[ 'site_id' ];

        model('promotion_friends_coupon')->startTrans();
        try {
            $condition = [
                [ 'coupon_id', '=', $coupon_id ],
                [ 'site_id', '=', $site_id ],
            ];

            $old_info = model("promotion_friends_coupon")->getInfo($condition);
            if (!empty($old_info[ 'image' ])) {
                $upload_model = new Upload();
                $upload_model->deletePic($old_info[ 'image' ], $site_id);
            }

            $res = model("promotion_friends_coupon")->delete($condition);
            #删除活动所建分组
            model('promotion_friends_coupon_group')->delete([ [ 'promotion_id', '=', $coupon_id ] ]);

            $cron = new Cron();
            $cron->deleteCron([ [ 'event', '=', 'CronChangeDivideticketStatus' ], [ 'relate_id', '=', $coupon_id ] ]);
            model('promotion_friends_coupon')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_friends_coupon')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 发起瓜分
     */
    public function launch($coupon_id, $member_id, $site_id)
    {
        $divideticket_info = model('promotion_friends_coupon')->getInfo(
            [
                [ 'coupon_id', '=', $coupon_id ],
                [ 'site_id', '=', $site_id ],
                [ 'status', '=', 1 ],
            ]
        );
        if (empty($divideticket_info)) return $this->error('', '未查到瓜分活动信息');
        if ($divideticket_info[ 'inventory' ] <= 0) return $this->error('', '优惠券已被抢完了，下次再来吧');
        $launch_info = model('promotion_friends_coupon_group')->getInfo([ [ 'promotion_id', '=', $divideticket_info[ 'coupon_id' ] ], [ 'header_id', '=', $member_id ] ], 'group_id,status');

        if (!empty($launch_info) && $launch_info[ 'status' ] == 0) return $this->error('', '该商品正在瓜分中');
        if (!empty($launch_info) && $launch_info[ 'status' ] == 1) return $this->error('', '已参与过此活动，无法重复发起瓜分');
        $member_info = model('member')->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ], 'nickname,headimg');
        if (empty($member_info)) return $this->error('', '未获取到会员信息');
        model('promotion_friends_coupon_group')->startTrans();
        $end_time = time() + ( $divideticket_info[ 'divide_time' ] * 3600 );
        try {
            $data = [
                'promotion_id' => $divideticket_info[ 'coupon_id' ],
                'coupon_type_id' => $divideticket_info[ 'coupon_type_id' ],
                'start_time' => time(),
                'header_id' => $member_id,
                'status' => 0,
                'num' => $divideticket_info[ 'divide_num' ],
                'site_id' => $site_id,
                'group_member_ids' => $member_id,
            ];
            if ($divideticket_info[ 'end_time' ] < $end_time) {
                $data[ 'end_time' ] = $divideticket_info[ 'end_time' ];
            } else {
                $data[ 'end_time' ] = $end_time;
            }
            $launch_id = model('promotion_friends_coupon_group')->add($data);
            #同一活动瓜分失败的改为去查看
            model('promotion_friends_coupon_group')->update([ 'is_look' => 1 ], [ [ 'promotion_id', '=', $divideticket_info[ 'coupon_id' ] ], [ 'header_id', '=', $member_id ], [ 'status', '=', 2 ] ]);

            model('promotion_friends_coupon')->setDec([ [ 'coupon_id', '=', $coupon_id ], [ 'site_id', '=', $site_id ] ], 'inventory');
            $cron = new Cron();

            #是否模拟好友 1 是 2 否
            if ($divideticket_info[ 'is_simulation' ] == 1) {
                #加个 瓜分时间到期自动补齐
                $cron->addCron(1, 0, '未成团自动模拟好友瓜分', 'DivideticketSimulation', $data[ 'end_time' ], $launch_id);
            } else {
                $cron->addCron(1, 0, '瓜分发起自动关闭', 'DivideticketLaunchClose', $data[ 'end_time' ], $launch_id);
            }

            model('promotion_friends_coupon_group')->commit();
            return $this->success($launch_id);
        } catch (\Exception $e) {
            model('promotion_friends_coupon_group')->rollback();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 帮瓜分
     * @param $launch_id
     * @param $member_id
     * @param $site_id
     * @return array
     */
    public function divideticket($launch_id, $member_id, $site_id)
    {
        $divideticket_group = model('promotion_friends_coupon_group')->getInfo(
            [
                [ 'group_id', '=', $launch_id ],
                [ 'site_id', '=', $site_id ],
            ]
        );
        if (empty($divideticket_group)) return $this->error('', '未查到好友瓜分券参与活动组信息');
        if ($divideticket_group[ 'status' ] == 1) return $this->error('', '已经被瓜分完了');
        if ($divideticket_group[ 'status' ] == 2) return $this->error('', '瓜分过期请重新发起瓜分');
        $member_info = model('member')->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ], 'nickname,headimg');
        if (empty($member_info)) return $this->error('', '未获取到会员信息');
        model('promotion_friends_coupon_group')->startTrans();
        try {
            $divideticket_info = model('promotion_friends_coupon')->getInfo(
                [
                    [ 'coupon_id', '=', $divideticket_group[ 'promotion_id' ] ],
                    [ 'site_id', '=', $site_id ],
                    [ 'status', '=', 1 ],
                ]
            );
            if (empty($divideticket_info)) return $this->error('', '未查到瓜分活动信息');
            #判断此用户是否是新人
            $divideticket_member_group = model('promotion_friends_coupon_group')->getList([ [ 'promotion_id', '=', $divideticket_info[ 'coupon_id' ] ] ], 'member_ids,group_member_ids');
            $is_new = 0;
            if (!empty($divideticket_member_group)) {
                foreach ($divideticket_member_group as $k => $v) {
                    if (in_array($member_id, explode(",", $v[ 'group_member_ids' ]))) {
                        $is_new = $is_new + 1;
                    }
                }
            }
            #活动限制仅新人可瓜
            if ($divideticket_info[ 'is_new' ] == 1) {
                if ($is_new > 0) return $this->error('', '您已参加过此活动了，此活动只可参与一次');
            }

            $member_arr = [];#帮瓜分用户
            $group_member_arr = [];#瓜分组用户
            if (!empty($divideticket_group[ 'group_member_ids' ])) {
                $group_member_arr = explode(",", $divideticket_group[ 'group_member_ids' ]);
            }
            if (!empty($divideticket_group[ 'member_ids' ])) {
                $member_arr = explode(",", $divideticket_group[ 'member_ids' ]);
            }
            if (in_array($member_id, $member_arr)) {
                return $this->error('', '已经帮助瓜分过啦');
            }
            #插入瓜分的用户组
            $member_arr[] = $member_id;
            $group_member_arr[] = $member_id;
            #达到人数(瓜分成功)
            if (count($group_member_arr) == $divideticket_group[ 'num' ]) {
                model('promotion_friends_coupon')->setInc([ [ 'coupon_id', '=', $divideticket_group[ 'promotion_id' ] ], [ 'site_id', '=', $site_id ] ], 'success_count');
                model('promotion_friends_coupon_group')->update([ 'status' => 1, 'is_look' => 1, 'member_ids' => implode(',', $member_arr), 'group_member_ids' => implode(',', $group_member_arr) ], [ [ 'group_id', '=', $launch_id ], [ 'site_id', '=', $site_id ] ]);
                $coupon_data = [
                    'type' => 'divideticket',
                    'at_least' => $divideticket_info[ 'at_least' ],
                    'coupon_name' => $divideticket_info[ 'name' ],
                    'coupon_type_id' => $divideticket_info[ 'coupon_type_id' ],
                    'site_id' => $site_id,
                    'coupon_code' => random_keys(8),
                    'goods_type' => $divideticket_info[ 'goods_type' ],
                    'goods_ids' => $divideticket_info[ 'goods_ids' ],
                    'state' => 1,
                    'get_type' => 3,
                    'fetch_time' => time(),
                    'start_time' => time(),
                ];
                if ($divideticket_info[ 'validity_type' ] == 0) {
                    $coupon_data[ 'end_time' ] = $divideticket_info[ 'validity_end_time' ];
                } else if ($divideticket_info[ 'validity_type' ] == 1) {
                    $coupon_data[ 'end_time' ] = time() + $divideticket_info[ 'fixed_term' ] * 86400;
                }
                #固定的瓜分金额
                if ($divideticket_info[ 'divide_type' ] == 0) {
                    $discount_coupon_money = round($divideticket_info[ 'money' ] / $divideticket_info[ 'divide_num' ], 2);
                    $coupon_data[ 'money' ] = $discount_coupon_money;
                    foreach ($group_member_arr as $k => $v) {
                        $coupon_data[ 'member_id' ] = $v;
                        $this->addCoupon($coupon_data, $launch_id);
                    }
                } else {
                    #新人组
                    $couple_group = [];
                    #旧人组
                    $old_group = [];
                    #判断参与活动的用户是否是新人
                    if (!empty($divideticket_member_group)) {
                        foreach ($group_member_arr as $key => $value) {
                            $fresh_num = 0;
                            foreach ($divideticket_member_group as $k => $v) {
                                if (in_array($value, explode(",", $v[ 'member_ids' ]))) {
                                    $fresh_num = $fresh_num + 1;
                                }
                            }
                            if ($fresh_num == 0) $couple_group[] = $value;
                        }
                        $old_group = array_diff($group_member_arr, $couple_group);
                    } else {
                        $couple_group = $group_member_arr;
                    }
                    #随机获取比例(整数)
                    $proportion = $this->rand_bouns($divideticket_info[ 'divide_num' ], $divideticket_info[ 'money' ]);
                    #比例从大到小排序
                    arsort($proportion);

                    $proportion = array_values($proportion);
                    #有新人
                    if (!empty($couple_group)) {
                        #打乱新人组排序
                        shuffle($couple_group);
                        $couple_group = array_values($couple_group);
                        foreach ($couple_group as $k => $v) {
                            $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                            $coupon_data[ 'member_id' ] = $v;
                            $this->addCoupon($coupon_data, $launch_id);
                            unset($proportion[ $k ]);
                        }
                        if (!empty($old_group)) {
                            shuffle($old_group);
                            $old_group = array_values($old_group);
                            $proportion = array_values($proportion);
                            foreach ($old_group as $k => $v) {
                                $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                                $coupon_data[ 'member_id' ] = $v;
                                $this->addCoupon($coupon_data, $launch_id);
                            }
                        }
                    } else {
                        #打乱旧人组排序
                        shuffle($old_group);
                        $old_group = array_values($old_group);
                        foreach ($old_group as $k => $v) {
                            $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                            $coupon_data[ 'member_id' ] = $v;
                            $this->addCoupon($coupon_data, $launch_id);
                        }
                    }
                    #修改 人组顺序
                    $new_group_member_ids = array_merge($couple_group, $old_group);
                    model('promotion_friends_coupon_group')->update([ 'group_member_ids' => implode(',', $new_group_member_ids) ], [ [ 'group_id', '=', $launch_id ], [ 'site_id', '=', $site_id ] ]);

                }

            } else {
                #增加参与人
                model('promotion_friends_coupon_group')->update([ 'member_ids' => implode(',', $member_arr), 'group_member_ids' => implode(',', $group_member_arr) ], [ [ 'group_id', '=', $launch_id ], [ 'site_id', '=', $site_id ] ]);
            }
            model('promotion_friends_coupon_group')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_friends_coupon_group')->rollback();
            return $this->error($e->getMessage());
        }
    }

    #新增优惠券
    public function addCoupon($data, $launch_id)
    {
        model('promotion_coupon')->startTrans();
        try {
            $res = model('promotion_coupon')->add($data);
            $divideticket_group = model('promotion_friends_coupon_group')->getInfo(
                [
                    [ 'group_id', '=', $launch_id ],
                ]
            );
            if (empty($divideticket_group)) return $this->error('', '未查到好友瓜分券参与活动组信息');
            $coupon_ids_arr = [];
            if (!empty($divideticket_group[ 'coupon_type_id' ])) {
                $coupon_ids_arr = explode(",", $divideticket_group[ 'coupon_ids' ]);
            }
            $coupon_ids_arr[] = $res;
            model('promotion_friends_coupon_group')->update([ 'coupon_ids' => implode(',', $coupon_ids_arr) ], [ [ 'group_id', '=', $launch_id ] ]);

            $cron = new Cron();
            #删除瓜分组定时结束
            $cron->deleteCron([ [ 'event', '=', 'DivideticketLaunchClose' ], [ 'relate_id', '=', $launch_id ] ]);
            model('promotion_coupon')->commit();
            return $res;
        } catch (\Exception $e) {
            model('promotion_coupon')->rollback();
            return $this->error($e->getMessage());
        }

    }

    /**
     * @param $person  人数
     * @param $percent  金额
     * @return array
     */
    public static function rand_bouns($person, $percent)
    {
        //百分比
        $now_person = $person;
        $bouns = array ();
        for ($i = 0; $i <= $person - 1; $i++) {
            $bouns[ $i ] = self::get_bouns($now_person, $percent);
            $percent = $percent - $bouns[ $i ];
            $now_person = $now_person - 1;
        }
        return $bouns;
    }

    public static function get_bouns($person, $percent)
    {
        if ($person == 1) return $percent;
        $max = 30;
        if ($percent < $max) $max = $percent;
        $min = $percent - $max * ( $person - 1 ) <= 0 ? 1 : $percent - $max * ( $person - 1 );
        $max = $max - ( $person ) <= 0 ? 1 : $max - ( $person );
        return rand($min, $max);
    }

    /**
     * 海报
     */
    public function poster($arr, $app_type, $site_id, $member_id)
    {
        try {
            $qrcode_info = $this->getQrcode($arr, $app_type, $site_id);
            if ($qrcode_info[ 'code' ] < 0) return $qrcode_info;

            $member_info = $this->getMemberInfo($member_id);
            if (empty($member_info)) return $this->error('未获取到会员信息');

            $poster = new PosterExtend(740, 1250);
            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data' => [
                        'public/uniapp/divideticket/poster_two.png',
                        0,
                        0,
                        740,
                        1250,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码
                    'data' => [
                        $qrcode_info[ 'data' ][ 'path' ],
                        505,
                        980,
                        205,
                        205,
                        'square',
                        0,
                        1
                    ]
                ],
                [
                    'action' => 'imageCircularCopy', // 写入用户头像
                    'data' => [
                        !empty($member_info[ 'headimg' ]) ? $member_info[ 'headimg' ] : 'public/static/img/default_img/head.png',
                        82,
                        852,
                        112,
                        112
                    ]
                ],
                [
                    'action' => 'imageText', // 写入分享人昵称
                    'data' => [
                        $member_info[ 'nickname' ],
                        22,
                        [ 255, 129, 61 ],
                        40,
                        1030,
                        440,
                        1,
                        true,
                        1
                    ]
                ]

            ];

            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/divideticket', 'coupon_id_' . $arr[ 'cid' ] . 'group_id_' . $arr[ 'gid' ] . '_' . $app_type);
            return $res;
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取用户信息
     * @param unknown $member_id
     */
    private function getMemberInfo($member_id)
    {
        $info = model('member')->getInfo([ 'member_id' => $member_id ], 'nickname,headimg');
        return $info;
    }

    /**
     * 生成优惠券二维码
     * @param $coupon_id
     * @param string $app_type all为全部
     * @param string $type 类型 create创建 get获取
     * @return mixed|array
     */
//    public function poster($arr, $app_type, $site_id, $type = 'create')
//    {
//        $res = event('Qrcode', [
//            'site_id' => $site_id,
//            'app_type' => $app_type,
//            'type' => $type,
//            'data' => $arr,
//            'page' => '/promotionpages/guafen/guafen/index',
//            'qrcode_path' => 'upload/qrcode/divideticket',
//            'qrcode_name' => 'coupon_id_' . $arr['coupon_id'] . 'inviter_id_' . $arr['inviter_id'] . 'group_id_' . $arr['group_id'] . '_' . $site_id,
//        ], true);
//        return $res;
//    }

    public function getQrcode($arr, $app_type, $site_id, $type = 'create')
    {
        $res = event('Qrcode', [
            'site_id' => $site_id,
            'app_type' => $app_type,
            'type' => $type,
            'data' => $arr,
            'page' => '/pages_promotion/divideticket/index',
            'qrcode_path' => 'upload/qrcode/divideticket',
            'qrcode_name' => 'coupon_id_' . $arr[ 'cid' ] . 'inviter_id_' . $arr[ 'id' ] . 'group_id_' . $arr[ 'gid' ] . '_' . $site_id,
        ], true);
        return $res;
    }

    /**
     * 到时模拟瓜分
     */
    public function cronDivideticketSimulation($launch_id)
    {
        $launch_info = model('promotion_friends_coupon_group')->getInfo([ [ 'group_id', '=', $launch_id ] ]);
        $divideticket_info = model('promotion_friends_coupon')->getInfo([ [ 'coupon_id', '=', $launch_info[ 'promotion_id' ] ] ]);

        model('promotion_friends_coupon')->setInc([ [ 'coupon_id', '=', $launch_info[ 'promotion_id' ] ] ], 'success_count');
        model('promotion_friends_coupon_group')->update([ 'status' => 1 ], [ [ 'group_id', '=', $launch_id ] ]);

        $coupon_data = [
            'type' => 'divideticket',
            'coupon_name' => $divideticket_info[ 'name' ],
            'at_least' => $divideticket_info[ 'at_least' ],
            'coupon_type_id' => $divideticket_info[ 'coupon_type_id' ],
            'site_id' => $divideticket_info[ 'site_id' ],
            'coupon_code' => random_keys(8),
            'goods_type' => $divideticket_info[ 'goods_type' ],
            'goods_ids' => $divideticket_info[ 'goods_ids' ],
            'state' => 1,
            'get_type' => 3,
            'fetch_time' => time(),
            'start_time' => time(),
        ];

        if ($divideticket_info[ 'validity_type' ] == 0) {
            $coupon_data[ 'end_time' ] = $divideticket_info[ 'validity_end_time' ];
        } else if ($divideticket_info[ 'validity_type' ] == 1) {
            $coupon_data[ 'end_time' ] = time() + $divideticket_info[ 'fixed_term' ] * 86400;
        }

        $group_member_arr = [];#瓜分组用户
        if (!empty($launch_info[ 'group_member_ids' ])) {
            $group_member_arr = explode(",", $launch_info[ 'group_member_ids' ]);
        }
        #固定的瓜分金额
        if ($divideticket_info[ 'divide_type' ] == 0) {
            $discount_coupon_money = round($divideticket_info[ 'money' ] / $divideticket_info[ 'divide_num' ], 2);
            $coupon_data[ 'money' ] = $discount_coupon_money;
            foreach ($group_member_arr as $k => $v) {
                $coupon_data[ 'member_id' ] = $v;
                $this->addCoupon($coupon_data, $launch_id);
            }
        } else {
            #新人组
            $couple_group = [];
            #旧人组
            $old_group = [];
            #判断参与活动的用户是否是新人
            if (!empty($divideticket_member_group)) {
                foreach ($group_member_arr as $key => $value) {
                    $fresh_num = 0;
                    foreach ($divideticket_member_group as $k => $v) {
                        if (in_array($value, explode(",", $v[ 'member_ids' ]))) {
                            $fresh_num = $fresh_num + 1;
                        }
                    }
                    if ($fresh_num == 0) $couple_group[] = $value;
                }
                $old_group = array_diff($group_member_arr, $couple_group);
            } else {
                $couple_group = $group_member_arr;
            }
            #随机获取比例(整数)
            $proportion = $this->rand_bouns($divideticket_info[ 'divide_num' ], $divideticket_info[ 'money' ]);
            #比例从大到小排序
            arsort($proportion);

            $proportion = array_values($proportion);
            #有新人
            if (!empty($couple_group)) {
                #打乱新人组排序
                shuffle($couple_group);
                $couple_group = array_values($couple_group);
                foreach ($couple_group as $k => $v) {
                    $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                    $coupon_data[ 'member_id' ] = $v;
                    $this->addCoupon($coupon_data, $launch_id);
                    unset($proportion[ $k ]);
                }
                if (!empty($old_group)) {
                    shuffle($old_group);
                    $old_group = array_values($old_group);
                    $proportion = array_values($proportion);

                    foreach ($old_group as $k => $v) {
                        $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                        $coupon_data[ 'member_id' ] = $v;
                        $this->addCoupon($coupon_data, $launch_id);
                    }
                }
            } else {
                #打乱旧人组排序
                shuffle($old_group);
                $old_group = array_values($old_group);
                foreach ($old_group as $k => $v) {
                    $coupon_data[ 'money' ] = round($proportion[ $k ], 2);
                    $coupon_data[ 'member_id' ] = $v;
                    $this->addCoupon($coupon_data, $launch_id);
                }
            }
            #修改 人组顺序
            $new_group_member_ids = array_merge($couple_group, $old_group);
            model('promotion_friends_coupon_group')->update([ 'group_member_ids' => implode(',', $new_group_member_ids) ], [ [ 'group_id', '=', $launch_id ], [ 'site_id', '=', $launch_info[ 'site_id' ] ] ]);

        }

    }

    /**
     * shop端推广
     * @param $coupon_id
     * @param $name
     * @param $site_id
     * @param string $type
     * @return array
     */
    public function spread($coupon_id, $name, $site_id, $type = "create")
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                "coupon_id" => $coupon_id
            ],
            'page' => '/pages_promotion/divideticket/index',
            'qrcode_path' => 'upload/qrcode/devideticket',
            'qrcode_name' => 'coupon_id_' . $coupon_id,
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ( $k ) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[ $k ][ 'status' ] = 1;
                    $path[ $k ][ 'url' ] = $wap_domain . $data[ 'page' ] . '?coupon_id=' . $coupon_id;
                    $path[ $k ][ 'img' ] = "upload/qrcode/devideticket/coupon_id_" . $coupon_id . "_" . $k . ".png";
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
            'name' => $name,
        ];

        return $this->success($return);
    }

    public function urlQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => $promotion_type,
            'app_type' => $app_type,
            'h5_path' => $page . '?cid=' . $qrcode_param[ 'cid' ],
            'qrcode_path' => 'upload/qrcode/devideticket',
            'qrcode_name' => 'coupon_id_' . $promotion_type . '_' . $qrcode_param[ 'cid' ] . '_' . $site_id,
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}