<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\event;

use think\facade\Db;

/**
 * 活动展示
 */
class ShowPromotion
{
    public $promotion_type = 'time_limit';

    /**
     * 活动展示
     * @param $params
     * @return array
     */
    public function handle($params)
    {
        $data = [
            'shop' => [
                [
                    //插件名称
                    'name' => 'bargain',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type' => 'shop',
                    //展示主题
                    'title' => '砍价',
                    //展示介绍
                    'description' => '邀请好友砍价后低价购买',
                    //展示图标
                    'icon' => 'addon/bargain/icon.png',
                    //跳转链接
                    'url' => 'bargain://shop/bargain/lists',
                    'summary' => $this->summary($params),
                ]
            ]
        ];
        return $data;
    }

    /**
     * 营销活动概况
     * @param $params
     * @return array
     */
    private function summary($params)
    {
        if (empty($params)) {
            return [];
        }

        if(isset($params['promotion_type']) && $params['promotion_type'] != $this->promotion_type){
            return [];
        }

        //获取活动数量
        if (isset($params[ 'count' ])) {
            $count = model("promotion_bargain")->getCount([ 'site_id' => $params[ 'site_id' ] ]);
            return [
                'count' => $count
            ];
        }
        //获取活动概况,需要获取开始时间与结束时间
        if (isset($params[ 'summary' ])) {
            $join = [
                [ 'goods g', 'p.goods_id = g.goods_id', 'inner' ]
            ];
            $list = model("promotion_bargain")->getList([
                [ '', 'exp', Db::raw('not ( (`start_time` >= ' . $params[ 'end_time' ] . ')  or (`end_time` <= ' . $params[ 'start_time' ] . '))') ],
                [ 'p.site_id', '=', $params[ 'site_id' ] ],
                [ 'p.status', '<>', 2 ],
                [ 'p.status', '<>', 3 ],
                [ 'p.status', '<>', 4 ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ], 'p.bargain_name as promotion_name,p.bargain_id as promotion_id,p.start_time,p.end_time', '', 'p', $join, 'p.create_time');
            return !empty($list) ? [
                'time_limit' => [
                    'count' => count($list),
                    'detail' => $list,
                    'color' => '#6699FF'
                ]
            ] : [];
        }
    }
}