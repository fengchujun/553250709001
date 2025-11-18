<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecommend\api\controller;

use addon\memberrecommend\model\Poster;
use app\api\controller\BaseApi;
use addon\memberrecommend\model\MemberRecommend as MemberRecommendModel;

/**
 * 邀请奖励
 */
class Memberrecommend extends BaseApi
{

    /**
     * 信息
     */
    public function info()
    {
        $memberRecommend_model = new MemberRecommendModel();
        $res = $memberRecommend_model->getRecommendFirstData($this->site_id);
        return $this->response($res);
    }

    /**
     * 奖励列表
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;

        $memberRecommend_model = new MemberRecommendModel();
        $condition = [
            [ 'member_id', '=', $this->member_id ],
            [ 'site_id', '=', $this->site_id ]
        ];
        $res = $memberRecommend_model->getRecommendAwardPageList($condition, $page, $page_size);

        return $this->response($res);
    }

    /**
     * 海报
     * @return false|string
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $qrcode_param[ 'source_member' ] ?? $this->member_id;

        $poster = new Poster();
        $res = $poster->poster($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $this->site_id);
        return $this->response($res);
    }
}