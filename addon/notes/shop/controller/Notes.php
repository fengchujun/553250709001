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
use addon\notes\model\Notes as NotesModel;
use addon\notes\model\Group as GroupModel;

/**
 * 笔记控制器
 */
class Notes extends BaseShop
{

    /*
     *  笔记活动列表
     */
    public function lists()
    {
        $model = new NotesModel();
        //获取续签信息
        if (request()->isJson()) {

            $condition[] = [ 'pn.site_id', '=', $this->site_id ];
            //笔记状态
            $status = input('status', '');
            if ($status !== '') {
                $condition[] = [ 'pn.status', '=', $status ];
            }
            //笔记标题
            $note_title = input('note_title', '');
            if ($note_title) {
                $condition[] = [ 'pn.note_title', 'like', '%' . $note_title . '%' ];
            }
            //笔记类型
            $note_type = input('note_type', '');
            if ($note_type) {
                $condition[] = [ 'pn.note_type', '=', $note_type ];
            }
            //分组
            $group_id = input('group_id', '');
            if ($group_id) {
                $condition[] = [ 'pn.group_id', '=', $group_id ];
            }

            //时间
            $start_time = input("start_time", '');
            $end_time = input("end_time", '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "pn.create_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "pn.create_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'pn.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            //排序
//            $order = input('order', 'sort');
//            $sort = input('sort', 'desc');
//            if($order == 'sort'){
//                $order_by = 'pn.'.$order . ' ' . $sort;
//            }else{
//                $order_by = 'pn.'.$order . ' ' . $sort.',pn.sort desc';
//            }
            $order_by = 'pn.create_time desc';
            $list = $model->getNotesPageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {
            //笔记类型
            $note_type = $model->getNoteType();
            $this->assign('note_type', $note_type);
            //笔记分组
            $group_model = new GroupModel();
            $group_list = $group_model->getNotesGroupList([ [ 'site_id', '=', $this->site_id ] ], 'group_id,group_name');
            $this->assign('group_list', $group_list[ 'data' ]);
            return $this->fetch("notes/lists");
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        $note_type = input('note_type', '');
        if (request()->isJson()) {
            $notes_data = [
                'site_id' => $this->site_id,
                'note_type' => $note_type,
                'note_title' => input('note_title', ''),
                'note_abstract' => input('note_abstract', ''),
                'group_id' => input('group_id', ''),
                'cover_type' => input('cover_type', ''),
                'cover_img' => input('cover_img', ''),
                'goods_ids' => input('goods_ids', ''),
                'goods_highlights' => input('goods_highlights', ''),
                'note_content' => htmlspecialchars_decode(input('note_content', '')),
                'status' => input('status', ''),
                'sort' => input('sort', '0'),
                'is_show_release_time' => input('is_show_release_time', ''),
                'is_show_read_num' => input('is_show_read_num', ''),
                'is_show_dianzan_num' => input('is_show_dianzan_num', ''),
                'initial_read_num' => input('initial_read_num', ''),
                'initial_dianzan_num' => input('initial_dianzan_num', ''),

                'note_link' => input('note_link', ''),
                'video_path' => input('video_path', ''),

            ];
            $notes_model = new NotesModel();
            return $notes_model->addNotes($notes_data);
        } else {
            $this->assign('note_type', $note_type);
            //笔记分组
            $group_model = new GroupModel();
            $group_list = $group_model->getNotesGroupList([ [ 'site_id', '=', $this->site_id ] ], 'group_id,group_name');
            $this->assign('group_list', $group_list[ 'data' ]);
            switch ( $note_type ) {
                case 'shop_said':
                    return $this->fetch('notes/add_shop_said');
                case 'goods_item':
                    return $this->fetch('notes/add_goods_item');
                case 'article':
                    return $this->fetch('notes/add_article');
                case 'wechat_article':
                    return $this->fetch('notes/add_wechat_article');
                case 'goods_video':
                    return $this->fetch('notes/add_goods_video');
                default:
                    $this->error("笔记类型不存在");
            }
        }
    }

    /*
     * 编辑活动
     */
    public function edit()
    {
        $notes_model = new NotesModel();
        $note_id = input('note_id', '');
        $note_type = input('note_type', '');
        if (request()->isJson()) {

            $notes_data = [
                'note_id' => $note_id,
                'site_id' => $this->site_id,
                'note_type' => $note_type,
                'note_title' => input('note_title', ''),
                'note_abstract' => input('note_abstract', ''),
                'group_id' => input('group_id', ''),
                'cover_type' => input('cover_type', ''),
                'cover_img' => input('cover_img', ''),
                'goods_ids' => input('goods_ids', ''),
                'goods_highlights' => input('goods_highlights', ''),
                'note_content' => htmlspecialchars_decode(input('note_content', '')),
                'status' => input('status', ''),
                'sort' => input('sort', '0'),
                'is_show_release_time' => input('is_show_release_time', ''),
                'is_show_read_num' => input('is_show_read_num', ''),
                'is_show_dianzan_num' => input('is_show_dianzan_num', ''),
                'initial_read_num' => input('initial_read_num', ''),
                'initial_dianzan_num' => input('initial_dianzan_num', ''),

                'note_link' => input('note_link', ''),
                'video_path' => input('video_path', ''),
            ];

            return $notes_model->editNotes($notes_data);

        } else {
            $this->assign('note_id', $note_id);
            $this->assign('note_type', $note_type);
            //笔记分组
            $group_model = new GroupModel();
            $group_list = $group_model->getNotesGroupList([ [ 'site_id', '=', $this->site_id ] ], 'group_id,group_name');
            $this->assign('group_list', $group_list[ 'data' ]);

            //获取笔记信息
            $note_info = $notes_model->getNotesDetailInfo([ [ 'note_id', '=', $note_id ], [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];
            if (empty($note_info)) $this->error('未获取到笔记数据', href_url('notes://shop/notes/lists'));
            $note_type = $note_info[ 'note_type' ];
            $this->assign('info', $note_info);
            switch ( $note_type ) {
                case 'shop_said':
                    return $this->fetch('notes/edit_shop_said');
                case 'goods_item':
                    return $this->fetch('notes/edit_goods_item');
                case 'article':
                    return $this->fetch('notes/edit_article');
                case 'wechat_article':
                    return $this->fetch('notes/edit_wechat_article');
                case 'goods_video':
                    return $this->fetch('notes/edit_goods_video');
                default:
                    $this->error("笔记类型不存在");
            }
        }
    }


    /*
     *  删除笔记活动
     */
    public function delete()
    {
        $note_id = input('note_id', '');

        $notes_model = new NotesModel();
        return $notes_model->deleteNotes([ [ 'note_id', '=', $note_id ], [ 'site_id', '=', $this->site_id ] ]);
    }


    /**
     * 草稿箱
     * @return mixed
     */
    public function drafts()
    {
        $model = new NotesModel();
        //笔记类型
        $note_type = $model->getNoteType();
        $this->assign('note_type', $note_type);
        //笔记分组
        $group_model = new GroupModel();
        $group_list = $group_model->getNotesGroupList([ [ 'site_id', '=', $this->site_id ] ], 'group_id,group_name');
        $this->assign('group_list', $group_list[ 'data' ]);
        return $this->fetch("notes/drafts");
    }

    /**
     * 发布或取消发布
     * @return array
     */
    public function releaseEvent()
    {
        if (request()->isJson()) {
            $note_id = input('note_id', 0);
            $notes_model = new NotesModel();
            $data = array (
                'note_id' => $note_id,
                'site_id' => $this->site_id,
                'status' => input('status', 0)
            );
            return $notes_model->releaseEvent($data);
        }
    }

    /**
     * 修改商品类型排序
     */
    public function modifySort()
    {
        if (request()->isJson()) {

            $sort = input('sort', 0);
            $note_id = input('note_id', 0);
            $notes_model = new NotesModel();
            return $notes_model->modifyNotesSort($sort, $note_id, $this->site_id);
        }
    }


    /**
     * 笔记选择组件
     * @return \multitype
     */
    public function notesSelect()
    {
        $model = new NotesModel();
        if (request()->isJson()) {
            $condition[] = [ 'pn.site_id', '=', $this->site_id ];
            //笔记状态
            $status = input('status', '');
            if ($status !== '') {
                $condition[] = [ 'pn.status', '=', $status ];
            }
            //笔记标题
            $note_title = input('note_title', '');
            if ($note_title) {
                $condition[] = [ 'pn.note_title', 'like', '%' . $note_title . '%' ];
            }
            //笔记类型
            $note_type = input('note_type', '');
            if ($note_type) {
                $condition[] = [ 'pn.note_type', '=', $note_type ];
            }
            //分组
            $group_id = input('group_id', '');
            if ($group_id) {
                $condition[] = [ 'pn.group_id', '=', $group_id ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list = $model->getNotesPageList($condition, $page, $page_size, 'pn.sort asc');
            return $list;

        } else {

            //已经选择的商品sku数据
            $select_id = input('select_id', '');
            $max_num = input('max_num', 0);
            $min_num = input('min_num', 0);

            $this->assign('select_id', $select_id);
            $this->assign('max_num', $max_num);
            $this->assign('min_num', $min_num);

            //笔记类型
            $note_type = $model->getNoteType();
            $this->assign('note_type', $note_type);
            //笔记分组
            $group_model = new GroupModel();
            $group_list = $group_model->getNotesGroupList([ [ 'site_id', '=', $this->site_id ] ], 'group_id,group_name');
            $this->assign('group_list', $group_list[ 'data' ]);

            return $this->fetch("notes/notes_select");
        }
    }

    /**
     * 采集文章
     */
    public function pullArticle()
    {
        $note_model = new NotesModel();
        $url = input('wechat_url', 'https://mp.weixin.qq.com/s/uyocVJ4DSGqDbu_BR77XwQ');
        $result = $note_model->pullWechatArticle([ 'url' => $url ]);
        //['title' => '', 'content' => '']
        return $result;
    }


}