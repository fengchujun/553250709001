<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\model;

use app\dict\order_refund\OrderRefundDict;
use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\order\Order;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use think\facade\Cache;
use think\facade\Db;


/**
 * 限时秒杀(时段)
 */
class Seckill extends BaseModel
{
    /**
     * 添加秒杀时段
     * @param $data
     * @return array
     */
    public function addSeckillTime($data)
    {
        if ($data[ 'seckill_start_time' ] > $data[ 'seckill_end_time' ]) {
            return $this->error('', '秒杀时间段设置错误');
        }

        //时间段检测
        $seckill_count = model('promotion_seckill_time')->getCount([
            ['seckill_start_time|seckill_end_time', 'between', [$data[ 'seckill_start_time' ], $data[ 'seckill_end_time' ]]],
            ['site_id', '=', $data[ 'site_id' ]]
        ]);
        if ($seckill_count > 0) {
            return $this->error('', '秒杀场次设置冲突');
        }
        $seckill_count = model('promotion_seckill_time')->getCount([
            ['seckill_start_time', '<=', $data[ 'seckill_start_time' ]],
            ['seckill_end_time', '>=', $data[ 'seckill_end_time' ]],
            ['site_id', '=', $data[ 'site_id' ]]
        ]);
        if ($seckill_count > 0) {
            return $this->error('', '秒杀场次设置冲突');
        }
        //添加数据
        $data[ 'create_time' ] = time();
        $seckill_id = model('promotion_seckill_time')->add($data);
        Cache::tag("promotion_seckill_time")->clear();
        return $this->success($seckill_id);
    }

    /**
     * 修改秒杀时段
     * @param $data
     * @param $site_id
     * @return array
     */
    public function editSeckillTime($data, $site_id)
    {
        if ($data[ 'seckill_start_time' ] > $data[ 'seckill_end_time' ]) {
            return $this->error('', '秒杀时间段设置错误');
        }
        //时间段检测
        $seckill_count = model('promotion_seckill_time')->getCount([
            ['seckill_start_time|seckill_end_time', 'between', [$data[ 'seckill_start_time' ], $data[ 'seckill_end_time' ]]],
            ['site_id', '=', $site_id],
            ['id', '<>', $data[ 'id' ]]
        ]);
        if ($seckill_count > 0) {
            return $this->error('', '秒杀场次设置冲突');
        }
        $seckill_count = model('promotion_seckill_time')->getCount([
            ['seckill_start_time', '<=', $data[ 'seckill_start_time' ]],
            ['seckill_end_time', '>=', $data[ 'seckill_end_time' ]],
            ['site_id', '=', $site_id],
            ['id', '<>', $data[ 'id' ]]
        ]);
        if ($seckill_count > 0) {
            return $this->error('', '秒杀场次设置冲突');
        }

        //更新数据
        $data[ 'modify_time' ] = time();
        $res = model('promotion_seckill_time')->update($data, [['id', '=', $data[ 'id' ]]]);
        Cache::tag("promotion_seckill_time")->clear();
        return $this->success($res);
    }

    /**
     * 删除秒杀时段
     * @param $seckill_time_id
     * @return array
     */
    public function deleteSeckillTime($seckill_time_id)
    {
        $res = model('promotion_seckill_time')->delete([['id', '=', $seckill_time_id]]);
        if ($res) {

            $goods = new Goods();

            $condition = [
                ['seckill_time_id', 'like', '%,' . $seckill_time_id . ',%']
            ];
            $seckill_list = model('promotion_seckill')->getList($condition, 'id,seckill_time_id,goods_id');

            foreach ($seckill_list as $k => $v) {

                $time_ids = explode(',', trim($v[ 'seckill_time_id' ], ','));
                unset($time_ids[ array_search($seckill_time_id, $time_ids) ]);

                if (empty($time_ids)) {
                    $goods->modifyPromotionAddon($v[ 'goods_id' ], ['seckill' => $v[ 'id' ]], true);
                    model('promotion_seckill_goods')->delete([['seckill_id', '=', $v[ 'id' ]]]);
                    model('promotion_seckill')->delete([['id', '=', $v[ 'id' ]]]);
                } else {

                    $time_id = ',' . implode(',', $time_ids) . ',';
                    model('promotion_seckill')->update(['seckill_time_id' => $time_id], [['id', '=', $v[ 'id' ]]]);
                    model('promotion_seckill_goods')->update(['seckill_time_id' => $time_id], [['seckill_id', '=', $v[ 'id' ]]]);
                }

            }
        }
        Cache::tag("promotion_seckill_time")->clear();
        return $this->success($res);
    }

    /**
     * 获取秒杀时段信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getSeckillTimeInfo($condition, $field = '*')
    {
        $res = model('promotion_seckill_time')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取秒杀时段列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getSeckillTimeList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_seckill_time')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取有商品的秒杀时段列表
     * @param $condition
     * @param $field
     * @param string $order
     * @return array
     */
    public function getGoodsSeckillTimeList($condition, $field, $order = 'seckill_start_time asc')
    {
        if (empty($field)) {
            $field = 'id, site_id, name, seckill_start_time, seckill_end_time';
        }

        $seckill_time = model('promotion_seckill_time')->getList($condition, $field, $order);
        foreach ($seckill_time as $k => $v) {
            $condition = [
                ['seckill_time_id', 'like', '%,' . $v[ 'id' ] . ',%'],
                ['status', '=', 1],
                ['g.goods_state', '=', 1],
                ['g.is_delete', '=', 0]
            ];
            $join = [
                ['goods g', 'g.goods_id = psg.goods_id', 'inner']
            ];
            $goods = model('promotion_seckill_goods')->getInfo($condition, 'id', 'psg', $join);
            if (empty($goods)) unset($seckill_time[ $k ]);
        }
        return $this->success($seckill_time);
    }

    /**
     * 转换秒杀时间
     * @param $info
     * @return mixed
     */
    public function transformSeckillTime($info)
    {
        $info[ 'start_hour' ] = floor($info[ 'seckill_start_time' ] / 3600);
        $info[ 'start_minute' ] = floor(($info[ 'seckill_start_time' ] % 3600) / 60);
        $info[ 'start_second' ] = $info[ 'seckill_start_time' ] % 60;

        $info[ 'end_hour' ] = floor($info[ 'seckill_end_time' ] / 3600);
        $info[ 'end_minute' ] = floor(($info[ 'seckill_end_time' ] % 3600) / 60);
        $info[ 'end_second' ] = $info[ 'seckill_end_time' ] % 60;

        if ($info[ 'start_hour' ] < 10) $info[ 'start_hour' ] = '0' . $info[ 'start_hour' ];
        if ($info[ 'start_minute' ] < 10) $info[ 'start_minute' ] = '0' . $info[ 'start_minute' ];
        if ($info[ 'start_second' ] < 10) $info[ 'start_second' ] = '0' . $info[ 'start_second' ];

        if ($info[ 'end_hour' ] < 10) $info[ 'end_hour' ] = '0' . $info[ 'end_hour' ];
        if ($info[ 'end_minute' ] < 10) $info[ 'end_minute' ] = '0' . $info[ 'end_minute' ];
        if ($info[ 'end_second' ] < 10) $info[ 'end_second' ] = '0' . $info[ 'end_second' ];

        return $info;
    }

    /******************************************************秒杀商品*********************************************************************/
    /**
     * 添加秒杀商品
     * @param $data
     * @return array
     */
    public function addSeckillGoods($data)
    {
        $cron = new Cron();
        $goods_data = $data[ 'goods_data' ];

        if (empty($data[ 'seckill_time_id' ])) {
            return $this->error('', '请选择秒杀时段');
        }
        $seckill_time_id = explode(',', $data[ 'seckill_time_id' ]);

        //时间段检测
        foreach ($seckill_time_id as $v) {
            $seckill_count = model('promotion_seckill')->getCount([
                ['goods_id', 'in', $data[ 'goods_ids' ]],
                ['status', 'in', '0,1'],
                ['seckill_time_id', 'like', '%,' . $v . ',%'],
                ['site_id', '=', $data[ 'site_id' ]],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))')]//todo  修正  所有的优惠都要一样
            ]);
            if ($seckill_count > 0) {
                return $this->error('', '有商品已设置秒杀，请不要重复设置');
            }
        }

        model('promotion_seckill')->startTrans();
        try {
            $seckill_data = [
                'site_id' => $data[ 'site_id' ],
                'seckill_name' => $data[ 'seckill_name' ],
                'remark' => $data[ 'remark' ],
                'seckill_time_id' => ',' . $data[ 'seckill_time_id' ] . ',',
                'start_time' => $data[ 'start_time' ],
                'end_time' => $data[ 'end_time' ],
                'create_time' => time(),
                'sort' => $data[ 'sort' ],
            ];

            $goods = new Goods();
            $add_goods_data = [];
            foreach ($goods_data as $k => $v) {
                $seckill_data[ 'goods_id' ] = $v[ 'goods_id' ];
                $seckill_data[ 'goods_name' ] = $v[ 'sku_list' ][ 0 ][ 'sku_name' ];
                $seckill_data[ 'goods_image' ] = $v[ 'sku_list' ][ 0 ][ 'sku_image' ];
                $seckill_data[ 'seckill_price' ] = $v[ 'sku_list' ][ 0 ][ 'seckill_price' ];

                if ($data[ 'start_time' ] <= time()) {
                    $seckill_data[ 'status' ] = 1;//直接启动
                    $seckill_id = model('promotion_seckill')->add($seckill_data);
                    $goods->modifyPromotionAddon($v[ 'goods_id' ], ['seckill' => $seckill_id]);
                    $cron->addCron(1, 0, "秒杀关闭", "CloseSeckill", $data[ 'end_time' ], $seckill_id);
                } else {
                    $seckill_data[ 'status' ] = 0;
                    $seckill_id = model('promotion_seckill')->add($seckill_data);
                    $cron->addCron(1, 0, "秒杀开启", "OpenSeckill", $data[ 'start_time' ], $seckill_id);
                    $cron->addCron(1, 0, "秒杀关闭", "CloseSeckill", $data[ 'end_time' ], $seckill_id);
                }

                $goods_stock = 0; // 秒杀商品总库存

                foreach ($v[ 'sku_list' ] as $key => $item) {
                    $sku_info = model('goods_sku')->getInfo([['sku_id', '=', $item[ 'sku_id' ]]], 'goods_id, sku_id, sku_name,price,sku_image');
                    $add_goods_data[] = [
                        'site_id' => $data[ 'site_id' ],
                        'seckill_id' => $seckill_id,
                        'seckill_time_id' => ',' . $data[ 'seckill_time_id' ] . ',',
                        'sku_id' => $item[ 'sku_id' ],
                        'goods_id' => $item[ 'goods_id' ],
                        'sku_image' => $sku_info[ 'sku_image' ],
                        'sku_name' => $sku_info[ 'sku_name' ],
                        'seckill_price' => $item[ 'seckill_price' ],
                        'price' => $sku_info[ 'price' ],
                        'stock' => $item[ 'seckill_stock' ],
                        'max_buy' => $item[ 'max_buy' ],
                        'status' => $seckill_data[ 'status' ]
                    ];
                    $goods_stock += $item[ 'seckill_stock' ];
                }
                model('promotion_seckill')->update(['goods_stock' => $goods_stock], [['id', '=', $seckill_id]]);
            }
            model('promotion_seckill_goods')->addList($add_goods_data);
            foreach ($seckill_time_id as $v) {
                $count = model('promotion_seckill_goods')->getCount([['seckill_time_id', 'like', '%,' . $v . ',%']], '*', 'a', '', 'goods_id');
                model('promotion_seckill_time')->update(['goods_num' => $count], [['id', '=', $v]]);
            }
            model('promotion_seckill')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_seckill')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 修改秒杀商品
     * @param $data
     * @return array
     */
    public function editSeckillGoods($data)
    {
        $cron = new Cron();

        if (empty($data[ 'seckill_time_id' ])) {
            return $this->error('', '请选择秒杀时段');
        }
        $seckill_time_id = explode(',', $data[ 'seckill_time_id' ]);

        //时间段检测
        foreach ($seckill_time_id as $v) {
            $seckill_count = model('promotion_seckill')->getInfo([
                ['goods_id', 'in', $data[ 'goods_ids' ]],
                ['status', 'in', '0,1'],
                ['site_id', '=', $data[ 'site_id' ]],
                ['seckill_time_id', 'like', '%,' . $v . ',%'],
                ['id', '<>', $data[ 'id' ]],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))')]//todo  修正  所有的优惠都要一样
            ]);
            if ($seckill_count > 0) {
                return $this->error('', '有商品已设置秒杀，请不要重复设置');
            }
        }

        model('promotion_seckill')->startTrans();
        try {
            $seckill_data = [
                'site_id' => $data[ 'site_id' ],
                'seckill_name' => $data[ 'seckill_name' ],
                'remark' => $data[ 'remark' ],
                'seckill_time_id' => ',' . $data[ 'seckill_time_id' ] . ',',
                'start_time' => $data[ 'start_time' ],
                'end_time' => $data[ 'end_time' ],
                'modify_time' => time(),
                'sort' => $data[ 'sort' ],
            ];

            $goods = new Goods();
            $add_goods_data = [];
            $seckill_data[ 'goods_id' ] = $data[ 'sku_list' ][ 0 ][ 'goods_id' ];
            $seckill_data[ 'goods_name' ] = $data[ 'sku_list' ][ 0 ][ 'sku_name' ];
            $seckill_data[ 'goods_image' ] = $data[ 'sku_list' ][ 0 ][ 'sku_image' ];
            $seckill_data[ 'seckill_price' ] = $data[ 'sku_list' ][ 0 ][ 'seckill_price' ];

            $seckill_id = $data[ 'id' ];

            if ($data[ 'start_time' ] <= time()) {
                $seckill_data[ 'status' ] = 1;//直接启动
                model('promotion_seckill')->update($seckill_data, [['id', '=', $seckill_id]]);
                $goods->modifyPromotionAddon($data[ 'sku_list' ][ 0 ][ 'goods_id' ], ['seckill' => $seckill_id]);
                $cron->addCron(1, 0, "秒杀关闭", "CloseSeckill", $data[ 'end_time' ], $seckill_id);
            } else {
                $seckill_data[ 'status' ] = 0;
                model('promotion_seckill')->update($seckill_data, [['id', '=', $seckill_id]]);
                $cron->addCron(1, 0, "秒杀开启", "OpenSeckill", $data[ 'start_time' ], $seckill_id);
                $cron->addCron(1, 0, "秒杀关闭", "CloseSeckill", $data[ 'end_time' ], $seckill_id);
            }

            model('promotion_seckill_goods')->delete([['seckill_id', '=', $seckill_id]]);

            $goods_stock = 0; // 秒杀商品总库存

            foreach ($data[ 'sku_list' ] as $key => $item) {
                $sku_info = model('goods_sku')->getInfo([['sku_id', '=', $item[ 'sku_id' ]]], 'goods_id, sku_id, sku_name,price,sku_image');
                $add_goods_data[] = [
                    'site_id' => $data[ 'site_id' ],
                    'seckill_id' => $seckill_id,
                    'seckill_time_id' => ',' . $data[ 'seckill_time_id' ] . ',',
                    'sku_id' => $item[ 'sku_id' ],
                    'goods_id' => $item[ 'goods_id' ],
                    'sku_image' => $sku_info[ 'sku_image' ],
                    'sku_name' => $sku_info[ 'sku_name' ],
                    'seckill_price' => $item[ 'seckill_price' ],
                    'price' => $sku_info[ 'price' ],
                    'stock' => $item[ 'seckill_stock' ],
                    'max_buy' => $item[ 'max_buy' ],
                    'status' => $seckill_data[ 'status' ]
                ];
                $goods_stock += $item[ 'seckill_stock' ];
            }
            model('promotion_seckill')->update(['goods_stock' => $goods_stock], [['id', '=', $seckill_id]]);
            model('promotion_seckill_goods')->addList($add_goods_data);

            foreach ($seckill_time_id as $v) {
                $count = model('promotion_seckill_goods')->getCount([['seckill_time_id', 'like', '%,' . $v . ',%']], '*', 'a', '', 'goods_id');
                model('promotion_seckill_time')->update(['goods_num' => $count], [['id', '=', $v]]);
            }
            model('promotion_seckill')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_seckill')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    public function seckillSort($id, $sort)
    {
        $res = model('promotion_seckill')->update(['sort' => $sort], [['id', '=', $id]]);
        return $this->success($res);
    }

    /**
     * 秒杀商品详情
     * @param array $condition
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getSeckillDetail($condition = [], $field = '*', $alias = '', $join = [])
    {
        $info = model("promotion_seckill")->getInfo($condition, $field, $alias, $join);

        $goods_sku = model('goods_sku')->getList([['goods_id', '=', $info[ 'goods_id' ]], ['is_delete', '=', 0], ['goods_state', '=', 1]], 'stock as goods_stock, goods_id, sku_id, sku_name,price,sku_image,stock');

        $discount_goods = model("promotion_seckill_goods")->getList([['goods_id', '=', $info[ 'goods_id' ]], ['seckill_id', '=', $info[ 'id' ]]], '*');

        foreach ($goods_sku as $k => $v) {
            $goods_sku[ $k ][ 'is_select' ] = 0;
            $goods_sku[ $k ][ 'seckill_price' ] = $v[ 'price' ];
            $goods_sku[ $k ][ 'seckill_stock' ] = 0;
            $goods_sku[ $k ][ 'max_buy' ] = 0;
            $goods_sku[ $k ][ 'stock' ] = numberFormat($goods_sku[ $k ][ 'stock' ]);
            $goods_sku[ $k ][ 'goods_stock' ] = numberFormat($goods_sku[ $k ][ 'goods_stock' ]);
            foreach ($discount_goods as $key => $val) {
                if ($val[ 'sku_id' ] == $v[ 'sku_id' ]) {
                    $goods_sku[ $k ][ 'is_select' ] = 1;
                    $goods_sku[ $k ][ 'seckill_price' ] = $val[ 'seckill_price' ];
                    $goods_sku[ $k ][ 'seckill_stock' ] = $val[ 'stock' ];
                    $goods_sku[ $k ][ 'max_buy' ] = $val[ 'max_buy' ];
                }
            }
        }

        $info[ 'goods_sku' ] = $goods_sku;
        $info[ 'seckill_goods' ] = $discount_goods;

        return $this->success($info);
    }

    /**
     * 修改秒杀商品限购数量
     * @param $seckill_id
     * @param $site_id
     * @param $sku_id
     * @param $max_buy
     * @return array
     */
    public function editSeckillGoodsNum($seckill_id, $site_id, $sku_id, $max_buy)
    {
        $data = [
            'seckill_id' => $seckill_id,
            'site_id' => $site_id,
            'sku_id' => $sku_id,
            'max_buy' => $max_buy
        ];
        model("promotion_seckill_goods")->update($data, [['seckill_id', '=', $seckill_id], ['sku_id', '=', $sku_id], ['site_id', '=', $site_id]]);
        return $this->success();
    }

    /**
     * 删除秒杀商品
     * @param $seckill_id
     * @param $site_id
     * @return array
     */
    public function deleteSeckillGoods($seckill_id, $site_id)
    {
        $info = model("promotion_seckill")->getInfo([['id', '=', $seckill_id]], 'goods_id,seckill_time_id,status');

        if (empty($info)) return $this->error('', '该秒杀活动不存在');
        if ($info[ 'status' ] == 1) return $this->error('', '该秒杀活动正在进行');

        $goods = new Goods();
        $goods->modifyPromotionAddon($info[ 'goods_id' ], ['seckill' => $seckill_id], true);
        model("promotion_seckill")->delete([['id', '=', $seckill_id], ['site_id', '=', $site_id]]);
        $goods_num = model("promotion_seckill_goods")->delete([['seckill_id', '=', $seckill_id], ['site_id', '=', $site_id]]);
        $seckill_time_id = explode(',', $info[ 'seckill_time_id' ]);
        foreach ($seckill_time_id as $v) {
            $count = model('promotion_seckill_goods')->getCount([['seckill_time_id', 'like', '%,' . $v . ',%']], '*', 'a', '', 'goods_id');
            model('promotion_seckill_time')->update(['goods_num' => $count], [['id', '=', $v]]);
        }
        return $this->success();
    }

    /**
     * 获取秒杀详情
     * @param $seckill_id
     * @return array
     */
    public function getSeckillInfo($seckill_id)
    {
        $seckill_info = model('promotion_seckill')->getInfo([['id', '=', $seckill_id]]);
        if (empty($seckill_info)) {
            return $this->success([]);
        }
        $seckill_info[ 'seckill_start_time' ] = 0;
        $seckill_info[ 'seckill_end_time' ] = 0;
        $today_time = strtotime(date("Y-m-d"), time());
        $time = time() - $today_time;//当日时间戳

        $seckill_time_id = trim($seckill_info[ 'seckill_time_id' ], ',');
        $condition = [
            ['id', 'in', $seckill_time_id],
            ['seckill_start_time', '<=', $time],
            ['seckill_end_time', '>', $time],
        ];
        $time_list = model('promotion_seckill_time')->getList($condition, 'seckill_start_time, seckill_end_time', 'seckill_start_time asc', '', '', '', 1);
        if (count($time_list) > 0) {
            $seckill_info[ 'seckill_start_time' ] = $time_list[ 0 ][ 'seckill_start_time' ];
            $seckill_info[ 'seckill_end_time' ] = $time_list[ 0 ][ 'seckill_end_time' ];
        }

        //所有秒杀时间段
        $seckill_time_id = trim($seckill_info[ 'seckill_time_id' ], ',');
        $condition = [
            ['id', 'in', $seckill_time_id],
        ];
        $time_list = model('promotion_seckill_time')->getList($condition, 'seckill_start_time, seckill_end_time', 'seckill_start_time asc');
        $seckill_info['time_list'] = $time_list;

        return $this->success($seckill_info);
    }

    /**
     * 获取秒杀列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return mixed
     */
    public function getSeckillPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '', $alias = '', $join = [])
    {
        $list = model('promotion_seckill')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取秒杀商品列表
     * @param $seckill_time_id
     * @param $site_id
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getSeckillGoodsPageList($seckill_time_id, $site_id, $page = 1, $page_size = PAGE_LIST_ROWS, $seckill_time_type = 'today')
    {
        //判断时间段
        $seckill_time_info = model('promotion_seckill_time')->getInfo([['id', '=', $seckill_time_id]]);
        $day_start_time = strtotime(date('Y-m-d'));
        if($seckill_time_type == 'tomorrow'){
            $day_start_time += 3600*24;
        }
        $start_time = $day_start_time + $seckill_time_info['seckill_start_time'];
        $end_time = $day_start_time + $seckill_time_info['seckill_end_time'];

        $alias = 'ps';
        $join = [
            ['goods g', 'ps.goods_id = g.goods_id', 'inner'],
            ['promotion_seckill_goods psg', 'psg.seckill_id = ps.id', 'inner'],
            ['goods_sku gs', 'psg.sku_id = gs.sku_id', 'inner'],
        ];
        $condition = [
            ['ps.seckill_time_id', 'like', '%,' . $seckill_time_id . ',%'],
            ['ps.status', '=', 1],
            ['ps.start_time', '<=', $start_time],
            ['ps.end_time', '>=', $end_time],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0],
            ['ps.site_id', '=', $site_id],
        ];
        //展示库存要和实际库存做比较，取二者的较小值
        $sum_field = 'sum(IF(psg.stock < gs.stock, psg.stock, gs.stock))';
        $goods_stock_field = 'IF(ps.goods_stock < '.$sum_field.', ps.goods_stock, '.$sum_field.') as goods_stock';
        $field = 'ps.id,ps.site_id,ps.seckill_name,ps.status,ps.remark,ps.start_time,ps.end_time,'.$goods_stock_field.',
        ps.goods_id,ps.seckill_time_id,ps.seckill_price,ps.sale_num,
        g.goods_name,g.goods_image,g.price';
        $order = 'ps.sort asc,g.goods_id desc';
        $group = 'ps.id';
        $res = model('promotion_seckill')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        foreach ($res[ 'list' ] as $k => $v) {
            $res[ 'list' ][ $k ][ 'goods_stock' ] = numberFormat($res[ 'list' ][ $k ][ 'goods_stock' ]);
        }
        return $this->success($res);
    }

    /**
     * 获取秒杀商品列表
     * @param $seckill_time_id
     * @param $site_id
     * @param null $limit
     * @return array
     */
    public function getSeckillList($seckill_time_id, $site_id, $limit = null)
    {
        $alias = 'ps';
        $join = [
            ['goods g', 'ps.goods_id = g.goods_id', 'inner'],
            ['promotion_seckill_goods psg', 'psg.seckill_id = ps.id', 'inner'],
            ['goods_sku gs', 'psg.sku_id = gs.sku_id', 'inner'],
        ];
        $condition = [
            ['ps.seckill_time_id', 'like', '%,' . $seckill_time_id . ',%'],
            ['ps.status', '=', 1],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0],
            ['ps.site_id', '=', $site_id]
        ];
        //展示库存要和实际库存做比较，取二者的较小值
        $sum_field = 'sum(IF(psg.stock < gs.stock, psg.stock, gs.stock))';
        $goods_stock_field = 'IF(ps.goods_stock < '.$sum_field.', ps.goods_stock, '.$sum_field.') as goods_stock';
        $field = 'ps.id,ps.site_id,ps.seckill_name,ps.status,ps.remark,ps.start_time,ps.end_time,'.$goods_stock_field.',
        ps.goods_id,ps.seckill_time_id,ps.seckill_price,ps.sale_num,ps.goods_stock,
        g.goods_name,g.goods_image,g.price';
        $order = 'ps.sort asc';
        $group = 'ps.id';
        $list = model('promotion_seckill')->getList($condition, $field, $order, $alias, $join, $group, $limit);
        foreach ($list as $k => $v) {
            $list[ $k ][ 'goods_stock' ] = numberFormat($list[ $k ][ 'goods_stock' ]);
        }
        return $this->success($list);
    }

    /**
     * 启动秒杀事件
     * @param $seckill_id
     * @return array
     */
    public function cronOpenSeckill($seckill_id)
    {
        $seckill_info = model('promotion_seckill')->getInfo([['id', '=', $seckill_id]], 'start_time,status, goods_id');
        if (!empty($seckill_info)) {
            if ($seckill_info[ 'start_time' ] <= time() && $seckill_info[ 'status' ] == 0) {
                model('promotion_seckill')->update(['status' => 1], [['id', '=', $seckill_id]]);
                model('promotion_seckill_goods')->update(['status' => 1], [['seckill_id', '=', $seckill_id]]);
                $goods = new Goods();
                $goods->modifyPromotionAddon($seckill_info[ 'goods_id' ], ['seckill' => $seckill_id]);
                return $this->success(1);
            } else {
                return $this->error("", "秒杀已开启或者关闭");
            }
        } else {
            return $this->error("", "秒杀不存在");
        }
    }

    /**
     * 结束秒杀事件
     * @param $seckill_id
     * @return array
     */
    public function cronCloseSeckill($seckill_id)
    {
        $seckill_info = model('promotion_seckill')->getInfo([['id', '=', $seckill_id]], 'start_time,status, goods_id');
        if (!empty($seckill_info)) {
            //针对正在进行的活动
            if ($seckill_info[ 'status' ] == 1) {

                model('promotion_seckill')->update(['status' => 2], [['id', '=', $seckill_id]]);
                model('promotion_seckill_goods')->update(['status' => 2], [['seckill_id', '=', $seckill_id]]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($seckill_info[ 'goods_id' ], ['seckill' => $seckill_id], true);

                return $this->success();
            } else {
                return $this->error("", "正在进行的秒杀才能进行关闭操作");
            }
        } else {
            return $this->error("", "活动不存在");
        }
    }

    /**
     * 手动结束秒杀事件
     * @param $seckill_id
     * @return array
     */
    public function closeSeckill($seckill_id)
    {
        $seckill_info = model('promotion_seckill')->getInfo([['id', '=', $seckill_id]], 'start_time,status, goods_id');
        if (!empty($seckill_info)) {
            //针对正在进行的活动
            if ($seckill_info[ 'status' ] == 1) {

                model('promotion_seckill')->update(['status' => -1], [['id', '=', $seckill_id]]);
                model('promotion_seckill_goods')->update(['status' => -1], [['seckill_id', '=', $seckill_id]]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($seckill_info[ 'goods_id' ], ['seckill' => $seckill_id], true);

                return $this->success();
            } else {
                return $this->error("", "正在进行的秒杀才能进行关闭操作");
            }
        } else {
            return $this->error("", "活动不存在");
        }
    }

    /**
     * 获取商品列表
     * @param $seckill_id
     * @return array
     */
    public function getSeckillGoodsList($seckill_id)
    {
        $field = 'psg.*,sku.sku_name,sku.price,sku.sku_image';
        $alias = 'psg';
        $join = [
            [
                'goods g',
                'g.goods_id = psg.goods_id',
                'inner'
            ],
            [
                'goods_sku sku',
                'sku.sku_id = psg.sku_id',
                'inner'
            ]
        ];
        $condition = [
            ['psg.seckill_id', '=', $seckill_id],
            ['g.is_delete', '=', 0], ['g.goods_state', '=', 1]
        ];
        $list = model('promotion_seckill_goods')->getList($condition, $field, '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取秒杀商品信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSeckillGoodsInfo($condition = [], $field = '')
    {
        if (empty($field)) {
            //展示库存要和实际库存做比较，取二者的较小值
            $stock_field = 'IF(psg.stock < sku.stock, psg.stock, sku.stock) as stock';
            $field = 'psg.id,psg.seckill_id,ps.goods_id,psg.sku_id,psg.seckill_price,psg.max_buy,'.$stock_field.',
            ps.status,ps.start_time,ps.end_time,(ps.sale_num + g.virtual_sale) as sale_num,ps.remark,
            sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,
            sku.click_num,sku.collect_num,sku.sku_image,sku.sku_images,
            sku.goods_content,sku.goods_state,sku.is_virtual,sku.is_free_shipping,
            sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,
            sku.video_url,g.evaluate,sku.goods_service_ids,sku.support_trade_type,g.goods_image,
            ps.goods_stock,g.goods_name,sku.qr_id,g.stock_show,g.sale_show,g.label_name,g.category_id,g.sale_store';
        }
        $join = [
            ['goods_sku sku', 'psg.sku_id = sku.sku_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
            ['promotion_seckill ps', 'ps.id = psg.seckill_id', 'inner'],
        ];
        $seckill_goods_info = model('promotion_seckill_goods')->getInfo($condition, $field, 'psg', $join);
        if (!empty($seckill_goods_info)) {
            if (isset($seckill_goods_info[ 'sale_num' ])) {
                $seckill_goods_info[ 'sale_num' ] = numberFormat($seckill_goods_info[ 'sale_num' ]);
            }
            if (isset($seckill_goods_info[ 'goods_stock' ])) {
                $seckill_goods_info[ 'goods_stock' ] = numberFormat($seckill_goods_info[ 'goods_stock' ]);
            }
            if (isset($seckill_goods_info[ 'stock' ])) {
                $seckill_goods_info[ 'stock' ] = numberFormat($seckill_goods_info[ 'stock' ]);
            }
        }
        return $this->success($seckill_goods_info);
    }

    /**
     * 获取秒杀商品信息
     * @param array $condition
     * @return array
     */
    public function getSeckillGoodsSkuList($condition = [])
    {
        //展示库存要和实际库存做比较，取二者的较小值
        $stock_field = 'IF(psg.stock < sku.stock, psg.stock, sku.stock) as stock';
        $field = 'psg.id,psg.seckill_id,ps.goods_id,psg.sku_id,psg.seckill_price,psg.max_buy,'.$stock_field.',
        ps.status,ps.start_time,ps.end_time,ps.sale_num,sku.sku_name,sku.sku_spec_format,sku.price,sku.sku_image,sku.sku_images,sku.goods_spec_format,g.goods_image,ps.goods_stock';
        $join = [
            ['goods_sku sku', 'psg.sku_id = sku.sku_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
            ['promotion_seckill ps', 'ps.id = psg.seckill_id', 'inner'],
        ];
        $list = model('promotion_seckill_goods')->getList($condition, $field, 'psg.id asc', 'psg', $join);
        foreach ($list as $k => $v) {
            $list[ $k ][ 'goods_stock' ] = numberFormat($list[ $k ][ 'goods_stock' ]);
            $list[ $k ][ 'stock' ] = numberFormat($list[ $k ][ 'stock' ]);
        }
        return $this->success($list);
    }

    /**
     * 判断规格值是否禁用
     * @param $seckill_id
     * @param $site_id
     * @param string $goods_spec_format
     * @return int|mixed
     */
    public function getGoodsSpecFormat($seckill_id, $site_id, $goods_spec_format = '')
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_seckill_goods')->getColumn([['seckill_id', '=', $seckill_id], ['site_id', '=', $site_id]], 'sku_id');
        $goods_model = new Goods();
        $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        return $res;
    }

    /**
     * 生成砍价二维码
     * @param $seckill_id
     * @param string $name
     * @param string $type 类型 create创建 get获取
     * @return mixed|array
     */
    public function qrcode($seckill_id, $name, $site_id, $type = 'create')
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                "seckill_id" => $seckill_id
            ],
            'page' => '/pages_promotion/seckill/detail',
            'qrcode_path' => 'upload/qrcode/seckill',
            'qrcode_name' => "seckill_qrcode_" . $seckill_id
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ($k) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[ $k ][ 'status' ] = 1;
                    $path[ $k ][ 'url' ] = $wap_domain . $data[ 'page' ] . '?id=' . $seckill_id;
                    $path[ $k ][ 'img' ] = "upload/qrcode/seckill/seckill_qrcode_" . $seckill_id . "_" . $k . ".png";
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WEAPP_CONFIG']]);
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
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WECHAT_CONFIG']]);
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

    public function urlQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => $promotion_type,
            'app_type' => $app_type,
            'h5_path' => $page . '?id=' . $qrcode_param[ 'id' ],
            'qrcode_path' => 'upload/qrcode/seckill',
            'qrcode_name' => 'seckill_qrcode_' . $promotion_type . '_' . $qrcode_param[ 'id' ] . '_' . $site_id,
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }

    /**
     * 获取会员已购该商品数
     * @param $goods_id
     * @param $member_id
     * @return float
     */
    public function getGoodsPurchasedNum($sku_id, $member_id)
    {
        $join = [
            ['order o', 'o.order_id = og.order_id', 'left']
        ];
        return model('order_goods')->getSum([
            ['og.member_id', '=', $member_id],
            ['og.sku_id', '=', $sku_id],
            ['o.order_status', '<>', Order::ORDER_CLOSE],
            ['o.promotion_type', '=', 'seckill'],
            ['og.refund_status', '<>', OrderRefundDict::REFUND_COMPLETE]
        ], 'og.num', 'og', $join);
    }

}