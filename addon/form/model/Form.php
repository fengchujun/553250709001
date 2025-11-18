<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\form\model;

use app\model\BaseModel;
use think\facade\Cache;

/**
 * 系统表单
 */
class Form extends BaseModel
{

    public $form_type = [
        'order' => [
            'type' => 'order',
            'name' => '统一下单',
            'preview_img' => 'addon/form/shop/view/public/img/preview/order.png',
            'head_img' => 'addon/form/shop/view/public/img/head/order.png'
        ],
        'goods' => [
            'type' => 'goods',
            'name' => '商品表单',
            'preview_img' => 'addon/form/shop/view/public/img/preview/goods.png',
            'head_img' => 'addon/form/shop/view/public/img/head/goods.png'
        ],
        'custom' => [
            'type' => 'custom',
            'name' => '自定义表单',
            'preview_img' => 'addon/form/shop/view/public/img/preview/custom.png',
            'head_img' => 'addon/form/shop/view/public/img/head/custom.png'
        ]
    ];

    /**
     * 表单组件
     */
    const FORM_COMPONENT = [
        'ONE_LINE_TEXT' => [
            'name' => 'ONE_LINE_TEXT',
            'title' => '单行文本',
            'type' => 'SYSTEM',
            'controller' => 'Text',
            'value' => [
                'title' => '单行文本框',
                'placeholder' => '请输入提示语',
                'default' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/on_line_text.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/on_line_text_selected.png'
        ],
        'MANY_LINE_TEXT' => [
            'name' => 'MANY_LINE_TEXT',
            'title' => '多行文本',
            'type' => 'SYSTEM',
            'controller' => 'Textarea',
            'value' => [
                'title' => '多行文本',
                'placeholder' => '请输入提示语',
                'default' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/many_line_text.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/many_line_text_selected.png'
        ],
        'ID_CARD' => [
            'name' => 'ID_CARD',
            'title' => '身份证号码',
            'type' => 'SYSTEM',
            'controller' => 'Text',
            'value' => [
                'title' => '身份证号码',
                'placeholder' => '请输入身份证号码',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/id_card.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/id_card_selected.png'
        ],
        'SELECT' => [
            'name' => 'SELECT',
            'title' => '下拉框',
            'type' => 'SYSTEM',
            'controller' => 'Select',
            'value' => [
                'title' => '下拉框',
                'options' => [
                    '选项一',
                    '选项二'
                ],
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/select.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/select_selected.png'
        ],
        'CHECKBOX' => [
            'name' => 'CHECKBOX',
            'title' => '多选框',
            'type' => 'SYSTEM',
            'controller' => 'Checkbox',
            'value' => [
                'title' => '多选框',
                'options' => [
                    '选项一',
                    '选项二'
                ],
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/checkbox.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/checkbox_selected.png'
        ],
        'RADIO' => [
            'name' => 'RADIO',
            'title' => '单选框',
            'type' => 'SYSTEM',
            'controller' => 'Radio',
            'value' => [
                'title' => '单选框',
                'options' => [
                    '选项一',
                    '选项二'
                ],
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/radio.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/radio_selected.png'
        ],
        'IMG' => [
            'name' => 'IMG',
            'title' => '图片',
            'type' => 'SYSTEM',
            'controller' => 'Img',
            'value' => [
                'title' => '图片',
                'max_count' => 3,
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/img.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/img_selected.png'
        ],
        'DATE' => [
            'name' => 'DATE',
            'title' => '日期',
            'type' => 'SYSTEM',
            'controller' => 'Date',
            'value' => [
                'title' => '日期',
                'placeholder' => '请输入提示语',
                'is_show_default' => true,
                'is_current' => true,
                'default' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/date.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/date_selected.png'
        ],
        'DATE_LIMIT' => [
            'name' => 'DATE_LIMIT',
            'title' => '日期范围',
            'type' => 'SYSTEM',
            'controller' => 'Datelimit',
            'value' => [
                'title' => '日期范围',
                'placeholder_start' => '请输入起始日期提示语',
                'placeholder_end' => '请输入结束日期提示语',
                'is_show_default_start' => true,
                'is_show_default_end' => true,
                'is_current_start' => true,
                'is_current_end' => true,
                'default_start' => '',
                'default_end' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/date_limit.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/date_limit_selected.png'
        ],
        'TIME' => [
            'name' => 'TIME',
            'title' => '时间',
            'type' => 'SYSTEM',
            'controller' => 'Time',
            'value' => [
                'title' => '时间',
                'placeholder' => '请输入提示语',
                'is_show_default' => true,
                'is_current' => true,
                'default' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/time.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/time_selected.png'
        ],
        'TIME_LIMIT' => [
            'name' => 'TIME_LIMIT',
            'title' => '时间范围',
            'type' => 'SYSTEM',
            'controller' => 'Timelimit',
            'value' => [
                'title' => '时间范围',
                'placeholder_start' => '请输入起始时间提示语',
                'placeholder_end' => '请输入结束时间提示语',
                'is_show_default_start' => true,
                'is_show_default_end' => true,
                'is_current_start' => true,
                'is_current_end' => true,
                'default_start' => '',
                'default_end' => '',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/time_limit.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/time_limit_selected.png'
        ],
        'CITY' => [
            'name' => 'CITY',
            'title' => '城市',
            'type' => 'SYSTEM',
            'controller' => 'City',
            'value' => [
                'title' => '城市',
                'placeholder' => '请输入提示语',
                'default_type' => 1,
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/city.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/city_selected.png'
        ],
        'MOBILE' => [
            'name' => 'MOBILE',
            'title' => '手机号码',
            'type' => 'SYSTEM',
            'controller' => 'Text',
            'value' => [
                'title' => '手机号码',
                'placeholder' => '请输入手机号码',
                'required' => true
            ],
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'addon/form/shop/view/public/img/icon/mobile.png',
            'icon_selected' => 'addon/form/shop/view/public/img/icon/mobile_selected.png'
        ],
    ];

    /**
     * 添加自定义模板
     * @param $data
     * @return array
     */
    public function addForm($data)
    {
        $res = model('form')->add($data);
        if ($res) {
            Cache::tag('form')->clear();
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }


    /**
     * 修改自定义模板
     * @param array $data
     * @param array $condition
     * @return array
     */
    public function editForm($data, $condition)
    {
        $res = model('form')->update($data, $condition);
        if ($res) {
            Cache::tag('form')->clear();
            return $this->success($res);
        } else {
            return $this->error($res);
        }
    }

    /**
     * 获取自定义模板信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getFormInfo($condition = [], $field = '*', $alias = 'a', $join = [])
    {
        $info = model('form')->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 删除表单
     * @param array $condition
     * @return array
     */
    public function deleteForm($id, $site_id)
    {
        model('form')->startTrans();

        $res = model('form')->delete([ [ 'id', '=', $id ], [ 'site_id', '=', $site_id ] ]);
        if ($res) {
            model('form_data')->delete([ [ 'form_id', '=', $id ], [ 'site_id', '=', $site_id ] ]);
            model('form')->commit();
            Cache::tag('form')->clear();
            return $this->success($res);
        } else {
            model('form')->rollback();
            return $this->error($res);
        }
    }

    /**
     * 获取表单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getFormPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = '*')
    {
        $list = model('form')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取表单列表
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getFormList($condition = [], $order = 'id desc', $field = '*')
    {
        $list = model('form')->getList($condition, $field, $order);
        return $this->success($list);
    }

    /**
     * 查询订单数据并导出
     * @param $condition
     */
    public function export($param)
    {
        $form_info = model('form')->getInfo([ [ 'id', '=', $param[ 'id' ] ], [ 'site_id', '=', $param[ 'site_id' ] ] ], 'form_name,id,json_data');
        if (empty($form_info)) return $this->error('', '未获取到表单信息');
        $form_info[ 'json_data' ] = json_decode($form_info[ 'json_data' ], true);

        $form_data_list = model('form_data')->getList([ [ 'fd.form_id', '=', $form_info[ 'id' ] ] ], 'fd.form_data,fd.create_time,m.nickname', 'id desc', 'fd', [ [ 'member m', 'm.member_id = fd.member_id', 'left' ] ]);
        if (empty($form_data_list)) return $this->error('', '表单没有可导出的数据');

        $phpExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $phpExcel->getProperties()->setTitle('表单数据');
        $phpExcel->getProperties()->setSubject('表单数据');
        $sheet = $phpExcel->getActiveSheet();

        // 创建表头
        foreach ($form_info[ 'json_data' ] as $k => $form_item) {
            $sheet->getCellByColumnAndRow(( $k + 1 ), 1)->setValue($form_item[ 'value' ][ 'title' ]);
        }
        $sheet->getCellByColumnAndRow(( count($form_info[ 'json_data' ]) + 1 ), 1)->setValue('会员');
        $sheet->getCellByColumnAndRow(( count($form_info[ 'json_data' ]) + 2 ), 1)->setValue('填写时间');

        // 填充数据
        foreach ($form_data_list as $data_k => $data_item) {
            foreach ($form_info[ 'json_data' ] as $k => $form_item) {
                $form_data = array_column(json_decode($data_item[ 'form_data' ], true), null, 'id');
                if (!empty($form_data[ $form_item[ 'id' ] ])) {
                    $sheet->getCellByColumnAndRow(( $k + 1 ), ( $data_k + 2 ))->setValue($form_data[ $form_item[ 'id' ] ][ 'val' ] ?? '');
                }
            }
            $sheet->getCellByColumnAndRow(( count($form_info[ 'json_data' ]) + 1 ), ( $data_k + 2 ))->setValue($data_item[ 'nickname' ] ?: '');
            $sheet->getCellByColumnAndRow(( count($form_info[ 'json_data' ]) + 2 ), ( $data_k + 2 ))->setValue(time_to_date($data_item[ 'create_time' ]));
        }

        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpExcel, 'Xlsx');
        $file = date('Y年m月d日-' . $form_info[ 'form_name' ] . '数据导出', time()) . '.xlsx';
        $objWriter->save($file);

        header('Content-type:application/octet-stream');

        $filename = basename($file);
        header('Content-Disposition:attachment;filename = ' . $filename);
        header('Accept-ranges:bytes');
        header('Accept-length:' . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    /**
     * 添加表单数据
     * @param $param
     */
    public function addFormData($param)
    {
        $info = model('form')->getInfo([ [ 'id', '=', $param[ 'form_id' ] ], [ 'site_id', '=', $param[ 'site_id' ] ] ]);
        if (empty($info)) return $this->error('', '该表单不存在');
        if (!$info[ 'is_use' ]) return $this->error('', '该表单未启用');

        $id = model('form_data')->add([
            'site_id' => $param[ 'site_id' ],
            'form_id' => $param[ 'form_id' ],
            'member_id' => $param[ 'member_id' ],
            'relation_id' => $param[ 'relation_id' ],
            'create_time' => time(),
            'form_data' => json_encode($param[ 'form_data' ]),
            'scene' => $param[ 'scene' ]
        ]);
        return $this->success($id);
    }

    public function urlQrcode($page, $qrcode_param, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => '',
            'app_type' => $app_type,
            'h5_path' => $page . '?id=' . $qrcode_param[ 'id' ],
            'qrcode_path' => 'upload/qrcode/form',
            'qrcode_name' => 'form_qrcode_' . $qrcode_param[ 'id' ] . '_' . $site_id
        ];

        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}