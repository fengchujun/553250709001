<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use think\facade\Cache;
use think\facade\Db;

/**
 * 预售活动
 */
class Presale extends BaseModel
{

    private $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        3 => '已关闭'
    ];

    /**
     * 获取预售活动状态
     * @return array
     */
    public function getPresaleStatus()
    {
        return $this->success($this->status);
    }

    /**
     * 添加预售
     * @param $presale_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function addPresale($presale_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $presale_data[ 'create_time' ] = time();

        //查询该商品是否存在预售
        $presale_info = model('promotion_presale_goods')->getInfo(
            [
                [ 'ppg.site_id', '=', $presale_data[ 'site_id' ] ],
                [ 'pp.status', 'in', '0,1' ],
                [ 'ppg.goods_id', 'in', $goods[ 'goods_ids' ] ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $presale_data[ 'end_time' ] . ' and `start_time` > ' . $presale_data[ 'start_time' ] . ' )  or (`end_time` < ' . $presale_data[ 'start_time' ] . ' and `end_time` < ' . $presale_data[ 'end_time' ] . '))') ]
            ], 'ppg.id', 'ppg', [ [ 'promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'left' ] ]
        );
        if (!empty($presale_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在预售活动");
        }

        if (time() > $presale_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($presale_data[ 'start_time' ] <= time()) {
            $presale_data[ 'status' ] = 1;
            $presale_data[ 'status_name' ] = $this->status[ 1 ];
        } else {
            $presale_data[ 'status' ] = 0;
            $presale_data[ 'status_name' ] = $this->status[ 0 ];
        }
        model("promotion_presale")->startTrans();
        try {

            foreach ($goods[ 'goods_ids' ] as $goods_id) {

                //添加预售活动
                $presale_data[ 'goods_id' ] = $goods_id;
                $presale_id = model("promotion_presale")->add($presale_data);

                $presale_stock = 0;

                $sku_list_data = [];
                foreach ($sku_list as $k => $sku) {

                    if ($sku[ 'goods_id' ] == $goods_id) {

                        $presale_stock += $sku[ 'presale_stock' ];//总库存
                        $sku_list_data[] = [
                            'site_id' => $presale_data[ 'site_id' ],
                            'presale_id' => $presale_id,
                            'goods_id' => $goods_id,
                            'sku_id' => $sku[ 'sku_id' ],
                            'presale_stock' => $sku[ 'presale_stock' ],
                            'presale_deposit' => $sku[ 'presale_deposit' ],
                            'presale_price' => $sku[ 'presale_price' ],
                        ];
                    }
                }
                array_multisort(array_column($sku_list_data, 'presale_deposit'), SORT_ASC, $sku_list_data);
                model('promotion_presale_goods')->addList($sku_list_data);

                model('promotion_presale')->update(
                    [
                        'presale_stock' => $presale_stock,
                        'presale_deposit' => $sku_list_data[ 0 ][ 'presale_deposit' ],
                        'presale_price' => $sku_list_data[ 0 ][ 'presale_price' ],
                        'sku_id' => $sku_list_data[ 0 ][ 'sku_id' ]
                    ],
                    [ [ 'presale_id', '=', $presale_id ] ]
                );

                $cron = new Cron();
                if ($presale_data[ 'status' ] == 1) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($goods_id, [ 'presale' => $presale_id ]);
                    $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data[ 'end_time' ], $presale_id);
                } else {
                    $cron->addCron(1, 0, "预售活动开启", "OpenPresale", $presale_data[ 'start_time' ], $presale_id);
                    $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data[ 'end_time' ], $presale_id);
                }
            }

            model('promotion_presale')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_presale')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 编辑预售
     * @param $presale_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function editPresale($presale_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        //查询该商品是否存在预售
        $presale_info = model('promotion_presale_goods')->getInfo(
            [
                [ 'ppg.site_id', '=', $presale_data[ 'site_id' ] ],
                [ 'pp.status', 'in', '0,1' ],
                [ 'ppg.presale_id', '<>', $presale_data[ 'presale_id' ] ],
                [ 'ppg.sku_id', 'in', $goods[ 'sku_ids' ] ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $presale_data[ 'end_time' ] . ' and `start_time` > ' . $presale_data[ 'start_time' ] . ' )  or (`end_time` < ' . $presale_data[ 'start_time' ] . ' and `end_time` < ' . $presale_data[ 'end_time' ] . '))') ]
            ], 'ppg.id', 'ppg', [ [ 'promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'left' ] ]
        );
        if (!empty($presale_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在预售活动");
        }

        $presale_count = model("promotion_presale")->getCount([ [ 'presale_id', '=', $presale_data[ 'presale_id' ] ], [ 'site_id', '=', $presale_data[ 'site_id' ] ] ]);
        if ($presale_count == 0) {
            return $this->error('', '该预售活动不存在');
        }

        $cron = new Cron();
        if (time() > $presale_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($presale_data[ 'start_time' ] <= time()) {
            $presale_data[ 'status' ] = 1;
            $presale_data[ 'status_name' ] = $this->status[ 1 ];
        } else {
            $presale_data[ 'status' ] = 0;
            $presale_data[ 'status_name' ] = $this->status[ 0 ];
        }

        $presale_data[ 'modify_time' ] = time();
        model('promotion_presale')->startTrans();
        try {
            $presale_stock = 0;
            $sku_list_data = [];
            foreach ($sku_list as $k => $sku) {

                $count = model('promotion_presale_goods')->getCount([ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'presale_id', '=', $presale_data[ 'presale_id' ] ] ]);
                $is_delete = $sku[ 'is_delete' ];
                unset($sku[ 'is_delete' ]);
                if ($is_delete == 2) {//是否参与  1参与  2不参与
                    if ($count) {
                        model('promotion_presale_goods')->delete([ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'presale_id', '=', $presale_data[ 'presale_id' ] ] ]);
                    }
                } else {

                    $presale_stock += $sku[ 'presale_stock' ];//总库存
                    $sku_data = [
                        'site_id' => $presale_data[ 'site_id' ],
                        'presale_id' => $presale_data[ 'presale_id' ],
                        'goods_id' => $goods[ 'goods_id' ],
                        'sku_id' => $sku[ 'sku_id' ],
                        'presale_stock' => $sku[ 'presale_stock' ],
                        'presale_deposit' => $sku[ 'presale_deposit' ],
                        'presale_price' => $sku[ 'presale_price' ],
                    ];

                    if ($count > 0) {
                        model('promotion_presale_goods')->update($sku_data, [ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'presale_id', '=', $presale_data[ 'presale_id' ] ] ]);
                    } else {
                        model('promotion_presale_goods')->add($sku_data);
                    }
                    $sku_list_data[] = $sku_data;
                }
            }
            array_multisort(array_column($sku_list_data, 'presale_deposit'), SORT_ASC, $sku_list_data);
            model("promotion_presale")->update(
                array_merge($presale_data, [
                    'presale_stock' => $presale_stock,
                    'presale_deposit' => $sku_list_data[ 0 ][ 'presale_deposit' ],
                    'presale_price' => $sku_list_data[ 0 ][ 'presale_price' ],
                    'sku_id' => $sku_list_data[ 0 ][ 'sku_id' ]
                ]),
                [ [ 'presale_id', '=', $presale_data[ 'presale_id' ] ] ]
            );

            if ($presale_data[ 'start_time' ] <= time()) {

                $goods_model = new Goods();
                $goods_model->modifyPromotionAddon($goods[ 'goods_id' ], [ 'presale' => $presale_data[ 'presale_id' ] ]);
                //活动商品启动
                $this->cronOpenPresale($presale_data[ 'presale_id' ]);
                $cron->deleteCron([ [ 'event', '=', 'OpenPresale' ], [ 'relate_id', '=', $presale_data[ 'presale_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'ClosePresale' ], [ 'relate_id', '=', $presale_data[ 'presale_id' ] ] ]);

                $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data[ 'end_time' ], $presale_data[ 'presale_id' ]);
            } else {
                $cron->deleteCron([ [ 'event', '=', 'OpenPresale' ], [ 'relate_id', '=', $presale_data[ 'presale_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'ClosePresale' ], [ 'relate_id', '=', $presale_data[ 'presale_id' ] ] ]);

                $cron->addCron(1, 0, "预售活动开启", "OpenPresale", $presale_data[ 'start_time' ], $presale_data[ 'presale_id' ]);
                $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data[ 'end_time' ], $presale_data[ 'presale_id' ]);
            }

            model('promotion_presale')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_presale')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 增加预售组人数及购买人数
     * @param array $data
     * @param array $condition
     * @return array
     */
    public function editPresaleNum($data = [], $condition = [])
    {
        $res = model('promotion_presale')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除预售活动
     * @param $presale_id
     * @param $site_id
     * @return array|\multitype
     */
    public function deletePresale($presale_id, $site_id)
    {
        //预售信息
        $presale_info = model('promotion_presale')->getInfo([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if ($presale_info) {

            if ($presale_info[ 'status' ] != 1) {
                $res = model('promotion_presale')->delete([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ]);
                if ($res) {
                    //删除商品
                    model('promotion_presale_goods')->delete([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ]);

                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenPresale' ], [ 'relate_id', '=', $presale_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'ClosePresale' ], [ 'relate_id', '=', $presale_id ] ]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '预售活动进行中,请先关闭该活动');
            }

        } else {
            return $this->error('', '预售活动不存在');
        }
    }

    /**
     * 关闭预售活动
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function finishPresale($presale_id, $site_id)
    {
        //预售信息
        $presale_info = model('promotion_presale')->getInfo([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info[ 'status' ] != 3) {
                $res = model('promotion_presale')->update([ 'status' => 3, 'status_name' => $this->status[ 3 ] ], [ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ]);

                $cron = new Cron();
                $cron->deleteCron([ [ 'event', '=', 'OpenPresale' ], [ 'relate_id', '=', $presale_id ] ]);
                $cron->deleteCron([ [ 'event', '=', 'ClosePresale' ], [ 'relate_id', '=', $presale_id ] ]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info[ 'goods_id' ], [ 'presale' => $presale_id ], true);

                $presale_order = new PresaleOrderCommon();
                $presale_order->depositPresaleOrderClose([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ]);

                return $this->success($res);
            } else {
                $this->error('', '该预售活动已关闭');
            }
        } else {
            $this->error('', '该预售活动不存在');
        }
    }


    /**
     * 获取预售信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleInfo($condition = [], $field = '*')
    {
        $presale_info = model("promotion_presale")->getInfo($condition, $field);
        return $this->success($presale_info);
    }

    /**
     * 获取预售详细信息
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleDetail($presale_id, $site_id)
    {
        //预售信息
        $alias = 'p';
        $join = [
            [
                'goods g',
                'g.goods_id = p.goods_id',
                'inner'
            ]
        ];
        $presale_info = model("promotion_presale")->getInfo(
            [
                [ 'p.presale_id', '=', $presale_id ], [ 'p.site_id', '=', $site_id ],
                [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
            ], 'p.*', $alias, $join
        );
        if (!empty($presale_info)) {
            //商品sku信息
            $goods_list = model('goods_sku')->getList(
                [ [ 'goods_id', '=', $presale_info[ 'goods_id' ] ] ],
                'goods_id,sku_id,sku_name,price,sku_images,stock,sku_image'
            );
            foreach ($goods_list as $k => $v) {
                $v[ 'stock' ] = numberFormat($v[ 'stock' ]);
                $presale_goods = model('promotion_presale_goods')->getInfo(
                    [ [ 'presale_id', '=', $presale_id ], [ 'sku_id', '=', $v[ 'sku_id' ] ] ],
                    'presale_stock,presale_deposit,presale_price'
                );
                if (empty($presale_goods)) {
                    $presale_goods = [
                        'presale_stock' => 0,
                        'presale_deposit' => 0,
                        'presale_price' => 0
                    ];
                }
                $goods_list[ $k ] = array_merge($v, $presale_goods);
            }
            array_multisort(array_column($goods_list, 'presale_price'), SORT_DESC, $goods_list);
            $presale_info[ 'sku_list' ] = $goods_list;
        }
        return $this->success($presale_info);
    }

    /**
     * 获取预售详细信息
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleJoinGoodsList($presale_id, $site_id)
    {
        //预售信息
        $alias = 'p';
        $join = [
            [ 'goods g', 'g.goods_id = p.goods_id', 'inner' ]
        ];
        $presale_info = model("promotion_presale")->getInfo(
            [
                [ 'p.presale_id', '=', $presale_id ], [ 'p.site_id', '=', $site_id ],
                [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
            ], 'p.*', $alias, $join
        );
        if (!empty($presale_info)) {

            $goods_list = model('promotion_presale_goods')->getList(
                [ [ 'ppg.presale_id', '=', $presale_info[ 'presale_id' ] ] ],
                'ppg.presale_stock,ppg.presale_deposit,ppg.presale_price,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '', 'ppg', [ [ 'goods_sku sku', 'sku.sku_id = ppg.sku_id', 'inner' ] ]
            );
            foreach ($goods_list as $k => $v) {
                $goods_list[ $k ][ 'stock' ] = numberFormat($goods_list[ $k ][ 'stock' ]);
            }

            $presale_info[ 'sku_list' ] = $goods_list;
        }
        return $this->success($presale_info);
    }

    /**
     * 预售商品详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleGoodsDetail($condition = [], $field = '')
    {
        $alias = 'ppg';

        if (empty($field)) {
            $field = 'ppg.id,ppg.presale_id,ppg.goods_id,ppg.sku_id,ppg.presale_stock,ppg.presale_stock as stock,ppg.presale_deposit,ppg.presale_price,(pp.sale_num + g.virtual_sale) as sale_num,pp.presale_name,
            pp.presale_num,pp.start_time,pp.end_time,pp.pay_start_time,pp.pay_end_time,pp.deliver_type,pp.deliver_time,sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,pp.remark,
            sku.click_num,(g.sale_num + g.virtual_sale) as goods_sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.site_id,sku.goods_content,sku.goods_state,sku.is_virtual,
            sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,sku.goods_service_ids,sku.support_trade_type,
            g.goods_image,g.goods_stock,g.goods_name,sku.qr_id,g.stock_show,g.sale_show,g.label_name';
        }
        $join = [
            [ 'goods_sku sku', 'ppg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_presale pp', 'ppg.presale_id = pp.presale_id', 'inner' ],
        ];
        $presale_goods_info = model('promotion_presale_goods')->getInfo($condition, $field, $alias, $join);
        if (!empty($presale_goods_info)) {
            if (isset($presale_goods_info[ 'goods_sale_num' ])) {
                $presale_goods_info[ 'goods_sale_num' ] = numberFormat($presale_goods_info[ 'goods_sale_num' ]);
            }
            if (isset($presale_goods_info[ 'goods_stock' ])) {
                $presale_goods_info[ 'goods_stock' ] = numberFormat($presale_goods_info[ 'goods_stock' ]);
            }
        }
        return $this->success($presale_goods_info);
    }

    /**
     * 预售商品详情
     * @param array $condition
     * @return array
     */
    public function getPresaleGoodsSkuList($condition = [])
    {
        $alias = 'ppg';

        $field = 'ppg.id,ppg.presale_id,ppg.sku_id,ppg.presale_stock,ppg.presale_stock as stock,ppg.presale_deposit,ppg.presale_price,sku.sku_name,
        sku.sku_spec_format,sku.price,sku.sku_image,sku.sku_images,sku.goods_spec_format,g.goods_image';
        $join = [
            [ 'promotion_presale pp', 'ppg.presale_id = pp.presale_id', 'inner' ],
            [ 'goods_sku sku', 'ppg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
        ];
        $list = model('promotion_presale_goods')->getList($condition, $field, 'ppg.id asc', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取预售列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getPresaleList($condition = [], $field = '', $order = '', $limit = null)
    {
        $alias = 'pp';

        if (empty($field)) {
            $field = 'pp.*,g.price,g.goods_name,g.goods_image,(g.sale_num + g.virtual_sale) as goods_sale_num,g.unit,g.goods_stock,g.recommend_way';
        }
        $join = [
            [ 'goods g', 'pp.goods_id = g.goods_id', 'inner' ]
        ];

        $list = model('promotion_presale')->getList($condition, $field, $order, $alias, $join, '', $limit);
        foreach ($list as $k => $v) {
            if (isset($v[ 'goods_sale_num' ])) {
                $list[ $k ][ 'goods_sale_num' ] = numberFormat($list[ $k ][ 'goods_sale_num' ]);
            }
            if (isset($v[ 'goods_stock' ])) {
                $list[ $k ][ 'goods_stock' ] = numberFormat($list[ $k ][ 'goods_stock' ]);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取预售分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresalePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $field = 'p.*,g.goods_name,g.goods_image,g.price';
        $alias = 'p';
        $join = [
            [
                'goods g',
                'p.goods_id = g.goods_id',
                'inner'
            ]
        ];
        $list = model('promotion_presale')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取预售商品列表
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleGoodsList($presale_id, $site_id)
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
            [ 'pbg.presale_id', '=', $presale_id ], [ 'pbg.site_id', '=', $site_id ],
            [ 'g.is_delete', '=', 0 ], [ 'g.goods_state', '=', 1 ]
        ];

        $list = model('promotion_presale_goods')->getList($condition, $field, '', $alias, $join);
        foreach ($list as $k => $v) {
            $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
        }
        return $this->success($list);
    }

    /**
     * 获取预售商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresaleGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pp.presale_id desc', $field = '')
    {
        $alias = 'pp';
        if (empty($field)) {
            $field = 'pp.*,g.price,g.goods_name,g.goods_image,(g.sale_num + g.virtual_sale) as goods_sale_num,g.unit,g.goods_stock,g.recommend_way';
        }
        $join = [
            [ 'goods g', 'pp.goods_id = g.goods_id', 'inner' ]
        ];
        $res = model('promotion_presale')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($res[ 'list' ] as $k => $v) {
            if (isset($v[ 'goods_sale_num' ])) {
                $res[ 'list' ][ $k ][ 'goods_sale_num' ] = numberFormat($res[ 'list' ][ $k ][ 'goods_sale_num' ]);
            }
            if (isset($v[ 'goods_stock' ])) {
                $res[ 'list' ][ $k ][ 'goods_stock' ] = numberFormat($res[ 'list' ][ $k ][ 'goods_stock' ]);
            }
        }
        return $this->success($res);
    }

    /**
     * 开启预售活动
     * @param $presale_id
     * @return array|\multitype
     */
    public function cronOpenPresale($presale_id)
    {
        $presale_info = model('promotion_presale')->getInfo([ [ 'presale_id', '=', $presale_id ] ], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info[ 'status' ] == 0) {
                $res = model('promotion_presale')->update([ 'status' => 1, 'status_name' => $this->status[ 1 ] ], [ [ 'presale_id', '=', $presale_id ] ]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info[ 'goods_id' ], [ 'presale' => $presale_id ]);

                return $this->success($res);
            } else {
                return $this->error("", "预售活动已开启或者关闭");
            }

        } else {
            return $this->error("", "预售活动不存在");
        }

    }

    /**
     * 关闭预售活动
     * @param $presale_id
     * @return array|\multitype
     */
    public function cronClosePresale($presale_id)
    {
        $presale_info = model('promotion_presale')->getInfo([ [ 'presale_id', '=', $presale_id ] ], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info[ 'status' ] == 1) {
                $res = model('promotion_presale')->update([ 'status' => 2, 'status_name' => $this->status[ 2 ] ], [ [ 'presale_id', '=', $presale_id ] ]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info[ 'goods_id' ], [ 'presale' => $presale_id ], true);

                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "预售活动不存在");
        }
    }


    /**
     * 判断规格值是否禁用
     * @param $presale_id
     * @param $site_id
     * @param string $goods_spec_format
     * @return int|mixed
     */
    public function getGoodsSpecFormat($presale_id, $site_id, $goods_spec_format = '')
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_presale_goods')->getColumn([ [ 'presale_id', '=', $presale_id ], [ 'site_id', '=', $site_id ] ], 'sku_id');
        $goods_model = new Goods();
        $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        return $res;
    }


    /**
     * 判断sku是否参与预售
     * @param $sku_id
     * @return array
     */
    public function isJoinPresaleBySkuId($sku_id)
    {
        $condition = [
            [ 'ppg.sku_id', '=', $sku_id ],
            [ 'pp.status', '=', 1 ],
            [ 'pp.end_time', '>=', time() ]
        ];
        $alias = 'ppg';
        $join = [
            [ 'promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner' ]
        ];
        $info = model('promotion_presale_goods')->getInfo($condition, 'ppg.presale_id,ppg.sku_id', $alias, $join);
        if (empty($info)) {
            return $this->error();
        } else {
            return $this->success([ 'promotion_type' => 'presale', 'presale_id' => $info[ 'presale_id' ], 'sku_id' => $sku_id ]);
        }
    }

    /**
     * 判断sku是否参与预售
     * @param $sku_ids
     * @return array
     */
    public function isJoinPresaleBySkuIds($sku_ids)
    {
        $condition = [
            [ 'ppg.sku_id', 'in', $sku_ids ],
            [ 'pp.status', '=', 1 ],
            [ 'pp.end_time', '>=', time() ]
        ];
        $alias = 'ppg';
        $join = [
            [ 'promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner' ]
        ];
        $list = model('promotion_presale_goods')->getList($condition, 'ppg.presale_id,ppg.sku_id', '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 判断sku是否参与预售
     * @param $sku_id
     * @return array
     */
    public function isJoinPresaleByGoodsId($goods_id)
    {
        $condition = [
            [ 'ppg.goods_id', '=', $goods_id ],
            [ 'pp.status', '=', 1 ],
            [ 'pp.end_time', '>=', time() ]
        ];
        $alias = 'ppg';
        $join = [
            [ 'promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner' ]
        ];
        $list = model('promotion_presale_goods')->getList($condition, 'ppg.presale_id,ppg.sku_id','', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取会员已购该商品数
     * @param $goods_id
     * @param $member_id
     * @return float
     */
    public function getGoodsPurchasedNum($presale_id, $member_id)
    {
        $num = model("promotion_presale_order")->getSum([
            [ 'member_id', '=', $member_id ],
            [ 'presale_id', '=', $presale_id ],
            [ 'order_status', '<>', PresaleOrderCommon::ORDER_CLOSE ],
            [ 'refund_status', '<>', PresaleOrderRefund::REFUND_COMPLETE ]
        ], 'num');
        return $num;
    }

    /**
     * 获取预售订单数量
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getPresaleOrderCount($condition, $field = '*')
    {
        $count = model("promotion_presale_order")->getCount($condition, $field);
        return $this->success($count);
    }

    /**
     * 查询预售商品数量
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @param null $group
     * @return array
     */
    public function getPresaleGoodsCount($where = [], $field = '*', $alias = 'a', $join = null, $group = null)
    {
        $count = model("promotion_presale_order")->getCount($where, $field, $alias, $join, $group);
        return $this->success($count);
    }

    /**
     * 生成预售二维码
     * @param $presale_id
     * @param string $name
     * @param string $type 类型 create创建 get获取
     * @return mixed|array
     */
    public function qrcode($presale_id, $name, $site_id, $type = 'create')
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                'id' => $presale_id
            ],
            'page' => '/pages_promotion/presale/detail',
            'qrcode_path' => 'upload/qrcode/presale',
            'qrcode_name' => "presale_qrcode_" . $presale_id
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ( $k ) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[ $k ][ 'status' ] = 1;
                    $path[ $k ][ 'url' ] = $wap_domain . $data[ 'page' ] . '?id=' . $presale_id;
                    $path[ $k ][ 'img' ] = "upload/qrcode/presale/presale_qrcode_" . $presale_id . "_" . $k . ".png";
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信小程序';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }

                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信小程序';
                    }
                    break;
                case 'wechat' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'WECHAT_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信公众号';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }
                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信公众号';
                    }
                    break;
            }

        }

        $return = [
            'path' => $path,
            'name' => $name,
        ];

        return $this->success($return);
    }

    /**
     * 商品用到的分类
     * @param $condition
     * @return array
     */
    public function getGoodsCategoryIds($condition)
    {
        $cache_name = "shop_presale_goods_category_" . md5(json_encode($condition));
        $cache_time = 60;
        $cache_res = Cache::get($cache_name);
        if (empty($cache_res) || time() - $cache_res[ 'time' ] > $cache_time) {
            $list = Db::name('promotion_presale')
                ->alias('pp')
                ->join('goods g', 'pp.goods_id = g.goods_id', 'inner')
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
            'app_type' => $app_type,
            'h5_path' => $page . '?id=' . $qrcode_param[ 'id' ],
            'qrcode_path' => 'upload/qrcode/presale',
            'qrcode_name' => 'presale_qrcode_' . $promotion_type . '_' . $qrcode_param[ 'id' ] . '_' . $site_id
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}