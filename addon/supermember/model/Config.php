<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\supermember\model;

use app\model\BaseModel;
use app\model\system\Document;

/**
 * 会员卡订单
 */
class Config extends BaseModel
{
    /**
     * 注册协议
     * @param unknown $site_id
     * @param unknown $name
     * @param unknown $value
     */
    public function setMemberCardDocument($title, $content, $site_id, $app_module = 'shop')
    {
        $document = new Document();
        $res = $document->setDocument($title, $content, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['document_key', '=', 'MEMBER_CARD_AGREEMENT']]);
        return $res;
    }

    /**
     * 查询注册协议
     * @param unknown $where
     * @param unknown $field
     * @param unknown $value
     */
    public function getMemberCardDocument($site_id, $app_module = 'shop')
    {
        $document = new Document();
        $info = $document->getDocument([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['document_key', '=', 'MEMBER_CARD_AGREEMENT']]);
        return $info;
    }
}