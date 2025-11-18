<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\offlinepay\shop\controller;

use addon\offlinepay\model\Config as ConfigModel;
use addon\offlinepay\model\Pay as PayModel;
use app\shop\controller\BaseShop;

/**
 * 支付 控制器
 */
class Pay extends BaseShop
{
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isJson()) {
            $data = [
                'pay_status' => input('pay_status', 0),//支付状态
                'bank' => [
                    'status' => input('bank_status', 0),//是否开启
                    'bank_name' => input('bank_bank_name', ''),//银行名称
                    'account_name' => input('bank_account_name', ''),//账户名称
                    'account_number' => input('bank_account_number', ''),//账号
                    'branch_name' => input('bank_branch_name', ''),//支行名称
                ],
                'wechat' => [
                    'status' => input('wechat_status', 0),//是否开启
                    'account_name' => input('wechat_account_name', ''),//账户名称
                    'payment_code' => input('wechat_payment_code', ''),//收款码
                ],
                'alipay' => [
                    'status' => input('alipay_status', 0),//是否开启
                    'account_name' => input('alipay_account_name', ''),//账户名称
                    'payment_code' => input('alipay_payment_code', ''),//收款码
                ],
            ];
            $result = $config_model->setPayConfig($data, $this->site_id, $this->app_module);
            return $result;
        } else {
            $config_info = $config_model->getPayConfig($this->site_id, $this->app_module)[ 'data' ][ 'value' ];
            $this->assign("config_info", $config_info);
            return $this->fetch("pay/config");
        }
    }

    public function lists()
    {
        if (request()->isJson()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', 0);
            $search_field = input('search_field', '');
            $search_field_value = input('search_field_value', '');
            $out_trade_no = input('out_trade_no', '');

            $alias = 'po';
            $join = [
                ['member m', 'm.member_id = po.member_id', 'left'],
                ['pay p', 'p.out_trade_no = po.out_trade_no', 'left'],
            ];
            $field = [
                'po.*',
                'm.nickname,m.mobile',
                'p.pay_detail,p.pay_money,p.event,p.relate_id',
            ];
            $condition = [];
            if($status !== 'all'){
                $condition[] = ['po.status', '=', $status];
            }
            if($search_field_value != ''){
                $condition[] = [$search_field, 'like', '%'.$search_field_value.'%'];
            }
            if($out_trade_no){
                $condition[] = ['po.out_trade_no', '=', $out_trade_no];
            }
            $order = 'po.create_time desc';


            $pay_model = new PayModel();
            $res = $pay_model->getPageList($condition, $page_index, $page_size, $order, $field, $alias, $join);

            //各种状态统计
            foreach ($condition as $key=>$val){ if($val[0] == 'po.status') unset($condition[$key]); }
            $condition = array_values($condition);
            $status_num_list = $pay_model->getList($condition, '', 'count(*) as num, po.status', $alias, $join, 'po.status')['data'];

            $status_num_data = array_column($status_num_list, 'num', 'status');
            $res['data']['status_num_data'] = $status_num_data;
$res['c'] = $condition;
            return $res;
        } else {
            $status_list = PayModel::getStatus();
            $this->assign('status_list', $status_list);

            $out_trade_no = input('out_trade_no', '');
            $this->assign('out_trade_no', $out_trade_no);

            return $this->fetch('pay/lists');
        }
    }

    public function auditPass()
    {
        if(request()->isJson()){
            $id = input('id', 0);
            $pay_model = new PayModel();
            return $pay_model->auditPass([['id', '=', $id]]);
        }
    }

    public function auditRefuse()
    {
        if(request()->isJson()){
            $id = input('id', 0);
            $audit_remark = input('audit_remark', '');
            $pay_model = new PayModel();
            return $pay_model->auditRefuse([['id', '=', $id]], $audit_remark);
        }
    }

    public function pay()
    {
        if(request()->isJson()){
            $imgs = input('imgs', '');
            $desc = input('desc', '');
            $out_trade_no = input('out_trade_no', '');
            $member_id = input('member_id', 0);
            $pay_model = new PayModel();
            //支付
            $pay_res = $pay_model->pay([
                'member_id' => $member_id,
                'out_trade_no' => $out_trade_no,
                'imgs' => $imgs,
                'desc' => $desc,
            ]);
            if($pay_res['code'] < 0) return $pay_res;
            //审核
            $audit_res = $pay_model->auditPass([
                ['out_trade_no', '=', $out_trade_no],
                ['member_id', '=', $member_id],
            ]);
            return $audit_res;
        }else{
            $out_trade_no = input('out_trade_no', '');
            $this->assign("out_trade_no", $out_trade_no);
            $member_id = input('member_id', 0);
            $this->assign("member_id", $member_id);
            return $this->fetch("pay/pay");
        }
    }

    public function test()
    {
        $out_trade_no = '171997599310581711000';
        $member_id = 171;
        $imgs = join(',', [
            'http://b2cv4.com/upload/1/common/images/20240618/20240618105545171867934599817_BIG.jpg',
            'http://b2cv4.com/upload/1/common/goods_grab/images/20240527/20240527032610171679477043213_BIG.jpg',
            'http://b2cv4.com/upload/1/common/images/20240618/20240618105545171867934599817_BIG.jpg',
        ]);
        $desc = '支付了33333次';
        $pay_model = new PayModel();
        $res = $pay_model->pay([
            'member_id' => $member_id,
            'out_trade_no' => $out_trade_no,
            'imgs' => $imgs,
            'desc' => $desc,
        ]);
        dd($res);
    }
}