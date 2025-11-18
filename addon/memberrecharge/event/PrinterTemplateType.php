<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\memberrecharge\event;

/**
 * 会员充值小票打印
 */
class PrinterTemplateType
{

    public function handle($data)
    {
       return [
           [
               'type' => 'recharge',
               'type_name' => '会员充值',
               'edit' => 'addon/memberrecharge/shop/view/template/recharge_template.html',
               'add' => 'addon/memberrecharge/shop/view/template/recharge_template.html',
           ]
       ];
    }
}