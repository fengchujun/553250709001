<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\api\controller;

use addon\giftcard\model\giftcard\Category as CategoryModel;
use app\api\controller\BaseApi;

/**
 * 礼品卡分类
 */
class Category extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $search_text = $this->params['search_text'] ?? '';
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
        );

        if (!empty($search_text)) {
            $condition[] = [ 'category_name', 'like', '%' . $search_text . '%' ];
        }

        $category_model = new CategoryModel();
        $list = $category_model->getPageList($condition, $page, $page_size);
        return $this->response($list);
    }

}