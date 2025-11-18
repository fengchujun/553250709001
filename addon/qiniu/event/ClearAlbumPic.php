<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\qiniu\event;

use addon\qiniu\model\Qiniu;
use addon\qiniu\model\Config;

/**
 * 删除七牛云图片
 */
class ClearAlbumPic
{

    public function handle($params)
    {
        $config_model = new Config();
        $qiniu_model = new Qiniu();

        $config = $config_model->getQiniuConfig($params[ 'site_id' ]);

        if (!empty($config[ 'data' ])) {
            if (!empty($config[ 'data' ][ 'value' ][ 'domain' ]) && strpos($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'domain' ]) === 0) {
                $result = $qiniu_model->deleteAlbumPic($params[ 'pic_path' ], $config[ 'data' ][ 'value' ][ 'domain' ]);
                return $result;
            }
        }
    }

}