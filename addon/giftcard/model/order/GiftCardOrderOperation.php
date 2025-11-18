<?php


namespace addon\giftcard\model\order;

use addon\giftcard\model\card\VirtualCard;
use addon\giftcard\model\giftcard\CardStat;
use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use app\model\member\MemberAccount;
use app\model\system\Pay;
use app\model\system\Stat;

class GiftCardOrderOperation extends BaseModel
{

    /**
     * 只涉及订单业务交互
     * @param $params
     * @return array
     */
    public function orderPay($params)
    {
        $out_trade_no = $params[ 'out_trade_no' ];
        $order_model = new GiftCardOrder();
        $order_condition = array (
            [ 'out_trade_no', '=', $out_trade_no ]
        );
        $order_info = $order_model->getOrderInfo($order_condition)[ 'data' ] ?? [];
        if (empty($order_info))
            return $this->error();

        $giftcard_id = $order_info[ 'giftcard_id' ];
        $num = $order_info[ 'num' ];//礼品卡套数
        $order_goods_model = new GiftCardOrderGoods();
        $order_goods_list = $order_goods_model->getOrderGoodsList([ [ 'order_id', '=', $order_info[ 'order_id' ] ] ])[ 'data' ] ?? [];
        $total_balance = 0;
        foreach ($order_goods_list as $k => $v) {
            $total_balance += $v[ 'total_balance' ];
        }

        //订单支付后生成礼品卡
        $virtual_card_model = new VirtualCard();
        $order_info[ 'source' ] = 'order';
        $order_info[ 'goods_list' ] = $order_goods_list;
        $order_info[ 'balance' ] = $total_balance;
        $temp_num = 0;
        model('giftcard_order')->startTrans();
        try {
            while ($temp_num < $num) {
                $virtual_card_model->addCard($order_info);
                $temp_num++;
            }
            $pay_type = $params[ 'pay_type' ];
            //订单相关操作(业务复杂后会拆开步骤)
            $pay_type_list = $order_model->getPayType();
            $data = array (
                'order_status' => 'complete',
                'pay_status' => 1,
                'pay_time' => time(),
                'pay_type' => $pay_type,
                'pay_type_name' => $pay_type_list[ $pay_type ]
            );
            model('giftcard_order')->update($data, $order_condition);
            //可能是消费奖励
            event('GiftCardOrderPay', $order_info);

            //业务和支付的融合
            $pay_money = new Pay();
            $pay_info = $pay_money->getPayInfo($out_trade_no)[ 'data' ] ?? [];
            if (!empty($pay_info)) {
                $balance = $pay_info[ 'balance' ];
                $balance_money = $pay_info[ 'balance_money' ];
                $member_account_model = new MemberAccount();
                if ($balance > 0) {
                    $use_res = $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], AccountDict::balance, -$balance, 'order', $order_info[ 'order_id' ], '订单消费扣除');
                    if ($use_res[ 'code' ] != 0) {
                        model('giftcard_order')->rollback();
                        return $use_res;
                    }
                }
                if ($balance_money > 0) {
                    $use_res = $member_account_model->addMemberAccount($order_info[ 'site_id' ], $order_info[ 'member_id' ], 'balance_money', -$balance_money, 'order', $order_info[ 'order_id' ], '订单消费扣除');
                    if ($use_res[ 'code' ] != 0) {
                        model('giftcard_order')->rollback();
                        return $use_res;
                    }
                }
            }
            model('giftcard_order')->commit();
            //活动增加销量
            ( new CardStat() )->stat([ 'stat_type' => 'sale', 'giftcard_id' => $giftcard_id, 'num' => $num ]);
            $stat_model = new Stat();
            $stat_model->switchStat([ 'type' => 'gift_card_order', 'data' => [ 'order_id' => $order_info[ 'order_id' ], 'site_id' => $order_info[ 'site_id' ] ] ]);
            return $this->success();
        } catch (\Exception $e) {
            model('giftcard_order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 删除礼品卡订单(伪删除)
     * @param $condition
     * @return array
     */
    public function delete($condition)
    {
        model('giftcard_order')->update([ 'is_delete' => 1 ], $condition);
        return $this->success();
    }

    /**
     * 订单关闭
     * @param $params
     * @return array
     */
    public function close($params)
    {
        $order_id = $params[ 'order_id' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $close_cause = $params[ 'close_cause' ] ?? '长时间未支付,订单自动关闭';
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'order_status', '=', 'topay' ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $order_model = new GiftCardOrder();
        $info = $order_model->getOrderInfo($condition, 'order_status, out_trade_no, pay_status')[ 'data' ] ?? [];
        if (empty($info)) {
            return $this->error([], '订单不存在');
        }

        if ($info[ 'order_status' ] != 'topay') {
            return $this->error([], '订单不是待支付状态');
        }

        $data = array (
            'order_status' => 'close',
            'close_time' => time(),
        );
        if (!empty($close_cause)) {
            $data[ 'close_cause' ] = $close_cause;
        }
        //关闭支付单据(没支付的话)
        if ($info[ 'pay_status' ] == 0) {
            $pay_model = new Pay();
            $pay_result = $pay_model->closePay($info[ 'out_trade_no' ]);
            if ($pay_result[ 'code' ] < 0) {
                return $pay_result;
            }
        }
        model('giftcard_order')->update($data, $condition);

        return $this->success();
    }

}
