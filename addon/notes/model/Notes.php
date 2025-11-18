<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\notes\model;

use app\model\BaseModel;
use extend\WxCrawler;

/**
 * 笔记
 */
class Notes extends BaseModel
{

    //笔记类型
    private $note_type = [
        [ 'type' => 'shop_said', 'name' => '掌柜说' ],
        [ 'type' => 'goods_item', 'name' => '单品介绍' ],
        //['type' => 'article', 'name' => '种草文章'],
        //['type' => 'wechat_article', 'name' => '公众号文章'],
        //['type' => 'goods_video', 'name' => '短视频']
    ];

    /**
     * 获取笔记类型
     * @return array
     */
    public function getNoteType()
    {
        return $this->note_type;
    }

    /**
     * 添加笔记
     * @param $data
     * @return array
     */
    public function addNotes($data)
    {
        $data[ 'create_time' ] = time();

        model('notes')->startTrans();
        try {
            //添加笔记
            if ($data[ 'status' ] == 1) {
                $data[ 'release_time' ] = time();
            }
            model('notes')->add($data);
            //更新分组笔记数等信息
            model('notes_group')->setInc([ [ 'group_id', '=', $data[ 'group_id' ] ] ], 'notes_num');
            if ($data[ 'status' ] == 1) {
                model('notes_group')->setInc([ [ 'group_id', '=', $data[ 'group_id' ] ] ], 'release_num');
            }
            model('notes')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('notes')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 文章发布
     * @param $params
     */
    public function releaseEvent($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $note_id = $params[ 'note_id' ];
        $status = $params[ 'status' ];
        $condition = array (
            [ 'note_id', '=', $note_id ],
            [ 'site_id', '=', $site_id ]
        );
        $info = model('notes')->getInfo($condition);
        if (!empty($info)) {
            if ($info[ 'status' ] != $status) {
                if ($status == 1) {
                    $release_time = time();
                    model('notes_group')->setInc([ [ 'group_id', '=', $info[ 'group_id' ] ] ], 'release_num');
                } else {
                    $release_time = 0;
                    model('notes_group')->setDec([ [ 'group_id', '=', $info[ 'group_id' ] ] ], 'release_num');
                }
                $data = array (
                    'release_time' => $release_time,
                    'status' => $status
                );
                model('notes')->update($data, $condition);
            }
            return $this->success();
        } else {
            return $this->error();
        }

    }

    /**
     * 编辑笔记
     * @param $data
     * @return array
     */
    public function editNotes($data)
    {
        $data[ 'update_time' ] = time();

        model('notes')->startTrans();
        try {
            $info = model('notes')->getInfo([ [ 'site_id', '=', $data[ 'site_id' ] ], [ 'note_id', '=', $data[ 'note_id' ] ] ]);

            //添加笔记

            model('notes')->update($data, [ [ 'site_id', '=', $data[ 'site_id' ] ], [ 'note_id', '=', $data[ 'note_id' ] ] ]);
            $release_data = array (
                'note_id' => $data[ 'note_id' ],
                'site_id' => $data[ 'site_id' ]
            );
            if ($info[ 'group_id' ] != $data[ 'group_id' ]) {
                model('notes_group')->setDec([ [ 'group_id', '=', $info[ 'group_id' ] ] ], 'notes_num');

                model('notes_group')->setInc([ [ 'group_id', '=', $data[ 'group_id' ] ] ], 'notes_num');
                //减去原分组的发布数
                if ($info[ 'status' ] == 1) {
                    model('notes_group')->setDec([ [ 'group_id', '=', $info[ 'group_id' ] ] ], 'release_num');
                }
                //增加新分组的发布数
                if ($data[ 'status' ] == 1) {
                    model('notes_group')->setInc([ [ 'group_id', '=', $data[ 'group_id' ] ] ], 'release_num');
                }

            } else {
                $release_data[ 'status' ] = $data[ 'status' ];
                $this->releaseEvent($release_data);
            }
            //更新分组笔记数等信息
            if ($data[ 'status' ] == 1) {
                model('notes_group')->setInc([ [ 'group_id', '=', $data[ 'group_id' ] ] ], 'release_num');
            }
            model('notes')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('notes')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除笔记
     * @param $condition
     * @return array|\multitype
     */
    public function deleteNotes($condition)
    {
        //笔记数
        $notes_info = model('notes')->getInfo($condition, 'group_id,status');
        if (empty($notes_info)) {
            return $this->success('', '数据不合法');
        } else {

            model('notes')->startTrans();
            try {
                //删除笔记
                model('notes')->delete($condition);

                //更新分组笔记数等信息
                if ($notes_info[ 'status' ] == 1) {
                    model('notes_group')->setDec([ [ 'group_id', '=', $notes_info[ 'group_id' ] ] ], 'notes_num');
                    model('notes_group')->setDec([ [ 'group_id', '=', $notes_info[ 'group_id' ] ] ], 'release_num');
                } else {
                    model('notes_group')->setDec([ [ 'group_id', '=', $notes_info[ 'group_id' ] ] ], 'notes_num');
                }
                model('notes')->commit();
                return $this->success();
            } catch (\Exception $e) {
                model('notes')->rollback();
                return $this->error('', $e->getMessage());
            }
        }
    }


    /**
     * 修改排序
     * @param int $sort
     * @param int $class_id
     */
    public function modifyNotesSort($sort, $note_id, $site_id)
    {
        $res = model('notes')->update([ 'sort' => $sort ], [ [ 'note_id', '=', $note_id ], [ 'site_id', '=', $site_id ] ]);
        return $this->success($res);
    }


    /**
     * 获取笔记信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getNotesInfo($condition = [], $field = '*')
    {
        $info = model("notes")->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取笔记信息
     * @param array $condition
     * @param string $field
     * @param int $type
     * @return array
     */
    public function getNotesDetailInfo($condition = [], $field = '*', $type = 1)
    {
        $info = model('notes')->getInfo($condition, $field);

        if (!empty($info)) {
            $goods_field = 'sku_id,goods_name,goods_stock,price,goods_image,goods_id';
            $goods_list = model('goods')->getList([ [ 'site_id', '=', $info[ 'site_id' ] ], [ 'goods_id', 'in', $info[ 'goods_ids' ] ], [ 'goods_state', '=', 1 ], [ 'is_delete', '=', 0 ] ], $goods_field);
            if (!empty($goods_list)) {
                foreach ($goods_list as $k => $v) {
                    $goods_list[ $k ][ 'goods_stock' ] = numberFormat($goods_list[ $k ][ 'goods_stock' ]);
                }
            }
            $info[ 'goods_list' ] = $goods_list;
        }
        //添加浏览记录
        if ($type == 2) {
            model('notes')->setInc($condition, 'read_num', 1);
        }
        return $this->success($info);
    }

    /**
     * 获取笔记列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getNotesList($condition = [], $field = '*', $order = '', $limit = null, $alias = '', $join = [])
    {
        $list = model('notes')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取笔记分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getNotesPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pn.sort asc')
    {
        $field = 'pn.*,png.group_name';
        $alias = 'pn';
        $join = [
            [
                'notes_group png',
                'png.group_id = pn.group_id',
                'left'
            ]
        ];
        $note_type = $this->getNoteType();
        $note_type = array_column($note_type, 'name', 'type');
        $list = model('notes')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($list[ 'list' ] as $k => $v) {
            $list[ 'list' ][ $k ][ 'note_type_name' ] = $note_type[ $v[ 'note_type' ] ];
        }
        return $this->success($list);
    }


    /**
     * 采集微信公众号的文章信息
     * @param $params
     */
    public function pullWechatArticle($params)
    {
        $url = $params[ 'url' ];
        $crawler = new WxCrawler();
        $data = $crawler->crawByUrl($url);
//        echo $data['data']['content_html'];exit();
        return $data;

    }


}