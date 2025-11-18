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

use app\Controller;

/**
 * 会员充值小票打印打印机添加
 */
class PrinterHtml extends Controller
{

    public function handle($data)
    {
        return $this->fetch('addon/memberrecharge/shop/view/template/printer_template.html');
    }
}