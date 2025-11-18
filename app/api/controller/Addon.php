<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 */

namespace app\api\controller;

use app\model\system\Addon as AddonModel;

/**
 * 插件管理
 * @author Administrator
 *
 */
class Addon extends BaseApi
{
    /**
     * 列表信息
     */
    public function lists()
    {
        $addon = new AddonModel();
        $list = $addon->getAddonList();
        return $this->response($list);
    }

    public function addonIsExit()
    {
        $addon_model = new AddonModel();
        $res = $addon_model->addonIsExist();
        return $this->response($this->success($res));
    }

}