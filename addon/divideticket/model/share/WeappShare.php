<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\divideticket\model\share;

use app\model\share\WeappShareBase;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WeappShare extends WeappShareBase
{
    protected $config = [
        [
            'title' => '好友瓜分列表',
            'config_key' => 'WEAPP_SHARE_CONFIG_DIVIDETICKET_LIST',
            'path' => ['/pages_promotion/divideticket/list'],
            'method_prefix' => 'divideticketList',
        ],
    ];

    protected $sort = 11;

    /**
     * 好友瓜分列表
     * @param $param
     * @return array
     */
    protected function divideticketListShareData($param)
    {
        //获取和替换配置数据
        $config_data = $this->divideticketListShareConfig($param);
        $title = $config_data['value']['title'];
        $image_url = $config_data['value']['imageUrl'] ? img($config_data['value']['imageUrl']) : '';
        $path = $this->getSharePath($param);

        $data = [
            'title' => $title,
            'path' => $path,
            'imageUrl' => $image_url,
        ];
        return [
            'permission' => [
                'onShareAppMessage' => true,
                'onShareTimeline' => true,
            ],
            'data' => $data,//分享内容
        ];
    }

    /**
     * 好友瓜分列表
     * @param $param
     * @return array
     */
    protected function divideticketListShareConfig($param)
    {
        $site_id = $param['site_id'];
        $config = $param['config'];

        $config_model = new ConfigModel();
        $data = $config_model->getConfig([
            ['site_id', '=', $site_id],
            ['app_module', '=', 'shop'],
            ['config_key', '=', $config['config_key']],
        ])['data'];
        if (empty($data['value'])) {
            $data['value'] = [
                'title' => '跟我一起来瓜分吧！',
                'imageUrl' => '',
            ];
        }
        $variable = [];

        return [
            'value' => $data['value'],
            'variable' => $variable,
        ];
    }
}
