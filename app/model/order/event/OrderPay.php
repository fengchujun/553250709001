<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\event;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\member\MemberAccount;
use app\model\message\Message;
use app\model\order\OrderCommon;
use Exception;
use app\model\system\Cron;

/**
 * 订单交易设置
 */
class OrderPay extends BaseModel
{

    public function check($data)
    {

    }

    public function event($data)
    {
        $pay_info = $data['pay_info'];
        $order_info = $data['order_info'];

        //订单余额账户处理
        if (($pay_info['balance'] || $pay_info['balance_money']) && $order_info['order_money']) {
            //订单处理会员消费业务
            $account_result = (new MemberAccount())->addMemberAccountInOrderPay($data);
            if (!empty($account_result) && $account_result['code'] < 0) throw new Exception($account_result['message']);
        }

        //会员业务
        $member_info = $data['member_info'];
        $data_member = [
            'order_money' => $member_info['order_money'] + $order_info['order_money'],
            'order_num' => $member_info['order_num'] + 1,
            'last_consum_time' => time()
        ];
        model('member')->update($data_member, [['member_id', '=', $member_info['member_id']]]);

        $order_info['pay_info'] = $pay_info;
        event('OrderPay', $order_info);
        return $this->success();
    }

    public function after($data)
    {
        $order_id = $data['order_id'];
        $order_info = model('order')->getInfo([['order_id', '=', $order_id]], '*');

        event('OrderPayAfter', $order_info);

        //商品业务
        $order_goods_list = model('order_goods')->getList([['order_id', '=', $order_id]], 'sku_id,num,goods_class,store_id');
        $goods_model = new Goods();
        foreach ($order_goods_list as $v) {
            $goods_model->incGoodsSaleNum($v['sku_id'], $v['num']);
        }

        //发送消息
        $message_model = new Message();
        $param = ['keywords' => 'ORDER_PAY'];
        $param = array_merge($param, $order_info);
        $message_model->sendMessage($param);
        $param = ['keywords' => 'BUYER_PAY'];
        $param = array_merge($param, $order_info);
        $message_model->sendMessage($param);

        //删除定时任务
        $cron = new Cron();
        $cron->deleteCron([ [ 'event', '=', 'CronOrderClose' ], [ 'relate_id', '=', $order_id ] ]);
        $cron->deleteCron([ [ 'event', '=', 'CronOrderUrgePayment' ], [ 'relate_id', '=', $order_id ] ]);

        return true;
    }
}