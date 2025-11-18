<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\notes\shop\controller;

use app\shop\controller\BaseShop;
use addon\notes\model\Group as GroupModel;

/**
 * 笔记控制器
 */
class Group extends BaseShop
{

    /*
     *  笔记分组列表
     */
    public function lists()
    {
        $model = new GroupModel();

        $condition[] = ['site_id', '=', $this->site_id];
        //获取续签信息
        if (request()->isJson()) {

            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            //排序
            $order = input('order', 'sort');
            $sort = input('sort', 'desc');
            if($order == 'sort'){
                $order_by = $order . ' ' . $sort;
            }else{
                $order_by = $order . ' ' . $sort.',sort desc';
            }
            $list      = $model->getNotesGroupPageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {

            return $this->fetch('group/lists');
        }
    }

    /**
     * 添加分组
     */
    public function add()
    {
        if (request()->isJson()) {

            $data = [
                'site_id'    => $this->site_id,
                'group_name' => input('group_name', ''),
                'sort'       => input('sort'),
            ];

            $notes_model = new GroupModel();
            return $notes_model->addNotesGroup($data);
        }
    }

    /**
     * 编辑分组
     */
    public function edit()
    {
        if (request()->isJson()) {

            $data = [
                'group_id'   => input('group_id'),
                'site_id'    => $this->site_id,
                'group_name' => input('group_name', ''),
                'sort'       => input('sort'),
            ];

            $notes_model = new GroupModel();
            return $notes_model->editNotesGroup([['site_id', '=', $this->site_id], ['group_id', '=', $data['group_id']]], $data);
        }
    }

    /**
     * 编辑分组排序
     * @return array
     */
    public function modifySort()
    {
        if (request()->isJson()) {

            $data        = [
                'group_id' => input('group_id'),
                'site_id'  => $this->site_id,
                'sort'     => input('sort'),
            ];
            $notes_model = new GroupModel();
            return $notes_model->editNotesGroup([['site_id', '=', $this->site_id], ['group_id', '=', $data['group_id']]], $data);
        }
    }

    /*
     *  删除分组
     */
    public function delete()
    {
        $group_id = input('group_id', '');
        $site_id  = $this->site_id;

        $notes_model = new GroupModel();
        return $notes_model->deleteNotesGroup($group_id, $site_id);
    }

}