<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use app\model\upload\Album as AlbumModel;
use think\App;

/**
 * 相册
 * @package app\shop\controller
 */
class Album extends BaseShop
{

    /**
     * 图像
     */
    public function lists()
    {
        header('Expires:-1');
        header('Cache-Control:no_cache');
        header('Pragma:no-cache');
        $type = input('type', 'img');
        $album_model = new AlbumModel();
        if (request()->isJson()) {
            $page = input('page', 1);
            $limit = input('limit', PAGE_LIST_ROWS);
            $album_id = input('album_id', '');
            $pic_name = input('pic_name', '');
            $order = input('order', 'update_time desc');
            $condition = array (
                ['site_id', '=', $this->site_id],
                ['album_id', 'in', $album_id],
            );
            if (!empty($pic_name)) {
                $condition[] = ['pic_name', 'like', '%' . $pic_name . '%'];
            }
            return $album_model->getAlbumPicPageList($condition, $page, $limit, $order);
        } else {
            $album_list = $album_model->getAlbumList([['site_id', '=', $this->site_id], ['type', '=', $type]]);
            $album_list_tree = $album_model->getAlbumListTree([['site_id', '=', $this->site_id], ['type', '=', $type]]);
            $this->assign('album_list', $album_list[ 'data' ]);
            $this->assign('album_list_tree', $album_list_tree[ 'data' ]);
            $this->assign('type_list', $album_model->getType());
            $this->assign('type', $type);
            return $this->fetch('album/lists');
        }
    }

    /**
     * 获取相册分组
     */
    function getAlbumListTree()
    {
        if (request()->isJson()) {
            $album_model = new AlbumModel();
            $type = input('type', 'img');
            return $album_model->getAlbumListTree([['site_id', "=", $this->site_id], ['type', '=', $type]]);
        }

    }

    /**
     * 获取相册分组
     */
    function getAlbumList()
    {
        if (request()->isJson()) {
            $type = input('type', 'img');
            $album_model = new AlbumModel();
            $album_list = $album_model->getAlbumList([['site_id', '=', $this->site_id], ['type', '=', $type]]);
            return $album_list;
        }

    }

    /**
     * 添加分组
     */
    public function addAlbum()
    {
        if (request()->isJson()) {
            $album_name = input('album_name', '');
            $pid = input('pid', '0');
            $type = input('type', '0');
            $data = array (
                'site_id' => $this->site_id,
                'album_name' => $album_name,
                'pid' => $pid,
                'type' => $type,
                'level' => empty($pid) ? 1 : 2
            );
            $album_model = new AlbumModel();
            return $album_model->addAlbum($data);
        }
    }

    /**
     * 修改分组
     */
    public function editAlbum()
    {
        if (request()->isJson()) {
            $album_name = input('album_name');
            $album_id = input('album_id');
            $data = array (
                'album_name' => $album_name
            );
            $condition = array (
                ['site_id', '=', $this->site_id],
                ['album_id', '=', $album_id]
            );
            $album_model = new AlbumModel();
            return $album_model->editAlbum($data, $condition);
        }
    }

    /**
     * 删除分组
     */
    public function deleteAlbum()
    {
        if (request()->isJson()) {
            $album_id = input('album_id');
            $album_model = new AlbumModel();
            $condition = array (
                ['album_id', '=', $album_id],
                ['site_id', '=', $this->site_id]
            );
            return $album_model->deleteAlbum($condition);
        }
    }

    /**
     * 分组详情
     */
    public function albumInfo()
    {
        if (request()->isJson()) {
            $album_id = input('album_id');
            $album_model = new AlbumModel();
            $condition = array (
                ['album_id', '=', $album_id],
                ['site_id', '=', $this->site_id]
            );
            $res = $album_model->getAlbumInfo($condition);

            return $res;
        }
    }

    /**
     * 修改文件名
     */
    public function modifyPicName()
    {
        if (request()->isJson()) {
            $pic_id = input('pic_id', 0);
            $pic_name = input('pic_name', '');
            $album_id = input('album_id', 0);

            $album_model = new AlbumModel();
            $condition = array (
                ['pic_id', '=', $pic_id],
                ['site_id', '=', $this->site_id],
                ['album_id', '=', $album_id]
            );
            $data = array (
                'pic_name' => $pic_name
            );
            return $album_model->editAlbumPic($data, $condition);
        }
    }

    /**
     * 修改图片分组
     */
    public function modifyFileAlbum()
    {
        if (request()->isJson()) {
            $pic_id = input('pic_id', 0);//图片id
            $album_id = input('album_id', 0);//相册id
            $album_model = new AlbumModel();
            $condition = array (
                ['pic_id', 'in', $pic_id],
                ['site_id', '=', $this->site_id]
            );
            return $album_model->modifyAlbumPicAlbum($album_id, $condition);
        }
    }

    /**
     * 删除图片
     */
    public function deleteFile()
    {
        if (request()->isJson()) {
            $pic_id = input('pic_id', 0);//图片id
            $album_model = new AlbumModel();
            $condition = array (
                ['pic_id', 'in', $pic_id],
                ['site_id', '=', $this->site_id],
            );
            return $album_model->deleteAlbumPic($condition);
        }
    }

    /**
     * 相册管理界面
     * @return mixed
     */
    public function album()
    {
        $album_model = new AlbumModel();
        $type = input('type', 'img');
        $display_type = input('display_type', 'img');
        $is_thumb = input('is_thumb', 0);
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $list_rows = input('limit', PAGE_LIST_ROWS);
            $album_id = input('album_id', '');
            $pic_name = input('pic_name', '');
            $condition = array (
                ['site_id', '=', $this->site_id],
                ['album_id', 'in', $album_id],
            );
            if (!empty($pic_name)) {
                $condition[] = ['pic_name', 'like', '%' . $pic_name . '%'];
            }
            return $album_model->getAlbumPicPageList($condition, $page_index, $list_rows, 'update_time desc');
        } else {
            $album_list = $album_model->getAlbumList([['site_id', '=', $this->site_id]]);
            $this->assign('album_list', $album_list[ 'data' ]);

            $album_tree_list = $album_model->getAlbumListTree([['site_id', '=', $this->site_id], ['type', '=', $type]]);
            $this->assign('album_tree_list', $album_tree_list[ 'data' ]);

            $this->assign('type_list', $album_model->getType());
            $this->assign('type', $type);
            $this->assign('display_type', $display_type);
            $this->assign('is_thumb', $is_thumb);
            return $this->fetch('album/album');
        }
    }

    /**
     * 生成缩略图
     */
    public function createThumb()
    {
        ignore_user_abort(true);
        if (request()->isJson()) {
            $upload_model = new AlbumModel();
            $pic_ids = input('pic_ids', '');
            return $upload_model->createThumbBatch($this->site_id, $pic_ids);
        }
    }

    /**
     * 刷新相册数量
     */
    public function refreshAlbumNum()
    {
        ignore_user_abort(true);
        $upload_model = new AlbumModel();
        $upload_model->refreshAlbumNum($this->site_id);
    }

}