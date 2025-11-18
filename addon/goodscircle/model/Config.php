<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\goodscircle\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;

/**
 *  好物圈
 */
class Config extends BaseModel
{
    /******************************************************************** 微信好物圈配置 start ****************************************************************************/
    /**
     * 设置微信好物圈配置
     * @param $data
     * @param $is_use
     * @param $site_id
     * @return array
     */
    public function setGoodscircleConfig($data, $is_use, $site_id)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '微信好物圈设置', $is_use, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'GOODSCIRCLE_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取微信好物圈配置信息
     * @param $site_id
     * @return array
     */
    public function getGoodscircleConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'GOODSCIRCLE_CONFIG' ] ]);
        return $res;
    }
    /******************************************************************** 微信好物圈配置 end ****************************************************************************/

}