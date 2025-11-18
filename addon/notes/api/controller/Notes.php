<?php
/**
 * Index.php
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

namespace addon\notes\api\controller;

use addon\notes\model\Group;
use app\api\controller\BaseApi;
use addon\notes\model\Notes as NotesModel;
use addon\notes\model\Record as RecordModel;

class Notes extends BaseApi
{

    /**
     *  获取笔记分组
     */
    public function group()
    {
        $model = new Group();
        $list = $model->getNotesGroupList([['site_id', '=', $this->site_id]], 'group_id,group_name,notes_num,release_num', 'sort asc');
        return $this->response($list);
    }

    /**
     * 获取文章分页列表
     */
    public function page()
    {
        $token = $this->checkToken();
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $group_id = $this->params['group_id'] ?? '';
		$note_id_arr = $this->params['note_id_arr'] ?? '';

        $condition[] = ['pn.site_id', '=', $this->site_id];
        if ($group_id) {
            $condition[] = ['pn.group_id', '=', $group_id];
        }
		
		if(!empty($note_id_arr)){
		    $condition[] = ['pn.note_id', 'in', $note_id_arr];
		}
        $note_model = new NotesModel();
        $list_result = $note_model->getNotesPageList($condition, $page, $page_size);
        if($token['code'] >= 0){
            $list = $list_result['data']['list'];

            $record_model = new RecordModel();
            foreach($list as $k=>$v){
                //获取用户是否点赞
                $is_dianzan = $record_model->getIsDianzan($v['note_id'],$this->member_id);
                $list[$k]['is_dianzan'] = $is_dianzan['data'];
            }
            $list_result['data']['list'] = $list;
        }
        return $this->response($list_result);
    }

    /**
     * 获取文章列表
     */
    public function lists()
    {
        $token = $this->checkToken();
        $num = $this->params['num'] ?? 0;
        $group_id = $this->params['group_id'] ?? '';
        $note_id_arr = $this->params['note_id_arr'] ?? '';

        $condition[] = ['pn.site_id', '=', $this->site_id];
        if ($group_id) {
            $condition[] = ['pn.group_id', '=', $group_id];
        }

        if(!empty($note_id_arr)){
            $condition[] = ['pn.note_id', 'in', $note_id_arr];
        }

        $field = 'pn.*,png.group_name';
        $alias = 'pn';
        $join  = [
            [
                'notes_group png',
                'png.group_id = pn.group_id',
                'left'
            ]
        ];

        $note_model = new NotesModel();
        $list_result = $note_model->getNotesList($condition,$field,'pn.sort asc', $num, $alias, $join);
        if($token['code'] >= 0){
            $list = $list_result['data'];
            $record_model = new RecordModel();
            $note_type = $note_model->getNoteType();
            $note_type = array_column($note_type, 'name', 'type');
            foreach($list as $k=>$v){
                //获取用户是否点赞
                $is_dianzan = $record_model->getIsDianzan($v['note_id'],$this->member_id);
                $list[$k]['is_dianzan'] = $is_dianzan['data'];
                $list[$k]['note_type_name'] = $note_type[$v['note_type']];
            }
            $list_result['data'] = $list;
        }
        return $this->response($list_result);
    }

    /**
     * 文章详情
     */
    public function detail()
    {
        $token = $this->checkToken();

        $note_id = $this->params['note_id'] ?? '';
        if (empty($note_id)) {
            return $this->response($this->error('', 'REQUEST_NOTE_ID'));
        }
        $condition = [
            ['site_id', '=', $this->site_id],
            ['note_id', '=', $note_id]
        ];

        $note_model = new NotesModel();
        $info_result = $note_model->getNotesDetailInfo($condition, '*', 2);
        if($token['code'] >= 0){
            $info = $info_result['data'];
            $record_model = new RecordModel();
            $is_dianzan = $record_model->getIsDianzan($info['note_id'],$this->member_id);
            $info['is_dianzan'] = $is_dianzan['data'];
            $info_result['data'] = $info;
        }
        return $this->response($info_result);
    }

}