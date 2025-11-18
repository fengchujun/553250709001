<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace addon\presale\event;

use think\facade\Db;

/**
 * 活动展示
 */
class ShowPromotion
{
    public $promotion_type = 'time_limit';

    /**
     * 活动展示
     *
     * @param array $params
     * @return array
     */
    public function handle($params = [])
    {
        $data = [
            'shop' => [
                [
                    //插件名称
                    'name' => 'presale',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type' => 'shop',
                    //展示主题
                    'title' => '商品预售',
                    //展示介绍
                    'description' => '定金膨胀引流预热',
                    //展示图标
                    'icon' => 'addon/presale/icon.png',
                    //跳转链接
                    'url' => 'presale://shop/presale/lists',
                    'summary' => $this->summary($params)
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
            $count = model("promotion_presale")->getCount([ [ 'site_id', '=', $params[ 'site_id' ] ] ]);
            return [
                'count' => $count
            ];
        }
        //获取活动概况,需要获取开始时间与结束时间
        if (isset($params[ 'summary' ])) {
            $join = [
                [ 'goods g', 'p.goods_id = g.goods_id', 'inner' ]
            ];
            $list = model("promotion_presale")->getList([
                [ '', 'exp', Db::raw('not ( (`start_time` >= ' . $params[ 'end_time' ] . ')  or (`end_time` <= ' . $params[ 'start_time' ] . '))') ],
                [ 'p.site_id', '=', $params[ 'site_id' ] ],
                [ 'p.status', '<>', 3 ],
                [ 'p.status', '<>', 4 ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ], 'p.presale_name as promotion_name,p.presale_id as promotion_id,p.start_time,p.end_time', '', 'p', $join, 'p.presale_name');
            return !empty($list) ? [
                'time_limit' => [
                    'count' => count($list),
                    'detail' => $list,
                    'color' => '#E066FF'
                ]
            ] : [];
        }
    }
}