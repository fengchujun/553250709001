<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\event;

use addon\giftcard\model\giftcard\Media;
use app\model\system\Cron;

/**
 * 应用安装
 */
class Install
{
    /**
     * 执行安装
     */
    public function handle()
    {
        try {
            execute_sql('addon/giftcard/data/install.sql');

            $cron = new Cron();
            $cron->deleteCron([ [ 'event', '=', 'CronCardExpire' ] ]);
            $cron->addCron(2, 1, '礼品卡过期', 'CronCardExpire', time(), 0);

            $site_id = 1;
            $media = ( new Media() )->getInfo([ [ 'is_system', '=', 1 ], [ 'site_id', '=', $site_id ] ], 'media_id')[ 'data' ];
            if (empty($media)) {
                ( new Media() )->addList($this->giftMedia($site_id));
            }
            return success();
        } catch (\Exception $e) {
            return error('', $e->getMessage());
        }
    }

    private function giftMedia($site_id)
    {
        return [
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '001.png',
                'media_path' => 'public/uniapp/giftcard/media/001.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ],
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '002.png',
                'media_path' => 'public/uniapp/giftcard/media/002.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ],
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '003.png',
                'media_path' => 'public/uniapp/giftcard/media/003.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ],
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '004.png',
                'media_path' => 'public/uniapp/giftcard/media/004.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ],
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '005.png',
                'media_path' => 'public/uniapp/giftcard/media/005.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ],
            [
                'site_id' => $site_id,
                'media_type' => 'img',
                'is_system' => 1,
                'media_name' => '006.png',
                'media_path' => 'public/uniapp/giftcard/media/006.png',
                'media_spec' => '640*400',
                'create_time' => time(),
            ]
        ];
    }
}