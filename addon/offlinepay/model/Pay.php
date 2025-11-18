<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\offlinepay\model;

use addon\weapp\model\Message as WeappMessage;
use addon\wechat\model\Message as WechatMessage;
use app\dict\member_account\AccountDict;
use app\model\member\Member;
use app\model\member\MemberAccount;
use app\model\message\Message;
use app\model\message\Sms;
use app\model\shop\ShopAcceptMessage;
use app\model\system\Pay as PayModel;
use app\model\BaseModel;
use app\model\order\OrderCommon;

/**
 * 微信支付配置
 * 版本 1.0.4
 */
class Pay extends BaseModel
{
    //支付类型
    const PAY_TYPE = 'offlinepay';

    //状态
    const STATUS_WAIT_AUDIT = 0;
    const STATUS_AUDIT_PASS = 1;
    const STATUS_AUDIT_REFUSE = 2;
    const STATUS_CLOSE = 3;

    static public function getStatus($key = null)
    {
        $arr = [
            [
                'id' => self::STATUS_WAIT_AUDIT,
                'name' => '待审核',
                'const' => 'WAIT_AUDIT',
            ],
            [
                'id' => self::STATUS_AUDIT_PASS,
                'name' => '审核通过',
                'const' => 'AUDIT_PASS',
            ],
            [
                'id' => self::STATUS_AUDIT_REFUSE,
                'name' => '审核拒绝',
                'const' => 'AUDIT_REFUSE',
            ],
            [
                'id' => self::STATUS_CLOSE,
                'name' => '已关闭',
                'const' => 'CLOSE',
            ],
        ];
        if(isset($arr[0][$key])){
            $arr = array_column($arr, null, $key);
        }
        return $arr;
    }

    /**
     * 支付操作
     * @param $param
     * @return array
     */
    public function pay($param)
    {
        $member_id = $param['member_id'] ?? 0;
        $out_trade_no = $param['out_trade_no'] ?? '';
        $imgs = $param['imgs'] ?? '';
        $desc = $param['desc'] ?? '';

        if(empty($member_id)) return $this->error(null, '用户id不可为空');
        if(empty($out_trade_no)) return $this->error(null, '外部交易号不可为空');
        if(empty($imgs)) return $this->error(null, '请上传支付凭证');

        $pay_model = new PayModel();
        $pay_info = $pay_model->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)) return $this->error(null, '支付信息有误');
        if(!in_array($pay_info['pay_status'], [PayModel::PAY_STATUS_NOT, PayModel::PAY_STATUS_IN_PROCESS])){
            return $this->error(null, '支付状态有误');
        }

        $offline_pay_info = model('pay_offline')->getInfo([['out_trade_no', '=', $out_trade_no], ['member_id', '=', $member_id]]);
        if(!empty($offline_pay_info) && $offline_pay_info['status'] != self::STATUS_AUDIT_REFUSE){
            return $this->error(null, '当前状态不可修改');
        }

        //记录线下支付信息
        $data = [
            'member_id' => $member_id,
            'out_trade_no' => $out_trade_no,
            'imgs' => $imgs,
            'desc' => $desc,
            'status' => self::STATUS_WAIT_AUDIT,
            'update_time' => time(),
        ];

        model('pay_offline')->startTrans();
        try{
            if(empty($offline_pay_info)){
                $data['create_time'] = time();
                model('pay_offline')->add($data);
                //绑定支付数据
                $pay_model->bindMchPay($out_trade_no, [
                    "pay_type" => 'offlinepay',
                ]);
            }else{
                model('pay_offline')->update($data, [['id', '=', $offline_pay_info['id']]]);
            }

            //支付信息修改
            $update_data = ['pay_type' => self::PAY_TYPE, 'pay_status' => PayModel::PAY_STATUS_IN_PROCESS];
            $pay_model->edit($update_data, [['out_trade_no', '=', $out_trade_no]]);
            $pay_info = array_merge($pay_info, $update_data);

            //具体业务处理
            event('OfflinePay', $pay_info);

            //发送消息
            $message_model = new Message();
            $message_model->sendMessage(['keywords' => 'OFFLINEPAY_WAIT_AUDIT', 'pay_info' => $pay_info, 'site_id' => $pay_info['site_id']]);

            model('pay_offline')->commit();
            return $this->success();
        }catch(\Exception $e){
            model('pay_offline')->rollback();
            return $this->error(['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * 审核通过
     * @param $condition
     * @return array|mixed|null
     */
    public function auditPass($condition)
    {
        $offline_pay_info = $this->getInfo($condition)['data'];
        if(empty($offline_pay_info)) return $this->error(null, '支付信息有误');
        if($offline_pay_info['status'] != self::STATUS_WAIT_AUDIT) return $this->error(null, '不是待审核状态');

        model('pay_offline')->startTrans();
        try{
            model('pay_offline')->update([
                'status' => self::STATUS_AUDIT_PASS,
                'update_time' => time(),
            ], $condition);

            $pay_model = new PayModel();
            $pay_res = $pay_model->onlinePay($offline_pay_info['out_trade_no'], self::PAY_TYPE, $offline_pay_info['out_trade_no'], 'offlinepay');
            if($pay_res['code'] < 0){
                model('pay_offline')->rollback();
                return $pay_res;
            }

            model('pay_offline')->commit();
            return $this->success();
        }catch(\Exception $e){
            model('pay_offline')->rollback();
            return $this->error(['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * 审核拒绝
     * @param $condition
     * @param $audit_remark
     * @return array
     */
    public function auditRefuse($condition, $audit_remark)
    {
        $offline_pay_info = $this->getInfo($condition)['data'];
        if(empty($offline_pay_info)) return $this->error(null, '支付信息有误');
        if($offline_pay_info['status'] != self::STATUS_WAIT_AUDIT) return $this->error(null, '不是待审核状态');

        $pay_model = new PayModel();
        $pay_info = $pay_model->getPayInfo($offline_pay_info['out_trade_no'])['data'];
        if(empty($pay_info)) return $this->error(null, '支付信息有误');

        model('pay_offline')->update([
            'status' => self::STATUS_AUDIT_REFUSE,
            'audit_remark' => $audit_remark,
            'update_time' => time(),
        ], $condition);

        //发送消息
        $message_model = new Message();
        $message_model->sendMessage(['keywords' => 'OFFLINEPAY_AUDIT_REFUSE', 'pay_info' => $pay_info, 'site_id' => $pay_info['site_id']]);

        return $this->success();
    }

    /**
     * 订单关闭取消处理
     * @param $condition
     * @return array
     */
    public function close($condition)
    {
        $offline_pay_info = $this->getInfo($condition)['data'];
        if(empty($offline_pay_info)) return $this->success();
        if($offline_pay_info['status'] == self::STATUS_AUDIT_PASS) return $this->error(null, '线下支付审核通过不可以关闭');
        if($offline_pay_info['status'] == self::STATUS_WAIT_AUDIT) return $this->error(null, '线下支付单据审核中不可以关闭');

        model('pay_offline')->update([
            'status' => self::STATUS_CLOSE,
            'update_time' => time(),
        ], $condition);

        return $this->success();
    }

    /**
     * 退款
     * @param $out_trade_no
     * @param $refund_money
     * @return array
     */
    public function refund($out_trade_no, $refund_money)
    {
        $offline_pay_info = $this->getInfo([['out_trade_no', '=', $out_trade_no]])['data'];
        if(empty($offline_pay_info)) return $this->error(null, '线下支付信息有误');

        $pay_model = new PayModel();
        $pay_info = $pay_model->getPayInfo($out_trade_no)['data'];
        if(empty($pay_info)) return $this->error(null, '支付信息有误');

        $member_account_model = new MemberAccount();
        return  $member_account_model->addMemberAccount($pay_info['site_id'], $offline_pay_info['member_id'], AccountDict::balance_money, $refund_money, 'refund', $pay_info['relate_id'], '订单退款返还！');
    }

    /**
     * 获取信息
     * @param $condition
     * @param $field
     * @return array
     */
    public function getInfo($condition, $field = '*')
    {
        $info = model('pay_offline')->getInfo($condition, $field);
        $info = $this->handleInfo($info);
        return $this->success($info);
    }

    /**
     * 获取分页列表
     * @param $condition
     * @param $page
     * @param $page_size
     * @param $order
     * @param $field
     * @param $alias
     * @param $join
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $res = model('pay_offline')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        foreach($res['list'] as $key=>$val){
            $res['list'][$key] = $this->handleInfo($val);
        }
        return $this->success($res);
    }

    /**
     * 获取列表
     * @param $condition
     * @param $order
     * @param $field
     * @param $alias
     * @param $join
     * @param $group
     * @return array
     */
    public function getList($condition, $order = '', $field = '*', $alias = 'a', $join = [], $group = '')
    {
        $list = model('pay_offline')->getList($condition, $field, $order, $alias, $join, $group);
        foreach($list as $key=>$val){
            $list[$key] = $this->handleInfo($val);
        }
        return $this->success($list);
    }

    public function handleInfo($info)
    {
        if(isset($info['status'])){
            $status_list = self::getStatus('id');
            $info['status_info'] = $status_list[$info['status']] ?? null;
        }
        return $info;
    }

    /**
     * 处理用户订单信息
     * @param $order_info
     * @return array
     */
    public function handleMemberOrderInfo($order_info)
    {
        //字段检测
        $fields = ['order_status','pay_type','out_trade_no','action'];
        foreach($fields as $field){
            if(!isset($order_info[$field])){
                return $order_info;
            }
        }

        if($order_info['order_status'] == OrderCommon::ORDER_CREATE && $order_info['pay_type'] == self::PAY_TYPE){
            $offline_pay_info = $this->getInfo([['out_trade_no', '=', $order_info['out_trade_no']]])['data'];
            if(!empty($offline_pay_info)){
                $order_info['offline_pay_info'] = $offline_pay_info;
                if(in_array($offline_pay_info['status'], [self::STATUS_WAIT_AUDIT, self::STATUS_AUDIT_REFUSE])){
                    foreach($order_info['action'] as $key=>$val){
                        if($val['action'] == 'orderPay'){
                            unset($order_info['action'][$key]);
                        }
                    }
                }
                if($offline_pay_info['status'] == self::STATUS_WAIT_AUDIT){
                    $order_info['order_status_name'] = '待审核';
                }else if($offline_pay_info['status'] == self::STATUS_AUDIT_REFUSE){
                    $order_info['order_status_name'] = '审核拒绝';
                    $order_info['action'][] = [
                        'action' => 'orderOfflinePay',
                        'title' => '线下支付',
                        'color' => '',
                    ];
                }
                $order_info['action'] = array_values($order_info['action']);
            }
        }
        return $order_info;
    }

    /**
     * 处理用户订单信息
     * @param $order_info
     * @return array
     */
    public function handleAdminOrderInfo($order_info)
    {
        //字段检测
        $fields = ['order_status','order_status_action','pay_type','out_trade_no'];
        foreach($fields as $field){
            if(!isset($order_info[$field])){
                return $order_info;
            }
        }

        if($order_info['order_status'] == OrderCommon::ORDER_CREATE){
            $order_status_info = json_decode($order_info['order_status_action'], true);
            if($order_info['pay_type'] == self::PAY_TYPE){
                $offline_pay_info = $this->getInfo([['out_trade_no', '=', $order_info['out_trade_no']]])['data'];
                $order_info['offline_pay_info'] = $offline_pay_info;
                if(!empty($offline_pay_info)){
                    if($offline_pay_info['status'] == self::STATUS_WAIT_AUDIT){
                        $order_info['order_status_name'] = '待审核';
                        $order_status_info['action'][] = [
                            'action' => 'offlinePayAudit',
                            'title' => '支付审核',
                            'color' => '',
                        ];
                    }else if($offline_pay_info['status'] == self::STATUS_AUDIT_REFUSE){
                        $order_info['status_name'] = '审核拒绝';
                    }
                }
            }else{
                $order_status_info['action'][] = [
                    'action' => 'offlinePay',
                    'title' => '线下支付',
                    'color' => '',
                ];
            }
            $order_info['order_status_action'] = json_encode($order_status_info);
        }
        return $order_info;
    }

    /**
     * 发送消息
     * @param $param
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messageWaitAudit($param)
    {
        $pay_info = $param['pay_info'];
        $sms_model = new Sms();
        $wechat_model = new WechatMessage();

        $shop_accept_message_model = new ShopAcceptMessage();
        $list = $shop_accept_message_model->getShopAcceptMessageList([['site_id', '=', $pay_info['site_id']]])['data'];
        if (!empty($list)) {
            foreach ($list as $v) {
                if(!empty($v['mobile'])){
                    $message_data = [
                        'var_parse' => [
                            'order_name' => str_sub(replaceSpecialChar($pay_info[ 'pay_body' ]), 25),
                            'pay_money' => $pay_info['pay_money'],
                            'out_trade_no' => $pay_info['out_trade_no'],
                        ],
                        'sms_account' => $v[ 'mobile' ],
                    ];
                    $sms_model->sendMessage(array_merge($param, $message_data));
                }

                if (!empty($v[ 'wx_openid' ])) {
                    $message_data = [
                        'openid' => $v[ 'wx_openid' ],
                        'template_data' => [
                            'thing10' => str_sub($pay_info['pay_body'], 20),
                            'amount4' => $pay_info['pay_money'],
                            'character_string1' => $pay_info['out_trade_no'],
                        ],
                        'page' => '',
                    ];
                    $wechat_model->sendMessage(array_merge($param, $message_data));
                }
            }
        }
    }

    /**
     * 发送消息
     * @param $param
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messageAuditRefuse($param)
    {
        $pay_info = $param['pay_info'];
        $sms_model = new Sms();
        $wechat_model = new WechatMessage();
        $weapp_model = new WeappMessage();

        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $pay_info[ 'member_id' ] ] ])[ 'data' ];

        if(!empty($member_info['mobile'])){
            $message_data = [
                'var_parse' => [
                    'order_name' => str_sub(replaceSpecialChar($pay_info[ 'pay_body' ]), 25),
                    'pay_money' => $pay_info['pay_money'],
                    'out_trade_no' => $pay_info['out_trade_no'],
                ],
                'sms_account' => $member_info[ 'mobile' ],
            ];
            $sms_model->sendMessage(array_merge($param, $message_data));
        }

        if (!empty($member_info[ 'wx_openid' ])) {
            $message_data = [
                'openid' => $member_info[ 'wx_openid' ],
                'template_data' => [
                    'thing10' => str_sub($pay_info['pay_body'], 20),
                    'amount6' => $pay_info['pay_money'],
                    'character_string5' => $pay_info['out_trade_no'],
                ],
                'page' => 'pages/order/detail?order_id='.$pay_info['relate_id'],
            ];
            $wechat_model->sendMessage(array_merge($param, $message_data));
        }

        if (!empty($member_info[ 'weapp_openid' ])) {
            $message_data = [
                'openid' => $member_info[ 'weapp_openid' ],
                'template_data' => [
                    'thing2' => [
                        'value' => str_sub($pay_info['pay_body'], 20)
                    ],
                    'amount3' => [
                        'value' => $pay_info['pay_money']
                    ],
                    'character_string1' => [
                        'value' => $pay_info['out_trade_no']
                    ],
                    'thing5' => [
                        'value' => '线下支付审核拒绝'
                    ],
                ],
                'page' => 'pages/order/detail?order_id='.$pay_info['relate_id'],
            ];
            $weapp_model->sendMessage(array_merge($param, $message_data));
        }
    }
}
