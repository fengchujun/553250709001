<?php
/**
 * Transfer.php
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

use addon\memberwithdraw\model\Withdraw as WithdrawModel;
use app\exception\ApiException;
use app\model\system\Site as SiteModel;
use app\model\shop\Shop as ShopModel;
use think\facade\Cache;

/**
 * 店铺
 * @author Administrator
 *
 */
class Transfer extends BaseApi
{
    public function __construct()
    {
        parent::__construct();
    }
}