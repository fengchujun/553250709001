<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\model;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 微信支付配置
 * 版本 1.0.4
 */
class Config extends BaseModel
{

    private $encrypt = '******';

    /**
     * 设置支付配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setPayConfig($data, $site_id = 0, $app_module = 'shop')
    {
        $config = new ConfigModel();

        // 未加密前的数据
        $original_config = $this->getPayConfig($site_id)['data']['value'];

        // 检测数据是否发生变化，如果没有变化，则保持未加密前的数据
        if (!empty($data['pay_signkey']) && $data['pay_signkey'] == $this->encrypt) {
            $data['pay_signkey'] = $original_config['pay_signkey']; // APIv2密钥
        }
        if (!empty($data['apiclient_cert']) && $data['apiclient_cert'] == $this->encrypt) {
            $data['apiclient_cert'] = $original_config['apiclient_cert']; // 支付证书cert
        }
        if (!empty($data['apiclient_key']) && $data['apiclient_key'] == $this->encrypt) {
            $data['apiclient_key'] = $original_config['apiclient_key']; // 支付证书key
        }
        if (!empty($data['plateform_cert']) && $data['plateform_cert'] == $this->encrypt) {
            $data['plateform_cert'] = $original_config['plateform_cert']; // 平台证书 生成的
        }
        if (!empty($data['plateform_certificate']) && $data['plateform_certificate'] == $this->encrypt) {
            $data['plateform_certificate'] = $original_config['plateform_certificate']; // 平台证书 主动上传的
        }
        if (!empty($data['plateform_certificate_serial']) && $data['plateform_certificate_serial'] == $this->encrypt) {
            $data['plateform_certificate_serial'] = $original_config['plateform_certificate_serial']; // 平台证书序列号
        }
        if (!empty($data['v3_pay_signkey']) && $data['v3_pay_signkey'] == $this->encrypt) {
            $data['v3_pay_signkey'] = $original_config['v3_pay_signkey']; // APIv3密钥
        }

        if(!($data['transfer_status'] == 1 && $data['transfer_type'] == 'v3' && $data['transfer_v3_type'] == self::TRANSFER_V3_TYPE_USER)){
            $data['member_transfer_scene'] = '';
            $data['store_transfer_scene'] = '';
            $data['fenxiao_transfer_scene'] = '';
            $data['member_transfer_code'] = '';
            $data['store_transfer_code'] = '';
            $data['fenxiao_transfer_code'] = '';
            $data['member_transfer_info'] = [];
            $data['fenxiao_transfer_info'] = [];
            $data['store_transfer_info'] = [];
            $data['member_transfer_recv'] = '';
            $data['store_transfer_recv'] = '';
            $data['fenxiao_transfer_recv'] = '';
        }

        $res = $config->setConfig($data, '微信支付配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'WECHAT_PAY_CONFIG']]);
        return $res;
    }

    /**
     * 获取支付配置
     * @param int $site_id
     * @param string $app_module
     * @param bool $need_encrypt 是否需要加密数据，true：加密、false：不加密
     * @return array
     */
    public function getPayConfig($site_id = 0, $app_module = 'shop', $need_encrypt = false)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'WECHAT_PAY_CONFIG']]);

        //旧定义字段变为新定义字段
        $res['data']['value'] = json_encode($res['data']['value']);
        $res['data']['value'] = str_replace('member_transfer', 'member_withdraw', $res['data']['value']);
        $res['data']['value'] = str_replace('store_transfer', 'store_withdraw', $res['data']['value']);
        $res['data']['value'] = str_replace('fenxiao_transfer', 'fenxiao_withdraw', $res['data']['value']);
        $res['data']['value'] = json_decode($res['data']['value'], true);

        $res['data']['value'] = array_merge([
            "appid" => '',
            "mch_id" => '',
            "pay_signkey" => '',
            "apiclient_cert" => '',
            "apiclient_key" => '',
            "refund_status" => 0,
            "pay_status" => 0,
            "transfer_status" => 0,
            'transfer_type' => 'v2',
            'plateform_cert' => '',
            'plateform_certificate' => '',
            'plateform_certificate_serial' => '',
            'api_type' => 'v2',
            'v3_pay_signkey' => '',
            'transfer_v3_type'=>'1',//旧版1 新版2
            'member_withdraw_scene'=>'',
            'store_withdraw_scene'=>'',
            'fenxiao_withdraw_scene'=>'',
            'member_withdraw_code'=>'',
            'store_withdraw_code'=>'',
            'fenxiao_withdraw_code'=>'',
            'member_withdraw_info' => [],
            'fenxiao_withdraw_info'=>  [],
            'store_withdraw_info' =>  [],
            'member_withdraw_recv'=>'',
            'store_withdraw_recv'=>'',
            'fenxiao_withdraw_recv'=>'',
        ], $res['data']['value']);

        // 加密敏感信息
        if (!empty($res['data']['value']) && $need_encrypt) {

            if (!empty($res['data']['value']['pay_signkey'])) {
                $res['data']['value']['pay_signkey'] = $this->encrypt; // APIv2密钥
            }
            if (!empty($res['data']['value']['apiclient_cert'])) {
                $res['data']['value']['apiclient_cert'] = $this->encrypt; // 支付证书cert
            }
            if (!empty($res['data']['value']['apiclient_key'])) {
                $res['data']['value']['apiclient_key'] = $this->encrypt; // 支付证书key
            }
            if (!empty($res['data']['value']['plateform_cert'])) {
                $res['data']['value']['plateform_cert'] = $this->encrypt; // 平台证书 通过接口获取和生成的
            }
            if (!empty($res['data']['value']['plateform_certificate'])) {
                $res['data']['value']['plateform_certificate'] = $this->encrypt; // 平台证书，直接上传的
            }
            if (!empty($res['data']['value']['plateform_certificate_serial'])) {
                $res['data']['value']['plateform_certificate_serial'] = $this->encrypt; // 平台证书ID
            }
            if (!empty($res['data']['value']['v3_pay_signkey'])) {
                $res['data']['value']['v3_pay_signkey'] = $this->encrypt; // APIv3密钥
            }
        }
        return $res;
    }

    CONST TRANSFER_V3_TYPE_SHOP = 1; //商户转账
    CONST TRANSFER_V3_TYPE_USER = 2; //会员收款

    public function getTransferSceneConfig()
    {
        $config = [
            [
                'num'=>1,
                'title' => '现金营销',
                'infos' => [
                    [
                        'info_type' => '活动名称',
                        'info_content' => ''
                    ],
                    [
                        'info_type' => '奖励说明',
                        'info_content' => '',
                    ],
                ],
                'user_recv'=>[
                    '活动奖励',
                    '现金奖励'
                ]
            ],
            [
                'num'=>2,
                'title' => '企业赔付',
                'infos' => [
                    [
                        'info_type' => '赔付原因',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '退款',
                    '商家赔付'
                ]
            ],
            [
                'num'=>3,
                'title' => '佣金报酬',
                'infos' => [
                    [
                        'info_type' => '岗位类型',
                        'info_content' => ''
                    ],
                    [
                        'info_type' => '报酬说明',
                        'info_content' => '',
                    ],
                ],
                'user_recv'=>[
                    '劳务报酬',
                    '报销款',
                    '企业补贴',
                    '开工利是',
                ]
            ],
            [
                'num'=>4,
                'title' => '采购货款',
                'infos' => [
                    [
                        'info_type' => '采购商品名称',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '货款',
                ]
            ],
            [
                'num'=>5,
                'title' => '二手回收',
                'infos' => [
                    [
                        'info_type' => '回收商品名称',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '二手回收货款',
                ]
            ],
            [
                'num'=>6,
                'title' => '公益补助',
                'infos' => [
                    [
                        'info_type' => '公益活动名称',
                        'info_content' => ''
                    ],
                    [
                        'info_type' => '公益活动备案编号',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '公益补助金',
                ]
            ],
            [
                'num'=>7,
                'title' => '行政补贴',
                'infos' => [
                    [
                        'info_type' => '补贴类型',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '行政补贴',
                    '行政奖励',
                ]
            ],
            [
                'num'=>8,
                'title' => '保险理赔',
                'infos' => [
                    [
                        'info_type' => '保险产品备案编号',
                        'info_content' => ''
                    ],
                    [
                        'info_type' => '保险名称',
                        'info_content' => ''
                    ],
                    [
                        'info_type' => '保险操作单号',
                        'info_content' => ''
                    ],
                ],
                'user_recv'=>[
                    '保险理赔款',
                ]
            ]
        ];

        $data = [];
        foreach ($config as $k=>$v){
            $data[$v['num']] = $v;
        }
        return $data;
    }


    public function getTransferSceneInfo($param){

        $data = [
            'member_withdraw_info'=>[],
            'fenxiao_withdraw_info'=>[],
            'store_withdraw_info'=>[]
        ];
        if(!isset($param['transfer_v3_type']) || $param['transfer_v3_type'] !=2){
            return $data;
        }
        $config = $this->getTransferSceneConfig();
        //会员提现场景
        if(!empty($param['member_withdraw_scene'])){
            foreach($config[$param['member_withdraw_scene']]['infos'] as $k=>$v){
                   $v['info_content'] = $param['member_withdraw_'.$k] ?? '';
                   $data['member_withdraw_info'][$k] = $v;
            }
        }

        //分销提现场景
        if(!empty($param['fenxiao_withdraw_scene'])){
            foreach($config[$param['fenxiao_withdraw_scene']]['infos'] as $k=>$v){
                $v['info_content'] = $param['fenxiao_withdraw_'.$k] ?? '';
                $data['fenxiao_withdraw_info'][$k] = $v;
            }
        }

        //店铺提现场景
        if(!empty($param['store_withdraw_scene'])){
            foreach($config[$param['store_withdraw_scene']]['infos'] as $k=>$v){
                $v['info_content'] = $param['store_withdraw_'.$k] ?? '';
                $data['store_withdraw_info'][$k] = $v;
            }
        }
        return $data;
    }
}