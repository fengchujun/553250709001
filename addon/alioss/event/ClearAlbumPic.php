<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\alioss\event;

use addon\alioss\model\Alioss;
use addon\alioss\model\Config;

/**
 * 删除阿里云图片
 */
class ClearAlbumPic
{
    public function handle($params)
    {
        $config_model = new Config();
        $alioss_model = new Alioss();

        $config = $config_model->getAliossConfig($params[ 'site_id' ]);
        if (!empty($config[ 'data' ])) {
            if (!empty($config[ 'data' ][ 'value' ][ 'endpoint' ]) && strpos($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'endpoint' ]) === 0) {
                $result = $alioss_model->deleteAlbumPic($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'endpoint' ]);
                return $result;
            }
            if (!empty($config[ 'data' ][ 'value' ][ 'domain' ]) && strpos($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'domain' ]) === 0) {
                $result = $alioss_model->deleteAlbumPic($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'domain' ]);
                return $result;
            }
        }
    }
}