<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\express;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use extend\Kdbird as KdbirdExtend;

/**
 * 快递鸟
 */
class Kdbird extends BaseModel
{

    /*********************************************************************** 快递100 start ***********************************************************************/
    /**
     * 快递鸟配置
     * @param $site_id
     * @return \multitype
     */
    public function getKdbirdConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'app_module', '=', 'shop' ], [ 'site_id', '=', $site_id ], [ 'config_key', '=', 'EXPRESS_KDBIRD_CONFIG' ] ]);
        $res['data']['value'] = assignData($res['data']['value'], [
            'EBusinessID' => '',
            'AppKey' => '',
            'RequestType' => '1002',//1002 免费版  8001 按次付费 8002 按单付费
        ]);
        return $res;
    }

    /**
     * 设置物流配送配置
     * @param $data
     * @param $is_use
     * @param $site_id
     * @return \multitype
     */
    public function setKdbirdConfig($data, $is_use, $site_id)
    {
        if ($is_use > 0) {
            $this->modifyStatus(0, $site_id);
        }
        $config = new ConfigModel();
        $res = $config->setConfig($data, '快递鸟设置', $is_use, [ [ 'app_module', '=', 'shop' ], [ 'site_id', '=', $site_id ], [ 'config_key', '=', 'EXPRESS_KDBIRD_CONFIG' ] ]);
        return $res;
    }

    /**
     * 开关状态
     * @param $is_use
     * @param $site_id
     * @return array
     */
    public function modifyStatus($is_use, $site_id)
    {
        $config = new ConfigModel();
        $res = $config->modifyConfigIsUse($is_use, [ [ 'app_module', '=', 'shop' ], [ 'site_id', '=', $site_id ], [ 'config_key', '=', 'EXPRESS_KDBIRD_CONFIG' ] ]);
        return $res;
    }
    /*********************************************************************** 快递100 end ***********************************************************************/

    /**
     * 查询物流轨迹 并且转化为兼容数据结构
     * @param $code
     * @param $express_no
     * @param $site_id
     * @param $mobile
     * @return array
     */
    public function trace($code, $express_no, $site_id, $mobile)
    {
        $config_result = $this->getKdbirdConfig($site_id);
        $config = $config_result[ 'data' ];

        if ($config[ 'is_use' ] == 0) return $this->error();

        $kd100_extend = new KdbirdExtend($config[ 'value' ]);
        $result = $kd100_extend->orderTracesSubByJson($express_no, $code, $mobile);
        if (isset($result[ 'success' ]) && $result[ 'success' ]) {
            return $this->success($result);
        } else {
            return $this->error($result, $result[ 'reason' ]);
        }
    }
}