<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\jielong\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Cron;

/**
 * 接龙活动
 */
class Jielong extends BaseModel
{

    private $status = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
//        3 => '已关闭'
    ];

    /**
     * 获取接龙活动状态
     * @return array
     */
    public function getJielongStatus()
    {
        return $this->success($this->status);
    }

    /**
     * 添加接龙
     * @param $jielong_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function addJielong($jielong_data, $goods)
    {
        if (empty($goods[ 'goods_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }

        $jielong_data[ 'create_time' ] = time();

        if (time() > $jielong_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($jielong_data[ 'start_time' ] <= time()) {
            $jielong_data[ 'status' ] = 1;
            $jielong_data[ 'status_name' ] = $this->status[ 1 ];
        } else {
            $jielong_data[ 'status' ] = 0;
            $jielong_data[ 'status_name' ] = $this->status[ 0 ];
        }

        model("promotion_jielong")->startTrans();
        try {

            //添加接龙活动
            $jielong_data[ 'goods_ids' ] = $goods[ 'goods_ids' ];
            $goods[ 'goods_ids' ] = explode(",", $goods[ 'goods_ids' ]);

            $jielong_id = model("promotion_jielong")->add($jielong_data);

            $list_data = [];
            foreach ($goods[ 'goods_ids' ] as $goods_id) {
                $list_data[] = [
                    'site_id' => $jielong_data[ 'site_id' ],
                    'jielong_id' => $jielong_id,
                    'goods_id' => $goods_id,
                    'sale_num' => 0
                ];

                if ($jielong_data[ 'status' ] == 1) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($goods_id, [ 'jielong' => $jielong_id ]);
                }

            }
            model('promotion_jielong_goods')->addList($list_data);

            $cron = new Cron();
            if ($jielong_data[ 'status' ] == 1) {
                $cron->addCron(1, 0, "接龙活动关闭", "CloseJielong", $jielong_data[ 'end_time' ], $jielong_id);
            } else {
                $cron->addCron(1, 0, "接龙活动开启", "OpenJielong", $jielong_data[ 'start_time' ], $jielong_id);
                $cron->addCron(1, 0, "接龙活动关闭", "CloseJielong", $jielong_data[ 'end_time' ], $jielong_id);
            }

            model('promotion_jielong')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_jielong')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 编辑接龙
     * @param $jielong_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function editJielong($jielong_data, $goods, $sku_list)
    {
        if (empty($goods)) {
            return $this->error('', '至少需要保留一个商品参与');
        }

        $jielong_info = model("promotion_jielong")->getInfo([ [ 'jielong_id', '=', $jielong_data[ 'jielong_id' ] ], [ 'site_id', '=', $jielong_data[ 'site_id' ] ] ], 'status,end_time,goods_ids');

        if (empty($jielong_info)) {
            return $this->error('', '该接龙活动不存在');
        }

        $cron = new Cron();
        if (time() > $jielong_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }

        $jielong_data[ 'status' ] = 0;

        if ($jielong_info[ 'status' ] == 1) {
            $jielong_data[ 'status' ] = 1;
            $jielong_data[ 'status_name' ] = $this->status[ 1 ];
            if ($jielong_data[ 'end_time' ] < $jielong_info[ 'end_time' ]) {
                return $this->error('', '进行中活动只能延长结束时间，不能缩短时间');
            }
        } elseif ($jielong_info[ 'status' ] == 0) {
            if ($jielong_data[ 'start_time' ] <= time()) {
                $jielong_data[ 'status' ] = 1;
                $jielong_data[ 'status_name' ] = $this->status[ 1 ];
            } else {
                $jielong_data[ 'status' ] = 0;
                $jielong_data[ 'status_name' ] = $this->status[ 0 ];
            }
        }

        $goods_ids = $goods;
        $goods = explode(",", $goods);
        model('promotion_jielong')->startTrans();
        try {

            if ($goods_ids != $jielong_info[ 'goods_ids' ]) {
                model("promotion_jielong_goods")->delete([ [ "jielong_id", "=", $jielong_data[ 'jielong_id' ] ], [ "site_id", "=", $jielong_data[ 'site_id' ] ] ]);

                $list_data = [];
                foreach ($goods as $v) {
                    $list_data[] = [
                        'site_id' => $jielong_data[ 'site_id' ],
                        'jielong_id' => $jielong_data[ 'jielong_id' ],
                        'goods_id' => $v,
                        'sale_num' => 0
                    ];
                }

                model('promotion_jielong_goods')->addList($list_data);

            }

            model("promotion_jielong")->update(
                array_merge($jielong_data, [
                    'update_time' => time(),
                    'goods_ids' => $goods_ids
                ]),
                [ [ 'jielong_id', '=', $jielong_data[ 'jielong_id' ] ] ]
            );

            if ($jielong_data[ 'status' ] == 1) {
                $goods_model = new Goods();

                foreach ($goods as $goods_id) {
                    $goods_model->modifyPromotionAddon($goods_id, [ 'jielong' => $jielong_data[ 'jielong_id' ] ]);
                }

                $cron->deleteCron([ [ 'event', '=', 'OpenJielong' ], [ 'relate_id', '=', $jielong_data[ 'jielong_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseJielong' ], [ 'relate_id', '=', $jielong_data[ 'jielong_id' ] ] ]);

                $cron->addCron(1, 0, "接龙活动关闭", "CloseJielong", $jielong_data[ 'end_time' ], $jielong_data[ 'jielong_id' ]);
            } else {
                $cron->deleteCron([ [ 'event', '=', 'OpenJielong' ], [ 'relate_id', '=', $jielong_data[ 'jielong_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseJielong' ], [ 'relate_id', '=', $jielong_data[ 'jielong_id' ] ] ]);

                $cron->addCron(1, 0, "接龙活动开启", "OpenJielong", $jielong_data[ 'start_time' ], $jielong_data[ 'jielong_id' ]);
                $cron->addCron(1, 0, "接龙活动关闭", "CloseJielong", $jielong_data[ 'end_time' ], $jielong_data[ 'jielong_id' ]);
            }

            model('promotion_jielong')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_jielong')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除接龙活动
     * @param $jielong_id
     * @param $site_id
     * @return array|\multitype
     */
    public function deleteJielong($jielong_id, $site_id)
    {
        //接龙信息
        $jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielong_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if ($jielong_info) {
            if ($jielong_info[ 'status' ] != 1) {
                $res = model('promotion_jielong')->update([ 'is_delete' => 1 ], [ [ 'jielong_id', '=', $jielong_id ], [ 'site_id', '=', $site_id ] ]);
                if ($res) {
//                    model('promotion_jielong_goods')->delete([['jielong_id', '=', $jielong_id], ['site_id', '=', $site_id]]);
                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenJielong' ], [ 'relate_id', '=', $jielong_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'CloseJielong' ], [ 'relate_id', '=', $jielong_id ] ]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '接龙活动进行中,请先关闭该活动');
            }

        } else {
            return $this->error('', '接龙活动不存在');
        }
    }

    /**
     * 关闭接龙活动
     * @param $jielong_id
     * @param $site_id
     * @return array
     */
    public function finishJielong($jielong_id, $site_id)
    {
        //接龙信息
        $jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielong_id ], [ 'site_id', '=', $site_id ] ], 'status,goods_ids');
        if (!empty($jielong_info)) {

            if ($jielong_info[ 'status' ] != 2) {
                $res = model('promotion_jielong')->update([ 'status' => 2, 'status_name' => $this->status[ 2 ] ], [ [ 'jielong_id', '=', $jielong_id ], [ 'site_id', '=', $site_id ], [ 'status', '=', 1 ] ]);
                $cron = new Cron();
                $cron->deleteCron([ [ 'event', '=', 'OpenJielong' ], [ 'relate_id', '=', $jielong_id ] ]);
                $cron->deleteCron([ [ 'event', '=', 'CloseJielong' ], [ 'relate_id', '=', $jielong_id ] ]);

                $goods = new Goods();
                $arr_ids = explode(',', $jielong_info[ 'goods_ids' ]);
                foreach ($arr_ids as $goods_id) {
                    $goods->modifyPromotionAddon($goods_id, [ 'jielong' => $jielong_id ], true);
                }

                return $this->success($res);
            } else {
                $this->error('', '该接龙活动已关闭');
            }
        } else {
            $this->error('', '该接龙活动不存在');
        }
    }

    /**
     * 获取接龙详细信息
     * @param $jielong_id
     * @param $site_id
     * @return array
     */
    public function getJielongDetail($jielong_id, $site_id)
    {
        //接龙信息
        $jielong_info = model("promotion_jielong")->getInfo([ [ 'jielong_id', '=', $jielong_id ], [ 'site_id', '=', $site_id ] ], '*');

        if (!empty($jielong_info)) {
            //商品信息
            $arr_ids = explode(',', $jielong_info[ 'goods_ids' ]);
            $goods_list = model('goods')->getList([ [ 'goods_id', 'in', $arr_ids ] ], 'goods_id,goods_class_name,goods_image,goods_name,goods_stock,is_virtual,price');
            if (!empty($goods_list)) {
                foreach ($goods_list as $k => $v) {
                    $goods_list[ $k ][ 'goods_stock' ] = numberFormat($goods_list[ $k ][ 'goods_stock' ]);
                }
            }
            $jielong_info[ 'sku_list' ] = $goods_list;
            $jielong_info[ 'sku_list_count' ] = count($jielong_info[ 'sku_list' ]);
        }
        return $this->success($jielong_info);
    }

    /**
     * 获取接龙活动分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getJielongPageList($condition, $page, $page_size, $order, $site_id)
    {
        $field = '*';
        $list = model('promotion_jielong')->pageList($condition, $field, $order, $page, $page_size);

        foreach ($list[ 'list' ] as &$v) {
            $v[ 'goods_num' ] = model('promotion_jielong_goods')->getCount([ [ 'jielong_id', '=', $v[ 'jielong_id' ] ], [ 'site_id', '=', $site_id ] ], 'id');
            $v[ 'order_num' ] = model('promotion_jielong_order')->getCount([ [ 'jielong_id', '=', $v[ 'jielong_id' ] ], [ 'site_id', '=', $site_id ] ], 'id');
        }
        return $this->success($list);
    }

    /**
     * 获取社群接龙活动分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getJielongActivityPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'jielong_id desc', $field = '*')
    {
        $list = model('promotion_jielong')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取社群接龙活动详情
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getJielongActivityDetail($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'jielong_id desc', $field = '*', $jielog_id = 0)
    {
        $alias = 'pjg';

        $field = 'pjg.*,g.goods_image,g.goods_name,g.goods_spec_format,g.sku_id,g.goods_stock,gs.price,gs.market_price,gs.discount_price,g.introduction,g.min_buy,g.max_buy,g.is_limit,g.limit_type';
        $join = [
            [ 'goods g', 'pjg.goods_id = g.goods_id', 'inner' ],
            [ 'goods_sku gs', 'g.sku_id = gs.sku_id', 'inner' ]
        ];
        //接龙活动中商品信息
        $list = model('promotion_jielong_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);

        //已抢件数,购买人数
        $order_ids = model('promotion_jielong_order')->getColumn([ [ 'jielong_id', '=', $jielog_id ], [ 'order_status', 'not in', [ 0, -1 ] ] ], 'relate_order_id');

        foreach ($list[ 'list' ] as $k => $v) {
            $sku_ids = model('goods_sku')->getColumn([ [ 'goods_id', '=', $v[ 'goods_id' ] ] ], 'sku_id');
            $where = [
                [ 'order_id', 'in', $order_ids ],
                [ 'sku_id', 'in', $sku_ids ]
            ];
            //已抢件数
            $list[ 'list' ][ $k ][ 'buy_num' ] = intval(model('order_goods')->getSum($where, 'num'));

            $member_ids = model('order_goods')->getColumn($where, 'member_id', '', 'order_goods_id desc');
            $member_ids = array_unique($member_ids);
            //购买会员数
            $list[ 'list' ][ $k ][ 'member_num' ] = count($member_ids);

            //已购买会员头像,取3个
            $new_ids = array_slice($member_ids, 0, 3);
            $head = [];
            foreach ($new_ids as $kk => $vv) {
                $member_info = model('member')->getInfo([ [ 'member_id', '=', $vv ] ], 'headimg');
                $head[] = $member_info[ 'headimg' ];
            }
            $list[ 'list' ][ $k ][ 'member_headimg' ] = $head;
        }

        //接龙活动信息
        $list[ 'info' ] = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielog_id ] ], '*');
        return $this->success($list);
    }

    /**
     * 获取社群接龙商品加车数量
     */
    public function getCartNum($jielong_id, $goods_id, $member_id, $site_id)
    {

        $sku_ids = model('goods_sku')->getColumn([ [ 'goods_id', '=', $goods_id ] ], 'sku_id');

        $where = [
            [ 'sku_id', 'in', $sku_ids ],
            [ 'jielong_id', '=', $jielong_id ],
            [ 'member_id', '=', $member_id ],
            [ 'site_id', '=', $site_id ]
        ];
        $num = model('promotion_jielong_cart')->getSum($where, 'num');

        return intval($num);
    }

    /**
     * 获取社群接龙购买分页列表
     */
    public function getJielongBuyPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS)
    {
        $alias = 'o';
        $join = [
            [ 'promotion_jielong_order pjo', 'o.order_id = pjo.relate_order_id', 'inner' ],
            [ 'member m', 'o.member_id = m.member_id', 'inner' ]
        ];
        $order = 'o.pay_time desc';
        $field = 'pjo.relate_order_id,o.member_id,o.order_name,o.pay_time,m.headimg,m.nickname';
        $list = model('order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);

    }

    /**
     * 开启接龙活动
     * @param $jielong_id
     * @return array|\multitype
     */
    public function cronOpenJielong($jielong_id)
    {
        $jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielong_id ] ], 'status,goods_ids');
        if (!empty($jielong_info)) {

            if ($jielong_info[ 'status' ] == 0) {
                $res = model('promotion_jielong')->update([ 'status' => 1, 'status_name' => $this->status[ 1 ] ], [ [ 'jielong_id', '=', $jielong_id ] ]);

                $goods = new Goods();
                $arr_ids = explode(',', $jielong_info[ 'goods_ids' ]);
                foreach ($arr_ids as $goods_id) {
                    $goods->modifyPromotionAddon($goods_id, [ 'jielong' => $jielong_id ]);
                }

                return $this->success($res);
            } else {
                return $this->error("", "接龙活动已开启或者关闭");
            }

        } else {
            return $this->error("", "接龙活动不存在");
        }

    }

    /**
     * 关闭接龙活动
     * @param $jielong_id
     * @return array|\multitype
     */
    public function cronCloseJielong($jielong_id)
    {
        $jielong_info = model('promotion_jielong')->getInfo([ [ 'jielong_id', '=', $jielong_id ] ], 'status,goods_ids');
        if (!empty($jielong_info)) {

            if ($jielong_info[ 'status' ] == 1) {
                $res = model('promotion_jielong')->update([ 'status' => 2, 'status_name' => $this->status[ 2 ] ], [ [ 'jielong_id', '=', $jielong_id ] ]);

                $goods = new Goods();
                $arr_ids = explode(',', $jielong_info[ 'goods_ids' ]);
                foreach ($arr_ids as $goods_id) {
                    $goods->modifyPromotionAddon($goods_id, [ 'jielong' => $jielong_id ], true);
                }

                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "接龙活动不存在");
        }
    }

}