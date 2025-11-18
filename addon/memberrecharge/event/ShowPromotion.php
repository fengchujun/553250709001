<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\event;

use addon\memberrecharge\model\Memberrecharge;

/**
 * 活动展示
 */
class ShowPromotion
{
    public $promotion_type = 'unlimited_time';

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
                    'name' => 'memberrecharge',
                    //展示分类（根据平台端设置，admin（平台营销），shop：店铺营销，member:会员营销, tool:应用工具）
                    'show_type' => 'member',
                    //展示主题
                    'title' => '充值礼包',
                    //展示介绍
                    'description' => '提高客户充值金额',
                    //展示图标
                    'icon' => 'addon/memberrecharge/icon.png',
                    //跳转链接
                    'url' => 'memberrecharge://shop/memberrecharge/lists',
                    'summary' => $this->summary($params)
                ]
            ]

        ];
        return $data;
    }

    private function summary($params)
    {
        if (empty($params)) {
            return [];
        }

        if(isset($params['promotion_type']) && $params['promotion_type'] != $this->promotion_type){
            return [];
        }

        if (isset($params[ 'count' ]) || isset($params[ 'summary' ])) $config = ( new Memberrecharge() )->getConfig($params[ 'site_id' ])[ 'data' ];
        //获取活动数量
        if (isset($params[ 'count' ])) {
            $count = model("member_recharge")->getCount([ [ 'site_id', '=', $params[ 'site_id' ] ] ]);
            return [
                'count' => $count
            ];
        }
        //获取活动概况,需要获取开始时间与结束时间
        if (isset($params[ 'summary' ])) {
            $count = model("member_recharge")->getCount([ [ 'site_id', '=', $params[ 'site_id' ] ], [ 'status', '=', 1 ] ]);

            return [
                'unlimited_time' => [
                    'status' => $config[ 'is_use' ],
                    'detail' => $count ? '已配置' . $count . '个充值套餐' : '未配置充值套餐',
                    'switch_type' => 'switch',
                    'config_key' => 'MEMBER_RECHARGE_CONFIG'
                ]
            ];
        }
    }
}