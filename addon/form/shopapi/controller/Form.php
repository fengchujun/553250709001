<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\form\shopapi\controller;

use app\shopapi\controller\BaseApi;
use addon\form\model\Form as FormModel;

/**
 * 表单管理
 * @author Administrator
 *
 */
class Form extends BaseApi
{
    /**
     * 获取表单列表
     */
    public function lists()
    {
        $form_type = $this->params[ 'form_type' ] ?? 'goods';
        $res = ( new FormModel() )->getFormList([ [ 'site_id', '=', $this->site_id ], [ 'form_type', '=', $form_type ], [ 'is_use', '=', 1 ] ], 'id desc', 'id, form_name');
        return $this->response($res);
    }
}