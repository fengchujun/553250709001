<?php

namespace addon\giftcard\model\giftcard;

use app\model\BaseModel;
use app\model\upload\Upload;

class Media extends BaseModel
{
    /**
     * 添加礼品卡素材
     * @param $data
     * @return array
     */
    public function add($data)
    {
        $data[ 'create_time' ] = time();
        model('giftcard_media')->add($data);
        return $this->success();
    }

    /**
     * 添加礼品卡素材
     * @param array $list
     * @return array
     */
    public function addList(array $list)
    {
        model('giftcard_media')->addList($list);
        return $this->success();
    }

    /**
     * 添加礼品卡素材
     * @param $data
     * @param $condition
     * @return array
     */
    public function edit($data, $condition)
    {
        $data[ 'update_time' ] = time();
        model('giftcard_media')->update($data, $condition);
        return $this->success();
    }

    /**
     * 删除礼品卡素材
     * @param $condition
     * @return array
     */
    public function delete($condition)
    {
        $info = $this->getInfo($condition, 'media_path,site_id')[ 'data' ] ?? [];

        $giftcard_count = model('giftcard')->getCount([ [ 'card_cover', 'like', '%' . $info[ 'media_path' ] . '%' ] ], 'giftcard_id');
        if ($giftcard_count > 0) $this->error('', '删除失败，不能删除正在使用的图');

        model('giftcard_media')->delete($condition);
        $upload_model = new Upload($info[ 'site_id' ]);
        $upload_model->deleteFile($info[ 'media_path' ]);
        return $this->success();
    }

    /**
     * 修改礼品卡活动所在素材
     * @param $cartgory_id
     * @param $condition
     * @return array
     */
    public function modifyGiftCardCategoryMediaCategoryId($cartgory_id, $condition)
    {
        $data = array (
            'cartgory_id' => $cartgory_id,
            'update_time' => time()
        );
        model('giftcard_media')->update($data, $condition);
        return $this->success();
    }

    /**
     * 获取礼品卡信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getInfo($condition, $field = '*')
    {
        $info = model('giftcard_media')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_media')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_media')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 上传图片并保存
     * @param $params
     */
    public function upload($params)
    {
        $site_id = $params[ 'site_id' ];
        $upload_model = new Upload($site_id);
        $path = $site_id > 0 ? 'common/giftcardimages/' . date('Ymd') . '/' : 'common/giftcardimages/' . date('Ymd') . '/';
        $result = $upload_model->setPath($path)->image($params);
        if ($result[ 'code' ] < 0) {
            return $result;
        }
        $result_data = $result[ 'data' ];
        $data = array (
            'media_path' => $result_data[ 'pic_path' ],
            'media_name' => $result_data[ 'pic_name' ],
            'media_spec' => $result_data[ 'pic_spec' ],
            'site_id' => $site_id,
        );
        $result = $this->add($data);
        return $result;
    }

    /**
     * 替换素材
     * @param $params
     * @return array|false|\multitype|string
     */
    public function replace($params)
    {
        $site_id = $params[ 'site_id' ];
        $media_id = $params[ 'media_id' ];
        // 图片信息
        $info = $this->getInfo([ [ 'site_id', '=', $site_id ], [ 'media_id', '=', $media_id ] ])[ 'data' ] ?? [];
        // 判断是否找到有效图片
        if (empty($info)) {
            return json_encode(error('', '未查到素材信息', ''));
        }
        $upload_model = new Upload($site_id);
        $path = $site_id > 0 ? 'common/giftcardimages/' . date('Ymd') . '/' : 'common/giftcardimages/' . date('Ymd') . '/';
        $result = $upload_model->setPath($path)->image($params);
        if ($result[ 'code' ] < 0) {
            return $result;
        }
        $result_data = $result[ 'data' ];
        $data = array (
            'media_path' => $result_data[ 'pic_path' ],
            'media_name' => $result_data[ 'pic_name' ],
            'media_spec' => $result_data[ 'pic_spec' ],
        );
        $result = $this->edit($data, [ [ 'media_id', '=', $media_id ] ]);
        $upload_model->deleteFile($info[ 'media_path' ]);
        return $result;
    }

}
