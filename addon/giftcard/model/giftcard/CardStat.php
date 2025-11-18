<?php


namespace addon\giftcard\model\giftcard;


use addon\giftcard\model\card\Card;
use app\model\BaseModel;
use app\model\system\Stat;


class CardStat extends BaseModel
{

    /**
     * 活动相关统计
     * @param $params
     */
    public function stat($params)
    {
        $stat_type = $params[ 'stat_type' ];
        $giftcard_id = $params[ 'giftcard_id' ] ?? 0;
        $num = $params[ 'num' ] ?? 1;
        $card_id = $params[ 'card_id' ] ?? 0;
        $card_info = $params[ 'card_info' ] ?? [];
        $import_id = $params[ 'import_id' ] ?? 0;
        if ($card_id > 0) {
            $card_model = new Card();
            $card_info = $card_model->getCardInfo([ [ 'card_id', '=', $card_id ] ])[ 'data' ] ?? [];
            $import_id = $card_info[ 'card_import_id' ];
            $giftcard_id = $card_info[ 'giftcard_id' ];
        }
        if (!empty($card_info)) {
            $import_id = $card_info[ 'card_import_id' ];
            $giftcard_id = $card_info[ 'giftcard_id' ];
        }
        switch ( $stat_type ) {
            case 'sale'://销量
                $condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                $giftcard_model = new GiftCard();
                $giftcard_model->incSaleNum($num, $condition);
                break;
            case 'invalid'://作废
                $giftcard_condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                model('giftcard')->setInc($giftcard_condition, 'invalid_count', $num);
                if ($import_id > 0) {
                    $import_condition = array (
                        [ 'import_id', '=', $import_id ]
                    );
                    model('giftcard_card_import')->setInc($import_condition, 'invalid_count', $num);
                }
                break;
            case 'activate'://激活
                $giftcard_condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                model('giftcard')->setInc($giftcard_condition, 'activate_count', $num);
                if ($import_id > 0) {
                    $import_condition = array (
                        [ 'import_id', '=', $import_id ]
                    );
                    model('giftcard_card_import')->setInc($import_condition, 'activate_count', $num);
                }
                break;
            case 'create'://制卡

                $giftcard_condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                model('giftcard')->setInc($giftcard_condition, 'card_count', $num);
                break;
            case 'use'://使用
                $giftcard_condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                model('giftcard')->setInc($giftcard_condition, 'use_count', $num);
                if ($import_id > 0) {
                    $import_condition = array (
                        [ 'import_id', '=', $import_id ]
                    );
                    model('giftcard_card_import')->setInc($import_condition, 'use_count', 1);
                }

                break;
            case 'del':
                $giftcard_condition = array (
                    [ 'giftcard_id', '=', $giftcard_id ]
                );
                model('giftcard')->setInc($giftcard_condition, 'del_count', $num);
                $card_type = $card_info[ 'card_type' ] ?? '';
                if ($card_type == 'real') {
                    //暂时只有实体未激活的卡才能删除
                    model('giftcard')->setDec($giftcard_condition, 'card_count', $num);
                }

                if ($import_id > 0) {
                    $import_condition = array (
                        [ 'import_id', '=', $import_id ]
                    );
                    model('giftcard_card_import')->setInc($import_condition, 'del_count', $num);
//                    model('giftcard_card_import')->setDec($giftcard_condition, 'total_count', $num);
                }
                break;

        }
        return $this->success();
    }

    /**
     * 写入礼品卡统计数据
     * @param $params
     * @return array
     */
    public function addGiftcardStat($params)
    {
        $order_id = $params[ 'order_id' ];

        $site_id = $params[ 'site_id' ] ?? 0;
        $order_condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $site_id ]
        );
        $order_info = model('giftcard_order')->getInfo($order_condition);
        if (empty($order_info))
            return $this->error();

        //存在余额支付，以实际付款为准
        $pay_money = model('pay')->getValue([ [ 'out_trade_no', '=', $order_info[ 'out_trade_no' ] ] ], 'pay_money') ?? '0.00';
        $stat_data = array (
            'site_id' => $site_id,
            'member_giftcard_count' => 1,
            'member_giftcard_total_money' => $pay_money
        );

        $stat_model = new Stat();
        $result = $stat_model->addShopStat($stat_data);
        return $result;
    }
}
