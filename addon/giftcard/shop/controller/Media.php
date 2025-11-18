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

use addon\giftcard\model\giftcard\Media as MediaModel;

/**
 * 礼品卡分组控制器
 */
class Media extends Giftcard
{
    /**
     * 分页列表
     * @return array|mixed
     */
    public function lists()
    {
        header("Expires:-1");
        header("Cache-Control:no_cache");
        header("Pragma:no-cache");
        $media_model = new MediaModel();
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = array (
                [ 'site_id', '=', $this->site_id ]
            );
            if (!empty($media_name)) {
                $condition[] = [ 'media_name', '=', $media_name ];
            }

            $list = $media_model->getPageList($condition, $page, $page_size, 'create_time desc');
            return $list;
        } else {
            return $this->fetch('media/lists');
        }
    }

    /**
     * 删除
     * @return mixed
     */
    public function delete()
    {
        $media_id = input('media_id', 0);
        $media_model = new MediaModel();
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'media_id', '=', $media_id ]
        );
        $result = $media_model->delete($condition);
        return $result;
    }

    /**
     * 上传
     */
    public function upload()
    {
        if (request()->isJson()) {
            $media_model = new MediaModel();
            $param = [
                'site_id' => $this->site_id,
                'name' => 'file'
            ];
            $result = $media_model->upload($param);
            return $result;
        }
    }

    /*
    * 替换
    * */
    public function modifyFile()
    {
        if (request()->isJson()) {
            // 参数
            $media_id = input('media_id', '');
            // 获取图片信息
            $media_model = new MediaModel();
            $params = [
                'media_id' => $media_id,
                'site_id' => $this->site_id,
                'name' => 'file'
            ];

            $result = $media_model->replace($params);
            return $result;
        }
    }

    /**
     * 相册管理界面
     * @return mixed
     */
    public function media()
    {
        $img_num = input('imgNum', '');
        $media_ids = input('mediaIds', '');
        $media_model = new MediaModel();
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $list_rows = input('limit', PAGE_LIST_ROWS);
            $media_name = input("media_name", "");
            $condition = array (
                [ 'site_id', "=", $this->site_id ],
            );
            if (!empty($media_name)) {
                $condition[] = [ 'media_name', 'like', '%' . $media_name . '%' ];
            }
            $list = $media_model->getPageList($condition, $page_index, $list_rows, 'update_time desc');
            return $list;
        } else {
            $media_list = $media_model->getList([ [ 'site_id', "=", $this->site_id ] ]);
            $this->assign("media_list", $media_list[ 'data' ]);
            if (!empty($media_ids)) {
                $media_ids = implode(',', array_unique(explode(',', $media_ids)));
            }
            $this->assign("media_ids", $media_ids);
            $this->assign("img_num", $img_num);
            return $this->fetch('media/media');
        }
    }
}