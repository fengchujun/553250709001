<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\model;

use addon\weapp\model\Message as WeappMessage;
use addon\wechat\model\Message as WechatMessage;
use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\member\Member;
use app\model\message\Message;
use app\model\message\Sms;
use app\model\system\Cron;
use app\model\order\Order;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 砍价活动
 */
class Bargain extends BaseModel
{

    private $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        3 => '已关闭'
    ];

    /**
     * 获取砍价活动状态
     * @return array
     */
    public function getBargainStatus()
    {
        return $this->success($this->status);
    }

    /**
     * 添加砍价
     * @param $common_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function addBargain($common_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        //时间段检测
        $bargain_count = model('promotion_bargain_goods')->getCount([
            [ 'goods_id', 'in', $goods[ 'goods_ids' ] ],
            [ 'status', 'in', '0,1,3,4' ],
            [ 'site_id', '=', $common_data[ 'site_id' ] ],
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $common_data[ 'end_time' ] . ' and `start_time` > ' . $common_data[ 'start_time' ] . ' )  or (`end_time` < ' . $common_data[ 'start_time' ] . ' and `end_time` < ' . $common_data[ 'end_time' ] . '))') ]//todo  修正  所有的优惠都要一样
        ]);

        if ($bargain_count > 0) {
            return $this->error('', '有商品已设置砍价活动，请不要重复设置');
        }

        $time = time();
        // 当前时间
        if ($time > $common_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($time > $common_data[ 'start_time' ] && $time < $common_data[ 'end_time' ]) {
            $common_data[ 'status' ] = 1;
            $common_data[ 'status_name' ] = $this->status[ 1 ];
        } else {
            $common_data[ 'status' ] = 0;
            $common_data[ 'status_name' ] = $this->status[ 0 ];
        }

        model('promotion_bargain')->startTrans();
        try {

            foreach ($goods[ 'goods_ids' ] as $goods_id) {

                //添加砍价活动
                $bargain_data = [
                    'goods_id' => $goods_id,
                    'create_time' => $time
                ];
                $bargain_id = model('promotion_bargain')->add(array_merge($bargain_data, $common_data));

                $bargain_goods_list = [];
                $bargain_stock = 0;

                //添加砍价活动商品
                foreach ($sku_list as $sku) {
                    if ($sku[ 'goods_id' ] == $goods_id) {

                        $bargain_stock += $sku[ 'bargain_stock' ];

                        $sku[ 'bargain_id' ] = $bargain_id;

                        $bargain_goods_list[] = array_merge($sku, $common_data);
                    }
                }

                array_multisort(array_column($bargain_goods_list, 'floor_price'), SORT_ASC, $bargain_goods_list);
                model('promotion_bargain')->update(
                    [
                        'sku_id' => $bargain_goods_list[ 0 ][ 'sku_id' ],
                        'floor_price' => $bargain_goods_list[ 0 ][ 'floor_price' ],
                        'bargain_stock' => $bargain_stock
                    ],
                    [ [ 'bargain_id', '=', $bargain_id ] ]
                );
                model('promotion_bargain_goods')->addList($bargain_goods_list);
                $cron = new Cron();
                if ($common_data[ 'status' ] == 1) {
                    $this->modifyPromotionAddon($bargain_id);
                    $cron->addCron(1, 0, '砍价活动关闭', 'CloseBargain', $common_data[ 'end_time' ], $bargain_id);
                } else {
                    $cron->addCron(1, 0, '砍价活动开启', 'OpenBargain', $common_data[ 'start_time' ], $bargain_id);
                    $cron->addCron(1, 0, '砍价活动关闭', 'CloseBargain', $common_data[ 'end_time' ], $bargain_id);
                }
            }

            model('promotion_bargain')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_bargain')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 编辑砍价
     * @param $common_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function editBargain($common_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $bargain_count = model('promotion_bargain')->getCount(
            [ [ 'bargain_id', '=', $common_data[ 'bargain_id' ] ], [ 'site_id', '=', $common_data[ 'site_id' ] ] ]
        );
        if ($bargain_count == 0) {
            return $this->error('', '数据有误');
        }

        //时间段检测
        $count = model('promotion_bargain_goods')->getCount([
            [ 'sku_id', 'in', $goods[ 'sku_ids' ] ],
            [ 'status', 'in', '0,1,3,4' ],
            [ 'bargain_id', '<>', $common_data[ 'bargain_id' ] ],
            [ 'site_id', '=', $common_data[ 'site_id' ] ],
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $common_data[ 'end_time' ] . ' and `start_time` > ' . $common_data[ 'start_time' ] . ' )  or (`end_time` < ' . $common_data[ 'start_time' ] . ' and `end_time` < ' . $common_data[ 'end_time' ] . '))') ]//todo  修正  所有的优惠都要一样
        ]);
        if ($count > 0) {
            return $this->error('', '有商品已设置砍价活动，请不要重复设置');
        }

        $time = time();
        if ($time > $common_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        // 当前时间
        if ($time > $common_data[ 'start_time' ] && $time < $common_data[ 'end_time' ]) {
            $common_data[ 'status' ] = 1;
            $common_data[ 'status_name' ] = $this->status[ 1 ];
        } else {
            $common_data[ 'status' ] = 0;
            $common_data[ 'status_name' ] = $this->status[ 0 ];
        }
        model('promotion_bargain')->startTrans();
        try {

            $bargain_goods_list = [];
            $bargain_stock = 0;
            //添加砍价活动商品
            foreach ($sku_list as $v) {
                $count = model('promotion_bargain_goods')->getCount([ [ 'sku_id', '=', $v[ 'sku_id' ] ], [ 'bargain_id', '=', $common_data[ 'bargain_id' ] ] ]);
                $is_delete = $v[ 'is_delete' ];
                unset($v[ 'is_delete' ]);
                // 是否参与  1参与  2不参与
                if ($is_delete == 2) {
                    if ($count > 0) {
                        model('promotion_bargain_goods')->delete([ [ 'sku_id', '=', $v[ 'sku_id' ] ], [ 'bargain_id', '=', $common_data[ 'bargain_id' ] ] ]);
                    }
                } else {
                    $bargain_stock += $v[ 'bargain_stock' ];

                    $bargain_data = array_merge($v, $common_data);
                    if ($count > 0) {
                        model('promotion_bargain_goods')->update(
                            $bargain_data,
                            [ [ 'sku_id', '=', $v[ 'sku_id' ] ], [ 'bargain_id', '=', $common_data[ 'bargain_id' ] ] ]
                        );
                    } else {
                        model('promotion_bargain_goods')->add($bargain_data);
                    }
                    $bargain_goods_list[] = $bargain_data;
                }
            }

            array_multisort(array_column($bargain_goods_list, 'floor_price'), SORT_ASC, $bargain_goods_list);
            //修改砍价活动
            model('promotion_bargain')->update(
                array_merge([
                    'sku_id' => $bargain_goods_list[ 0 ][ 'sku_id' ],
                    'floor_price' => $bargain_goods_list[ 0 ][ 'floor_price' ],
                    'bargain_stock' => $bargain_stock,
                    'modify_time' => $time
                ], $common_data),
                [ [ 'bargain_id', '=', $common_data[ 'bargain_id' ] ] ]
            );

            $cron = new Cron();
            if ($common_data[ 'status' ] == 1) {
                $this->modifyPromotionAddon($common_data[ 'bargain_id' ]);

                $cron->deleteCron([ [ 'event', '=', 'OpenBargain' ], [ 'relate_id', '=', $common_data[ 'bargain_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseBargain' ], [ 'relate_id', '=', $common_data[ 'bargain_id' ] ] ]);

                $cron->addCron(1, 0, '砍价活动关闭', 'CloseBargain', $common_data[ 'end_time' ], $common_data[ 'bargain_id' ]);
            } else {
                $cron->deleteCron([ [ 'event', '=', 'OpenBargain' ], [ 'relate_id', '=', $common_data[ 'bargain_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseBargain' ], [ 'relate_id', '=', $common_data[ 'bargain_id' ] ] ]);

                $cron->addCron(1, 0, '砍价活动开启', 'OpenBargain', $common_data[ 'start_time' ], $common_data[ 'bargain_id' ]);
                $cron->addCron(1, 0, '砍价活动关闭', 'CloseBargain', $common_data[ 'end_time' ], $common_data[ 'bargain_id' ]);
            }

            // 清除活动分享图片
            ( new Poster() )->clearShareImg($common_data[ 'bargain_id' ]);

            model('promotion_bargain')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_bargain')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 删除砍价活动
     * @param $bargain_id
     * @param $site_id
     * @return array
     */
    public function deleteBargain($bargain_id, $site_id)
    {
        //砍价信息
        $bargain_info = model('promotion_bargain')->getInfo([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if ($bargain_info) {

            if ($bargain_info[ 'status' ] != 1) {
                $res = model('promotion_bargain')->delete([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ]);
                if ($res) {
                    //删除商品
                    model('promotion_bargain_goods')->delete([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ]);
                    //删除砍价记录
                    model('promotion_bargain_launch')->delete([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ]);

                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenBargain' ], [ 'relate_id', '=', $bargain_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'CloseBargain' ], [ 'relate_id', '=', $bargain_id ] ]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '砍价活动进行中,请先关闭该活动');
            }

        } else {
            return $this->error('', '砍价活动不存在');
        }
    }

    /**
     * 关闭砍价活动
     * @param $bargain_id
     * @param $site_id
     * @return array
     */
    public function finishBargain($bargain_id, $site_id)
    {
        //砍价信息
        $bargain_info = model('promotion_bargain')->getInfo([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if (!empty($bargain_info)) {

            if ($bargain_info[ 'status' ] != 3) {
                $res = model('promotion_bargain')->update([ 'status' => 3, 'status_name' => $this->status[ 3 ] ], [ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ]);
                if ($res) {

                    model('promotion_bargain_goods')->update(
                        [ 'status' => 3, 'status_name' => $this->status[ 3 ] ],
                        [ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ]
                    );

                    model('promotion_bargain_launch')->update([ 'status' => 2 ], [ [ 'bargain_id', '=', $bargain_id ], [ 'status', '=', 0 ] ]);

                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenBargain' ], [ 'relate_id', '=', $bargain_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'CloseBargain' ], [ 'relate_id', '=', $bargain_id ] ]);
                    $this->modifyPromotionAddon($bargain_id, true);
                }
                return $this->success($res);
            } else {
                $this->error('', '该砍价活动已关闭');
            }

        } else {
            $this->error('', '该砍价活动不存在');
        }
    }

    /**
     * 获取砍价信息
     * @param array $condition
     * @return array
     */
    public function getBargainInfo($condition = [])
    {
        $field = 'pb.*,g.goods_name,g.goods_image';
        $alias = 'pb';
        $join = [
            [
                'goods g',
                'g.goods_id = pb.goods_id',
                'inner'
            ]
        ];
        $bargain_info = model('promotion_bargain')->getInfo($condition, $field, $alias, $join);
        if (!empty($bargain_info)) {

            $goods_list = model('goods_sku')->getList(
                [ [ 'goods_id', '=', $bargain_info[ 'goods_id' ] ] ],
                'goods_id,sku_id,sku_name,price,sku_image,stock'
            );
            foreach ($goods_list as $k => $v) {
                $v[ 'stock' ] = numberFormat($v[ 'stock' ]);
                $bargain_goods = model('promotion_bargain_goods')->getInfo(
                    [ [ 'sku_id', '=', $v[ 'sku_id' ] ], [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ] ],
                    'first_bargain_price,bargain_stock,floor_price'
                );
                if (empty($bargain_goods)) {
                    $bargain_goods = [
                        'first_bargain_price' => 0,
                        'bargain_stock' => 0,
                        'floor_price' => 0
                    ];
                }
                $goods_list[ $k ] = array_merge($v, $bargain_goods);
            }
            array_multisort(array_column($goods_list, 'bargain_stock'), SORT_DESC, $goods_list);
            $bargain_info[ 'goods_list' ] = $goods_list;
        }
        return $this->success($bargain_info);
    }

    /**
     * 获取砍价信息
     * @param array $condition
     * @return array
     */
    public function getBargainJoinGoodsList($condition = [])
    {
        $field = 'pb.*,g.goods_name,g.goods_image';
        $alias = 'pb';
        $join = [
            [
                'goods g',
                'g.goods_id = pb.goods_id',
                'inner'
            ]
        ];
        $bargain_info = model('promotion_bargain')->getInfo($condition, $field, $alias, $join);
        if (!empty($bargain_info)) {

            $goods_list = model('promotion_bargain_goods')->getList(
                [ [ 'pbg.bargain_id', '=', $bargain_info[ 'bargain_id' ] ] ],
                'pbg.first_bargain_price,pbg.bargain_stock,pbg.floor_price,sku.goods_id,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '', 'pbg', [ [ 'goods_sku sku', 'sku.sku_id = pbg.sku_id', 'inner' ] ]
            );
            foreach ($goods_list as $k => $v) {
                $goods_list[ $k ][ 'stock' ] = numberFormat($goods_list[ $k ][ 'stock' ]);
            }

            $bargain_info[ 'goods_list' ] = $goods_list;
        }
        return $this->success($bargain_info);
    }

    /**
     * 获取砍价商品信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getBargainGoodsDetail($condition = [], $field = '')
    {
        if (empty($field)) $field = 'pb.join_num,pb.browse_num,pb.share_num,pbg.id,pbg.bargain_id,pbg.goods_id,pbg.sku_id,pbg.floor_price,pbg.bargain_stock,pbg.bargain_name,pbg.start_time,pbg.end_time,pbg.buy_type,pbg.remark,sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.stock,sku.click_num,sku.form_id,
        (pb.sale_num + g.virtual_sale) as sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.goods_content,sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,g.evaluate,sku.goods_service_ids, g.goods_image,g.goods_stock,g.goods_name,sku.qr_id,g.stock_show,g.sale_show,g.label_name,pb.status as bargain_status,pb.is_own';
        $join = [
            [ 'goods_sku sku', 'pbg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_bargain pb', 'pb.bargain_id = pbg.bargain_id', 'inner' ],
        ];
        $bargain_goods_info = model('promotion_bargain_goods')->getInfo($condition, $field, 'pbg', $join);
        if (!empty($bargain_goods_info)) {
            if (isset($bargain_goods_info[ 'stock' ])) {
                $bargain_goods_info[ 'stock' ] = numberFormat($bargain_goods_info[ 'stock' ]);
            }
            if (isset($bargain_goods_info[ 'goods_stock' ])) {
                $bargain_goods_info[ 'goods_stock' ] = numberFormat($bargain_goods_info[ 'goods_stock' ]);
            }
        }
        return $this->success($bargain_goods_info);
    }

    /**
     * 获取砍价商品信息
     * @param array $condition
     * @return array
     */
    public function getBargainGoodsSkuList($condition = [])
    {
        $field = 'pb.join_num,pb.browse_num,pb.share_num,pbg.id,pbg.bargain_id,pbg.goods_id,pbg.sku_id,pbg.floor_price
        ,pbg.bargain_stock,pbg.bargain_name,pbg.start_time,pbg.end_time,pbg.buy_type,sku.sku_name
        ,sku.sku_spec_format,sku.price,sku.stock,sku.sku_image
        ,sku.sku_images,sku.goods_spec_format,g.goods_image,pb.status as bargain_status,pb.is_own';
        $join = [
            [ 'goods_sku sku', 'pbg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_bargain pb', 'pb.bargain_id = pbg.bargain_id', 'inner' ],
        ];
        $list = model('promotion_bargain_goods')->getList($condition, $field, 'pbg.id asc', 'pbg', $join);
        foreach ($list as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取砍价列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getBargainList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $alias = 'pb';

        if (empty($field)) {
            $field = 'pb.*,g.goods_name,g.goods_image,g.price,g.recommend_way,sku.sku_id,sku.price,sku.sku_name,sku.sku_image,sku.stock as goods_stock,g.label_name,g.goods_class_name,g.stock_show,g.sale_show';
        }

        $join = [
            [ 'goods g', 'g.goods_id = pb.goods_id', 'inner' ],
            [ 'goods_sku sku', 'g.sku_id = sku.sku_id', 'inner' ],
        ];
        $list = model('promotion_bargain')->getList($condition, $field, $order, $alias, $join, '', $limit);
        foreach ($list as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
            }
            if (isset($v[ 'goods_stock' ])) {
                $list[ $k ][ 'goods_stock' ] = numberFormat($list[ $k ][ 'goods_stock' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取砍价分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @return array
     */
    public function getBargainPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pb.bargain_id desc', $field = '')
    {
        $alias = 'pb';

        if (empty($field)) {
            $field = 'pb.*,g.goods_name,g.goods_image,g.price,g.recommend_way,sku.sku_id,sku.price,sku.sku_name,sku.sku_image,sku.stock as goods_stock,g.label_name,g.goods_class_name,g.stock_show,g.sale_show';
        }

        $join = [
            [ 'goods g', 'g.goods_id = pb.goods_id', 'inner' ],
            [ 'goods_sku sku', 'g.sku_id = sku.sku_id', 'inner' ],
        ];
        $res = model('promotion_bargain')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($res[ 'list' ] as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $res[ 'list' ][ $k ][ 'stock' ] = numberFormat($res[ 'list' ][ $k ][ 'stock' ]);
            }
            if (isset($v[ 'goods_stock' ])) {
                $res[ 'list' ][ $k ][ 'goods_stock' ] = numberFormat($res[ 'list' ][ $k ][ 'goods_stock' ]);
            }
        }
        return $this->success($res);
    }

    /**
     * 获取砍价商品列表
     * @param $bargain_id
     * @param $site_id
     * @return array
     */
    public function getBargainGoodsList($bargain_id, $site_id)
    {
        $field = 'pbg.*,sku.sku_name,sku.price,sku.sku_image,sku.stock';
        $alias = 'pbg';
        $join = [
            [
                'goods g',
                'g.goods_id = pbg.goods_id',
                'inner'
            ],
            [
                'goods_sku sku',
                'sku.sku_id = pbg.sku_id',
                'inner'
            ]
        ];
        $condition = [
            [ 'pbg.bargain_id', '=', $bargain_id ], [ 'pbg.site_id', '=', $site_id ],
            [ 'g.is_delete', '=', 0 ], [ 'g.goods_state', '=', 1 ]
        ];

        $list = model('promotion_bargain_goods')->getList($condition, $field, '', $alias, $join);
        foreach ($list as $k => $v) {
            if (isset($v[ 'stock' ])) {
                $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取砍价商品分页列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getBargainGoodsPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $list = model('promotion_bargain_goods')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        return $this->success($list);
    }

    /**
     * 开启砍价活动
     * @param $bargain_id
     * @return array
     */
    public function cronOpenBargain($bargain_id)
    {
        $bargain_info = model('promotion_bargain')->getInfo([ [ 'bargain_id', '=', $bargain_id ] ], 'status');
        if (!empty($bargain_info)) {

            if ($bargain_info[ 'status' ] == 0) {
                $res = model('promotion_bargain')->update([ 'status' => 1, 'status_name' => $this->status[ 1 ] ], [ [ 'bargain_id', '=', $bargain_id ] ]);
                if ($res) {
                    model('promotion_bargain_goods')->update(
                        [ 'status' => 1, 'status_name' => $this->status[ 1 ] ],
                        [ [ 'bargain_id', '=', $bargain_id ] ]
                    );

                    $this->modifyPromotionAddon($bargain_id);
                }
                return $this->success($res);
            } else {
                return $this->error('', '砍价活动已开启或者关闭');
            }

        } else {
            return $this->error('', '砍价活动不存在');
        }

    }

    /**
     * 关闭砍价活动
     * @param $bargain_id
     * @return array
     */
    public function cronCloseBargain($bargain_id)
    {
        $bargain_info = model('promotion_bargain')->getInfo([ [ 'bargain_id', '=', $bargain_id ] ], 'status');
        if (!empty($bargain_info)) {

            if ($bargain_info[ 'status' ] != 2) {
                $res = model('promotion_bargain')->update([ 'status' => 2, 'status_name' => $this->status[ 2 ] ], [ [ 'bargain_id', '=', $bargain_id ] ]);
                if ($res) {
                    model('promotion_bargain_goods')->update(
                        [ 'status' => 2, 'status_name' => $this->status[ 2 ] ],
                        [ [ 'bargain_id', '=', $bargain_id ] ]
                    );
                    $data = [ 'status' => 2 ];
                    model('promotion_bargain_launch')->update($data, [ [ 'bargain_id', '=', $bargain_id ], [ 'status', '=', 0 ] ]);
                    $this->modifyPromotionAddon($bargain_id, true);
                }
                return $this->success($res);
            } else {
                return $this->error('', '该活动已结束');
            }
        } else {
            return $this->error('', '砍价活动不存在');
        }
    }

    /**
     * 获取砍价发起信息
     * @param array $condition
     * @param string $field
     * @param string $alias
     * @param string $join
     * @return array
     */
    public function getBargainLaunchDetail($condition = [], $field = '*', $alias = '', $join = '')
    {
        $data = model('promotion_bargain_launch')->getInfo($condition, $field, $alias, $join);
        if (!empty($data)) {
            return $this->success($data);
        } else {
            return $this->error();
        }
    }

    /**
     * 获取砍价发起分页列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getBargainLaunchPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $data = model('promotion_bargain_launch')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        if (!empty($data[ 'list' ])) {
            foreach ($data[ 'list' ] as $k => $item) {
                $record_data = model('promotion_bargain_record')->pageList([ [ 'launch_id', '=', $item[ 'launch_id' ] ] ], 'headimg', 'id asc', 1, 6);
                $data[ 'list' ][ $k ][ 'bargain_record' ] = $record_data[ 'list' ];
            }
        }
        return $this->success($data);
    }

    /**
     * 获取砍价发起分页列表(管理端)
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getBargainLaunchPageListInAdmin($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $data = model('promotion_bargain_launch')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        return $this->success($data);
    }

    /**
     * 获取砍价发起列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param string $alias
     * @param array $join
     * @param string $group
     * @param null $limit
     * @return mixed
     */
    public function getBargainLaunchList($condition = [], $field = true, $order = '', $alias = 'a', $join = [], $group = '', $limit = null)
    {
        return model('promotion_bargain_launch')->getList($condition, $field, $order, $alias, $join, $group, $limit);
    }

    /**
     * 查询数据
     * @param array $condition
     */
    public function getBargainLaunchCount($condition = [])
    {
        $count = model('promotion_bargain_launch')->getCount($condition, 'launch_id');
        return $this->success($count);
    }

    /**
     * 获取砍价记录
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getBargainRecordPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $data = model('promotion_bargain_record')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        return $this->success($data);
    }

    /**
     * 获取砍价记录信息
     * @param array $condition
     * @param string $field
     */
    public function getBargainRecordInfo($condition = [], $field = '*')
    {
        $data = model('promotion_bargain_record')->getInfo($condition, $field);
        return $this->success($data);
    }

    /**
     * 发起砍价
     * @param $id
     * @param $member_id
     * @param $site_id
     * @param $store_id
     * @return array
     */
    public function launch($id, $member_id, $site_id, $store_id)
    {
        $condition = [
            [ 'pbg.id', '=', $id ],
            [ 'pbg.site_id', '=', $site_id ],
            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];

        $field = 'pbg.*, sku.sku_image,sku.sku_name,sku.price,sku.stock';
        $join = [
            [ 'goods_sku sku', 'pbg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = pbg.goods_id', 'inner' ]
        ];
        $bargain_info = model('promotion_bargain_goods')->getInfo($condition, $field, 'pbg', $join);

        if (empty($bargain_info)) return $this->error('', '砍价活动已结束');

        if ($bargain_info[ 'bargain_stock' ] <= 0) return $this->error('', '库存不足');
        if ($bargain_info[ 'stock' ] <= 0) return $this->error('', '库存不足');

        $bargain_info[ 'stock' ] = numberFormat($bargain_info[ 'stock' ]);

        $launch_info = model('promotion_bargain_launch')->getInfo([ [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ], [ 'member_id', '=', $member_id ] ]);
        if ($launch_info && empty($launch_info[ 'status' ])) return $this->error('', '该商品正在砍价中');

        if ($launch_info && $launch_info[ 'status' ] == 1) return $this->error('', '该商品已砍价成功，不能再次发起');

        $launch_info = model('promotion_bargain_launch')->getInfo([
            [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ],
            [ 'member_id', '=', $member_id ],
            [ 'order_id', '>', 0 ],
            [ 'status', 'in', [ 0, 1 ] ]
        ]);

        if ($launch_info && !empty($launch_info[ 'order_id' ])) return $this->error('', '您已发起过砍价，不能再次发起');

        $member_info = model('member')->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ], 'nickname,headimg');
        if (empty($member_info)) return $this->error('', '未获取到会员信息');

        try {
            $data = [
                'bargain_id' => $bargain_info[ 'bargain_id' ],
                'sku_id' => $bargain_info[ 'sku_id' ],
                'goods_id' => $bargain_info[ 'goods_id' ],
                'site_id' => $bargain_info[ 'site_id' ],
                'sku_name' => $bargain_info[ 'sku_name' ],
                'sku_image' => $bargain_info[ 'sku_image' ],
                'price' => $bargain_info[ 'price' ],
                'floor_price' => $bargain_info[ 'floor_price' ],
                'buy_type' => $bargain_info[ 'buy_type' ],
                'bargain_type' => $bargain_info[ 'bargain_type' ],
                'need_num' => $bargain_info[ 'bargain_num' ],
                'start_time' => time(),
                'end_time' => ( time() + ( $bargain_info[ 'bargain_time' ] * 3600 ) ),
                'member_id' => $member_id,
                'nickname' => $member_info[ 'nickname' ],
                'headimg' => $member_info[ 'headimg' ],
                'is_fenxiao' => $bargain_info[ 'is_fenxiao' ],
                'first_bargain_price' => $bargain_info[ 'first_bargain_price' ],
                'curr_price' => $bargain_info[ 'price' ],
                'is_own' => $bargain_info[ 'is_own' ],
                'is_differ_new_user' => $bargain_info[ 'is_differ_new_user' ],
                'distinguish' => $bargain_info[ 'distinguish' ],
                'new_low' => $bargain_info[ 'new_low' ],
                'aged_tall' => $bargain_info[ 'aged_tall' ],
                'aged_fixation' => $bargain_info[ 'aged_fixation' ],
                'store_id' => $store_id
            ];

            $launch_id = model('promotion_bargain_launch')->add($data);

            if ($launch_id) {
                //修改砍价商品信息
//                model('promotion_bargain_goods')->update(
//                    [
//                        'bargain_stock' => $bargain_info[ 'bargain_stock' ] - 1,
//                        'join_num' => $bargain_info[ 'join_num' ] + 1,
//                    ]
//                    , [ [ 'id', '=', $id ] ]
//                );
                //增加参与人数
                model('promotion_bargain')->setInc([ [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ] ], 'join_num');

                if ($bargain_info[ 'is_own' ]) {
                    $this->bargain($launch_id, $member_id, $site_id);
                }
                $cron = new Cron();
                $cron->addCron(1, 0, '砍价发起自动关闭', 'BargainLaunchClose', $data[ 'end_time' ], $launch_id);
                return $this->success($launch_id);
            } else {
                return $this->error();
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 砍价
     * @param $launch_id
     * @param $member_id
     * @param $site_id
     * @return array
     */
    public function bargain($launch_id, $member_id, $site_id)
    {
        model('promotion_bargain_launch')->startTrans();
        try {
            $launch_info = Db::name('promotion_bargain_launch')->where([ [ 'launch_id', '=', $launch_id ], [ 'site_id', '=', $site_id ] ])->lock(true)->find();

            if (empty($launch_info)) {
                model('promotion_bargain_launch')->rollback();
                return $this->error('', '未获取到砍价信息');
            }
            if ($launch_info[ 'status' ] != 0) {
                model('promotion_bargain_launch')->rollback();
                return $this->error('', '砍价已结束');
            }
            if ($launch_info[ 'is_own' ] == 0 && $launch_info[ 'member_id' ] == $member_id) {
                model('promotion_bargain_launch')->rollback();
                return $this->error('', '不支持给自己砍价');
            }
            $member_bargain_count = model('promotion_bargain_record')->getCount([
                [ 'bl.bargain_id', '=', $launch_info[ 'bargain_id' ] ],
                [ 'br.member_id', '=', $member_id ],
            ], 'br.id', 'br', [
                [ 'promotion_bargain_launch bl', 'bl.launch_id = br.launch_id', 'left' ]
            ]);

            $bargain_info = model('promotion_bargain')->getInfo([ [ 'bargain_id', '=', $launch_info[ 'bargain_id' ] ] ], 'help_bargain_num,bargain_max_num');

            if ($bargain_info && !empty($bargain_info[ 'help_bargain_num' ]) && $member_bargain_count >= $bargain_info[ 'help_bargain_num' ]) {
                return $this->error('', '您的帮砍次数已达上限');
            }
            $launch_info = array_merge($launch_info, $bargain_info);
            //判断商品库存
            $goods_model = new Goods();

            $goods_sku_stock = $goods_model->getGoodsSkuInfo([ [ 'sku_id', '=', $launch_info[ 'sku_id' ] ] ], 'stock');
            $stock = $goods_sku_stock[ 'data' ][ 'stock' ];

            if ($stock <= 0) {
                model('promotion_bargain_launch')->rollback();
                return $this->error('', '库存不足');
            }

            $member_info = model('member')->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ], 'nickname,headimg');
            if (empty($member_info)) {
                model('promotion_bargain_launch')->rollback();
                return $this->error('', '未获取到会员信息');
            }

            //区分新老用户
            $start_time = time();
            $end_time = date('Y-m-d H:i:s', strtotime('-' . $launch_info[ 'distinguish' ] . ' day'));
            $member_info_data = model('member')->getInfo([ [ 'site_id', '=', $launch_info[ 'site_id' ] ], [ 'member_id', '=', $member_id ], [ 'reg_time', 'between', [ date_to_time($end_time), $start_time ] ] ], 'member_id');
            $is_first = model('promotion_bargain_record')->getCount([ [ 'launch_id', '=', $launch_id ] ], 'id');
            $is_first_member = model('promotion_bargain_record')->getCount([ [ 'member_id', '=', $member_id ] ], 'id');
            if (!$is_first) {
                // 如果是首刀
                $bargain_money = $launch_info[ 'first_bargain_price' ] > 0 ? $launch_info[ 'first_bargain_price' ] : $this->bargainMoneyCalculate($launch_info, $member_info_data, $is_first_member);
            } else {
                $is_exist = model('promotion_bargain_record')->getCount([ [ 'launch_id', '=', $launch_id ], [ 'member_id', '=', $member_id ] ], 'id');
                if ($is_exist) {
                    model('promotion_bargain_launch')->rollback();
                    return $this->error('', '您已帮好友砍过价了！');
                }
                $bargain_money = $this->bargainMoneyCalculate($launch_info, $member_info_data, $is_first_member);
            }

            if (( $launch_info[ 'curr_price' ] - $bargain_money ) < $launch_info[ 'floor_price' ]) {
                $bargain_money = $launch_info[ 'curr_price' ] - $launch_info[ 'floor_price' ];
            }

            if ($bargain_money <= 0) {
                model('promotion_bargain_launch')->rollback();
                return $this->error();
            }
            $data = [
                'launch_id' => $launch_id,
                'member_id' => $member_id,
                'nickname' => $member_info[ 'nickname' ],
                'headimg' => $member_info[ 'headimg' ],
                'money' => $bargain_money,
                'bargain_time' => time()
            ];

            model('promotion_bargain_record')->add($data);

            // 砍价人数自增
            model('promotion_bargain_launch')->setInc([ [ 'launch_id', '=', $launch_id ] ], 'curr_num');

            // 当前砍价金额自减
            model('promotion_bargain_launch')->setDec([ [ 'launch_id', '=', $launch_id ] ], 'curr_price', $bargain_money);

            // 砍价状态
            $status = 0;

            if (( (float) $launch_info[ 'curr_price' ] - (float) $bargain_money ) <= ( (float) $launch_info[ 'floor_price' ] )) {
                model('promotion_bargain_launch')->update([ 'status' => 1, 'end_time' => time() ], [ [ 'launch_id', '=', $launch_id ] ]);
                $status = 1;

                $message_model = new Message();
                // 发送消息
                $param = [ 'keywords' => 'BARGAIN_COMPLETE', 'launch_id' => $launch_id, 'site_id' => $launch_info[ 'site_id' ] ];
                $message_model->sendMessage($param);
            }

            model('promotion_bargain_launch')->commit();
            return $this->success([ 'bargain_money' => sprintf('%.2f', $bargain_money), 'status' => $status ]);
        } catch (\Exception $e) {
            model('promotion_bargain_launch')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 砍价金额计算
     * @param $data
     * @param $member_info
     * @param $is_first_member
     * @return float|int
     */
    private function bargainMoneyCalculate($data, $member_info, $is_first_member)
    {
        $bargain_money = 0;
        $bargain_num = $data[ 'first_bargain_price' ] > 0 ? $data[ 'need_num' ] - 1 : $data[ 'need_num' ];
        $bargain_max_num = $data[ 'bargain_max_num' ] ? ( $data[ 'first_bargain_price' ] > 0 ? $data[ 'bargain_max_num' ] - 1 : $data[ 'bargain_max_num' ] ) : 0;

        //平均砍价金额
        $average_bargain_money = ( $data[ 'price' ] - $data[ 'floor_price' ] - $data[ 'first_bargain_price' ] ) / $bargain_num;
        $need_money = $data[ 'curr_price' ] - $data[ 'floor_price' ]; // 剩余需砍金额
        if ($need_money <= 0.01) return 0.01;
        $rate = 0.5;
        if (!empty($bargain_max_num)) $rate = ( $bargain_max_num - $bargain_num ) / $bargain_max_num;

        if ($data[ 'buy_type' ] == 0 && $data[ 'bargain_type' ] == 1) {
            if ($bargain_max_num && ( $data[ 'curr_num' ] + 1 ) >= $data[ 'bargain_max_num' ]) return $need_money;
            //帮砍随机金额,任意金额可购买
            $bargain_money = round(mt_rand(( $average_bargain_money * 100 ) - ( $average_bargain_money * $rate * 100 ), $average_bargain_money * 100), 2);

            $bargain_money /= 100;
        } else if ($data[ 'buy_type' ] == 1 && $data[ 'bargain_type' ] == 1) {
            //随机绑砍，指定金额购买
            $need_num = $data[ 'need_num' ] - $data[ 'curr_num' ]; // 剩余需帮砍人数
            if ($need_num > 0) {
                if ($data[ 'is_differ_new_user' ] && $member_info && empty($is_first_member)) {
                    $bargain_money = mt_rand($data[ 'new_low' ], ( round(( $need_money / $need_num ), 2) * 100 ));
                } else {
                    if ($data[ 'is_differ_new_user' ]) {
                        $bargain_money = mt_rand(1, ( $data[ 'aged_tall' ] * 100 ));
                    } else {
                        $bargain_money = mt_rand(1, ( round(( $need_money / $need_num ), 2) * 100 ));
                    }
                }
            } else {

                if ($bargain_max_num && ( $data[ 'curr_num' ] + 1 ) >= $data[ 'bargain_max_num' ]) {
                    $bargain_money = $need_money * 100;
                } else {
                    if ($data[ 'is_differ_new_user' ] && $member_info && empty($is_first_member)) {
                        $bargain_money = mt_rand(1, ( $data[ 'aged_tall' ] * 100 ));
                    } else {
                        $bargain_money = mt_rand(1, ( $need_money * 100 ));
                    }
                }
            }
            $bargain_money = $bargain_money / 100;
        } else {
            if (( $data[ 'curr_num' ] + 1 ) >= $data[ 'need_num' ]) return $need_money;

            $bargain_money = round(mt_rand(( $average_bargain_money - $average_bargain_money * ( 1 - $rate ) ) * 100, ( $average_bargain_money + $average_bargain_money * ( 1 - $rate ) ) * 100), 2);
            $bargain_money /= 100;
        }

        return $bargain_money;
    }

    /**
     * 关闭到了时间的砍价
     * @param $launch_id
     */
    public function cronCloseBargainLaunch($launch_id)
    {
        $launch_info = model('promotion_bargain_launch')->getInfo([ [ 'launch_id', '=', $launch_id ], [ 'status', '=', 0 ] ]);
        if (!empty($launch_info)) {
            if ($launch_info[ 'curr_price' ] == $launch_info[ 'floor_price' ]) {
                $data = [ 'status' => 1 ];
            } else {
                // 未砍到低价都算失败
                $data = [ 'status' => 2 ];
            }
            model('promotion_bargain_launch')->update($data, [ [ 'launch_id', '=', $launch_id ], [ 'status', '=', 0 ] ]);
        }
    }

    /**
     * 商品营销活动标识
     * @param $bargain_id
     * @param bool $is_delete
     */
    private function modifyPromotionAddon($bargain_id, $is_delete = false)
    {
        $goods = new Goods();
        $condition = [
            [ 'bargain_id', '=', $bargain_id ]
        ];
        $goods_id = model('promotion_bargain')->getValue($condition, 'goods_id');

        $goods->modifyPromotionAddon($goods_id, [ 'bargain' => $bargain_id ], $is_delete);
    }

    /**
     * 判断规格值是否禁用
     * @param $bargain_id
     * @param $site_id
     * @param string $goods_spec_format
     * @return false|int|mixed|string
     */
    public function getGoodsSpecFormat($bargain_id, $site_id, $goods_spec_format = '')
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_bargain_goods')->getColumn([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $site_id ] ], 'sku_id');
        $goods_model = new Goods();
        return $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
    }

    public function orderPay($param)
    {
        //获取砍价id
        $bargain_id = model('promotion_bargain_launch')->getValue([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'bargain_id');
        if ($bargain_id != 0) {
            //更新砍价销量
            model('promotion_bargain')->setInc([ [ 'bargain_id', '=', $bargain_id ] ], 'sale_num');

            //获取sku_id
            $sku_id = model('order_goods')->getValue([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'sku_id');
            if ($sku_id != 0) {
                model('promotion_bargain_goods')->setInc([ [ 'bargain_id', '=', $bargain_id ], [ 'sku_id', '=', $sku_id ] ], 'sale_num');
            }
        }
        return $this->success();
    }

    /**
     * 订单关闭
     * @param $param
     * @return array
     */
    public function orderClose($param)
    {
        //获取砍价id
        $bargain_info = model('promotion_bargain_launch')->getInfo([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'bargain_id,sku_id');
        if (!empty($bargain_info)) {
            $num = model('order_goods')->getValue([ [ 'order_id', '=', $param[ 'order_id' ] ], [ 'sku_id', '=', $bargain_info[ 'sku_id' ] ] ], 'num');
            $param = [
                'bargain_id' => $bargain_info[ 'bargain_id' ],
                'sku_id' => $bargain_info[ 'sku_id' ],
                'num' => $num
            ];
            $this->incStock($param);
        }
        return $this->success();
    }

    /**
     * 订单退款
     * @param $param
     * @return array
     */
    public function orderGoodsRefund($param)
    {
        if ($param[ 'delivery_status' ] == Order::DELIVERY_WAIT) {
            $order_info = model('order')->getInfo([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'promotion_type');
            if (!empty($order_info) && $order_info[ 'promotion_type' ] == 'bargain') {
                $bargain_info = model('promotion_bargain_launch')->getInfo([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'bargain_id,sku_id');
                if (!empty($bargain_info)) {
                    $param = [
                        'bargain_id' => $bargain_info[ 'bargain_id' ],
                        'sku_id' => $bargain_info[ 'sku_id' ],
                        'num' => $param[ 'num' ]
                    ];
                    $this->incStock($param);

                    model('promotion_bargain')->setDec([ [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ] ], 'sale_num');

                    model('promotion_bargain_goods')->setDec([ [ 'bargain_id', '=', $bargain_info[ 'bargain_id' ] ], [ 'sku_id', '=', $bargain_info[ 'sku_id' ] ] ], 'sale_num');

                }
            }
        }

        return $this->success();
    }

    /**
     * 获取用户正在砍价中的商品
     * @param $member_id
     * @return array
     */
    public function getBargainingGoodsId($member_id)
    {
        $list = model('promotion_bargain_launch')->getList([ [ 'member_id', '=', $member_id ], [ 'status', '=', 0 ] ], 'goods_id', '', 'a', [], 'goods_id');
        $goods_id = [];
        if (!empty($list)) $goods_id = array_column($list, 'goods_id');
        return $goods_id;
    }

    /**
     * 增加库存
     * @param $param
     * @return array
     * @throws DbException
     */
    public function incStock($param)
    {
        $condition = array (
            [ 'sku_id', '=', $param[ 'sku_id' ] ],
            [ 'bargain_id', '=', $param[ 'bargain_id' ] ],
        );
        $num = $param[ 'num' ];
        $sku_info = model('promotion_bargain_goods')->getInfo($condition, 'goods_id,bargain_stock');
        if (empty($sku_info))
            return $this->error(-1, '');

        //编辑sku库存
        $result = model('promotion_bargain_goods')->setInc($condition, 'bargain_stock', $num);
        return $this->success($result);
    }

    /**
     * 减少库存
     * @param $param
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function decStock($param)
    {

        $num = $param[ 'num' ];
        $condition = array (
            [ 'sku_id', '=', $param[ 'sku_id' ] ],
            [ 'bargain_id', '=', $param[ 'bargain_id' ] ],
        );
        $sku_info = Db::name('promotion_bargain_goods')->where($condition)->field('goods_id,bargain_stock,bargain_name')->lock(true)->find();

        if (empty($sku_info)) {
            return $this->error();
        }

        if ($sku_info[ 'bargain_stock' ] < $num) {
            return $this->error('', $sku_info[ 'bargain_name' ] . '库存不足！');
        }
        //编辑sku库存
        $result = model('promotion_bargain_goods')->setDec($condition, 'bargain_stock', $num);

        if ($result === false) {
            return $this->error();
        }

        return $this->success($result);
    }

    /**
     * 商品用到的分类
     * @param $condition
     * @return array
     */
    public function getGoodsCategoryIds($condition)
    {
        $cache_name = 'shop_bargain_goods_category_' . md5(json_encode($condition));
        $cache_time = 60;
        $cache_res = Cache::get($cache_name);
        if (empty($cache_res) || time() - $cache_res[ 'time' ] > $cache_time) {
            $list = Db::name('promotion_bargain')
                ->alias('pb')
                ->join('goods g', 'pb.goods_id = g.goods_id', 'inner')
                ->where($condition)
                ->group('g.category_id')
                ->column('g.category_id');
            $category_ids = trim(join('0', $list), ',');
            $category_id_arr = array_unique(explode(',', $category_ids));
            Cache::set($cache_name, [ 'time' => time(), 'data' => $category_id_arr ]);
        } else {
            $category_id_arr = $cache_res[ 'data' ];
        }
        return $this->success($category_id_arr);
    }

    public function urlQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => $promotion_type,
            'h5_path' => $page . '?b_id=' . $qrcode_param[ 'b_id' ],
            'app_type' => $app_type,
            'qrcode_path' => 'upload/qrcode/bargain',
            'qrcode_name' => 'bargain_qrcode_' . $promotion_type . '_' . $qrcode_param[ 'b_id' ] . '_' . $site_id
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }

    /**
     * 浏览量增加
     * @param $condition
     * @return array
     */
    public function bargainBrowseInc($condition)
    {
        $res = model('promotion_bargain')->setInc($condition, 'browse_num');
        return $this->success($res);
    }

    /**
     * 分享量增加
     * @param $condition
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function bargainShareInc($condition)
    {
        $res = model('promotion_bargain')->setInc($condition, 'share_num');
        return $this->success($res);
    }

    /**
     * 砍价成功
     * @param $data
     */
    public function bargainCompleteMessage($data)
    {
        //发送短信
        $sms_model = new Sms();
        $launch_id = $data[ 'launch_id' ];
        $launch_info = model('promotion_bargain_launch')->getInfo([ [ 'launch_id', '=', $launch_id ] ]);

        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $launch_info[ 'member_id' ] ] ])[ 'data' ];

        $var_parse = array (
            'sku_name' => $launch_info[ 'sku_name' ],//商品名称
        );
        $data[ 'sms_account' ] = $member_info[ 'mobile' ];//手机号
        $data[ 'var_parse' ] = $var_parse;

//        Log::write('bargainCompleteMessage-sms-' . json_encode($data));

        $sms_model->sendMessage($data);

        // 【弃用，暂无模板信息，无法使用，等待后续微信支持后开发】
//        if (!empty($member_info) && !empty($member_info[ "wx_openid" ])) {
//            $wechat_model = new WechatMessage();
//            $data[ "openid" ] = $member_info[ "wx_openid" ];
//            $data[ "template_data" ] = [
//                'keyword1' => str_sub($launch_info[ 'sku_name' ]),
//                'keyword2' => $launch_info[ 'price' ],
//                'keyword3' => $launch_info[ 'floor_price' ],
//                'keyword4' => time_to_date($launch_info[ 'end_time' ]),
//            ];
//            $data[ "page" ] = 'pages_promotion/bargain/detail?l_id=' . $launch_info[ 'launch_id' ] . '&b_id=' . $launch_info[ 'bargain_id' ];
////            Log::write('bargainCompleteMessage-wechat-' . json_encode($data));
//            $wechat_model->sendMessage($data);
//        }

        //发送订阅消息
        if (!empty($member_info) && !empty($member_info[ 'weapp_openid' ])) {
            $weapp_model = new WeappMessage();
            $data[ 'openid' ] = $member_info[ 'weapp_openid' ];
            $data[ 'template_data' ] = [
                'thing1' => [
                    'value' => str_sub($launch_info[ 'sku_name' ])
                ],
                'amount2' => [
                    'value' => $launch_info[ 'price' ]
                ],
                'amount8' => [
                    'value' => $launch_info[ 'floor_price' ]
                ],
                'time3' => [
                    'value' => time_to_date($launch_info[ 'end_time' ])
                ],
            ];
            $data[ 'page' ] = 'pages_promotion/bargain/detail?l_id=' . $launch_info[ 'launch_id' ] . '&b_id=' . $launch_info[ 'bargain_id' ];
//            Log::write('bargainCompleteMessage-wechat-' . json_encode($data));
            $weapp_model->sendMessage($data);
        }
    }

}