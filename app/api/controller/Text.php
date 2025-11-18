<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace app\api\controller;

use app\model\web\Help as HelpModel;

/**
 *测试
 * @author Administrator
 *
 */
class Text extends BaseApi
{


    /**
     * 基础信息
     */
    public function cronVerifyCodeExpire()
    {
        $order_id = $this->params['order_id'] ?? 0;
        if (empty($order_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $res = event('CronVerifyCodeExpire', ['relate_id' => $order_id]);
        dd($res);

    }


}