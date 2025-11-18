<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use think\facade\Cache;
use think\facade\Db;

/**
 * 团购活动
 */
class Groupbuy extends BaseModel
{
    /**
     * 添加团购
     * @param $groupbuy_data
     * @param $goods_list
     * @param $goods_ids
     * @return array
     */
    public function addGroupbuy($groupbuy_data, $goods_list, $goods_ids)
    {
        //查询该商品是否存在团购
        $count = model('promotion_groupbuy')->getCount(
            [
                ['site_id', '=', $groupbuy_data['site_id']],
                ['status', 'in', '1,2'],
                ['goods_id', 'in', $goods_ids],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $groupbuy_data['end_time'] . ' and `start_time` > ' . $groupbuy_data['start_time'] . ' )  or (`end_time` < ' . $groupbuy_data['start_time'] . ' and `end_time` < ' . $groupbuy_data['end_time'] . '))')]
            ]
        );
        if ($count > 0) {
            return $this->error('', '当前时间段内有商品存在团购活动');
        }

        // 当前时间
        $time = time();
        if ($time > $groupbuy_data['end_time']) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($time > $groupbuy_data['start_time'] && $time < $groupbuy_data['end_time']) {
            $groupbuy_data['status'] = 2;
        } else {
            $groupbuy_data['status'] = 1;
        }

        model('promotion_groupbuy')->startTrans();
        try {

            $groupbuy_data['create_time'] = $time;
            foreach ($goods_list as $v) {

                $groupbuy_id = model('promotion_groupbuy')->add(array_merge($v, $groupbuy_data));
                $cron = new Cron();
                if ($groupbuy_data['status'] == 2) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($v['goods_id'], ['groupbuy' => $groupbuy_id]);
                    $cron->addCron(1, 0, '团购活动关闭', 'CloseGroupbuy', $groupbuy_data['end_time'], $groupbuy_id);
                } else {
                    $cron->addCron(1, 0, '团购活动开启', 'OpenGroupbuy', $groupbuy_data['start_time'], $groupbuy_id);
                    $cron->addCron(1, 0, '团购活动关闭', 'CloseGroupbuy', $groupbuy_data['end_time'], $groupbuy_id);
                }
            }
            model('promotion_groupbuy')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_groupbuy')->rollback();
            return $this->error();
        }

    }

    /**
     * 编辑团购
     * @param $groupbuy_id
     * @param $site_id
     * @param $groupbuy_data
     * @return array|\multitype
     */
    public function editGroupbuy($groupbuy_id, $site_id, $groupbuy_data)
    {
        //查询该商品是否存在团购
        $count = model('promotion_groupbuy')->getCount(
            [
                ['site_id', '=', $site_id],
                ['status', 'in', '1,2'],
                ['groupbuy_id', '<>', $groupbuy_id],
                ['goods_id', '=', $groupbuy_data['goods_id']],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $groupbuy_data['end_time'] . ' and `start_time` > ' . $groupbuy_data['start_time'] . ' )  or (`end_time` < ' . $groupbuy_data['start_time'] . ' and `end_time` < ' . $groupbuy_data['end_time'] . '))')]
            ]
        );
        if ($count > 0) {
            return $this->error('', '当前时间段内该商品存在团购活动');
        }
        // 当前时间
        $time = time();
        if ($time > $groupbuy_data['end_time']) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($time > $groupbuy_data['start_time'] && $time < $groupbuy_data['end_time']) {
            $groupbuy_data['status'] = 2;
        } else {
            $groupbuy_data['status'] = 1;
        }

        $groupbuy_data['modify_time'] = time();

        $res = model('promotion_groupbuy')->update($groupbuy_data, [['groupbuy_id', '=', $groupbuy_id], ['site_id', '=', $site_id]]);

        $cron = new Cron();
        if ($groupbuy_data['status'] == 2) {
            //活动商品启动
            $this->cronOpenGroupbuy($groupbuy_id);
            $cron->deleteCron([['event', '=', 'OpenGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
            $cron->deleteCron([['event', '=', 'CloseGroupbuy'], ['relate_id', '=', $groupbuy_id]]);

            $cron->addCron(1, 0, '团购活动关闭', 'CloseGroupbuy', $groupbuy_data['end_time'], $groupbuy_id);
        } else {
            $cron->deleteCron([['event', '=', 'OpenGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
            $cron->deleteCron([['event', '=', 'CloseGroupbuy'], ['relate_id', '=', $groupbuy_id]]);

            $cron->addCron(1, 0, '团购活动开启', 'OpenGroupbuy', $groupbuy_data['start_time'], $groupbuy_id);
            $cron->addCron(1, 0, '团购活动关闭', 'CloseGroupbuy', $groupbuy_data['end_time'], $groupbuy_id);
        }
        return $this->success($res);
    }

    /**
     * 删除团购活动
     * @param $groupbuy_id
     * @param $site_id
     * @return array|\multitype
     */
    public function deleteGroupbuy($groupbuy_id, $site_id)
    {
        //团购信息
        $groupbuy_info = model('promotion_groupbuy')->getInfo([['groupbuy_id', '=', $groupbuy_id], ['site_id', '=', $site_id]], 'groupbuy_id,status,goods_id');
        if ($groupbuy_info) {
            if (in_array($groupbuy_info['status'], [1, 3])) {
                $res = model('promotion_groupbuy')->delete([['groupbuy_id', '=', $groupbuy_id]]);
                if ($res) {
                    $cron = new Cron();
                    $cron->deleteCron([['event', '=', 'OpenGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
                    $cron->deleteCron([['event', '=', 'CloseGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '团购活动进行中或已结束');
            }
        } else {
            return $this->error('', '团购活动不存在');
        }
    }

    /**
     * 结束团购活动
     * @param $groupbuy_id
     * @param $site_id
     * @return array
     */
    public function finishGroupbuy($groupbuy_id, $site_id)
    {
        //团购信息
        $groupbuy_info = model('promotion_groupbuy')->getInfo([['groupbuy_id', '=', $groupbuy_id], ['site_id', '=', $site_id]], 'groupbuy_id,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info['goods_id'], ['groupbuy' => $groupbuy_id], true);
            if ($groupbuy_info['status'] != 3) {
                $res = model('promotion_groupbuy')->update(['status' => 3], [['groupbuy_id', '=', $groupbuy_id]]);
                if ($res) {
                    $cron = new Cron();
                    $cron->deleteCron([['event', '=', 'OpenGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
                    $cron->deleteCron([['event', '=', 'CloseGroupbuy'], ['relate_id', '=', $groupbuy_id]]);
                }
                return $this->success($res);
            } else {
                $this->error('', '该团购活动已结束');
            }
        } else {
            $this->error('', '该团购活动不存在');
        }
    }

    /**
     * 获取团购信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGroupbuyInfo($condition = [], $field = 'pg.groupbuy_id,pg.site_id,pg.goods_id,pg.groupbuy_price,pg.buy_num,pg.create_time,pg.start_time,pg.end_time,pg.sell_num,pg.status,pg.rule,g.goods_name,g.goods_image,g.price,g.goods_stock')
    {
        $alias = 'pg';
        $join = [
            ['goods g', 'g.goods_id = pg.goods_id', 'inner']
        ];
        $groupbuy_info = model('promotion_groupbuy')->getInfo($condition, $field, $alias, $join);
        if (!empty($groupbuy_info)) {
            if (isset($groupbuy_info['goods_stock'])) {
                $groupbuy_info['goods_stock'] = numberFormat($groupbuy_info['goods_stock']);
            }
        }
        return $this->success($groupbuy_info);
    }

    /**
     * 团购商品详情
     * @param array $condition
     * @return array
     */
    public function getGroupbuyGoodsDetail($condition = [])
    {
        $alias = 'pg';

        $field = 'g.fenxiao_type,sku.fenxiao_price,g.is_fenxiao,pg.groupbuy_id,pg.groupbuy_price,pg.buy_num,pg.start_time,pg.end_time,pg.sell_num,pg.status,pg.rule,
        sku.sku_id,sku.site_id,sku.sku_name,sku.price,sku.sku_spec_format,
        sku.promotion_type,sku.stock,sku.click_num,(g.sale_num + g.virtual_sale) as sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.goods_id,sku.site_id,sku.goods_content,
        sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,g.evaluate,sku.goods_id,
        sku.goods_service_ids,sku.support_trade_type,g.goods_image,g.goods_stock,g.goods_name,sku.qr_id,g.stock_show,g.sale_show,g.label_name,g.category_id';
        $join = [
            ['goods_sku sku', 'pg.goods_id = sku.goods_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
        ];

        $goods_info = model('promotion_groupbuy')->getInfo($condition, $field, $alias, $join);
        if (!empty($goods_info)) {
            $goods_info['sale_num'] = numberFormat($goods_info['sale_num']);
            $goods_info['stock'] = numberFormat($goods_info['stock']);
            $goods_info['goods_stock'] = numberFormat($goods_info['goods_stock']);
        }
        return $this->success($goods_info);
    }

    /**
     * 团购商品
     * @param array $condition
     * @return array
     */
    public function getGroupbuyGoodsSkuList($condition = [], $limit = null)
    {
        $alias = 'pg';

        $field = 'pg.groupbuy_id,pg.groupbuy_price,pg.buy_num,pg.start_time,pg.end_time,pg.sell_num,pg.status,g.goods_id,g.goods_name,g.goods_stock,
        sku.sku_id,sku.sku_name,sku.price,sku.sku_spec_format,sku.stock,sku.sku_image,sku.sku_images,sku.goods_spec_format,g.goods_image';
        $join = [
            ['goods_sku sku', 'pg.goods_id = sku.goods_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
        ];

        $list = model('promotion_groupbuy')->getList($condition, $field, 'pg.groupbuy_id asc', $alias, $join, '', $limit);
        foreach ($list as $k => $v) {
            $list[$k]['stock'] = numberFormat($list[$k]['stock']);
            $list[$k]['goods_stock'] = numberFormat($list[$k]['goods_stock']);
        }
        return $this->success($list);
    }

    /**
     * 获取团购列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGroupbuyList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_groupbuy')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取团购分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGroupbuyPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {

        $field = 'pg.groupbuy_id,pg.site_id,pg.goods_id,pg.groupbuy_price,pg.buy_num,pg.create_time,
        pg.start_time,pg.end_time,pg.sell_num,pg.status,
        g.goods_name,g.goods_image,g.price,g.goods_stock,g.recommend_way';
        $alias = 'pg';
        $join = [
            ['goods g', 'g.goods_id = pg.goods_id', 'inner'],
        ];
        $res = model('promotion_groupbuy')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($res['list'] as $k => $v) {
            $res['list'][$k]['goods_stock'] = numberFormat($res['list'][$k]['goods_stock']);
        }
        return $this->success($res);
    }

    /**
     * 获取团购商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGroupbuyGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pg.groupbuy_id desc', $field = '')
    {
        if (empty($field)) {
            $field = 'pg.groupbuy_id,pg.groupbuy_price,pg.sell_num,pg.site_id,pg.buy_num,
            sku.sku_id,sku.price,sku.sku_name,sku.sku_image,g.goods_id,g.goods_name,g.goods_image,g.goods_stock,g.recommend_way';
        }
        $alias = 'pg';
        $join = [
            ['goods g', 'pg.goods_id = g.goods_id', 'inner'],
            ['goods_sku sku', 'g.sku_id = sku.sku_id', 'inner']
        ];
        $res = model('promotion_groupbuy')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach ($res['list'] as $k => $v) {
            if (isset($v['goods_stock'])) {
                $res['list'][$k]['goods_stock'] = numberFormat($res['list'][$k]['goods_stock']);
            }
        }
        return $this->success($res);
    }

    /**
     * 获取团购商品列表
     * @param array $condition
     * @param string $order
     * @param string $field
     */
    public function getGroupbuyGoodsList($condition = [], $field = '', $order = 'pg.groupbuy_id desc', $limit = null)
    {
        if (empty($field)) {
            $field = 'pg.groupbuy_id,pg.groupbuy_price,pg.sell_num,pg.site_id,pg.buy_num,
            sku.sku_id,sku.price,sku.sku_name,sku.sku_image,g.goods_id,g.goods_name,g.goods_image,g.goods_stock,g.recommend_way';
        }
        $alias = 'pg';
        $join = [
            ['goods g', 'pg.goods_id = g.goods_id', 'inner'],
            ['goods_sku sku', 'g.sku_id = sku.sku_id', 'inner']
        ];
        $list = model('promotion_groupbuy')->getList($condition, $field, $order, $alias, $join, '', $limit);
        foreach ($list as $k => $v) {
            if (isset($v['goods_stock'])) {
                $list[$k]['goods_stock'] = numberFormat($list[$k]['goods_stock']);
            }
        }
        return $this->success($list);
    }

    /**
     * 开启团购活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronOpenGroupbuy($groupbuy_id)
    {
        $groupbuy_info = model('promotion_groupbuy')->getInfo([['groupbuy_id', '=', $groupbuy_id]], 'start_time,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info['goods_id'], ['groupbuy' => $groupbuy_id]);
            if ($groupbuy_info['start_time'] <= time() && $groupbuy_info['status'] == 1) {
                $res = model('promotion_groupbuy')->update(['status' => 2], [['groupbuy_id', '=', $groupbuy_id]]);
                return $this->success($res);
            } else {
                return $this->error('', '团购活动已开启或者关闭');
            }
        } else {
            return $this->error('', '团购活动不存在');
        }

    }

    /**
     * 关闭团购活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronCloseGroupbuy($groupbuy_id)
    {
        $groupbuy_info = model('promotion_groupbuy')->getInfo([['groupbuy_id', '=', $groupbuy_id]], 'start_time,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info['goods_id'], ['groupbuy' => $groupbuy_id], true);
            if ($groupbuy_info['status'] != 3) {
                $res = model('promotion_groupbuy')->update(['status' => 3], [['groupbuy_id', '=', $groupbuy_id]]);
                return $this->success($res);
            } else {
                return $this->error('', '该活动已结束');
            }
        } else {
            return $this->error('', '团购活动不存在');
        }
    }

    /**
     * 订单支付
     * @param $param
     * @return mixed
     */
    public function orderPay($param)
    {
        $order_goods = model('order_goods')->getInfo([['order_id', '=', $param['order_id']]], 'goods_id,num');
        if (!empty($order_goods)) {

            //获取团购id
            $groupbuy_id = model('promotion_groupbuy')->getValue(
                [['goods_id', '=', $order_goods['goods_id']], ['status', '=', 2]],
                'groupbuy_id'
            );
            if ($groupbuy_id != 0) {
                //增加销售量
                model('promotion_groupbuy')->setInc([['groupbuy_id', '=', $groupbuy_id]], 'sell_num', $order_goods['num']);
            }
        }
        return $this->success();
    }

    /**
     * 生成团购二维码
     * @param $groupbuy_id
     * @param string $app_type all为全部
     * @param string $type 类型 create创建 get获取
     * @return mixed|array
     */
    public function qrcode($groupbuy_id, $name, $site_id, $type = 'create')
    {
        $data = [
            'site_id' => $site_id,
            'app_type' => 'all', // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                'groupbuy_id' => $groupbuy_id
            ],
            'page' => '/pages_promotion/groupbuy/detail',
            'qrcode_path' => 'upload/qrcode/groupbuy',
            'qrcode_name' => 'groupbuy_qrcode_' . $groupbuy_id
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');
        $path = [];
        foreach ($app_type_list as $k => $v) {
            switch ($k) {
                case 'h5':
                    $wap_domain = getH5Domain();
                    $path[$k]['status'] = 1;
                    $path[$k]['url'] = $wap_domain . $data['page'] . '?id=' . $groupbuy_id;
                    $path[$k]['img'] = 'upload/qrcode/groupbuy/groupbuy_qrcode_' . $groupbuy_id . '_' . $k . '.png';
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WEAPP_CONFIG']]);
                    if (!empty($res['data'])) {
                        if (empty($res['data']['value']['qrcode'])) {
                            $path[$k]['status'] = 2;
                            $path[$k]['message'] = '未配置微信小程序';
                        } else {
                            $path[$k]['status'] = 1;
                            $path[$k]['img'] = $res['data']['value']['qrcode'];
                        }

                    } else {
                        $path[$k]['status'] = 2;
                        $path[$k]['message'] = '未配置微信小程序';
                    }
                    break;

                case 'wechat' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'WECHAT_CONFIG']]);
                    if (!empty($res['data'])) {
                        if (empty($res['data']['value']['qrcode'])) {
                            $path[$k]['status'] = 2;
                            $path[$k]['message'] = '未配置微信公众号';
                        } else {
                            $path[$k]['status'] = 1;
                            $path[$k]['img'] = $res['data']['value']['qrcode'];
                        }
                    } else {
                        $path[$k]['status'] = 2;
                        $path[$k]['message'] = '未配置微信公众号';
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
        $cache_name = 'shop_groupbuy_goods_category_' . md5(json_encode($condition));
        $cache_time = 60;
        $cache_res = Cache::get($cache_name);
        if (empty($cache_res) || time() - $cache_res['time'] > $cache_time) {
            $list = Db::name('promotion_groupbuy')
                ->alias('pg')
                ->join('goods g', 'pg.goods_id = g.goods_id', 'inner')
                ->where($condition)
                ->group('g.category_id')
                ->column('g.category_id');
            $category_ids = trim(join('0', $list), ',');
            $category_id_arr = array_unique(explode(',', $category_ids));
            Cache::set($cache_name, ['time' => time(), 'data' => $category_id_arr]);
        } else {
            $category_id_arr = $cache_res['data'];
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
            'h5_path' => $page . '?id=' . $qrcode_param['id'],
            'qrcode_path' => 'upload/qrcode/groupbuy',
            'qrcode_name' => 'groupbuy_qrcode_' . $promotion_type . '_' . $qrcode_param['id'] . '_' . $site_id
        ];

        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}