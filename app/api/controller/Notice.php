<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use app\model\web\Notice as NoticeModel;

/**
 * Class Notice
 * @package app\api\controller
 */
class Notice extends BaseApi
{

    /**
     * 基础信息
     */
    public function info()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $notice = new NoticeModel();
        $info = $notice->getNoticeInfo([ [ 'id', '=', $id ], [ 'site_id', '=', $this->site_id ] ]);
        return $this->response($info);
    }

    public function lists()
    {
        $id_arr = $this->params['id_arr'] ?? '';//id数组

        $notice = new NoticeModel();
        $condition = [
            [ 'receiving_type', 'like', '%mobile%' ],
            [ 'site_id', '=', $this->site_id ],
            [ 'id', 'in', $id_arr ]
        ];
        $list = $notice->getNoticeList($condition);
        return $this->response($list);
    }

    public function page()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $id_arr = $this->params['id_arr'] ?? '';//id数组

        $notice = new NoticeModel();
        $order = 'is_top desc,sort desc,create_time desc';
        $condition = [
            [ 'receiving_type', 'like', '%mobile%' ],
            [ 'site_id', '=', $this->site_id ]
        ];
        if (!empty($id_arr)) {
            $condition[] = [ 'id', 'in', $id_arr ];
        }
        $list = $notice->getNoticePageList($condition, $page, $page_size, $order);
        return $this->response($list);
    }

}