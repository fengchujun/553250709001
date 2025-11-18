<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pointcash\event;

use addon\pointcash\model\Config;

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

            'admin' => [

            ],
            'shop' => [
                [
                    //插件名称
                    'name' => 'pointcash',
                    //展示分类（根据平台端设置，admin（平台营销），shop：店铺营销，member:会员营销, tool:应用工具）
                    'show_type' => 'shop',
                    //展示主题
                    'title' => '积分抵现',
                    //展示介绍
                    'description' => '下单时积分抵扣现金',
                    //展示图标
                    'icon' => 'addon/pointcash/icon.png',
                    //跳转链接
                    'url' => 'pointcash://shop/config/index',
                    'summary' => $this->summary($params)
                ],

            ],

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

        if (isset($params[ 'count' ]) || isset($params[ 'summary' ])) $config = ( new Config() )->getPointCashConfig($params[ 'site_id' ])[ 'data' ];
        //获取活动数量
        if (isset($params[ 'count' ])) {
            return [
                'count' => $config[ 'is_use' ]
            ];
        }

        //获取活动概况,需要获取开始时间与结束时间

        if (isset($params[ 'summary' ])) {
            $value = $config[ 'value' ];
            return [
                'unlimited_time' => [
                    'status' => $config[ 'is_use' ],
                    'detail' => empty($value[ 'cash_rate' ]) ? '未配置活动' : $value[ 'cash_rate' ] . '积分可抵1元',
                    'switch_type' => empty($value[ 'cash_rate' ]) ? 'jump' : 'switch',
                    'config_key' => 'POINTCASH_CONFIG'
                ]
            ];
        }
    }

}