<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supermember\event;

use addon\blindbox\model\BlindboxOrder;
use addon\supermember\model\MemberLevelOrder;

/**
 * 通过支付信息获取手机端订单详情路径
 */
class WapOrderDetailPathByPayInfo
{
    public function handle($data)
    {
        if($data['event'] == 'MemberLevelOrderPayNotify'){
            return '/pages_tool/member/card';
        }
    }
}