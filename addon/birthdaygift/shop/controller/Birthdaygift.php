<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\birthdaygift\shop\controller;

use addon\coupon\model\CouponType;
use app\model\member\MemberLevel;
use app\shop\controller\BaseShop;
use addon\birthdaygift\model\BirthdayGift as BirthdayGiftModel;
use think\App;

/**
 * 生日有礼控制器
 */
class Birthdaygift extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'BIRTHDAYGIFT_CSS' => __ROOT__ . '/addon/birthdaygift/shop/view/public/css',
            'BIRTHDAYGIFT_JS' => __ROOT__ . '/addon/birthdaygift/shop/view/public/js',
            'BIRTHDAYGIFT_IMG' => __ROOT__ . '/addon/birthdaygift/shop/view/public/img',
        ];
        //执行父类构造函数
        parent::__construct($app);
    }

    /**
     * 生日有礼活动列表
     * @return array|mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $status = input('status', '');
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $condition[] = [ 'is_delete', '=', 0 ];
            if (!empty($search_text)) {
                $condition[] = [ 'activity_name', 'like', '%' . $search_text . '%' ];
            }
            if (!empty($status)) {
                $condition[] = [ 'status', '=', $status ];
            }
            $gift_model = new BirthdayGiftModel();
            $order = 'create_time DESC';
            $field = '*';
            $lists = $gift_model->birthdayGiftPageList($condition, $page, $page_size, $order, $field);
            return $lists;
        } else {
            return $this->fetch('birthdaygift/lists');
        }
    }

    /**
     * 创建生日有礼活动
     * @return array|mixed
     */
    public function add()
    {
        if (request()->isJson()) {
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
            $data = [
                'activity_name' => input('activity_name', ''),
                'activity_time_type' => input('activity_time_type', 1),// 活动时间(1生日当天2生日当周3生日当月)
                'blessing_content' => input('blessing_content', ''),
                'level_id' => input('level_id', 0),
                'level_name' => input('level_name', ''),
                'type' => input('type', ''),
                'point' => $point,
                'balance' => $balance,
                'balance_type' => input('balance_type', '0'),
                'balance_money' => $balance_money,
                'coupon' => $coupon,
                'site_id' => $this->site_id,
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
            ];
            $gift_model = new BirthdayGiftModel();
            $res = $gift_model->addBirthdayGiftActivity($data);
            return $res;
        } else {

            //会员等级
            $member_level_model = new MemberLevel();
            $member_level_list = $member_level_model->getMemberLevelList([ [ 'site_id', '=', $this->site_id ] ], 'level_id, level_name', 'growth asc');
            $this->assign('member_level_list', $member_level_list[ 'data' ]);

            return $this->fetch('birthdaygift/add', $this->replace);
        }
    }

    /**
     * 生日有礼详情
     * @return array|mixed
     */
    public function detail()
    {
        $activity_id = input('id', '0');
        //获取信息
        $activity_model = new BirthdayGiftModel();
        $info = $activity_model->getBirthdayGiftDetail([ [ 'site_id', '=', $this->site_id ], [ 'id', '=', $activity_id ] ], '*')[ 'data' ] ?? [];
        if (empty($info)) $this->error('未获取到活动数据', href_url('birthdaygift://shop/birthdaygift/lists'));
        $info[ 'status_name' ] = $activity_model->status[ $info[ 'status' ] ] ?? '';
        $this->assign('info', $info);

        return $this->fetch('birthdaygift/detail');
    }

    /**
     * 生日有礼活动关闭
     * @return array|mixed
     */
    public function finish()
    {
        $activity_id = input('activity_id', '0');
        if (empty($activity_id)) $this->error('缺少必传参数', href_url('birthdaygift://shop/birthdaygift/lists'));
        $activity_model = new BirthdayGiftModel();
        $data = [
            'status' => -1
        ];
        $res = $activity_model->updateBirthdayGift($activity_id, $this->site_id, $data);
        return $res;
    }

    /**
     * 生日有礼编辑
     * @return array|mixed
     */
    public function edit()
    {
        $activity_id = input('id', '0');
        if (request()->isJson()) {
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
            $data = [
                'activity_name' => input('activity_name', ''),
                'activity_time_type' => input('activity_time_type', 1),// 活动时间(1生日当天2生日当周3生日当月)
                'blessing_content' => input('blessing_content', ''),
                'level_id' => input('level_id', 0),
                'level_name' => input('level_name', ''),
                'type' => input('type', ''),
                'point' => $point,
                'balance' => $balance,
                'balance_type' => input('balance_type', '0'),
                'balance_money' => $balance_money,
                'coupon' => $coupon,
                'site_id' => $this->site_id,
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
            ];

            $gift_model = new BirthdayGiftModel();
            $res = $gift_model->editBirthdayGiftActivity($data, $activity_id);
            return $res;
        } else {
            //会员等级
            $member_level_model = new MemberLevel();
            $member_level_list = $member_level_model->getMemberLevelList([ [ 'site_id', '=', $this->site_id ] ], 'level_id, level_name', 'growth asc');
            $this->assign('member_level_list', $member_level_list[ 'data' ]);

            //获取信息
            $activity_model = new BirthdayGiftModel();
            $info = $activity_model->getBirthdayGiftDetail([ [ 'site_id', '=', $this->site_id ], [ 'id', '=', $activity_id ] ], '*');
            $this->assign('info', $info[ 'data' ]);
            if (empty($info[ 'data' ])) $this->error('未获取到活动数据', href_url('birthdaygift://shop/birthdaygift/lists'));

            return $this->fetch('birthdaygift/edit', $this->replace);
        }
    }

    /**
     * 删除生日有礼活动
     * @return array|mixed
     */
    public function delete()
    {
        $activity_id = input('activity_id', 0);
        if (empty($activity_id)) $this->error('缺少必传参数', href_url('birthdaygift://shop/birthdaygift/lists'));
        $activity_model = new BirthdayGiftModel();
        $data = [
            'is_delete' => 1
        ];
        $res = $activity_model->updateBirthdayGift($activity_id, $this->site_id, $data);
        return $res;
    }

}