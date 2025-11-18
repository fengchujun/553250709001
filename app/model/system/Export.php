<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use think\facade\Db;

class Export extends BaseModel
{
    const STATUS_IN_PROCESS = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;
    
    static public function getStatus($key = null)
    {
        $arr = [
            [
                'id' => self::STATUS_IN_PROCESS,
                'name' => '导出中',
            ],
            [
                'id' => self::STATUS_SUCCESS,
                'name' => '导出成功',
            ],
            [
                'id' => self::STATUS_FAIL,
                'name' => '导出失败',
            ],
        ];
        if(isset($arr[0][$key])){
            $arr = array_column($arr, null, $key);
        }
        return $arr;
    }

    protected $data_num = 0;

    /**
     * 导出
     * @param $param
     * @return array
     */
    public function export($param)
    {
        //传参说明
        /*$param = [
            'site_id' => $site_id,
            'store_id' => $export_store_id,
            'from_type' => 'store_account',
            'from_type_name' => '门店账户',
            'condition_desc' => $condition_desc,
            'export_field' => [
                ['field' => 'store_name', 'name' => '门店名称'],
                ['field' => 'type_name', 'name' => '来源方式'],
                ['field' => 'account_data', 'name' => '记录金额'],
                ['field' => 'create_time', 'name' => '发生时间'],
                ['field' => 'remark', 'name' => '备注'],
                ['field' => 'related_no', 'name' => '关联编号'],
            ],
            //数据库查询相关参数
            'query' => [
                'table' => 'store_account',
                'alias' => 'sa',
                'join' => [
                    ['store s', 'sa.store_id = s.store_id', 'inner'],
                ],
                'condition' => $condition,
                'field' => 'sa.*,s.store_name',
                'chunk_field' => 'sa.id',
                'chunk_order' => 'asc',
            ],
            //数据库查询结果处理 这个其实应该放在query中
            'handle' => function($item_list){
                return $item_list;
            },
            //直接传递导出的数据
            'data' => [],
        ];*/

        set_time_limit(0);

        $site_id = $param['site_id'];
        $store_id = $param['store_id'] ?? 0;
        $from_type = $param['from_type'];
        $from_type_name = $param['from_type_name'];
        $condition_desc= $param['condition_desc'];
        $export_field = $param['export_field'];
        $query = $param['query'] ?? null;//table alias join condition field order
        $handle = $param['handle'] ?? null;//数据处理

        try {
            //预先创建导出的记录
            $data = array(
                'condition' => json_encode($condition_desc, JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'from_type' => $from_type,
                'from_type_name' => $from_type_name,
                'status' => self::STATUS_IN_PROCESS,
                'site_id' => $site_id,
                'store_id' => $store_id,
            );
            $records_result = $this->addExport($data);
            $export_id = $records_result['data'];
            if (empty($export_id)) return $this->error(null, '创建导出记录失败');
            $this->data_num = 0;

            //创建目录
            $file_path = 'upload/export/'.$from_type.'/';
            if (!dir_mkdir($file_path)) return $this->error(null, '导出目录创建失败');

            //创建并打开文件
            $file_name = $from_type_name.date('YmdHis');//csv文件名
            $file_path = $file_path . $file_name . '.csv';
            $fp = fopen($file_path, 'w');
            fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            //写入第一行表头
            fwrite($fp, join(',', array_column($export_field, 'name'))."\n");

            //导出数据
            if(isset($param['query'])){
                $export_table = Db::name($query['table'])->where($query['condition']);
                if(!empty($query['alias'])) $export_table = $export_table->alias($query['alias']);
                if(!empty($query['join'])) $export_table = $this->parseJoin($export_table, $query['join']);
                if(!empty($query['group'])) $export_table = $export_table->group($query['group']);
                $export_table->field($query['field'])->chunk(1000, function ($item_list) use ($fp, $export_field, $handle) {
                    $item_list = $item_list->toArray();
                    if(!empty($handle)){
                        $item_list = $handle($item_list);
                    }
                    $this->data_num += count($item_list);
                    $this->itemExport($item_list, $export_field, $fp);
                    unset($item_list);
                }, $query['chunk_field'], $query['chunk_order']);
                $export_table->removeOption();
            }else if(isset($param['data'])){
                $this->itemExport($param['data'], $export_field, $fp);
            }else{
                throw new \Exception('query参数和data参数至少要传一个');
            }

            fclose($fp);  //每生成一个文件关闭
            unset($export_table);

            //更新导出记录
            $this->editExport([
                'path' => $file_path,
                'status' => self::STATUS_SUCCESS,
                'data_num' => $this->data_num,
            ], [['export_id', '=', $export_id]]);

            //返回导出信息
            $export_info = $this->getExportInfo([['export_id', '=', $export_id]])['data'];
            return $this->success($export_info);
        } catch (\Exception $e ) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            if(isset($export_id)){
                //更新导出记录
                $this->editExport([
                    'status' => self::STATUS_FAIL,
                    'fail_log' => json_encode($error, JSON_UNESCAPED_UNICODE),
                ], [['export_id', '=', $export_id]]);
            }
            return $this->error($error, '导出失败');
        }
    }

    /**
     * 给csv写入新的数据
     * @param $item_list
     * @param $field_key
     * @param $temp_line
     * @param $fp
     */
    protected function itemExport($item_list, $export_field, $fp)
    {
        if(method_exists($item_list, 'toArray')){
            $item_list = $item_list->toArray();
        }
        foreach ($item_list as $item_info) {
            $values = [];
            foreach($export_field as $field_info){
                if(isset($item_info[$field_info['field']])){
                    $value = $item_info[$field_info['field']];
                    $value = trim($value);
                    $value = str_replace(',', '，', $value . "\t");
                    $value = str_replace("\n", '', $value);
                    $value = str_replace("\r", '', $value);
                    $values[] = $value;
                }
            }
            //写入数据
            fwrite($fp, join(',', $values)."\n");
            //销毁变量, 防止内存溢出
            unset($new_line_value);
        }
    }

    /**
     * 解析关联
     * @param $db_obj
     * @param $join
     * @return mixed
     */
    protected function parseJoin($db_obj, $join)
    {
        foreach ($join as $item) {
            list($table, $on, $type) = $item;
            $type = strtolower($type);
            switch ($type) {
                case 'left':
                    $db_obj = $db_obj->leftJoin($table, $on);
                    break;
                case 'inner':
                    $db_obj = $db_obj->join($table, $on);
                    break;
                case 'right':
                    $db_obj = $db_obj->rightjoin($table, $on);
                    break;
                case 'full':
                    $db_obj = $db_obj->fulljoin($table, $on);
                    break;
                default:
                    break;
            }
        }
        return $db_obj;
    }

    /**
     * 添加导出记录
     * @param $data
     * @return array
     */
    public function addExport($data)
    {
        $res = model('export')->add($data);
        return $this->success($res);
    }

    /**
     * 更新导出记录
     * @param $data
     * @param $condition
     * @return array
     */
    public function editExport($data, $condition)
    {
        $res = model('export')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除导出记录
     * @param $condition
     * @return array
     */
    public function deleteExport($condition)
    {
        //先查询数据
        $list = model('export')->getList($condition, '*');
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (file_exists($v['path'])) {
                    //删除物理文件路径
                    if (!unlink($v['path'])) {
                        //失败
                    } else {
                        //成功
                    }
                }
            }
            $res = model('export')->delete($condition);
        }
        return $this->success($res ?? '');
    }

    public function getExportInfo($condition, $field = '*')
    {
        $info = model('export')->getInfo($condition, $field);
        $info = $this->handleData($info);
        return $this->success($info);
    }

    /**
     * 获取导出记录
     * @param $condition
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getExportList($condition, $field = '*', $order = '')
    {
        $list = model('export')->getList($condition, $field, $order);
        foreach($list as &$val){
            $val = $this->handleData($val);
        }
        return $this->success($list);
    }

    /**
     * 导出记录
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getExportPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('export')->pageList($condition, $field, $order, $page, $page_size);
        foreach($list['list'] as &$val){
            $val = $this->handleData($val);
        }
        return $this->success($list);
    }

    protected function handleData($data)
    {
        if(isset($data['status'])){
            $status_list = self::getStatus('id');
            $data['status_name'] = $status_list[$data['status']]['name'] ?? '';
        }
        return $data;
    }
}
