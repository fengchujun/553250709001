<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\form\shop\controller;

use addon\form\model\Form as FormModel;
use addon\form\model\FormData as FormDataModel;
use app\model\order\Order as OrderModel;
use app\shop\controller\BaseShop;
use think\App;

/**
 * 分销订单
 */
class Form extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'FORM_JS' => __ROOT__ . '/addon/form/shop/view/public/js',
            'FORM_CSS' => __ROOT__ . '/addon/form/shop/view/public/css',
            'FORM_IMG' => __ROOT__ . '/addon/form/shop/view/public/img'
        ];
        parent::__construct($app);
    }

    /**
     * 编辑添加系统表单
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function editForm()
    {
        $from_model = new FormModel();
        if (request()->isJson()) {
            $id = input('id', '');
            $form_name = input('form_name', '');
            $json_data = input('json_data', '');
            $data = [
                'form_name' => $form_name,
                'json_data' => $json_data,
                'modify_time' => time()
            ];
            $res = $from_model->editForm($data, [ [ 'id', '=', $id ], [ 'site_id', '=', $this->site_id ] ]);
            return $res;
        }
        $id = input('id', '');
        $info = $from_model->getFormInfo([ [ 'id', '=', $id ], [ 'site_id', '=', $this->site_id ] ]);
        if (empty($info[ 'data' ])) $this->error('未获取到表单数据');

        $this->assign('component', FormModel::FORM_COMPONENT);
        $this->assign('info', $info[ 'data' ]);
        $this->assign('form_type', ( new FormModel() )->form_type[ $info[ 'data' ][ 'form_type' ] ]);

        return $this->fetch('form/edit');
    }

    /**
     * 添加表单
     * @return array|mixed
     */
    public function addForm()
    {
        if (request()->isJson()) {
            $form_name = input('form_name', '');
            $json_data = input('json_data', '');
            $form_type = input('form_type', 'order');
            $data = [
                'form_name' => $form_name,
                'json_data' => $json_data,
                'form_type' => $form_type,
                'site_id' => $this->site_id,
                'create_time' => time(),
                'is_use' => $form_type != 'order' ? 1 : 0
            ];
            $from_model = new FormModel();
            $res = $from_model->addForm($data);
            return $res;
        }
        $form_type = input('form_type', 'order');
        $this->assign('component', FormModel::FORM_COMPONENT);
        $this->assign('form_type', ( new FormModel() )->form_type[ $form_type ]);
        return $this->fetch('form/edit');
    }

    /**
     * 是否开启
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function editIsUse()
    {
        $from_model = new FormModel();
        $is_use = input('is_use', '');
        $id = input('id', '');
        $form_type = input('form_type', '');
        if (empty($is_use) && empty($id)) {
            $this->error('id和is_use不可为空');
        }
        //启用之前先把该类型的全都不启用
        if ($form_type == 'order') {
            $from_model->editForm([ 'is_use' => 0 ], [ [ 'form_type', '=', 'order' ], [ 'site_id', '=', $this->site_id ] ]);
        }

        $data = [
            'is_use' => $is_use
        ];

        $res = $from_model->editForm($data, [ [ 'id', '=', $id ], [ 'site_id', '=', $this->site_id ] ]);
        return $res;
    }

    /**
     * 删除数据
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function deleteForm()
    {
        $id = input('id', '');
        if (empty($id)) {
            $this->error('id不可为空');
        }
        $from_model = new FormModel();
        $res = $from_model->deleteForm($id, $this->site_id);
        return $res;
    }

    /**
     * 表单列表
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function lists()
    {
        if (request()->isJson()) {
            $form_type = input('form_type', '');
            $form_name = input('form_name', '');
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $is_use = input('is_use', '');
            $condition = [];
            $condition[] = [ 'site_id', '=', $this->site_id ];
            if (!empty($form_type)) {
                $condition[] = [ 'form_type', '=', $form_type ];
            }
            if (!empty($form_name)) {
                $condition[] = [ 'form_name', 'like', '%' . $form_name . '%' ];
            }
            if (!empty($is_use)) {
                $condition[] = [ 'is_use', '=', $is_use ];
            }
            $from_model = new FormModel();
            $res = $from_model->getFormPageList($condition, $page, $page_size);

            return $res;
        }
        $this->assign('form_type', ( new FormModel() )->form_type);
        return $this->fetch('form/lists');
    }


    /**
     * 导出表单列表
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function exportForm()
    {
        $id = input('id', '');
        if (empty($id)) {
            $this->error('id不可为空');
        }
        $from_model = new FormModel();
        $res = $from_model->export([ 'id' => $id, 'site_id' => $this->site_id ]);
        if (isset($res[ 'code' ]) && $res[ 'code' ] != 0) $this->error($res[ 'message' ]);
    }

    /**
     * 获取详情
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function formInfo()
    {
        $id = input('id', '');
        $condition[] = [ 'site_id', '=', $this->site_id ];
        if (empty($id)) {
            $this->error('id不可为空');
        }
        $condition[] = [ 'id', '=', $id ];
        $from_model = new FormModel();
        $res = $from_model->getFormInfo($condition);
        return $res;
    }

    /**
     * 查看表单数据
     * @param $game_data
     * @param $award_json
     * @return array
     */
    public function formData()
    {
        $form_id = input('form_id', '');
        if (request()->isJson()) {
            if (empty($form_id)) {
                return error(-1, 'id不可为空');
            }
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $nickname = input('nickname', '');
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            $condition[] = [ 'fd.form_id', '=', $form_id ];
            $condition[] = [ 'fd.site_id', '=', $this->site_id ];

            if ($start_time && !$end_time) {
                $condition[] = [ 'fd.create_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'fd.create_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'fd.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            if (!empty($nickname)) $condition[] = [ 'm.nickname', 'like', "%{$nickname}%" ];

            $alias = 'fd';
            $join = [
                [
                    'member m',
                    'fd.member_id = m.member_id',
                    'inner'
                ],

            ];
            $field = 'fd.*,m.headimg,m.nickname,m.status,m.member_level_type';

            $from_model = new FormDataModel();
            $res = $from_model->getFormDataPageList($condition, $field, 'fd.id desc', $page, $page_size, $alias, $join);
            if (!empty($res[ 'data' ][ 'list' ])) {
                $order_model = new OrderModel();
                foreach ($res[ 'data' ][ 'list' ] as $k => &$item_v) {
                    $item_v[ 'form_data' ] = json_decode($item_v[ 'form_data' ], true);
                    if ($item_v[ 'scene' ] == 'order') {
                        $item_v[ 'order_info' ] = $order_model->getOrderInfo([ [ 'order_id', '=', $item_v[ 'relation_id' ] ] ], 'order_id,order_no')[ 'data' ];
                    } elseif ($item_v[ 'scene' ] == 'goods') {
                        $item_v[ 'order_goods_info' ] = $order_model->getOrderGoodsInfo([ [ 'order_goods_id', '=', $item_v[ 'relation_id' ] ] ], 'order_goods_id,order_id,order_no')[ 'data' ];
                    }
                }
            }
            return $res;
        } else {
            $this->assign('form_id', $form_id);
            return $this->fetch('form/formdata');
        }
    }

    /**
     * 查询表单列表
     * @return array
     */
    public function getFormList()
    {
        if (request()->isJson()) {
            $form_type = input('form_type', 'goods');
            $is_use = input('is_use', 1);

            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'form_type', '=', $form_type ],
                [ 'is_use', '=', $is_use ]
            ];
            return ( new FormModel() )->getFormList($condition, 'id desc', 'id,form_name');
        }
    }

    /**
     * 推广
     * @return array
     */
    public function promote()
    {
        if (request()->isJson()) {
            $form_id = input('form_id', '');
            $app_type = input('app_type', 'all');
            $from_model = new FormModel();
            $res = $from_model->urlQrcode('/pages_tool/form/form', [ 'id' => $form_id ], $app_type, $this->site_id);
            return $res;
        }
    }

    /**
     * 删除表单数据
     * @return array|string|string[]
     */
    public function deleteFormData()
    {
        if (request()->isJson()) {
            $id = input('id', '');
            if (empty($id)) return error(-1, 'id不可为空');

            $from_model = new FormDataModel();
            $res = $from_model->deleteFormData([ [ 'id', '=', $id ], [ 'site_id', '=', $this->site_id ] ]);
            return $res;
        }
    }
}