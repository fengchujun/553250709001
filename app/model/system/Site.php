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
use app\model\upload\Upload;
use think\facade\Cache;

/**
 * 站点管理
 * @author Administrator
 *
 */
class Site extends BaseModel
{
    public $cache_model = 'cache_model_site';

    /**
     * 添加站点
     * @param $data
     * @return array
     */
    public function addSite($data)
    {
        $res = model('site')->add($data);
        return $this->success($res);
    }

    /**
     * getSiteInfo 获取站点详情
     * @param $condition
     * @param string $fields
     * @return array
     */
    public function getSiteInfo($condition, $fields = '*')
    {
        $res = model('site')->getInfo($condition, $fields);
        return $this->success($res);
    }

    /**
     * 修改商城站点信息
     * @param $site_data
     * @param $condition
     * @return int
     */
    public function editSite($site_data, $condition)
    {
        $site_info = $this->getSiteInfo($condition);
        if($site_info['data'] && $site_data['logo'] && $site_info['data']['logo'] != $site_data['logo']){
            $upload_model = new Upload();
            $upload_model->deletePic($site_info['data']['logo'], $site_info['data']['site_id']);
        }
        if($site_info['data'] && !empty($site_data['logo_square']) && $site_info['data']['logo_square'] != $site_data['logo_square']){
            $upload_model = new Upload();
            $upload_model->deletePic($site_info['data']['logo_square'], $site_info['data']['site_id']);
        }
        $res = model('site')->update($site_data, $condition);
        if($res && $site_data['logo']){
            if(file_exists($site_data['logo']))
            {
                copy($site_data['logo'],"public/static/img/default_img/login.png");
            }

        }
        Cache::tag($this->cache_model )->clear();
        return $this->success($res);
    }
}