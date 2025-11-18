<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\goods;

use addon\supply\model\Supplier as SupplierModel;
use app\model\BaseModel;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsBrand as GoodsBrandModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\goods\GoodsLabel as GoodsLabelModel;
use app\model\system\Export as ExportModel;

/**
 * 商品导出
 * @author Administrator
 */
class GoodsExport extends BaseModel
{
    public function export($input, $param)
    {
        $site_id = $param['site_id'];
        
        $search_text = $input['search_text'] ?? '';
        $goods_state = $input['goods_state'] ?? '';
        $start_sale = $input['start_sale'] ?? 0;
        $end_sale = $input['end_sale'] ?? 0;
        $start_price = $input['start_price'] ?? 0;
        $end_price = $input['end_price'] ?? 0;
        $goods_class = $input['goods_class'] ?? '';
        $label_id = $input['label_id'] ?? '';
        $brand_id = $input['brand_id'] ?? '';
        $sku_no = $input['sku_no'] ?? '';
        $promotion_type = $input['promotion_type'] ?? '';
        $category_id = $input['category_id'] ?? '';
        $stockalarm = $input['stockalarm'] ?? 0;
        $goods_ids = $input['goods_ids'] ?? '';
        $supplier_id = $input['supplier_id'] ?? 0;

        $goods_model = new GoodsModel();
        $is_install_supply = addon_is_exit('supply');

        $condition_desc = [];
        $condition = [
            ['g.is_delete', '=', 0],
            ['g.site_id', '=', $site_id],
        ];
        if (!empty($goods_ids)) {
            $condition[] = ['g.goods_id', 'in', explode(',', $goods_ids)];
            $condition_desc[] = ['name' => 'goods_id', 'value' => $goods_ids];
        }
        if (!empty($search_text)) {
            $condition[] = ['g.goods_name', 'like', '%' . $search_text . '%'];
            $condition_desc[] = ['name' => '商品名称', 'value' => $search_text];
        }
        if (!empty($sku_no)) {
            $condition[] = ['gs.sku_no', 'like', '%' . $sku_no . '%'];
            $condition_desc[] = ['name' => '商品编码', 'value' => $sku_no];
        }
        if (!empty($category_id)) {
            $condition[] = ['g.category_id', 'like', '%,' . $category_id . ',%'];
            $goods_category_model = new GoodsCategoryModel();
            $category_info = $goods_category_model->getCategoryInfo([
                ['category_id', '=', $category_id],
                ['site_id', '=', $site_id]
            ], 'category_full_name')[ 'data' ];
            if (!empty($category_info)) {
                $condition_desc[] = ['name' => '商品分类', 'value' => $category_info[ 'category_full_name' ]];
            }
        }
        if (!empty($brand_id)) {
            $condition[] = ['g.brand_id', '=', $brand_id];
            $goods_brand_model = new GoodsBrandModel();
            $goods_brand_info = $goods_brand_model->getBrandInfo([
                ['site_id', '=', $site_id],
            ], 'brand_name')[ 'data' ];
            if (!empty($goods_brand_info)) {
                $brand_name = $goods_brand_info[ 'brand_name' ];
                $condition_desc[] = ['name' => '商品品牌', 'value' => $brand_name];
            }
        }
        if ($goods_class !== '') {
            $condition[] = ['g.goods_class', '=', $goods_class];
            $goods_class_info = array_column(event('GoodsClass'), null, 'goods_class');
            $goods_class_name = $goods_class_info[ $goods_class ][ 'goods_class_name' ];
            $condition_desc[] = ['name' => '商品类型', 'value' => $goods_class_name];
        }
        if (!empty($label_id)) {
            $condition[] = ['g.label_id', '=', $label_id];
            $goods_label_model = new GoodsLabelModel();
            $label_info = $goods_label_model->getLabelInfo([
                ['site_id', '=', $site_id],
                ['id', '=', $label_id]
            ], 'label_name')[ 'data' ];
            if (!empty($label_info)) {
                $label_name = $label_info[ 'label_name' ];
                $condition_desc[] = ['name' => '商品标签', 'value' => $label_name];
            }
        }
        if (!empty($promotion_type)) {
            $condition[] = ['g.promotion_addon', 'like', "%{$promotion_type}%"];
            $goods_promotion_type = event('GoodsPromotionType');
            $goods_promotion_type = array_column($goods_promotion_type, null, 'type');
            $condition_desc[] = ['name' => '营销活动', 'value' => $goods_promotion_type[$promotion_type]['name'] ?? ''];
        }
        if ($goods_state !== '') {
            $condition[] = ['g.goods_state', '=', $goods_state];
            $condition_desc[] = ['name' => '商品状态', 'value' => $goods_state == 1 ? '销售中' : '仓库中'];
        }
        if ($is_install_supply) {
            $supplier_model = new SupplierModel();
            $supplier_info = $supplier_model->getSupplierInfo([['supplier_id', '=', $supplier_id]], 'title')['data'];
            if(!empty($supplier_info)){
                $condition[] = ['g.supplier_id', '=', $supplier_id];
                $condition_desc[] = ['name'=> '供应商', 'value' => $supplier_info['title']];
            }
        }
        if (!empty($start_sale)) $condition[] = ['g.sale_num', '>=', $start_sale];
        if (!empty($end_sale)) $condition[] = ['g.sale_num', '<=', $end_sale];
        $sale_name = '';
        if (!empty($start_sale) && empty($end_sale)) {
            $sale_name = '大于等于'.$start_sale;
        } elseif (empty($start_sale) && !empty($end_sale)) {
            $sale_name = '小于等于'.$end_sale;
        } elseif (!empty($start_sale) && !empty($end_sale)) {
            $sale_name = $start_sale . '到' . $end_sale;
        }
        if($sale_name) $condition_desc[] = [ 'name' => '商品销量', 'value' => $sale_name ];
        
        if (!empty($start_price)) $condition[] = ['gs.price', '>=', $start_price];
        if (!empty($end_price)) $condition[] = ['gs.price', '<=', $end_price];
        $price_name = '';
        if (!empty($start_price) && empty($end_price)) {
            $price_name = '大于等于'.$start_price;
        } elseif (empty($start_price) && !empty($end_price)) {
            $price_name = '小于等于'.$end_price;
        } elseif (!empty($start_price) && !empty($end_price)) {
            $price_name = $start_price . '到' . $end_price;
        }
        if($price_name) $condition_desc[] = [ 'name' => '商品价格', 'value' => $price_name ];

        // 查询库存预警的商品
        if ($stockalarm) {
            $stock_alarm = $goods_model->getGoodsStockAlarm($site_id);
            if(empty($stock_alarm[ 'data' ])) $stock_alarm[ 'data' ] = '-1';
            $condition[] = ['g.goods_id', 'in', $stock_alarm[ 'data' ]];
        }

        $join = [];
        $join[] = ['goods g', "g.goods_id = gs.goods_id", 'left'];
        if($is_install_supply){
            $join[] = ['supplier s', 'g.supplier_id = s.supplier_id', 'left'];
        }

        $table_field = 'g.goods_class,g.category_id,g.category_json,g.service_category,gs.sku_id, gs.sku_no, gs.sku_name, gs.goods_state, gs.goods_class_name, gs.price, gs.market_price, gs.cost_price, gs.stock, gs.stock_alarm, gs.real_stock, gs.weight, gs.volume, gs.unit, gs.sale_num, gs.click_num, gs.collect_num, gs.keywords, gs.introduction, gs.brand_name';
        if($is_install_supply){
            $table_field .= ',IF(s.title is null, "", s.title) as supplier_name';
        }

        $export_field = [
            ['field' => 'sku_no', 'name' => '商品编码'],
            ['field' => 'sku_name', 'name' => '商品名称'],
            ['field' => 'goods_class_name', 'name' => '商品类型'],
            ['field' => 'category_names', 'name' => '商品分类'],
            ['field' => 'goods_state', 'name' => '商品状态'],
            ['field' => 'price', 'name' => '销售价'],
            ['field' => 'market_price', 'name' => '划线价'],
            ['field' => 'cost_price', 'name' => '成本价'],
            ['field' => 'stock', 'name' => '库存'],
            ['field' => 'stock_alarm', 'name' => '库存预警'],
            ['field' => 'real_stock', 'name' => '实物库存'],
            ['field' => 'weight', 'name' => '重量（单位g）'],
            ['field' => 'volume', 'name' => '体积（单位立方米）'],
            ['field' => 'unit', 'name' => '单位'],
            ['field' => 'sale_num', 'name' => '销量'],
            ['field' => 'click_num', 'name' => '点击量'],
            ['field' => 'collect_num', 'name' => '收藏量'],
            ['field' => 'keywords', 'name' => '关键词'],
            ['field' => 'introduction', 'name' => '促销语'],
            ['field' => 'brand_name', 'name' => '品牌名称'],
        ];
        if($is_install_supply) $export_field[] = ['field' => 'supplier_name', 'name' => '供应商'];

        $param = [
            'site_id' => $site_id,
            'from_type' => 'goods',
            'from_type_name' => '商品',
            'condition_desc' => $condition_desc,
            'query' => [
                'table' => 'goods_sku',
                'alias' => 'gs',
                'join' => $join,
                'condition' => $condition,
                'field' => $table_field,
                'chunk_field' => 'gs.sku_id',
                'chunk_order' => 'asc',
            ],
            'export_field' => $export_field,
            'handle' => function($item_list){
                return $this->handle($item_list);
            },
        ];
        $export_model = new ExportModel();
        return $export_model->export($param);
    }

    /**
     * 处理数据
     * @param $item_list
     * @return mixed
     */
    protected function handle($item_list)
    {
        //获取分类数据
        $goods_model = new Goods();
        $item_list = $goods_model->getCategoryNames($item_list);

        //处理其他字段
        $format_field = ['goods_num','num','stock','stock_alarm','sale_num','virtual_sale','real_stock'];
        foreach ($item_list as &$item_v) {
            foreach($format_field as $field_v){
                if (isset($item_v[$field_v])) {
                    $item_v[$field_v] = numberFormat($item_v[$field_v]);
                }
            }
            $item_v[ 'goods_state' ] = $item_v[ 'goods_state' ] == 1 ? '销售中' : '仓库中';
        }
        return $item_list;
    }
}
