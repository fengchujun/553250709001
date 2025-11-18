<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\event\pay;

use app\model\system\PayTransfer;

/**
 * 定时查询转账结果
 * @author Administrator
 *
 */
class CronPayTransferResult
{
    public function handle($param)
    {
        $model  = new PayTransfer();
        $result = $model->result($param['relate_id']);
        return $result;
    }
}
