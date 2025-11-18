<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\stock;

use app\model\store\Store;
use extend\exception\StockException;
use think\facade\Db;
use think\facade\Log;

/**
 * 库存 可调用的工具类
 */
trait StockTool
{
    //商品sku
    public $goods_sku_ids = [];
    public $goods_list_column = [];

    /**
     * 将商品数据整理为不存在需添加的数据和存在需编辑的商品
     * @param $param
     * @return array
     */
    public function getCheckGoodsSkuData($param)
    {
        $store_id = $param['store_id'];
        $stock_action_type = $param['stock_action_type'] ?? '';//出入库类型
        $result = $this->getStoreSkuAndCreateIfNotExists($param);
        $sku_column = $result['sku_column'];
        $goods_list_column = $result['goods_list_column'];
        $is_allow_negative = $param['is_allow_negative'] ?? true;
        $update_sku_data = [];
        $update_goods_data = [];
        foreach ($sku_column as $goods_k => $goods_v) {
            $temp_stock = $goods_v['stock'] ?? $goods_v['num'];
            //校验整理不存在的门店商品项或sku项
            $item_temp_v = $goods_list_column[$goods_k] ?? [];
            $sku_name = str_sub($item_temp_v['sku_name'], 12, true, 'end');
            //todo 这儿需要考虑
            if (!$item_temp_v) throw new StockException('存在已删除的商品！');
            $item_sku_id = $item_temp_v['sku_id'];
            $item_goods_id = $item_temp_v['goods_id'];
            $item_stock = $item_temp_v['stock'] ?? 0;
            $item_real_stock = $item_temp_v['real_stock'] ?? 0;
            //出库才需要校验库存
            if ($stock_action_type == 'dec') {
                $o_stock = $temp_stock;
                $temp_stock = -abs($temp_stock);
                if (!$is_allow_negative) {
                    if ($item_real_stock < $o_stock) throw new StockException('商品“' . $sku_name . '”库存不足！');
                    if ($item_stock < $o_stock) throw new StockException('商品“' . $sku_name . '“库存不足！');
                }
            } else if ($stock_action_type == 'inc') {
                $temp_stock = abs($temp_stock);
            } else if ($stock_action_type == 'set') {
                if (!$is_allow_negative) {
                    if ($temp_stock < 0) throw new StockException('库存不足！');
                }
                if(!empty($param['field']) == 'stock'){
                    $temp_stock = $temp_stock - $item_stock;
                }else{
                    $temp_stock = $temp_stock - $item_real_stock;
                }

            }
            if ($temp_stock != 0) {
                //数组的键值如果是小数会自动转化为整数，需要强制转化为字符串
                $update_sku_data[(string)$temp_stock][] = [
                    'sku_id' => $item_sku_id,
                    'goods_id' => $item_goods_id,
                    'store_id' => $store_id,
                ];
                if (!isset($update_goods_data[$item_goods_id])) {
                    $update_goods_data[$item_goods_id] = [
                        'goods_id' => $item_goods_id,
                        'store_id' => $store_id,
                        'stock' => 0
                    ];
                }
                $update_goods_data[$item_goods_id]['stock'] += $temp_stock;
            }
        }

        if ($update_goods_data) {
            $temp_update_data = [];
            foreach ($update_goods_data as $v) {
                //数组的键值如果是小数会自动转化为整数，需要强制转化为字符串
                $temp_update_data[(string)$v['stock']][] = [
                    'goods_id' => $v['goods_id'],
                    'store_id' => $store_id,
                ];
            }
        }
        //返回需要添加或编辑的数据
        return [
            'update_sku_data' => $update_sku_data,
            'update_goods_data' => $temp_update_data ?? []
        ];
    }

    /**
     * 创建门店商品(不存在创建)
     * @param $param
     * @return array
     */
    public function getStoreSkuAndCreateIfNotExists($param)
    {
        $goods_sku_list = $param['goods_sku_list'] ?? [];//[['sku_id' => 1]]
        $store_id = $param['store_id'];
        $sku_column = array_column($goods_sku_list, null, 'sku_id');
        $sku_ids = array_keys($sku_column);
        $condition = [];
        if ($goods_sku_list) {
            $condition[] = ['gs.sku_id', 'in', $sku_ids];
        }
        $goods_list = model('goods_sku')->getList(
            $condition,
            'gs.goods_id,gs.sku_name,gs.sku_id, gs.stock as sku_stock, gs.real_stock as sku_real_stock,gs.price as sku_price, 
            gs.is_unify_price, gs.goods_class, 
            g.goods_stock as goods_stock, g.real_stock as goods_real_stock, g.price as goods_price,
            sg.store_id as store_goods_store_id, sgs.store_id as store_goods_sku_store_id, sgs.real_stock, sgs.stock',
            '',
            'gs',
            [
                [
                    'goods g',
                    'gs.goods_id = g.goods_id',
                    'left'
                ],
                [
                    'store_goods sg',
                    'gs.goods_id = sg.goods_id and sg.store_id = ' . $store_id,
                    'left'
                ],
                [
                    'store_goods_sku sgs',
                    'gs.sku_id = sgs.sku_id and sgs.store_id = ' . $store_id,
                    'left'
                ]
            ]
        );
        if (empty($goods_list)) throw new StockException('商品不存在！');
        $goods_list_column = array_column($goods_list, null, 'sku_id');
        $is_default_store = $this->isDefaultStore($store_id);

        $insert_goods_data = [];
        $insert_sku_data = [];
        $now_time = time();

        foreach ($sku_column as $goods_k => $goods_v) {
            //校验整理不存在的门店商品项或sku项
            $item_temp_v = $goods_list_column[$goods_k] ?? [];
            //todo 这儿需要考虑
            if (!$item_temp_v) throw new StockException('存在已删除的商品！');
            $item_sku_id = $item_temp_v['sku_id'];
            $item_goods_id = $item_temp_v['goods_id'];

            $store_goods_store_id = $item_temp_v['store_goods_store_id'] ?? 0;
            $item_insert_goods = $insert_goods_data[$item_goods_id] ?? [];

            if (!$store_goods_store_id > 0 && !$item_insert_goods) {
                $insert_goods_data[$item_goods_id] = [
                    'goods_id' => $item_goods_id,
                    'store_id' => $store_id,
                    'create_time' => $now_time,
//                    'stock' => $is_default_store ? $item_temp_v['goods_stock'] : 0,
//                    'real_stock' => $is_default_store ? $item_temp_v['goods_real_stock'] : 0,
                    'price' => $item_temp_v['goods_price']
                ];
            }
            //todo  默认认为门店商品sku只要存在,门店商品就存在
            $store_goods_sku_store_id = $item_temp_v['store_goods_sku_store_id'] ?? 0;
            $item_insert_sku = $insert_sku_data[$item_sku_id] ?? [];
            if (!$store_goods_sku_store_id > 0) {
                if (!$item_insert_sku) {
                    $insert_sku_data[$item_sku_id] = [
                        'goods_id' => $item_goods_id,
                        'sku_id' => $item_sku_id,
                        'store_id' => $store_id,
                        'create_time' => $now_time,
//                        'stock' => $is_default_store ? $item_temp_v['sku_stock'] : 0,
//                        'real_stock' => $is_default_store ? $item_temp_v['sku_real_stock'] : 0,
                        'price' => $item_temp_v['sku_price']
                    ];
                }
            }
        }
        //统一添加未定义的门店商品
        if ($insert_goods_data) {
            model('store_goods')->addList($insert_goods_data);
        }
        //添加门店商品sku数据
        if ($insert_sku_data) {
            model('store_goods_sku')->addList($insert_sku_data);
        }
        return [
            'sku_column' => $sku_column,
            'goods_list_column' => $goods_list_column,
        ];
    }

    /**
     * 当前传入的门店是否是默认门店(默认门店需要同步修改总的goods表和sku表)
     * @param $store_id
     * @return bool
     */
    public function isDefaultStore($store_id)
    {
        $default_store_id = (new Store())->getDefaultStore()['data']['store_id'] ?? 0;
        return $default_store_id == $store_id;
    }

    /**
     * 获取默认门店
     * @param $store_id
     * @return mixed
     */
    public function getDefaultStore($store_id)
    {
        if ($store_id == 0 || !addon_is_exit('store')) {
            $store_model = new Store();
            $store_info = $store_model->getDefaultStore()['data'] ?? [];
            $store_id = $store_info['store_id'];
        }
        return $store_id;
    }

    /**
     * 批量设置库存
     * @param $param
     * @return void
     */
    public function setStock($param)
    {
        $store_id = $param['store_id'];
        $is_out_stock = 1;
        $stock_action_type = $param['stock_action_type'] ?? '';//出入库类型
        if ($stock_action_type == 'dec') {
            $is_out_stock = $param['is_out_stock'];
        }
        $update_sku_data = $param['update_sku_data'];
        $update_goods_data = $param['update_goods_data'];

        //存在则直接编辑
        if ($update_sku_data) {
            $condition = [
                ['sgs.store_id', '=', $store_id]
            ];
            $data = [];
            //库存变更相同项可以合并同类项
            foreach ($update_sku_data as $item_stock => $v) {
                if ($item_stock > 0) {
                    $stock_sql = ' + ' . abs($item_stock);
                } else {
                    $stock_sql = ' - ' . abs($item_stock);
                }
                $item_goods_condition = $condition;
                if (count($v) > 1) {
                    $item_goods_condition[] = [
                        'sgs.goods_id', 'in', array_column($v, 'goods_id')
                    ];
                    $item_goods_sku_condition = $item_goods_condition;
                    $item_goods_sku_condition[] = [
                        'sgs.sku_id', 'in', array_column($v, 'sku_id')
                    ];
                } else {
                    $first_item = reset($v);
                    $item_goods_condition[] = [
                        'sgs.goods_id', '=', $first_item['goods_id']
                    ];
                    $item_goods_sku_condition = $item_goods_condition;
                    $item_goods_sku_condition[] = [
                        'sgs.sku_id', '=', $first_item['sku_id']
                    ];
                }
                $update_obj = Db::name('store_goods_sku')->where($item_goods_sku_condition);
                //sku表联表goods表,合并sql
                $update_obj->alias('sgs');
                $data = [];
                //是否扣除销售库存
                if ($is_out_stock > 0) {
                    $data['sgs.stock'] = Db::raw('IF(sgs.stock > sgs.real_stock,sgs.real_stock, sgs.stock)' . $stock_sql);
                }
                $data['sgs.real_stock'] = Db::raw('sgs.real_stock' . $stock_sql);
                //todo  销售库存不能大于实际库存,编辑的过程中需要校准
                //如果是默认门店,需要同步修改平台商品和sku
                if ($this->isDefaultStore($store_id)) {
                    $update_obj->leftJoin('goods_sku gs', 'gs.sku_id = sgs.sku_id');
                    if ($is_out_stock > 0) {
                        $data['gs.stock'] = Db::raw('IF(gs.stock > gs.real_stock,gs.real_stock, gs.stock)' . $stock_sql);
                    }
                    $data['gs.real_stock'] = Db::raw('gs.real_stock' . $stock_sql);
                }
                $update_obj->update($data);
            }
        }


        //存在则直接编辑
        if ($update_goods_data) {
            $condition = [
                ['sg.store_id', '=', $store_id]
            ];
            $data = [];
            //库存变更相同项可以合并同类项
            foreach ($update_goods_data as $item_stock => $v) {
                if ($item_stock > 0) {
                    $stock_sql = ' + ' . abs($item_stock);
                } else {
                    $stock_sql = ' - ' . abs($item_stock);
                }
                $item_goods_condition = $condition;
                if (count($v) > 1) {
                    $item_goods_condition[] = [
                        'sg.goods_id', 'in', array_column($v, 'goods_id')
                    ];

                } else {
                    $first_item = reset($v);
                    $item_goods_condition[] = [
                        'sg.goods_id', '=', $first_item['goods_id']
                    ];
                }
                $update_obj = Db::name('store_goods')->where($item_goods_condition);
                //sku表联表goods表,合并sql
                $update_obj->alias('sg');
                $data = [];
                //是否扣除销售库存
                if ($is_out_stock > 0) {
                    $data['sg.stock'] = Db::raw('IF(sg.stock > sg.real_stock,sg.real_stock, sg.stock)' . $stock_sql);
                }
                $data['sg.real_stock'] = Db::raw('sg.real_stock' . $stock_sql);

                //todo  销售库存不能大于实际库存,编辑的过程中需要校准
                //如果是默认门店,需要同步修改平台商品和sku
                if ($this->isDefaultStore($store_id)) {
                    $update_obj->leftJoin('goods g', 'g.goods_id = sg.goods_id');
                    if ($is_out_stock > 0) {
                        $data['g.goods_stock'] = Db::raw('IF(g.goods_stock > g.real_stock,g.real_stock, g.goods_stock)' . $stock_sql);
                    }
                    $data['g.real_stock'] = Db::raw('g.real_stock' . $stock_sql);
                }
                $update_obj->update($data);
            }
        }
        return true;
    }


    /**
     * 设置销售库存
     * @param $param
     * @return true
     * @throws \think\db\exception\DbException
     */
    public function setSaleStock($param)
    {
        $store_id = $param['store_id'];
        $update_sku_data = $param['update_sku_data'];
        $update_goods_data = $param['update_goods_data'];
        //存在则直接编辑
        if ($update_sku_data) {
            $condition = [
                ['sgs.store_id', '=', $store_id]
            ];
            $data = [];
            //库存变更相同项可以合并同类项
            foreach ($update_sku_data as $item_stock => $v) {
                if ($item_stock > 0) {
                    $stock_sql = ' + ' . abs($item_stock);
                } else {
                    $stock_sql = ' - ' . abs($item_stock);
                }
                $item_goods_condition = $condition;
                if (count($v) > 1) {
                    $item_goods_condition[] = [
                        'sgs.goods_id', 'in', array_column($v, 'goods_id')
                    ];
                    $item_goods_sku_condition = $item_goods_condition;
                    $item_goods_sku_condition[] = [
                        'sgs.sku_id', 'in', array_column($v, 'sku_id')
                    ];
                } else {
                    $first_item = reset($v);
                    $item_goods_condition[] = [
                        'sgs.goods_id', '=', $first_item['goods_id']
                    ];
                    $item_goods_sku_condition = $item_goods_condition;
                    $item_goods_sku_condition[] = [
                        'sgs.sku_id', '=', $first_item['sku_id']
                    ];
                }
                $update_obj = Db::name('store_goods_sku')->where($item_goods_sku_condition);
                //sku表联表goods表,合并sql
                $update_obj->alias('sgs');
                $data['sgs.stock'] = Db::raw('sgs.stock' . $stock_sql);

                //todo  销售库存不能大于实际库存,编辑的过程中需要校准
                //如果是默认门店,需要同步修改平台商品和sku
                if ($this->isDefaultStore($store_id)) {
                    $update_obj->leftJoin('goods_sku gs', 'gs.sku_id = sgs.sku_id');
                    $data['gs.stock'] = Db::raw('IF(gs.stock > gs.real_stock,gs.real_stock, gs.stock)' . $stock_sql);
                }
                $update_obj->update($data);
            }
        }


        //存在则直接编辑
        if ($update_goods_data) {
            $condition = [
                ['sg.store_id', '=', $store_id]
            ];
            $data = [];
            //库存变更相同项可以合并同类项
            foreach ($update_goods_data as $item_stock => $v) {
                if ($item_stock > 0) {
                    $stock_sql = ' + ' . abs($item_stock);
                } else {
                    $stock_sql = ' - ' . abs($item_stock);
                }
                $item_goods_condition = $condition;
                if (count($v) > 1) {
                    $item_goods_condition[] = [
                        'sg.goods_id', 'in', array_column($v, 'goods_id')
                    ];

                } else {
                    $first_item = reset($v);
                    $item_goods_condition[] = [
                        'sg.goods_id', '=', $first_item['goods_id']
                    ];
                }
                $update_obj = Db::name('store_goods')->where($item_goods_condition);
                //sku表联表goods表,合并sql
                $update_obj->alias('sg');

                $data['sg.stock'] = Db::raw('sg.stock' . $stock_sql);

                //todo  销售库存不能大于实际库存,编辑的过程中需要校准
                //如果是默认门店,需要同步修改平台商品和sku
                if ($this->isDefaultStore($store_id)) {
                    $update_obj->leftJoin('goods g', 'g.goods_id = sg.goods_id');
                    $data['g.goods_stock'] = Db::raw('IF(g.goods_stock > g.real_stock,g.real_stock, g.goods_stock)' . $stock_sql);
                }
                $update_obj->update($data);
            }
        }
        return true;
    }

    /**
     * 获取统一格式的商品数据
     * @param $params
     * @return array|array[]|mixed
     */
    public function getFormatSkuList($params)
    {
        $goods_sku_list = $params['goods_sku_list'] ?? [];
        if (empty($goods_sku_list)) {
            $goods_id = $params['goods_id'] ?? 0;
            $sku_id = $params['sku_id'];
            $temp_stock = $params['stock'] ?? $params['num'];
            $goods_sku_list = [
                [
                    'stock' => $temp_stock,
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id
                ]
            ];
        }
        return $goods_sku_list;
    }
}
