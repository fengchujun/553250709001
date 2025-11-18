<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\member;

use app\model\system\Document;
use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 会员设置
 */
class Config extends BaseModel
{
    /**
     * 注册协议
     * @param $title
     * @param $content
     * @param unknown $site_id
     * @param string $app_module
     * @return array
     */
    public function setRegisterDocument($title, $content, $site_id, $app_module = 'shop')
    {
        $document = new Document();
        $res = $document->setDocument($title, $content, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'REGISTER_AGREEMENT' ] ]);
        return $res;
    }

    /**
     * 查询注册协议
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getRegisterDocument($site_id, $app_module = 'shop')
    {
        $document = new Document();
        $info = $document->getDocument([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'REGISTER_AGREEMENT' ] ]);
        return $info;
    }

    /**
     * 隐私协议
     * @param $title
     * @param $content
     * @param unknown $site_id
     * @param string $app_module
     * @return array
     */
    public function setPrivacyConfig($title, $content, $site_id, $app_module = 'shop')
    {
        $document = new Document();
        $res = $document->setDocument($title, $content, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'PRIVACY_AGREEMENT' ] ]);
        return $res;
    }

    /**
     * 查询隐私协议
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getPrivacyDocument($site_id, $app_module = 'shop')
    {
        $document = new Document();
        $info = $document->getDocument([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'PRIVACY_AGREEMENT' ] ]);
        return $info;
    }

    /**
     * 注册规则
     * array $data
     */
    public function setRegisterConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '注册规则', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'REGISTER_CONFIG' ] ]);
        return $res;
    }

    /**
     * 查询注册规则
     */
    public function getRegisterConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'REGISTER_CONFIG' ] ]);
        $res['data']['value'] = array_merge([
            'login' => 'username,mobile',//登录类型 账号 手机号
            'register' => 'username,mobile',//注册类型 账号 手机号
            'third_party' => 1,//允许三方平台自动注册
            'bind_mobile' => 0,//是否强制绑定手机
            'pwd_len' => 6,//密码最小长度
            'pwd_complexity' => '',//密码复杂程度
            'agreement_show' => 1,//是否显示政策协议
            'wap_bg' => '',//手机端背景图
            'wap_desc' => '',//描述
        ], $res['data']['value']);
        return $res;
    }


    /**
     * 注销协议
     * @param $title
     * @param $content
     * @param unknown $site_id
     * @param string $app_module
     * @return array
     */
    public function setCancelDocument($title, $content, $site_id, $app_module = 'shop')
    {
        $document = new Document();
        $res = $document->setDocument($title, $content, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'CANCEL_AGREEMENT' ] ]);
        return $res;
    }

    /**
     * 查询注销协议
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getCancelDocument($site_id, $app_module = 'shop')
    {
        $document = new Document();
        $info = $document->getDocument([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'document_key', '=', 'CANCEL_AGREEMENT' ] ]);
        return $info;
    }


    /**
     * 注销规则
     * array $data
     */
    public function setCancelConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '注销规则', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'CANCEL_CONFIG' ] ]);
        return $res;
    }

    /**
     * 查询注销规则
     */
    public function getCancelConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'CANCEL_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            //默认值设置
            $res[ 'data' ][ 'value' ] = [
                'is_enable' => 0,  //注销开关
                'is_audit' => 1, //审核开关
            ];
        }
        return $res;
    }

    /**
     * 查询会员配置
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function getMemberConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'MEMBER_LEVEL_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            //默认值设置
            $res[ 'data' ][ 'value' ] = [
                'is_update' => 1,
            ];
        } else {
            $value = $res[ 'data' ][ 'value' ];
            $value[ 'is_update' ] = $value[ 'is_update' ] ?? 1;
            $res[ 'data' ][ 'value' ] = $value;
        }
        return $res;
    }

    /**
     * 积分任务
     * @param $data
     * @param $site_id
     * @param string $app_module
     * @return array
     */
    public function setMemberConfig($data, $site_id, $app_module = 'shop')
    {

        $config = new ConfigModel();
        $res = $config->setConfig($data, '会员等级更新配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'MEMBER_LEVEL_CONFIG' ] ]);
        return $res;
    }
}