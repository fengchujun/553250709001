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

use app\shop\controller\BaseShop;
use addon\birthdaygift\model\BirthdayGift as BirthdayGiftModel;

/**
 * 生日有礼控制器
 */
class Record extends BaseShop
{

    /**
     * 生日有礼活动领取列表
     * @return array|mixed
     */
    public function lists()
    {
        $activity_id = input('id',0);
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $alias = 'pbgr';
            $condition[] = ['pbgr.activity_id','=',$activity_id];
            $join = [
                [
                    'promotion_birthdaygift pbg',
                    'pbg.id = pbgr.activity_id',
                    'left'
                ]
            ];

            //领取时间
            $start_time = input('start_time', '');
            $end_time   = input('end_time', '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = ['pbgr.receive_time', '>=', date_to_time($start_time)];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = ['pbgr.receive_time', '<=', date_to_time($end_time)];
            } elseif (!empty($start_time) && !empty(date_to_time($end_time))) {
                $condition[] = ['pbgr.receive_time', 'between', [date_to_time($start_time), date_to_time($end_time)]];
            }
            $field = 'pbgr.record_id,pbg.activity_name,pbg.activity_time_type,pbgr.member_name,pbgr.receive_time';
            $gift_model = new BirthdayGiftModel();
            $order = 'pbgr.receive_time DESC';
            $lists = $gift_model->getRecordPageList($condition,$page,$page_size,$order,$field,$alias,$join);
            return $lists;
        } else {
            $this->assign('activity_id',$activity_id);
            return $this->fetch("record/lists");
        }
    }

}