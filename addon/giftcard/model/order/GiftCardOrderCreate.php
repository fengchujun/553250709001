<?php


namespace addon\giftcard\model\order;

use addon\giftcard\model\giftcard\Media;
use app\model\BaseModel;
use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use app\model\order\Config;
use app\model\system\Cron;
use app\model\system\Pay;
use app\model\system\Site;
use think\facade\Cache;

/**
 * 礼品卡订单创建
 * Class GiftCardOrderCreate
 * @package addon\giftcard\model\order
 */
class GiftCardOrderCreate extends BaseModel
{

    public function create($params)
    {
        if(isset($params['num']) && $params['num'] === '') $params['num'] = 0;
        $calculate_result = $this->calculate($params);
        if ($calculate_result[ 'code' ] < 0)
            return $calculate_result;

        $calculate_data = $calculate_result[ 'data' ];

        $member_id = $calculate_data[ 'member_id' ];
        $site_id = $calculate_data[ 'site_id' ];
        $pay = new Pay();
        $out_trade_no = $pay->createOutTradeNo($member_id);
        $order_no = $this->createOrderNo($site_id, $member_id);

        $site_info = $calculate_data[ 'site_info' ];
        $site_name = $site_info[ 'site_name' ];
        $card_right_type = $calculate_data[ 'card_right_type' ];
        $giftcard_info = $calculate_data[ 'giftcard_info' ];

        $validity_type = $giftcard_info[ 'validity_type' ];
        $validity_time = $giftcard_info[ 'validity_time' ];
        $validity_day = $giftcard_info[ 'validity_day' ];
        $common_data = array (
            'order_no' => $order_no,
            'site_id' => $site_id,
            'site_name' => $site_name,
            'member_id' => $member_id,
        );
        $pay_money = $calculate_data[ 'pay_money' ];
        $order_name = $calculate_data[ 'order_name' ];
        $order_data = [
            'out_trade_no' => $out_trade_no,
            'order_from' => $calculate_data[ 'order_from' ],
            'order_from_name' => $calculate_data[ 'order_from_name' ],
            'order_status' => 'topay',
            'buyer_ip' => request()->ip(),
            'goods_money' => $calculate_data[ 'goods_money' ],
            'order_money' => $calculate_data[ 'order_money' ],
            'pay_money' => $calculate_data[ 'pay_money' ],
            'create_time' => time(),
            'order_name' => $order_name,
//            'buyer_message' => $calculate_data['buyer_message'],
            'giftcard_id' => $calculate_data[ 'giftcard_id' ],
            'card_right_type' => $card_right_type,
            'card_cover' => $calculate_data[ 'card_cover' ],
            'is_allow_transfer' => $calculate_data[ 'is_allow_transfer' ],
            'media_id' => $calculate_data[ 'media_id' ],
            'card_price' => $calculate_data[ 'item_money' ],
            'validity_type' => $validity_type,
            'validity_time' => $validity_time,
            'validity_day' => $validity_day,
            'num' => $calculate_data[ 'num' ],
            'card_right_goods_type' => $giftcard_info[ 'card_right_goods_type' ],
            'card_right_goods_count' => $giftcard_info[ 'card_right_goods_count' ],
        ];
        model('giftcard_order')->startTrans();
        //循环生成多个订单
        try {
            $order_id = model('giftcard_order')->add(array_merge($common_data, $order_data));
            $order_goods_list = $calculate_data[ 'order_goods_list' ];
            $order_goods_data_list = [];
            foreach ($order_goods_list as $k => $v) {
                $order_goods_data_list[] = array_merge($common_data, [
                    'order_id' => $order_id,
                    'sku_id' => $v[ 'sku_id' ],
                    'sku_name' => $v[ 'sku_name' ],
                    'sku_image' => $v[ 'sku_image' ],
                    'sku_no' => $v[ 'sku_no' ] ?? '',
                    'goods_id' => $v[ 'goods_id' ] ?? 0,
                    'goods_name' => $v[ 'goods_name' ] ?? '',
                    'goods_class' => $v[ 'goods_class' ] ?? '',
                    'goods_class_name' => $v[ 'goods_class_name' ] ?? '',
                    'price' => $v[ 'price' ],
                    'num' => $v[ 'num' ],
                    'card_right_type' => $card_right_type,
                    'goods_money' => $v[ 'goods_money' ],
                    'balance' => $v[ 'balance' ] ?? 0,
                    'total_balance' => $v[ 'total_balance' ] ?? 0,
                ]);
            }
            model('giftcard_order_goods')->addList($order_goods_data_list);
            //生成整体付费支付单据
            $pay_model = new Pay();
            $res = $pay_model->addPay($site_id, $out_trade_no, 'ONLINE_PAY', $order_name, $order_name, $pay_money, '', 'GiftCardOrderPayNotify', '/pages_promotion/giftcard/order_detail?order_id=' . $order_id, $order_id, $member_id);
            if($res['code'] < 0){
                model('giftcard_order')->rollback();
                return $res;
            }

            $config_model = new Config();
            $order_config = $config_model->getOrderEventTimeConfig($site_id)[ 'data' ];
            if ($order_config[ 'value' ][ 'auto_close' ] > 0) {
                $now_time = time();
                $execute_time = $now_time + $order_config[ 'value' ][ 'auto_close' ] * 60; //自动关闭时间
                $cron_model = new Cron();
                $cron_model->addCron(1, 0, '订单自动关闭', 'GiftCardOrderClose', $execute_time, $order_id);
            }

            model('giftcard_order')->commit();
            return $this->success($out_trade_no);
        } catch (\Exception $e) {
            model('giftcard_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    public function calculate($params)
    {
        if(isset($params['num']) && $params['num'] === '') $params['num'] = 0;
        $giftcard_id = $params[ 'giftcard_id' ] ?? 0;
        $site_id = $params[ 'site_id' ];

        $site_result = $this->initSite($params);
        if ($site_result[ 'code' ] < 0)
            return $site_result;

        $params = $site_result[ 'data' ];
        $num = $params[ 'num' ] ?? 1;
        $item_result = $this->itemCalculate($params);
        if ($item_result[ 'code' ] < 0)
            return $item_result;

        $params = $item_result[ 'data' ];
        $goods_money = $params[ 'goods_money' ];
        $total_money = $params[ 'giftcard_info' ][ 'card_price' ] * $num;
        $order_money = $total_money;
        $pay_money = $order_money;
        $params[ 'goods_money' ] = $total_money;
        $params[ 'order_money' ] = $order_money;
        $params[ 'pay_money' ] = $pay_money;
        $params[ 'item_money' ] = $params[ 'giftcard_info' ][ 'card_price' ];

        return $this->success($params);
    }

    /**
     * 单项计算
     * @param $params
     * @return array
     */
    public function itemCalculate($params)
    {
        $giftcard_id = $params[ 'giftcard_id' ];
        $media_id = $params[ 'media_id' ];
        $site_id = $params[ 'site_id' ];
        $giftcard_model = new GiftCardModel();
        $info = $giftcard_model->getGiftcardDetail([ 'site_id' => $site_id, 'giftcard_id' => $giftcard_id ])[ 'data' ] ?? [];
        if (empty($info))
            return $this->error([], '当前礼品卡活动不存在');

        if ($info[ 'status' ] != 1)
            return $this->error([], '当前礼品卡活动已下架');

        $params[ 'giftcard_info' ] = $info;
        $media_model = new Media();
        $media_condition = array (
            [ 'media_id', '=', $media_id ],
        );
        $media_info = $media_model->getInfo($media_condition)[ 'data' ] ?? [];
        if (empty($media_info))
            return $this->error([], '封面图不存在');

        $card_cover = $media_info[ 'media_path' ];
        $params[ 'card_cover' ] = $card_cover;
        $card_name = $info[ 'card_name' ];
        $params[ 'order_name' ] = $card_name;
        $goods_money = 0;
        $card_right_type = $info[ 'card_right_type' ];//卡券类型
        $params[ 'card_right_type' ] = $card_right_type;
        $params[ 'is_allow_transfer' ] = $info[ 'is_allow_transfer' ];
        $goods_item_list = [];
        switch ( $card_right_type ) {
            case 'goods'://商品
                $goods_list = $info[ 'goods_list' ];
                foreach ($goods_list as $v) {
                    $item_sku_id = $v[ 'sku_id' ];
                    $item_num = $v[ 'goods_num' ];
                    $goods_id = $v[ 'goods_id' ];
                    $item_goods_price = $v[ 'goods_price' ];
                    $item_goods_money = $item_goods_price * $item_num;
                    $goods_item_list[] = [
                        'sku_id' => $item_sku_id,
                        'goods_id' => $goods_id,
                        'price' => $item_goods_price,
                        'goods_money' => $item_goods_money,
                        'num' => $item_num,
                        'sku_name' => $v[ 'sku_info' ][ 'sku_name' ],
                        'sku_image' => $v[ 'sku_info' ][ 'sku_image' ],
                        'sku_no' => $v[ 'sku_info' ][ 'sku_no' ],
                        'goods_class' => $v[ 'sku_info' ][ 'goods_class' ],
                        'goods_class_name' => $v[ 'sku_info' ][ 'goods_class_name' ],
                        'goods_name' => $v[ 'sku_info' ][ 'goods_name' ],
                    ];
                    $goods_money = $info[ 'card_price' ];
                }
                break;
            case 'balance'://储值
                $num = 1;
                $item_goods_price = $info[ 'card_price' ];
                $item_goods_money = $item_goods_price * $num;
                $item_sku_name = $info[ 'balance' ] . '元储值卡';
                $item_sku_image = $card_cover;
                $balance = $info[ 'balance' ];
                $total_balance = $balance * $num;
                $goods_item_list[] = [
                    'sku_id' => $giftcard_id,
                    'price' => $item_goods_price,
                    'goods_money' => $item_goods_money,
                    'num' => $num,
                    'sku_name' => $item_sku_name,
                    'sku_image' => $item_sku_image,
                    'goods_name' => $card_name,
                    'balance' => $balance,
                    'total_balance' => $total_balance
                ];
                $goods_money += $item_goods_money;
                break;
        }
        $params[ 'order_goods_list' ] = $goods_item_list;
        $params[ 'goods_money' ] = $goods_money;
        return $this->success($params);
    }


    public function initSite($params)
    {
        $site_id = $params[ 'site_id' ];
        $site_model = new Site();
        $condition = array (
            [ 'site_id', '=', $site_id ]
        );
        $site_info = $site_model->getSiteInfo($condition)[ 'data' ] ?? [];
        if (empty($site_info))
            return $this->error();
        $params[ 'site_info' ] = $site_info;
        return $this->success($params);
    }

    /**
     * 生成订单编号
     * @param $site_id
     * @param int $member_id
     * @return string
     */
    public function createOrderNo($site_id, $member_id = 0)
    {
        $time_str = date('YmdHi');
        $max_no = Cache::get($site_id . '_' . $member_id . '_' . $time_str);
        if (empty($max_no)) {
            $max_no = 1;
        } else {
            $max_no = $max_no + 1;
        }
        $order_no = $time_str . sprintf('%05d', $member_id) . sprintf('%03d', $max_no);
        Cache::set($site_id . '_' . $member_id . '_' . $time_str, $max_no);
        return $order_no;
    }
}
