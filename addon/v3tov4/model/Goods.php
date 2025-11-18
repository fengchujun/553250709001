<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\v3tov4\model;

use app\model\goods\Goods as GoodsModel;
use app\model\system\Stat;

/**
 * 迁移商品相关数据（商品、商品分类、商品标签）
 */
class Goods extends Upgrade
{

    private $site_id = 1;

    /**
     * 迁移商品数据
     * @param $page_index
     * @param $page_size
     * @return array
     */
    public function getGoodsList($page_index, $page_size)
    {
        try {

            // 查询v3商品表
            $field = 'goods_id, goods_name, category_id, category_id_1, category_id_2, category_id_3, group_id_array, goods_type, market_price, price, promotion_price, cost_price, shipping_fee, shipping_fee_id, stock, max_buy, clicks, min_stock_alarm, sales, collects, star, evaluates, picture, keywords, introduction, description, code, state, sort, img_id_array, sku_img_array, goods_attribute_id, goods_spec_format, goods_weight, goods_volume, supplier_id, create_time, update_time, min_buy, is_virtual, goods_video_address, goods_unit';
            $goods_list = $this->getPageList('ns_goods', [ [ 'goods_type', 'in', '1,2' ] ], $field, $page_index, $page_size);

            if (!empty($goods_list)) {
                if ($page_index == 1) {
                    // 首次清空商品表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('goods')->execute("TRUNCATE TABLE {$prefix}goods");
                    model('goods_sku')->execute("TRUNCATE TABLE {$prefix}goods_sku");
                }

                foreach ($goods_list as $item) {

                    // 商品参数
                    $item[ 'goods_attr_class' ] = 0;
                    $item[ 'goods_attr_name' ] = '';
                    if (!empty($item[ 'goods_attribute_id' ])) {
                        $attribute_info = $this->getInfo('ns_attribute', [ [ 'attr_id', '=', $item[ 'goods_attribute_id' ] ] ], 'attr_id,attr_name');
                        if (!empty($attribute_info)) {
                            $item[ 'goods_attr_class' ] = $attribute_info[ 'attr_id' ];
                            $item[ 'goods_attr_name' ] = $attribute_info[ 'attr_name' ];
                        }
                    }
                    $join = [
                        [ 'ns_attribute_value nav', 'nav.attr_value_id = nga.attr_value_id', 'right' ],
                    ];
                    $goods_attribute_list = $this->getList("ns_goods_attribute", [ [ 'goods_id', '=', $item[ 'goods_id' ] ] ], 'nav.attr_id as attr_class_id,nga.attr_id,nga.attr_value_id,nga.attr_value,nga.attr_value_name', '', 'nga', $join);
                    $item[ 'goods_attr_format' ] = '';
                    if (!empty($goods_attribute_list)) {
                        $item[ 'goods_attr_format' ] = [];
                        foreach ($goods_attribute_list as $attr_k => $attr_v) {
                            $item[ 'goods_attr_format' ][] = [
                                "attr_class_id" => $attr_v[ 'attr_class_id' ],
                                "attr_id" => $attr_v[ 'attr_id' ],
                                "attr_name" => $attr_v[ 'attr_value' ],
                                "attr_value_id" => $attr_v[ 'attr_value_id' ],
                                "attr_value_name" => $attr_v[ 'attr_value_name' ]
                            ];
                        }
                        $item[ 'goods_attr_format' ] = json_encode($item[ 'goods_attr_format' ]);
                    }

                    // 商品标签
                    $item[ 'label_id' ] = 0;
                    if (!empty($item[ 'group_id_array' ])) {
                        $item[ 'label_id' ] = explode(",", $item[ 'group_id_array' ])[ 0 ];
                    }

                    if ($item[ 'goods_type' ] == 1) {
                        $item[ 'goods_class' ] = 1;
                        $item[ 'goods_class_name' ] = '实物商品';
                    } elseif ($item[ 'goods_type' ] == 2) {
                        $item[ 'goods_class' ] = 0;
                        $item[ 'goods_class_name' ] = '虚拟商品';
                    }

                    //商品主图
                    $picture_info = $this->getInfo("sys_album_picture", [ [ 'pic_id', '=', $item[ 'picture' ] ] ], 'pic_cover');
                    $item[ 'goods_image' ] = $picture_info[ 'pic_cover' ];

                    $goods_spec_format = json_decode($item[ 'goods_spec_format' ], true);
                    $goods_spec_format_temp = [];
                    // 循环处理规格
                    foreach ($goods_spec_format as $spec_k => $spec_v) {
                        $goods_spec_format_temp[ $spec_k ] = [
                            "spec_name" => $spec_v[ 'spec_name' ],
                            "spec_id" => $spec_v[ 'spec_id' ],
                            "value" => []
                        ];
                        foreach ($spec_v[ 'value' ] as $spec_value_k => $spec_value_v) {
                            $goods_spec_format_temp[ $spec_k ] [ 'value' ][ $spec_value_k ] = [
                                "spec_name" => $spec_v[ 'spec_name' ],
                                "spec_id" => $spec_v[ 'spec_id' ],
                                "spec_value_name" => $spec_value_v[ 'spec_value_name' ],
                                "spec_value_id" => $spec_value_v[ 'spec_value_id' ]
                            ];
                            if ($spec_value_v[ 'spec_show_type' ] == 2) {
                                $goods_spec_format_temp[ $spec_k ] [ 'value' ][ $spec_value_k ][ 'image' ] = $spec_value_v[ 'spec_value_data' ];
                            }

                        }
                    }

                    // SKU数据
                    $item[ 'goods_sku_data' ] = [];

                    if (!empty($goods_spec_format_temp)) {
                        $item[ 'goods_spec_format' ] = json_encode($goods_spec_format_temp);
                    } else {
                        $item[ 'goods_spec_format' ] = '';
                    }


                    // 排序：按价格升序
                    $goods_sku_list = $this->getList("ns_goods_sku", [ [ 'goods_id', '=', $item[ 'goods_id' ] ] ], "sku_id,goods_id,sku_name,attr_value_items,market_price, price, promote_price, cost_price, stock, picture, code, weight, volume, sku_img_array", 'price asc');

                    foreach ($goods_sku_list as $sku_k => $sku_v) {

                        $sku_spec_format = '';
                        if (!empty($sku_v[ 'attr_value_items' ])) {
                            $sku_spec_format = [];
                            $attr_value_items = explode(";", $sku_v[ 'attr_value_items' ]);
                            foreach ($attr_value_items as $attr_value_k => $attr_value_v) {
                                $temp = explode(":", $attr_value_v);
                                $spec_id = $temp[ 0 ];
                                $spec_value_id = $temp[ 1 ];
                                foreach ($goods_spec_format_temp as $spec_temp_k => $spec_temp_v) {
                                    if ($spec_temp_v[ 'spec_id' ] == $spec_id) {
                                        foreach ($spec_temp_v[ 'value' ] as $spec_temp_value_k => $spec_temp_value_v) {
                                            if ($spec_temp_value_v[ 'spec_value_id' ] == $spec_value_id) {
                                                $sku_spec_format[] = [
                                                    "spec_name" => $spec_temp_value_v[ 'spec_name' ],
                                                    "spec_id" => $spec_id,
                                                    "spec_value_id" => $spec_value_id,
                                                    "spec_value_name" => $spec_temp_value_v[ 'spec_value_name' ]
                                                ];
                                                if (!empty($spec_temp_value_v[ 'image' ])) {
                                                    $sku_spec_format[ count($sku_spec_format) - 1 ][ 'image' ] = $spec_temp_value_v[ 'image' ];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $pic_id_arr = explode(",", $item[ 'img_id_array' ]);
                        if (!empty($sku_v[ 'sku_img_array' ])) {
                            $pic_id_arr = array_merge(explode(",", $sku_v[ 'sku_img_array' ]), $pic_id_arr);
                        }

                        $sku_images = [];
                        $picture_list = $this->getList("sys_album_picture", [ [ 'pic_id', 'in', implode(",", $pic_id_arr) ] ], 'pic_cover');
                        foreach ($picture_list as $picture_list_k => $picture_list_v) {
                            $sku_images[] = $picture_list_v[ 'pic_cover' ];
                        }

                        $item[ 'goods_sku_data' ] [] = [
                            'sku_id' => $sku_v[ 'sku_id' ],
                            'site_id' => $this->site_id,
                            'sku_name' => $item[ 'goods_name' ] . ' ' . $sku_v[ 'sku_name' ],
                            'spec_name' => $sku_v[ 'sku_name' ],
                            'sku_no' => $sku_v[ 'code' ],
                            'sku_spec_format' => !empty($sku_spec_format) ? json_encode($sku_spec_format) : "",
                            'price' => $sku_v[ 'price' ],
                            'cost_price' => $sku_v[ 'cost_price' ],
                            'market_price' => $sku_v[ 'market_price' ],
                            'discount_price' => $sku_v[ 'promote_price' ],//sku折扣价（默认等于单价）
                            'is_free_shipping' => $item[ 'shipping_fee' ] == 0 ? 1 : 0,
                            'shipping_template' => 0,//$item[ 'shipping_fee_id' ],
                            'stock' => $sku_v[ 'stock' ],
                            'weight' => $sku_v[ 'weight' ],
                            'volume' => $sku_v[ 'volume' ],
                            'goods_id' => $item[ 'goods_id' ],
                            'goods_class' => $item[ 'goods_type' ],
                            "sku_image" => $sku_images[ 0 ],
                            "sku_images" => implode(",", $sku_images),
                            'collect_num' => $item[ 'collects' ],
                            'click_num' => $item[ 'clicks' ],
                            'goods_content' => $item['description']
                        ];
                    }

                    $res = $this->addGoods($item);
                    if ($res[ 'code' ] < 0) {
                        return $res;
                    }

                }

            }
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要迁移商品数量
     * @return int
     */
    public function getGoodsCount()
    {
        return $this->getCount('ns_goods', [ [ 'goods_type', 'in', '1,2' ] ], 'goods_id');
    }

    /**
     * 商品添加
     * @param $data
     * @return array
     */
    private function addGoods($data)
    {
        model('goods')->startTrans();

        try {

            $goods_image = $data[ 'goods_image' ];

            $category_id = [];
            if (!empty($data[ 'category_id_1' ])) {
                $category_id[] = $data[ 'category_id_1' ];
            }
            if (!empty($data[ 'category_id_2' ])) {
                $category_id[] = $data[ 'category_id_2' ];
            }
            if (!empty($data[ 'category_id_3' ])) {
                $category_id[] = $data[ 'category_id_3' ];
            }
            $category_json = '["' . implode(",", $category_id) . '"]';

            $goods_data = array (
                'goods_image' => $goods_image,
                'goods_stock' => $data[ 'stock' ],
                'price' => $data[ 'price' ],
                'market_price' => $data[ 'market_price' ],
                'cost_price' => $data[ 'cost_price' ],
                'goods_spec_format' => $data[ 'goods_spec_format' ],
                'category_id' => implode(",", $category_id),
                'category_json' => $category_json,
                'label_id' => $data[ 'label_id' ],
                'sku_id' => $data[ 'goods_sku_data' ][ 0 ][ 'sku_id' ]
            );

            $common_data = array (
                'goods_id' => $data[ 'goods_id' ],
                'goods_name' => $data[ 'goods_name' ],
                'goods_class' => $data[ 'goods_class' ],
                'goods_class_name' => $data[ 'goods_class_name' ],
                'goods_attr_class' => $data[ 'goods_attr_class' ],
                'goods_attr_name' => $data[ 'goods_attr_name' ],
                'site_id' => $this->site_id,
                'goods_content' => $data[ 'description' ],
                'goods_state' => $data[ 'state' ] == 10 ? 0 : $data[ 'state' ],
                'goods_stock_alarm' => $data[ 'min_stock_alarm' ],
                'is_free_shipping' => $data[ 'shipping_fee' ] == 0 ? 1 : 0,
                'shipping_template' => 0,//$data[ 'shipping_fee_id' ],
                'goods_attr_format' => $data[ 'goods_attr_format' ],
                'introduction' => $data[ 'introduction' ],
                'keywords' => $data[ 'keywords' ],
                'unit' => $data[ 'goods_unit' ],
                'video_url' => $data[ 'goods_video_address' ],
                'sort' => $data[ 'sort' ],
                'goods_service_ids' => '',
                'virtual_sale' => 0,
                'max_buy' => $data[ 'max_buy' ],
                'min_buy' => $data[ 'min_buy' ],
                'evaluate' => $data[ 'evaluates' ],
                'sale_num' => $data[ 'sales' ],
                'create_time' => $data[ 'create_time' ],
                'modify_time' => $data[ 'update_time' ],
                'is_virtual' => $data[ 'is_virtual' ],
                'supplier_id' => $data[ 'supplier_id' ]
            );

            $goods_id = model('goods')->add(array_merge($goods_data, $common_data));
            model('goods_sku')->addList($data[ 'goods_sku_data' ]);

            if (!empty($data[ 'goods_spec_format' ])) {
                // 刷新SKU商品规格项/规格值JSON字符串
                $goods_model = new GoodsModel();
                $goods_model->dealGoodsSkuSpecFormat($goods_id, $data[ 'goods_spec_format' ]);
            }

            // 添加店铺添加统计
            $stat = new Stat();
//            $stat->addShopStat([ 'add_goods_count' => 1, 'site_id' => $this->site_id ]);
            $stat->switchStat(['type' => 'add_goods', 'data' => [ 'add_goods_count' => 1, 'site_id' => $this->site_id ]]);
            model('goods')->commit();
            return $this->success($goods_id);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 迁移商品分类数据
     * @param $page_index
     * @param $page_size
     * @return array
     */
    public function getGoodsCategoryList($page_index, $page_size)
    {
        try {

            model("goods_category")->startTrans();

            // 查询v3商品分类表
            $field = 'category_id, category_name, short_name, pid, level , is_visible, attr_id, attr_name, keywords, description, sort, category_pic';
            $goods_category_list = $this->getPageList('ns_goods_category', [], $field, $page_index, $page_size);

            if (!empty($goods_category_list)) {
                if ($page_index == 1) {
                    // 首次清空商品分类表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('goods_category')->execute("TRUNCATE TABLE {$prefix}goods_category");
                }
                $data = [];
                foreach ($goods_category_list as $k => $v) {
                    $category_id_1 = 0;
                    $category_id_2 = 0;
                    $category_id_3 = 0;
                    $category_full_name = [];
                    if ($v[ 'level' ] == 1) {
                        $category_id_1 = $v[ 'category_id' ];
                        $category_full_name[] = $v[ 'category_name' ];
                    } elseif ($v[ 'level' ] == 2) {
                        $category_id_2 = $v[ 'category_id' ];
                        $one_category = $this->getInfo("ns_goods_category", [ [ 'category_id', '=', $v[ 'pid' ] ] ], 'category_id,category_name');
                        $category_id_1 = $one_category[ 'category_id' ];

                        $category_full_name[] = $one_category[ 'category_name' ];
                        $category_full_name[] = $v[ 'category_name' ];
                    } elseif ($v[ 'level' ] == 3) {
                        $category_id_3 = $v[ 'category_id' ];
                        $two_category = $this->getInfo("ns_goods_category", [ [ 'category_id', '=', $v[ 'pid' ] ] ], 'category_id,pid,category_name');
                        $one_category = $this->getInfo("ns_goods_category", [ [ 'category_id', '=', $two_category[ 'pid' ] ] ], 'category_id,category_name');
                        $category_id_1 = $one_category[ 'category_id' ];
                        $category_id_2 = $two_category[ 'category_id' ];

                        $category_full_name[] = $one_category[ 'category_name' ];
                        $category_full_name[] = $two_category[ 'category_name' ];
                        $category_full_name[] = $v[ 'category_name' ];
                    }
                    $data[] = [
                        'category_id' => $v[ 'category_id' ],
                        'site_id' => $this->site_id,
                        'category_name' => $v[ 'category_name' ],
                        'short_name' => $v[ 'short_name' ],
                        'pid' => $v[ 'pid' ],
                        'level' => $v[ 'level' ],
                        'is_show' => $v[ 'is_visible' ],
                        'sort' => $v[ 'sort' ],
                        'image' => $v[ 'category_pic' ],
                        'keywords' => $v[ 'keywords' ],
                        'description' => $v[ 'description' ],
                        'category_id_1' => $category_id_1,
                        'category_id_2' => $category_id_2,
                        'category_id_3' => $category_id_3,
                        'category_full_name' => implode("/", $category_full_name),
                        'image_adv' => '',
                        'commission_rate' => 0
                    ];
                }
                model("goods_category")->addList($data);
            }

            model('goods_category')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods_category')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取需要迁移商品分类数量
     * @return int
     */
    public function getGoodsCategoryCount()
    {
        return $this->getCount('ns_goods_category', [], 'category_id');
    }

    /**
     * 迁移商品标签数据
     * 丢失数据：上下级、图片、是否显示
     * @param $page_index
     * @param $page_size
     * @return array
     */
    public function getGoodsLabelList($page_index, $page_size)
    {
        try {
            model("goods_label")->startTrans();

            // 查询v3商品标签表
            $field = 'group_id, group_name, is_visible, sort, group_dec';
            $goods_group_list = $this->getPageList('ns_goods_group', [], $field, $page_index, $page_size);

            if (!empty($goods_group_list)) {
                if ($page_index == 1) {
                    // 首次清空商品标签表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('goods_label')->execute("TRUNCATE TABLE {$prefix}goods_label");
                }
                $data = [];
                foreach ($goods_group_list as $k => $v) {
                    $data[] = [
                        'id' => $v[ 'group_id' ],
                        'site_id' => $this->site_id,
                        'label_name' => $v[ 'group_name' ],
                        'desc' => $v[ 'group_dec' ],
                        'create_time' => time(),
                        'update_time' => 0,
                        'sort' => $v[ 'sort' ]
                    ];
                }
                model("goods_label")->addList($data);
            }

            model("goods_label")->commit();
            return $this->success();
        } catch (\Exception $e) {
            model("goods_label")->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取需要迁移商品标签数量
     * @return int
     */
    public function getGoodsLabelCount()
    {
        return $this->getCount('ns_goods_group', [], 'group_id');
    }

}