<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\shop\controller;

use addon\giftcard\model\giftcard\Category as CategoryModel;

/**
 * 礼品卡分组控制器
 */
class Category extends Giftcard
{
    /**
     * 兑换卡列表
     * @return array|mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $condition = array (
                [ 'site_id', '=', $this->site_id ]
            );
            if (!empty($search_text)) {
                $condition[] = [ 'category_name', 'like', '%' . $search_text . '%' ];
            }

            $category_model = new CategoryModel();
            $list = $category_model->getPageList($condition, $page, $page_size);
            return $list;
        } else {
            return $this->fetch('category/lists');
        }
    }

    public function add()
    {
        if (request()->isJson()) {
            $data = [
                'category_name' => input('category_name', ''),
                'sort' => input('sort', 0),
                'font_color' => input('font_color', ''),
                'site_id' => $this->site_id
            ];
            $category_model = new CategoryModel();
            $result = $category_model->add($data);
            return $result;
        } else {
            return $this->fetch('category/add');
        }
    }

    /**
     * 编辑礼品卡活动
     */
    public function edit()
    {
        $category_id = input('category_id', 0);
        $category_model = new CategoryModel();
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'category_id', '=', $category_id ]
        );
        if (request()->isJson()) {
            $data = [
                'category_name' => input('category_name', ''),
                'sort' => input('sort', 0),
                'font_color' => input('font_color', ''),
                'site_id' => $this->site_id
            ];
            $result = $category_model->edit($data, $condition);
            return $result;
        } else {
            $detail = $category_model->getInfo($condition)[ 'data' ] ?? [];
            $this->assign('detail', $detail);
            return $this->fetch('category/edit');
        }
    }

    /**
     * 删除
     * @return mixed
     */
    public function delete()
    {
        $category_id = input('category_id', 0);
        $category_model = new CategoryModel();
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'category_id', '=', $category_id ]
        );
        $result = $category_model->delete($condition);

        return $result;
    }

    public function modifySort()
    {
        $category_id = input('category_id', 0);
        $category_model = new CategoryModel();
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'category_id', '=', $category_id ]
        );
        $sort = input('sort', 0);
        $result = $category_model->modifySort($sort, $condition);
        return $result;
    }
}