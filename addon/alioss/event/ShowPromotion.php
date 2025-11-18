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


namespace addon\alioss\event;


/**
 * 活动展示
 */
class ShowPromotion
{

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
                    'name' => 'alioss',
                    //展示分类（根据平台端设置，admin（平台营销），shop：店铺营销，member:会员营销, tool:应用工具）
                    'show_type' => 'tool',
                    //展示主题
                    'title' => '阿里云OSS',
                    //展示介绍
                    'description' => '阿里云OSS配置',
                    //展示图标
                    'icon' => 'addon/alioss/icon.png',
                    //跳转链接
                    'url' => 'alioss://shop/config/config',
                ],

            ],

        ];
        return $data;
    }

}