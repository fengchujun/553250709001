<?php
/**
 * NiuShop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 */

namespace addon\niusms\shop\controller;

use addon\niusms\model\Config as ConfigModel;
use addon\niusms\model\Sms as SmsModel;
use addon\niusms\model\Template;
use app\shop\controller\BaseShop;

/**
 * 牛云短信消息管理
 */
class Message extends BaseShop
{

    /**
     * 编辑模板消息
     * @return mixed|void
     */
    public function edit()
    {
        $message_model = new Template();
        $sms_model = new SmsModel();
        $keywords = input("keywords", "");

        $info = $message_model->getTemplateInfo($this->site_id, $keywords)[ 'data' ];

        if (empty($info)) {
            $this->error("不存在的模板信息！");
            return;
        }

        $audit_status = $sms_model->getAuditStatus();
        $info[ 'audit_status_name' ] = $audit_status[ $info[ 'audit_status' ] ];

        $this->assign("info", $info);
        $this->assign("keywords", $keywords);

        $this->assign('sms_is_open', $info[ 'sms_is_open' ]);

        $template_info = $this->queryTemplate($info[ 'tem_id' ]);
        $this->assign('template_info', $template_info);
        return $this->fetch('message/edit');
    }

    /**
     * 查询短信模板审核状态
     * @param string $tem_id
     * @return array|mixed
     */
    public function queryTemplate($tem_id = '')
    {
        $sms_model = new SmsModel();
        $config_model = new ConfigModel();
        $sms_config = $config_model->getSmsConfig($this->site_id, $this->app_module)[ 'data' ][ 'value' ];
        $tKey = time();
        $data = [
            'username' => $sms_config[ 'username' ],
            'password' => md5(md5($sms_config[ 'password' ]) . $tKey),
            'tKey' => $tKey,
            'sign' => input('signature', $sms_config[ 'signature' ]),//短信签名
            'temId' => input('tem_id', $tem_id)//模板id
        ];
        $res = $sms_model->queryTemplate($data);
        return $res;
    }

}