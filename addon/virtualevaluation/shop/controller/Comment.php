<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\virtualevaluation\shop\controller;

use addon\virtualevaluation\model\VirtualEvaluation;
use addon\virtualevaluation\model\VirtualStock;
use app\model\express\ExpressTemplate as ExpressTemplateModel;
use app\model\goods\Goods;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\goods\GoodsLabel as GoodsLabelModel;
use app\model\goods\GoodsService as GoodsServiceModel;
use app\model\upload\Album;
use app\shop\controller\BaseShop;


/**
 * 虚拟评价
 * Class Virtualgoods
 * @package app\shop\controller
 */
class Comment extends BaseShop
{
    public function goodsLists()
    {
        $stockalarm = input('stockalarm', 0);
        $goods_model = new GoodsModel();
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', "");
            $goods_state = input('goods_state', "");
            $start_sale = input('start_sale', 0);
            $end_sale = input('end_sale', 0);
            $start_price = input('start_price', 0);
            $end_price = input('end_price', 0);
            $goods_class = input('goods_class', "");
            $label_id = input('label_id', "");
            $order = input('order', '');
            $sort = input('sort', 'asc');

            $order_by = 'create_time desc';
            if ($order != '') {
                if ($order == 'sort') {
                    $order_by = $order . ' ' . $sort . ',create_time desc';
                } else {
                    $order_by = $order . ' ' . $sort;
                }
            }
            $promotion_type = input('promotion_type', "");

            $condition = [ [ 'is_delete', '=', 0 ], [ 'site_id', '=', $this->site_id ] ];

            if (!empty($search_text)) {
                $condition[] = [ 'goods_name', 'like', '%' . $search_text . '%' ];
            }
            $category_id = input('category_id', "");
            if (!empty($category_id)) {
                $condition[] = [ 'category_id', 'like', '%,' . $category_id . ',%' ];
            }

            if ($goods_class !== "") {
                $condition[] = [ 'goods_class', '=', $goods_class ];
            }

            if (!empty($label_id)) {
                $condition[] = [ 'label_id', '=', $label_id ];
            }

            if (!empty($promotion_type)) {
                $condition[] = [ 'promotion_addon', 'like', "%{$promotion_type}%" ];
            }

            // 上架状态
            if ($goods_state !== '') {
                $condition[] = [ 'goods_state', '=', $goods_state ];
            }
            if (!empty($start_sale)) $condition[] = [ 'sale_num', '>=', $start_sale ];
            if (!empty($end_sale)) $condition[] = [ 'sale_num', '<=', $end_sale ];
            if (!empty($start_price)) $condition[] = [ 'price', '>=', $start_price ];
            if (!empty($end_price)) $condition[] = [ 'price', '<=', $end_price ];

            // 查询库存预警的商品
            if ($stockalarm) {
                $stock_alarm = $goods_model->getGoodsStockAlarm($this->site_id);
                if (!empty($stock_alarm[ 'data' ])) {
                    $condition[] = [ 'goods_id', 'in', $stock_alarm[ 'data' ] ];
                } else {
                    return success(0, '', [ 'page_count' => 1, 'count' => 0, 'list' => [] ]);
                }
            }
            $field = 'goods_id,goods_name,site_id,site_name,goods_image,goods_state,price,goods_stock,goods_stock_alarm,create_time,sale_num,is_virtual,goods_class,is_fenxiao,fenxiao_type,promotion_addon,sku_id,is_consume_discount,discount_config,discount_method,sort,evaluate,evaluate_shaitu,success_evaluate_num,fail_evaluate_num,wait_evaluate_num';
            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size, $order_by, $field);
            $goods_promotion_type = event('GoodsPromotionType');
            if (!empty($res[ 'data' ][ 'list' ])) {
                foreach ($res[ 'data' ][ 'list' ] as $k => $v) {

                    $res[ 'data' ][ 'list' ][ $k ][ 'goods_stock' ] = numberFormat($res[ 'data' ][ 'list' ][ $k ][ 'goods_stock' ]);
                    $res[ 'data' ][ 'list' ][ $k ][ 'sale_num' ] = numberFormat($res[ 'data' ][ 'list' ][ $k ][ 'sale_num' ]);

                    if (!empty($v[ 'promotion_addon' ])) {
                        $v[ 'promotion_addon' ] = json_decode($v[ 'promotion_addon' ], true);
                        foreach ($v[ 'promotion_addon' ] as $ck => $cv) {
                            foreach ($goods_promotion_type as $gk => $gv) {
                                if ($gv[ 'type' ] == $ck) {
                                    $res[ 'data' ][ 'list' ][ $k ][ 'promotion_addon_list' ][] = $gv;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            return $res;
        } else {

            $goods_state = input('state', '');
            $this->assign('goods_state', $goods_state);
            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                [ 'pid', '=', 0 ],
                [ 'site_id', '=', $this->site_id ]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate');
            $goods_category_list = $goods_category_list[ 'data' ];
            $this->assign("goods_category_list", $goods_category_list);

            // 商品分组
            $goods_label_model = new GoodsLabelModel();
            $label_list = $goods_label_model->getLabelList([ [ 'site_id', '=', $this->site_id ] ], 'id,label_name', 'create_time desc')[ 'data' ];
            $this->assign("label_list", $label_list);

            // 商品服务
            $goods_service_model = new GoodsServiceModel();
            $service_list = $goods_service_model->getServiceList([ [ 'site_id', '=', $this->site_id ] ], 'id,service_name,icon')[ 'data' ];
            $this->assign("service_list", $service_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->site_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            //判断会员价插件
            $memberprice_is_exit = addon_is_exit('memberprice', $this->site_id);
            $this->assign('memberprice_is_exit', $memberprice_is_exit);

            // 判断采集插件
            $goodsgrab_is_exit = addon_is_exit('goodsgrab', $this->site_id);
            $this->assign('goodsgrab_is_exit', $goodsgrab_is_exit);

            // 营销活动
            $goods_promotion_type = event('GoodsPromotionType');
            $this->assign('promotion_type', $goods_promotion_type);

            $this->assign('virtualcard_exit', addon_is_exit('virtualcard', $this->site_id));

            $this->assign('stockalarm', $stockalarm);

            return $this->fetch("comment/goods_lists");
        }
    }

    /**
     * 创建虚拟评价
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function setting()
    {
        $goods_ids = input('goods_ids', 0);
        $goods_model = new Goods();
        $goods_data = $goods_model->getGoodsList([ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $this->site_id ] ], 'sku_id,goods_id,goods_name,price,goods_image');

        $data = json_decode(input('field'), true);

        if (!empty($data[ 'member_headimg' ])) {
            $data[ 'member_headimg' ] = $data[ 'member_headimg' ][ 0 ];
        }
        $pingjia_img = '';
        if (!empty($data[ 'pingjia_img' ])) {
            foreach ($data[ 'pingjia_img' ] as $k => $val) {
                $pingjia_img = $pingjia_img . ',' . $val;
            }
            $pingjia_img = trim($pingjia_img, ',');
        }

        $data[ 'pingjia_img' ] = $pingjia_img;
        $data[ 'goods_data' ] = '';
        if (!empty($goods_data[ 'data' ])) {
            $data[ 'goods_data' ] = $goods_data[ 'data' ];
        }
        $VirtualEvaluation_model = new VirtualEvaluation();
        $res = $VirtualEvaluation_model->addGoodsComment($data, $this->site_id);
        return $res;
    }

    /**
     * 虚拟评价库
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function stock()
    {
        if (request()->isJson()) {
            $stock_model = new VirtualStock();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                'site_id' => $this->site_id
            ];
            $stock_data = $stock_model->getStockPageList($condition, $page, $page_size, '', '');
            return $stock_data;
        } else {

            return $this->fetch("comment/stock");
        }
    }

    /**
     * 添加虚拟评价库
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function addStock()
    {
        $stock_model = new VirtualStock();
        $stock_name = input('stock_name', '');
        $data = [
            'stock_name' => $stock_name,
            'site_id' => $this->site_id,
            'create_time' => time(),
        ];
        return $stock_model->addStock($data);
    }

    /**
     * 删除虚拟评价库
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function deleteStock()
    {
        $stock_model = new VirtualStock();
        $stock_id = input('stock_id', '');

        return $stock_model->deleteStock($stock_id, $this->site_id);
    }

    /**
     * 获取虚拟评价库信息
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function getStockInfo()
    {
        $stock_model = new VirtualStock();
        $stock_id = input('stock_id', '');
        return $stock_model->getStockInfo($stock_id, $this->site_id);
    }

    /**
     * 编辑虚拟评价库
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function editStock()
    {
        $stock_model = new VirtualStock();
        $stock_id = input('stock_id', 0);
        $stock_name = input('stock_name', '');
        $data = [
            'stock_name' => $stock_name,
            'modify_time' => time(),
            'stock_id' => $stock_id
        ];

        return $stock_model->updateStock($data, $this->site_id);
    }

    /**
     * 获取虚拟评价库里的评论信息
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function getContents()
    {
        $stock_id = input('stock_id', 0);
        if (request()->isJson()) {
            $stock_model = new VirtualStock();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                'site_id' => $this->site_id,
                'stock_id' => $stock_id,
            ];
            $stock_data = $stock_model->getContentsPageList($condition, $page, $page_size, '', '');
            return $stock_data;
        } else {
            $this->assign('stock_id', $stock_id);
            return $this->fetch("comment/contents");
        }
    }

    /**
     * 添加虚拟评价库评论
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function addContent()
    {
        $stock_model = new VirtualStock();
        $stock_id = input('stock_id', 0);
        $content = input('content', '');
        $data = [
            'stock_id' => $stock_id,
            'site_id' => $this->site_id,
            'content' => $content,
            'create_time' => time(),
        ];
        return $stock_model->addContent($data);
    }

    /**
     * 删除虚拟评价库评论
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function deleteContent()
    {
        $stock_model = new VirtualStock();
        $id = input('id', 0);

        return $stock_model->deleteContent($id, $this->site_id);
    }

    /**
     * 获取虚拟评价库评论信息
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function getContentInfo()
    {
        $stock_model = new VirtualStock();
        $id = input('id', 0);

        return $stock_model->getContentInfo($id, $this->site_id);
    }

    /**
     * 修改虚拟评价库评论信息
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function editContent()
    {
        $stock_model = new VirtualStock();
        $id = input('id', 0);
        $content = input('content', '');
        $data = [
            'content' => $content,
            'id' => $id
        ];
        return $stock_model->editContentInfo($data, $this->site_id);
    }

    /**
     * 批量添加单个商品的评论
     * Class Virtualgoods
     * @package app\shop\controller
     */
    public function batchAdd()
    {
        $album_model = new Album();
        $stock_model = new VirtualStock();
        $goods_id = input('goods_id', 0);
        if (request()->isJson()) {
            $start_time = date_to_time(input('start_time'));
            $end_time = date_to_time(input('end_time'));
            if ($start_time == '' || $end_time == '' || $end_time < $start_time) return error('-1', '时间格式不正确');
            $virtual_evaluation_model = new VirtualEvaluation();
            $data = [
                'goods_id' => input('goods_id', 0),
                'number' => input('number', 0),
                'pingfen' => input('pingfen', ''),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'album_id' => input('album_id', 0),
                'stock_id' => input('stock_id', 0)
            ];
            $res = $virtual_evaluation_model->batghAdd($data, $this->site_id);
            return $res;
        } else {
            //获取相册
            $album_list = $album_model->getAlbumList([ [ 'site_id', '=', $this->site_id ], [ 'num', '>', 0 ] ]);
            $this->assign('album_list', $album_list[ 'data' ]);
            //获取评论库
            $stock_list = $stock_model->getStockList([ [ 'site_id', '=', $this->site_id ], [ 'num', '>', 0 ] ]);
            $this->assign('stock_list', $stock_list[ 'data' ]);
            $this->assign('goods_id', $goods_id);
            return $this->fetch("comment/batchadd");
        }
    }

}