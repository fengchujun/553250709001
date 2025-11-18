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

use addon\scenefestival\model\Record as RecordModel;
use addon\scenefestival\model\SceneFestival as Festival;
use app\shop\controller\BaseShop;

/**
 * 节日有礼活动领取记录
 */
class Record extends BaseShop
{
    /*
     *  领取记录
     */
    public function lists()
    {
        $game_id = input('festival_id');
        if (request()->isJson()) {
            $condition = [
                [ 'pfdr.site_id', '=', $this->site_id ],
                [ 'pfdr.festival_id', '=', $game_id ]
            ];

            //会员昵称
            $member_nick_name = input('member_nick_name', '');
            if ($member_nick_name) {
                $condition[] = [ 'pfdr.member_nick_name', 'like', '%' . $member_nick_name . '%' ];
            }
            //参与时间
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = [ 'pfdr.receive_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } else if (!$start_time && $end_time) {
                $condition[] = [ 'pfdr.receive_time', '<=', date_to_time($end_time) ];
            } else if ($start_time && !$end_time) {
                $condition[] = [ 'pfdr.receive_time', '>=', date_to_time($start_time) ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $alias = 'pfdr';
            $join = [
                [
                    'promotion_festival_award pfa',
                    'pfa.award_id = pfdr.award_id',
                    'left'
                ],
            ];
            $fields = 'pfdr.*,pfa.award_type,pfa.coupon,pfa.point,pfa.balance,pfa.balance_type,pfa.balance_money';
            $model = new RecordModel();
            $list = $model->getGamesDrawRecordPageList($condition, $page, $page_size, 'pfdr.record_id desc', $fields, $alias, $join);
            return $list;

        } else {

            $this->assign('festival_id', $game_id);
            //游戏活动信息
            $game_model = new Festival();
            $game_info = $game_model->getGamesInfo([ [ 'site_id', '=', $this->site_id ], [ 'festival_id', '=', $game_id ] ]);
            if (empty($game_info[ 'data' ])) $this->error('未获取到活动数据', href_url('scenefestival://shop/scenefestival/lists'));
            $this->assign('game_info', $game_info[ 'data' ]);
            return $this->fetch("record/lists");
        }
    }

}