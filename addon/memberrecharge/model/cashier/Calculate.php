<?php


namespace addon\memberrecharge\model\cashier;

use app\model\BaseModel;

class Calculate extends BaseModel
{

    /**
     * 计算
     * @param $data
     * @return array
     */
    public function calculate($data)
    {
        $order_object = $data['order_object'];
        $type = $order_object->param[ 'type' ] ?? '';
        if ($type == 'recharge') {
            $order_name = '';
            $goods_list = [];

            $order_object->cashier_order_type = 'recharge';
            $goods_money = 0;
            $sku_array = $order_object->param[ 'sku_array' ];
            $reward = $data[ 'reward' ] ?? [];
            $reward_goods_list = $data[ 'reward_goods_list' ] ?? [];
            $sku_ids = [];
            $money_array = [];
            foreach ($sku_array as $k => $v) {
                $item_recharge_id = $v[ 'recharge_id' ] ?? 0;
                if ($item_recharge_id > 0) {
                    $sku_ids[] = $item_recharge_id;
                }
                $item_money = $v[ 'money' ] ?? 0;
                if ($item_money > 0) {
                    $money_array[] = $item_money;
                }
            }
            $goods_image = 'upload/cashier/member-recharge-icon.png';
            if (!empty($sku_ids)) {
                $member_recharge_condition = array (
                    [ 'recharge_id', 'in', $sku_ids ]
                );
                $member_recharge_list = model('member_recharge')->getList($member_recharge_condition);

                if (!empty($member_recharge_list)) {
                    foreach ($member_recharge_list as $k => $v) {
                        $item_sku_id = $v[ 'recharge_id' ];
                        $price = $v[ 'buy_price' ];
                        $item_goods_id = 0;//sku_id已经是充值个体组件
                        $num = 1;
                        $item_goods_money = $price * $num;
                        $goods_money += $item_goods_money;
                        $sku_name = '充值套餐 ' . $v[ 'recharge_name' ];
                        $order_name = string_split($order_name, ',', '充值套餐' . $v[ 'recharge_name' ]);
                        $goods_list[] = array (
                            'goods_id' => $item_goods_id,
                            'sku_id' => $item_sku_id,
                            'price' => $price,
                            'num' => $num,
                            'goods_money' => $item_goods_money,
                            'sku_name' => $sku_name,
                            'real_goods_money' => $item_goods_money,
                            'goods_name' => '充值礼包',
                            'goods_image' => $goods_image,
                            'spec_name' => $v[ 'recharge_name' ],
                            'goods_class' => 'recharge',
                            'goods_class_name' => '充值礼包',
                            'is_virtual' => 1
                        );

                    }
                }
            }
            if (!empty($money_array)) {
                foreach ($money_array as $k => $v) {
                    $item_sku_id = 0;
                    $price = $v;
                    if ($price > 0) {
                        $item_goods_id = 0;
                        $num = 1;
                        $item_goods_money = $price * $num;
                        $goods_money += $item_goods_money;
                        $sku_name = '自定义充值金额' . $item_goods_money;
                        $order_name = string_split($order_name, ',', '自定义充值金额' . $item_goods_money);
                        $goods_list[] = array (
                            'goods_id' => $item_goods_id,
                            'sku_id' => $item_sku_id,
                            'price' => $price,
                            'num' => $num,
                            'goods_money' => $item_goods_money,
                            'real_goods_money' => $item_goods_money,
                            'sku_name' => $sku_name,
                            'goods_name' => '充值',
                            'goods_image' => $goods_image,
                            'spec_name' => '自定义充值',
                            'goods_class' => 'recharge',
                            'goods_class_name' => '充值礼包',
                            'is_virtual' => 1
                        );
                    }

                }
            }
            if (empty($goods_list)) throw new  \Exception('缺少必填参数商品数据');

            $order_object->goods_money = $goods_money;
            $order_object->real_goods_money = $goods_money;
            $order_object->goods_list = $goods_list;
            $order_object->order_name = $order_name;
            return true;
        }
    }

}