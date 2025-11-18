<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\event;

use think\facade\Db;

/**
 * 活动展示
 */
class ShowPromotion
{
    public $promotion_type = 'time_limit';

    /**
     * 活动展示
     * @param array $params
     * @return array
     */
    public function handle($params = [])
    {
        $data = [
            'shop' => [
                [
                    //插件名称
                    'name' => 'topic',
                    //展示分类（根据平台端设置，shop：店铺营销，member:会员营销, tool:应用工具）
                    'show_type' => 'shop',
                    //展示主题
                    'title' => '专题活动',
                    //展示介绍
                    'description' => '打造主题营销专区',
                    //展示图标
                    'icon' => 'addon/topic/icon.png',
                    //跳转链接
                    'url' => 'topic://shop/topic/lists',
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
            $count = model("promotion_topic")->getCount([ [ 'site_id', '=', $params[ 'site_id' ] ] ]);
            return [
                'count' => $count
            ];
        }
        //获取活动概况,需要获取开始时间与结束时间
        if (isset($params[ 'summary' ])) {
            $list = model("promotion_topic")->getList([
                [ '', 'exp', Db::raw('not ( (`start_time` >= ' . $params[ 'end_time' ] . ')  or (`end_time` <= ' . $params[ 'start_time' ] . '))') ],
                [ 'site_id', '=', $params[ 'site_id' ] ]
            ], 'topic_name as promotion_name,topic_id as promotion_id,start_time,end_time');
            return !empty($list) ? [
                'time_limit' => [
                    'count' => count($list),
                    'detail' => $list,
                    'color' => '#7BE295'
                ]
            ] : [];
        }
    }
}