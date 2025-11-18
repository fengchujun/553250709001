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

use app\model\BaseModel;
use think\facade\Db;

class GoodsCommon extends BaseModel
{
    /**
     * 刷新SKU商品规格项/规格值JSON字符串
     * @param int $goods_id 商品id
     * @param string $goods_spec_format 商品完整规格项/规格值json
     */
    public function dealGoodsSkuSpecFormat($goods_id, $goods_spec_format)
    {
        if (empty($goods_spec_format)) return;

        $goods_spec_format = json_decode($goods_spec_format, true);

        //根据goods_id查询sku商品列表，查询：sku_id、sku_spec_format 列
        $sku_list = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ], [ 'sku_spec_format', '<>', '' ] ], 'sku_id,sku_spec_format', 'sku_id asc');
        if (!empty($sku_list)) {

//			$temp = 0;//测试性能，勿删

            //循环SKU商品列表
            foreach ($sku_list as $k => $v) {
//				$temp++;

                $sku_format = $goods_spec_format;//最终要存储的值
                $current_format = json_decode($v[ 'sku_spec_format' ], true);//当前SKU商品规格值json

                $selected_data = [];//已选规格/规格值json

                //1、找出已选规格/规格值json

                //循环完整商品规格json
                foreach ($sku_format as $sku_k => $sku_v) {
//					$temp++;

                    //循环当前SKU商品规格json
                    foreach ($current_format as $current_k => $current_v) {
//						$temp++;

                        //匹配规格项
                        if ($current_v[ 'spec_id' ] == $sku_v[ 'spec_id' ]) {

                            //循环规格值
                            foreach ($sku_v[ 'value' ] as $sku_value_k => $sku_value_v) {
//								$temp++;

                                //匹配规格值id
                                if ($current_v[ 'spec_value_id' ] == $sku_value_v[ 'spec_value_id' ]) {
                                    $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ] = true;
                                    $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'sku_id' ] = $v[ 'sku_id' ];
                                    $selected_data[] = $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ];
                                    break;
                                }
                            }

                        }

                    }
                }

                //2、找出未选中的规格/规格值json
                foreach ($sku_format as $sku_k => $sku_v) {
//					$temp++;

                    foreach ($sku_v[ 'value' ] as $sku_value_k => $sku_value_v) {
//						$temp++;

                        if (!isset($sku_value_v[ 'selected' ])) {

                            $refer_data = [];//参考已选中的规格/规格值json
                            $refer_data[] = $sku_value_v;

//							根据已选中的规格值进行参考
                            foreach ($selected_data as $selected_k => $selected_v) {
//								$temp++;
//								排除自身，然后进行参考
                                if ($selected_v[ 'spec_id' ] != $sku_value_v[ 'spec_id' ]) {
                                    $refer_data[] = $selected_v;
                                }
                            }

                            foreach ($sku_list as $again_k => $again_v) {
//								$temp++;

                                //排除当前SKU商品
                                if ($again_v[ 'sku_id' ] != $v[ 'sku_id' ]) {

                                    $current_format_again = json_decode($again_v[ 'sku_spec_format' ], true);
                                    $count = count($current_format_again);//规格总数量
                                    $curr_count = 0;//当前匹配规格数量

                                    //循环当前SKU商品规格json
                                    foreach ($current_format_again as $current_again_k => $current_again_v) {
//										$temp++;

                                        foreach ($refer_data as $fan_k => $fan_v) {
//											$temp++;

                                            if ($current_again_v[ 'spec_value_id' ] == $fan_v[ 'spec_value_id' ]) {
                                                $curr_count++;
                                            }
                                        }

                                    }

//									匹配数量跟规格总数一致表示匹配成功
                                    if ($curr_count == $count) {
                                        $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ] = false;
                                        $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'sku_id' ] = $again_v[ 'sku_id' ];
                                        break;
                                    }
                                }

                            }

                            //没有匹配到规格值，则禁用
                            if (!isset($sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ])) {
                                $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'disabled' ] = false;
//                                var_dump(json_encode($sku_format));
//                                var_dump('==========');
                            }

                        }
                    }
                }

//				var_dump($sku_format);
//				var_dump("=========");
                //修改ns_goods_sku表表中的goods_spec_format字段，将$sku_format值传入
                model('goods_sku')->update([ 'goods_spec_format' => json_encode($sku_format) ], [ [ 'sku_id', '=', $v[ 'sku_id' ] ] ]);

            }

//			var_dump("性能：" . $temp);

        }

    }

    /**
     * 检测商品编码重复
     * @param $params
     * @return array
     */
    public function checkSkuNoRepeat($params)
    {
        /*$params = [
            'sku_list' => [],
            'site_id' => 1,
            'goods_id' => 0,
        ];*/

        //查看配置是否开启
        $config_model = new \app\model\web\Config();
        $info = $config_model->getGoodsNo($params['site_id'])['data']['value'];
        if ($info['uniqueness_switch'] == 0) {
            return $this->success();
        }

        //商品本身编码重复检测
        $all_sku_no_arr = [];
        foreach($params['sku_list'] as $sku_info){
            if(!empty($sku_info['sku_no'])){
                $sku_arr = explode(',', $sku_info['sku_no']);
                foreach($sku_arr as $sku_no){
                    if(in_array($sku_no, $all_sku_no_arr)){
                        return $this->error(null, "编码[{$sku_no}]不可重复录入");
                    }
                    $all_sku_no_arr[] = $sku_no;
                }

            }
        }
        if(empty($all_sku_no_arr)) return $this->success();

        //与其他商品是否重复检测
        $sql_arr = [];
        foreach($all_sku_no_arr as $sku_no){
            $sql_arr[] = "FIND_IN_SET('{$sku_no}', sku_no)";
        }
        $condition = [
            ['site_id', '=', $params['site_id']],
            ['', 'exp', Db::raw(join(' or ', $sql_arr))],
            ['is_delete', '=', 0]
        ];
        if (!empty($params['goods_id'])) {
            $condition[] = ['goods_id', '<>', $params['goods_id']];
        }
        $info = model('goods_sku')->getInfo($condition, 'sku_no');
        if (!empty($info)) {
            $exist_sku_no_arr = array_intersect($all_sku_no_arr, explode(',', $info['sku_no']));
            $exist_sku_no_arr = array_values($exist_sku_no_arr);
            return $this->error(null, "条码[{$exist_sku_no_arr[0]}]已存在");
        }
        return $this->success();
    }

    /**
     * 删除商品规格检测
     * @param $sku_ids
     * @return array
     */
    public function deleteGoodsSkuCheck($sku_ids)
    {
        $check_res = event('DeleteGoodsCheck', ['ids' => $sku_ids, 'field' => 'sku_id']);
        foreach ($check_res as $val){
            if(!empty($val['cannot_delete_ids'])){
                $sku_list = model('goods_sku')->getList([['sku_id', 'in', $val['cannot_delete_ids']]], 'sku_name,spec_name');
                $spec_name = $sku_list[0]['spec_name'];
                if(empty($spec_name)) $spec_name = '默认规格';
                return $this->error(null, "规格[".$spec_name."]".$val['reason']."，不可删除");
            }
        }
        return $this->success();
    }

    /**
     * 矫正商品属性数据
     * @param $goods_info
     */
    public function correctGoodsSpecFormat($goods_info)
    {
        if(!empty($goods_info['goods_spec_format'])){
            $goods_spec_format = json_decode($goods_info['goods_spec_format'], true);
            $spec_value_ids = [];
            $repeat_data = [];
            $min_spec_value_id = 0;
            foreach($goods_spec_format as $spec_index => $spec){
                foreach($spec['value'] as $spec_value_index => $spec_value){
                    $spec_value_id = $spec_value['spec_value_id'];
                    if(!in_array($spec_value_id, $spec_value_ids)){
                        $spec_value_ids[] = $spec_value_id;
                    }else{
                        $repeat_data[] = [
                            'spec_index' => $spec_index,
                            'spec_value_index' => $spec_value_index,
                        ];
                    }
                    if($spec_value_id < $min_spec_value_id){
                        $min_spec_value_id = $spec_value_id;
                    }
                }
            }
            if(!empty($repeat_data)){
                foreach($repeat_data as $val){
                    $min_spec_value_id -= 1;
                    $goods_spec_format[$val['spec_index']]['value'][$val['spec_value_index']]['spec_value_id'] = $min_spec_value_id;
                }
            }
            $goods_info['goods_spec_format'] = json_encode($goods_spec_format);

            $goods_spec_format = array_column($goods_spec_format, null, 'spec_name');
            foreach($goods_spec_format as &$spec){
                $spec['value'] = array_column($spec['value'], null, 'spec_value_name');
            }

            foreach($goods_info['sku_list'] as &$sku_info){
                $sku_spec_format = json_decode($sku_info['sku_spec_format'], true);
                foreach($sku_spec_format as &$spec_value){
                    $spec_value_id = $goods_spec_format[$spec_value['spec_name']]['value'][$spec_value['spec_value_name']]['spec_value_id'] ?? null;
                    if(!is_null($spec_value_id) && $spec_value['spec_value_id'] != $spec_value_id){
                        $spec_value['spec_value_id'] = $spec_value_id;
                    }
                }
                $sku_info['sku_spec_format'] = json_encode($sku_spec_format);
            }
        }
        return $goods_info;
    }

    /**
     * 处理数据
     * @param $item_list
     * @return mixed
     */
    public function getCategoryNames($item_list)
    {
        //分类id数据 服务商品goods_class=4，分类字段是service_category
        $category_ids_arr = [];
        $service_category_ids_arr = [];
        foreach($item_list as $item){
            if($item['goods_class'] != 4){
                $category_ids = trim($item['category_id'], ',');
                if($category_ids) $category_ids_arr[] = $category_ids;
            }else{
                $service_category_ids = trim($item['service_category'], ',');
                if($service_category_ids) $service_category_ids_arr[] = $service_category_ids;
            }
        }
        //所有分类数据
        $category_ids = array_unique(explode(',', join(',', $category_ids_arr)));
        $category_list = model('goods_category')->getList([['category_id', 'in', $category_ids]], 'category_id,category_name');
        $category_list = array_column($category_list, null, 'category_id');
        //所有服务分类数据
        $service_category_ids = array_unique(explode(',', join(',', $service_category_ids_arr)));
        $service_category_list = model('service_category')->getList([['category_id', 'in', $service_category_ids]], 'category_id,category_name');
        $service_category_list = array_column($service_category_list, null, 'category_id');

        foreach ($item_list as &$item_v) {
            //分类数据
            $category_names_arr = [];
            if($item_v['goods_class'] != 4){
                if(!empty($item_v['category_json'])){
                    $category_ids_arr = json_decode($item_v['category_json'], true);
                    foreach($category_ids_arr as $category_ids){
                        $category_ids = explode(',', $category_ids);
                        $category_names = [];
                        foreach($category_ids as $category_id){
                            $category_name = $category_list[$category_id]['category_name'] ?? '';
                            if($category_name) $category_names[] = $category_name;
                        }
                        $category_names = join('/',$category_names);
                        $category_names_arr[] = $category_names;
                    }
                }
            }else{
                $category_names = [];
                $category_ids = explode(',', trim($item_v['service_category']));
                foreach($category_ids as $category_id){
                    $category_name = $service_category_list[$category_id]['category_name'] ?? '';
                    if($category_name) $category_names[] = $category_name;
                }
                $category_names = join('/',$category_names);
                $category_names_arr[] = $category_names;
            }
            $item_v['category_names'] = join(';', $category_names_arr);
        }
        return $item_list;
    }


    /**
     * 检测商品核销日期
     */

    public function checkVirtualDate($verify_validity_type,$virtual_indate=''){
        if ($verify_validity_type == 1) {
            if (empty($virtual_indate)) {
                return $this->error('', '有效期不能为空');
            }
        } else if ($verify_validity_type == 2) {
            if (empty($virtual_indate)) {
                return $this->error('', '有效期不能为空');
            }
            if($virtual_indate < time()){
                return $this->error('', '核销有效期不能小于当前日期');
            }
        }
        return $this->success();
    }
}
