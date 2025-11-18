<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use app\model\system\Export as ExportModel;

class Export extends BaseShop
{
    /**
     * 导出记录
     * @return mixed
     */
    public function lists($param = [])
    {
        //传参格式
        /*$param = [
            'from_type_list' => [
                ['id' => 'store_account', 'name' => '门店账户'],
            ],
            'lists_url' => 'store://shop/account/exportList',
            'delete_url' => 'store://shop/account/deleteExport',
        ];*/
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $from_type = input('from_type', '');
            $status = input('status', 'all');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            $export_model = new ExportModel();
            $condition = array (
                ['site_id', '=', $this->site_id],
                ['from_type', 'in', $from_type],
            );
            if($status !== 'all'){
                $condition[] = ['status', '=', $status];
            }
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ 'create_time', '>=', date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', '<=', date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $result = $export_model->getExportPageList($condition, $page_index, $page_size, 'create_time desc', '*');
            return $result;
        } else {
            $this->assign('from_type_list', $param['from_type_list']);
            $this->assign('lists_url', $param['lists_url']);
            $this->assign('delete_url', $param['delete_url']);
            $this->assign('status_list', ExportModel::getStatus());
            return $this->fetch('app/shop/view/export/lists.html');
        }
    }

    /**
     * 删除导出记录
     */
    public function delete($from_type)
    {
        if (request()->isJson()) {
            $export_ids = input('export_ids', '');

            $export_model = new ExportModel();
            $condition = array (
                [ 'site_id', '=', $this->site_id ],
                [ 'export_id', 'in', (string) $export_ids ],
                ['from_type', 'in', $from_type],
            );
            $result = $export_model->deleteExport($condition);
            return $result;
        }
    }
}