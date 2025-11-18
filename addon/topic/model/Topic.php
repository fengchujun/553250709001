<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Cron;
use app\model\upload\Upload;

/**
 * 专题活动
 */
class Topic extends BaseModel
{
    /**
     * 添加专题活动
     * @param unknown $data
     */
    public function addTopic($data)
    {
        try {
            if (empty($data[ 'goods' ])) return $this->error('', '请选择活动商品');
            $topic_goods = $data[ 'goods' ];
            unset($data[ 'goods' ]);

            model('promotion_topic')->startTrans();

            $data[ 'status' ] = 1;
            if (time() > $data[ 'start_time' ] && time() < $data[ 'end_time' ]) {
                $data[ 'status' ] = 2;
            }
            $topic_id = model('promotion_topic')->add($data);
            $sku_list = [];
            $goods = new Goods();

            foreach ($topic_goods as $good_item) {
                if (count($good_item) > 1) array_multisort(array_column($good_item, 'topic_price'), SORT_ASC, $good_item);
                foreach ($good_item as $k => $sku_item) {
                    $sku_list[] = [
                        'topic_id' => $topic_id,
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'site_id' => $data['site_id'],
                        'sku_id' => $sku_item['sku_id'],
                        'topic_price' => $sku_item['topic_price'],
                        'goods_id' => $sku_item['goods_id'],
                        'default' => $k == 0 ? 1 : 0
                    ];
                }
                $goods->modifyPromotionAddon($sku_item[ 'goods_id' ], [ 'topic' => $topic_id ]);
            }
            model('promotion_topic_goods')->addList($sku_list);

            $cron = new Cron();
            if ($data[ 'status' ] == 2) {
                $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $topic_id);
            } else {
                $cron->addCron(1, 0, "专题活动开启", "OpenTopic", $data[ 'start_time' ], $topic_id);
                $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $topic_id);
            }
            model('promotion_topic')->commit();
            return $this->success($topic_id);
        } catch (\Exception $e) {
            model('promotion_topic')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 修改专题活动
     * @param $data
     * @param $site_id
     * @param $del_id
     * @return array
     */
    public function editTopic($data, $site_id, $del_id)
    {
        try {
            if (empty($data[ 'goods' ])) return $this->error('', '请选择活动商品');
            $topic_goods = $data[ 'goods' ];
            unset($data[ 'goods' ]);

            model('promotion_topic')->startTrans();

            $data[ 'status' ] = 1;
            if (time() > $data[ 'start_time' ] && time() < $data[ 'end_time' ]) {
                $data[ 'status' ] = 2;
            }

            $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $data[ 'topic_id' ] ], [ 'site_id', '=', $site_id ] ]);
            if (!empty($topic_info[ 'topic_adv' ]) && !empty($data[ 'topic_adv' ]) && $topic_info[ 'topic_adv' ] != $data[ 'topic_adv' ]) {
                $upload_model = new Upload();
                $upload_model->deletePic($topic_info[ 'topic_adv' ], $site_id);
            }

            $res = model('promotion_topic')->update($data, [ [ 'topic_id', '=', $data[ 'topic_id' ] ], [ 'site_id', '=', $site_id ] ]);

            if (!empty($del_id)) {
                model('promotion_topic_goods')->delete([ [ 'id', 'in', explode(',', $del_id) ], [ 'topic_id', '=', $data[ 'topic_id' ] ], [ 'site_id', '=', $site_id ] ]);
            }

            $sku_list = [];
            foreach ($topic_goods as $good_item) {
                if (count($good_item) > 1) array_multisort(array_column($good_item, 'topic_price'), SORT_ASC, $good_item);
                foreach ($good_item as $k => $sku_item) {
                    if (!empty($sku_item[ 'id' ])) {
                        model('promotion_topic_goods')->update([
                            'topic_price' => $sku_item[ 'topic_price' ],
                            'start_time' => $data[ 'start_time' ],
                            'end_time' => $data[ 'end_time' ]
                        ], [
                            [ 'topic_id', '=', $data[ 'topic_id' ] ],
                            [ 'id', '=', $sku_item[ 'id' ] ],
                            [ 'goods_id', '=', $sku_item[ 'goods_id' ] ],
                            [ 'sku_id', '=', $sku_item[ 'sku_id' ] ],
                        ]);
                    } else {
                        $sku_list[] = [
                            'topic_id' => $data['topic_id'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'site_id' => $site_id,
                            'sku_id' => $sku_item['sku_id'],
                            'topic_price' => $sku_item['topic_price'],
                            'goods_id' => $sku_item['goods_id'],
                            'default' => $k == 0 ? 1 : 0
                        ];
                    }
                }
            }
            model('promotion_topic_goods')->addList($sku_list);

            $cron = new Cron();
            if ($data[ 'status' ] == 2) {
                //活动商品启动
                $this->cronOpenTopic($data[ 'topic_id' ]);
                $cron->deleteCron([ [ 'event', '=', 'OpenTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);

                $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $data[ 'topic_id' ]);
            } else {
                $cron->deleteCron([ [ 'event', '=', 'OpenTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);

                $cron->addCron(1, 0, "专题活动开启", "OpenTopic", $data[ 'start_time' ], $data[ 'topic_id' ]);
                $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $data[ 'topic_id' ]);
            }
            model('promotion_topic')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_topic')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除专题活动
     * @param unknown $topic_id
     */
    public function deleteTopic($topic_id, $site_id)
    {
        try {
            model('promotion_topic')->startTrans();

            $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $topic_id ], [ 'site_id', '=', $site_id ] ]);
            if (!empty($topic_info[ 'topic_adv' ])) {
                $upload_model = new Upload();
                $upload_model->deletePic($topic_info[ 'topic_adv' ], $site_id);
            }

            $topic_list = model("promotion_topic_goods")->getList([ [ 'topic_id', '=', $topic_id ], [ 'site_id', '=', $site_id ] ]);
            $goods = new Goods();
            foreach ($topic_list as $k => $v) {
                $goods->modifyPromotionAddon($v[ 'goods_id' ], [ 'topic' => $topic_id ], true);
            }

            $res = model('promotion_topic')->delete([ [ 'topic_id', '=', $topic_id ], [ 'site_id', '=', $site_id ] ]);
            model('promotion_topic_goods')->delete([ [ 'topic_id', '=', $topic_id ], [ 'site_id', '=', $site_id ] ]);
            model('promotion_topic')->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model('promotion_topic')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取专题活动信息
     * @param array $condition
     * @param string $field
     */
    public function getTopicInfo($condition, $field = '*')
    {
        $res = model('promotion_topic')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取专题活动详情
     * @param array $condition
     * @param string $field
     */
    public function getTopicDetail($condition)
    {
        $res = model('promotion_topic')->getInfo($condition, '*');
        if (!empty($res)) {
            $alias = 'nptg';
            $join = [
                [ 'goods g', 'nptg.goods_id = g.goods_id', 'inner' ],
                [ 'goods_sku ngs', 'nptg.sku_id = ngs.sku_id', 'inner' ],
            ];
            $field = 'nptg.id,nptg.sku_id,nptg.topic_price,nptg.goods_id,ngs.price,ngs.stock,ngs.sku_name,ngs.sku_image';
            $res[ 'goods_list' ] = model('promotion_topic_goods')->getList([ [ 'nptg.topic_id', '=', $res[ 'topic_id' ] ], [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ] ], $field, 'id asc', $alias, $join);
            foreach ($res[ 'goods_list' ] as $k => $v) {
                $res[ 'goods_list' ][ $k ][ 'stock' ] = numberFormat($res[ 'goods_list' ][ $k ][ 'stock' ]);
            }
            $res[ 'goods_list_count' ] = count($res[ 'goods_list' ]);
        }
        return $this->success($res);
    }

    /**
     * 获取专题活动列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getTopicList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_topic')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取专题分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getTopicPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'modify_time desc,create_time desc', $field = '*')
    {
        $list = model('promotion_topic')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 开启专题活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronOpenTopic($topic_id)
    {
        $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $topic_id ] ], 'start_time,status');
        if (!empty($topic_info)) {
            if ($topic_info[ 'start_time' ] <= time() && $topic_info[ 'status' ] == 1) {
                $res = model('promotion_topic')->update([ 'status' => 2 ], [ [ 'topic_id', '=', $topic_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "专题活动已开启或者关闭");
            }

        } else {
            return $this->error("", "专题活动不存在");
        }

    }

    /**
     * 关闭专题活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronCloseTopic($topic_id)
    {
        $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $topic_id ] ], 'start_time,status');
        if (!empty($topic_info)) {
            if ($topic_info[ 'status' ] != 3) {
                $res = model('promotion_topic')->update([ 'status' => 3 ], [ [ 'topic_id', '=', $topic_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }

        } else {
            return $this->error("", "专题活动不存在");
        }
    }

}