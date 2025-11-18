<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\offlinepay\api\controller;

use addon\offlinepay\model\Config as ConfigModel;
use addon\offlinepay\model\Pay as PayModel;
use app\api\controller\BaseApi;
use app\model\upload\Upload as UploadModel;

/**
 * 线下支付
 */
class Pay extends BaseApi
{
    /**
     * 配置
     */
    public function config()
    {
        $config_model = new ConfigModel();
        $res = $config_model->getPayConfig($this->site_id);
        return $this->response($res);
    }

    /**
     * 信息
     */
    public function info()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $out_trade_no = $this->params['out_trade_no'] ?? '';
        $pay_model = new PayModel();
        $res = $pay_model->getInfo([['out_trade_no', '=', $out_trade_no], ['member_id', '=', $this->member_id]]);
        return $this->response($res);
    }

    /**
     * 支付
     */
    public function pay()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $pay_model = new PayModel();
        $res = $pay_model->pay([
            'member_id' => $this->member_id,
            'out_trade_no' => $this->params['out_trade_no'] ?? '',
            'imgs' => $this->params['imgs'] ?? '',
            'desc' => $this->params['desc'] ?? ''
        ]);
        return $this->response($res);
    }

    /**
     * 图片上传
     */
    public function uploadImg()
    {
        $upload_model = new UploadModel(0);
        $param = [
            'thumb_type' => '',
            'name' => 'file',
            'cloud' => 1,
        ];
        $result = $upload_model->setPath('offlinepay/' . date('Ymd') . '/')->image($param);
        return $this->response($result);
    }
}