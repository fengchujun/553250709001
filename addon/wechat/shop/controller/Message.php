<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechat\shop\controller;

use addon\wechat\model\Config;
use app\model\message\Message as MessageModel;
use app\model\message\MessageTemplate as MessageTemplateModel;

/**
 * 微信公众号模板消息
 */
class Message extends BaseWechat
{
    /**
     * 编辑模板消息
     * @return array|mixed|string
     */
    public function edit()
    {
        $message_model = new MessageModel();
        $keywords = input("keywords", "");
        $info = $message_model->getMessageInfo($this->site_id, $keywords)[ 'data' ];

        if (empty($info))
            $this->error("不存在的模板信息！");

        $wechat_json_array = $info[ "wechat_json_array" ];
        if (request()->isJson()) {

            $wechat_is_open = input('wechat_is_open', 0);
//            $bottomtext = input("bottomtext", "");
//            $headtext = input("headtext", "");
//            $bottomtextcolor = input("bottomtextcolor", "");
//            $headtextcolor = input("headtextcolor", "");
////            $wechat_json_array[ 'headtext' ] = $headtext;//头部标题
//            $wechat_json_array[ 'headtextcolor' ] = $headtextcolor;//头部标题颜色
//            $wechat_json_array[ 'bottomtext' ] = $bottomtext;//尾部描述
//            $wechat_json_array[ 'bottomtextcolor' ] = $bottomtextcolor;//尾部描述颜色

            $data = array (
                'wechat_json' => json_encode($wechat_json_array),
            );
            $condition = array (
                [ "keywords", "=", $keywords ]
            );
            $template_model = new MessageTemplateModel();
            $res = $template_model->editMessageTemplate($data, $condition);
            if ($res[ 'code' ] == 0) {
                $res = $message_model->editMessage([ 'wechat_is_open' => $wechat_is_open, 'site_id' => $this->site_id, 'keywords' => $keywords ], [
                    [ "keywords", "=", $keywords ],
                    [ 'site_id', '=', $this->site_id ],
                ]);
            }
            return $res;
        } else {
            $this->assign("keywords", $keywords);
            if (isset($wechat_json_array[ 'keyword_name_list' ])) {
                $wechat_json_array[ 'keyword_name_list' ] = implode(',', $wechat_json_array[ 'keyword_name_list' ]);
            }
            $this->assign("info", $wechat_json_array);
            $this->assign('wechat_is_open', $info[ 'wechat_is_open' ]);
            $this->assign('message_title', $info[ 'title' ]);
            return $this->fetch('message/edit');
        }
    }

    /**
     * 模板消息设置
     * @return array|mixed
     */
    public function config()
    {
        $message_model = new MessageModel();

        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = array (
                [ "support_type", "like", '%wechat%' ],
            );
            $list = $message_model->getMessagePageList($condition, $this->site_id, $page, $page_size);
            return $list;
        } else {
            $config_model = new Config();
            $config = $config_model->getTemplateMessageConfig($this->site_id)[ 'data' ][ 'value' ];
            $this->assign('config', $config);
            return $this->fetch('message/config');
        }

    }

    /**
     * 微信模板消息状态设置
     */
    public function setWechatStatus()
    {
        $message_model = new MessageModel();

        if (request()->isJson()) {
            $keywords = input("keywords", "");
            $wechat_is_open = input('wechat_is_open', 0);
            $res = $message_model->getWechatTemplateNo($keywords, $this->site_id, $wechat_is_open);
            return $res;
        }
    }

    /**
     * 获取模板编号
     */
    public function getWechatTemplateNo()
    {
        if (request()->isJson()) {
            $keywords = input("keywords", "");
            $message_model = new MessageModel();
            $res = $message_model->getWechatTemplateNo($keywords, $this->site_id, -1);
            return $res;
        }

    }

    /**
     * 设置模板消息配置
     * @return array
     */
    public function messageConfig()
    {
        if (request()->isJson()) {
            $is_jump_weapp = input("is_jump_weapp", 0);
            $config_model = new Config();
            $res = $config_model->setTemplateMessageConfig([ 'is_jump_weapp' => $is_jump_weapp ], 1, $this->site_id);
            return $res;
        }
    }
}