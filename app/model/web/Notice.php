<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\web;

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 公告管理
 * @author Administrator
 *
 */
class Notice extends BaseModel
{

    /**
     * 添加公告
     * @param unknown $data
     */
    public function addNotice($data)
    {
        $data[ 'create_time' ] = time();
        $res = model('notice')->add($data);
        return $this->success($res);
    }

    /**
     * 修改公告
     * @param $data
     * @param $condition
     * @return array
     */
    public function editNotice($data, $condition)
    {
        $data[ 'modify_time' ] = time();
        $res = model('notice')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除公告
     * @param array $condition
     */
    public function deleteNotice($condition)
    {
        $res = model('notice')->delete($condition);
        return $this->success($res);
    }

    /**
     * 获取公告数量
     * @param $condition
     * @return array
     */
    public function getNoticeCount($condition)
    {
        $count = model('notice')->getCount($condition);
        return $this->success($count);
    }

    /**
     * 获取公告信息
     * @param array $condition
     * @param string $field
     */
    public function getNoticeInfo($condition, $field = 'id, title, content, create_time, modify_time, is_top,receiving_type,receiving_name')
    {
        $res = model('notice')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取公告列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getNoticeList($condition = [], $field = 'id, title, content, create_time, modify_time, is_top,receiving_type,receiving_name', $order = '', $limit = null)
    {
        $list = model('notice')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取公告分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getNoticePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'is_top desc,create_time desc', $field = 'id, title,content, create_time, is_top,receiving_type,receiving_name,sort')
    {
        $list = model('notice')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 修改标签排序
     * @param $sort
     * @param $id
     * @return array
     */
    public function modifyNoticeSort($sort, $id)
    {
        $res = model('notice')->update([ 'sort' => $sort ], [ [ 'id', '=', $id ] ]);
        return $this->success($res);
    }

    /**
     * 生成推广二维码链接
     * @param $qrcode_param
     * @param $site_id
     * @return array
     */
    public function urlQrcode($qrcode_param, $app_type, $site_id)
    {
        $h5_page = '/pages_tool/notice/detail';
        $pc_page = '/cms/notice/detail';
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'pc_data' => [ 'id' => $qrcode_param[ 'notice_id' ] ],
            'page' => $h5_page,
            'h5_path' => $h5_page . '?notice_id=' . $qrcode_param[ 'notice_id' ],
            'pc_page' => $pc_page,
            'pc_path' => $pc_page . '?id=' . $qrcode_param[ 'notice_id' ],
            'qrcode_path' => 'upload/qrcode/notice',
            'qrcode_name' => 'notice_qrcode' . $qrcode_param[ 'notice_id' ] . '_' . $site_id,
            'app_type' => $app_type,
        ];

        $solitaire = event('PromotionQrcode', $params);
        return $this->success($solitaire[ 0 ]);
    }
}