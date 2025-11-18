<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\form\api\controller;

use app\api\controller\BaseApi;
use addon\form\model\Form as FormModel;
use app\model\goods\Goods;

class Form extends BaseApi
{
    /**
     * 获取商品表单数据
     * @return false|string
     */
    public function goodsForm()
    {
        if (!addon_is_exit('form', $this->site_id)) return $this->response( $this->success([]) );

        $goods_id = $this->params['goods_id'] ?? 0;

        $condition = [
            ['g.site_id', '=', $this->site_id ],
            ['g.goods_id', '=', $goods_id ],
            ['g.form_id', '>', 0 ],
            ['f.is_use', '=', 1 ]
        ];
        $data = (new Goods())->getGoodsInfo($condition, 'f.json_data', 'g', [ ['form f', 'g.form_id = f.id', 'left'] ])['data'];
        if (!empty($data)) {
            return $this->response( $this->success( json_decode($data['json_data'], true) ) );
        }
        return $this->response( $this->success($data) );
    }

    /**
     * 获取表单信息
     * @return false|string
     */
    public function info(){
        $form_id = $this->params['form_id'] ?? 0;

        $condition = [
            ['site_id', '=', $this->site_id ],
            ['id', '=', $form_id ],
            ['is_use', '=', 1 ],
            ['form_type', '=', 'custom' ]
        ];
        $data = (new FormModel())->getFormInfo($condition, 'json_data');
        if (!empty($data['data'])) {
            $data['data']['json_data'] = json_decode($data['data']['json_data'], true);
        }
        return $this->response($data);
    }

    /**
     * 添加表单数据
     * @return false|string
     */
    public function create(){
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $form_id = $this->params['form_id'] ?? 0;
        $form_data = $this->params[ 'form_data' ] ?? '[]';

        $data = [
            'site_id' => $this->site_id,
            'form_id' => $form_id,
            'member_id' => $this->member_id,
            'relation_id' => 0,
            'form_data' => json_decode($form_data, true),
            'scene' => 'custom'
        ];

        $res = (new FormModel())->addFormData($data);
        return $this->response($res);
    }
}