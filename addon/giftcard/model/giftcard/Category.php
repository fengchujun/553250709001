<?php


namespace addon\giftcard\model\giftcard;

use app\model\BaseModel;

class Category extends BaseModel
{

    /**
     * 添加礼品卡分组
     * @param $data
     * @return array
     */
    public function add($data)
    {
        $data[ 'create_time' ] = time();
        model('giftcard_category')->add($data);
        return $this->success();
    }

    /**
     * 编辑礼品卡分组
     * @param $data
     * @param $condition
     * @return array
     */
    public function edit($data, $condition)
    {
        $data[ 'update_time' ] = time();
        model('giftcard_category')->update($data, $condition);
        return $this->success();
    }

    /**
     * 删除礼品卡分组
     * @param $condition
     * @return array
     */
    public function delete($condition)
    {
        model('giftcard_category')->delete($condition);
        return $this->success();
    }

    /**
     * 修改礼品卡分组活动排序号
     * @param $sort
     * @param $condition
     * @return array
     */
    public function modifySort($sort, $condition)
    {
        $data = array (
            'sort' => $sort,
            'update_time' => time()
        );
        model('giftcard_category')->update($data, $condition);
        return $this->success();
    }

    /**
     * 获取礼品卡分组信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getInfo($condition, $field = '*')
    {
        $info = model('giftcard_category')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡分组列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_category')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡分组分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_category')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取礼品卡分组详情
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getDetail($params)
    {
        $category_id = $params[ 'category_id' ];
        $site_id = $params[ 'site_id' ];
        $condition = array (
            [ 'category_id', '=', $category_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $info = $this->getInfo($condition)[ 'data' ] ?? [];
        return $this->success($info);
    }

}
