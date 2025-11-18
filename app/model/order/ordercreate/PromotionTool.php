<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\order\ordercreate;

use addon\coupon\dict\CouponDict;
use addon\coupon\model\Coupon;
use addon\freeshipping\model\Freeshipping;
use addon\manjian\model\Manjian;
use addon\supermember\model\MemberCard;
use addon\supermember\model\MemberLevelOrder;
use app\dict\member_account\AccountDict;
use app\dict\order\OrderPayDict;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\member\MemberAccount;
use Exception;
use extend\exception\OrderException;
use think\facade\Db;

/**
 * 订单创建  活动优惠项
 */
trait PromotionTool
{

    /****************************************************************************** 余额 start *****************************************************************************/
    /**
     * 使用余额
     * @return array
     */
    public function useBalance()
    {
        $balance_config = $this->config('balance');
        //扣除余额(统一扣除)
        if ($this->balance_money > 0 && $balance_config['balance_show'] == 1) {
            $from_type = $this->order_from_type ?: 'order';
            $this->pay_type = OrderPayDict::balance;
            $balance_money = $this->member_account['balance_money']; //储值余额
            $member_balance = $this->member_account['balance']; //现金余额
            $member_account_model = new MemberAccount();
            $surplus_balance = $this->balance_money;
            //优先扣除储值余额
            if ($member_balance > 0) {
                $real_balance = min($member_balance, $surplus_balance);
                $result = $member_account_model->addMemberAccount($this->site_id, $this->member_id, AccountDict::balance, -$real_balance, $from_type, $this->order_id, '订单消费扣除');
                if ($result['code'] < 0) throw new OrderException($result['message']);

                $surplus_balance -= $real_balance;
            }
            if ($surplus_balance > 0) {
                $result = $member_account_model->addMemberAccount($this->site_id, $this->member_id, AccountDict::balance_money, -$surplus_balance, $from_type, $this->order_id, '订单消费扣除');
                if ($result['code'] < 0) throw new OrderException($result['message']);
            }
            return $result;
        }
    }

    /**
     * 计算余额优惠
     * @return true
     */
    public function calculateBalcnce()
    {
        //重新计算订单总额
        $this->getOrderMoney();
        //使用余额
        $is_use_balance = $this->param['is_balance'] ?? 0;
        if ($is_use_balance > 0) {
            //余额付款
            $this->member_balance_money = $this->member_account['balance_total'] ?? 0;
            if ($this->member_balance_money > 0) {
                $temp_order_money = $this->order_money;
                if ($temp_order_money <= $this->member_balance_money) {
                    $balance_money = $temp_order_money;
                } else {
                    $balance_money = $this->member_balance_money;
                }
            } else {
                $balance_money = 0;
            }
            $this->balance_money = $balance_money;
            $this->member_balance_money -= $this->balance_money;//预减少账户余额,还没有实际扣除
            $this->pay_money = $this->order_money - $this->balance_money;
        } else {
            $this->pay_money = $this->order_money;
        }
        return true;
    }
    /****************************************************************************** 余额 end *****************************************************************************/
    /****************************************************************************** 满额包邮 start *****************************************************************************/
    /**
     * 满额包邮
     * @return true
     */
    public function freeShippingCalculate()
    {
        if (addon_is_exit('freeshipping', $this->site_id)) {
            $free_shipping_model = new Freeshipping();
            $district_id = $this->delivery['member_address']['district_id'] ?? 0;
            $free_result = $free_shipping_model->calculate($this->goods_money, $district_id, $this->site_id);
            if ($free_result['code'] >= 0) {
                $this->promotion['freeshipping'] = $free_result['data']; //优惠活动  满额包邮
                $this->is_free_delivery = true;
            }
        }
        return true;
    }
    /****************************************************************************** 满额包邮 end *****************************************************************************/
    /****************************************************************************** 会员等级 start *****************************************************************************/
    /**
     * 会员等级免邮
     * @return true
     */
    public function memberLevelCalculate()
    {
        if ($this->member_level) {
            $is_free_shipping = $this->member_level['is_free_shipping'] ?? 0;
            if ($is_free_shipping > 0) {
                $this->promotion['member_level'] = $this->member_level; //优惠活动  满额包邮
                $this->is_free_delivery = true;
            }
        }
        return true;
    }
    /****************************************************************************** 会员等级 end *****************************************************************************/
    /****************************************************************************** 订单优惠券 start *****************************************************************************/
    /**
     * 查询可用优惠券
     * @return array
     * @throws Exception
     */
    public function getOrderCouponList()
    {
        $this->getOrderCache($this->param['order_key']);
        $store_id = $this->store_id;
        $condition = array(
            ['member_id', '=', $this->member_id],
            ['state', '=', 1],
            ['site_id', '=', $this->site_id],
            //只查看线上的
            ['use_channel', '<>', 'offline'],
            ['', 'exp', Db::raw("use_store = 'all' or FIND_IN_SET({$store_id}, use_store)")],
        );
        $member_coupon_model = new Coupon();
        $goods_category_model = new GoodsCategoryModel();
        $member_coupon_list = $member_coupon_model->getCouponList($condition)['data'];
        $coupon_array = [];
        foreach ($member_coupon_list as $k => $v) {
            switch ($v['goods_type']) {
                //全场优惠券
                case CouponDict::all:
                    if ($v['at_least'] <= $this->goods_money) {
                        $coupon_array[] = $v;
                    }
                    break;
                //指定商品可用/不可用优惠券
                case CouponDict::selected:
                case CouponDict::selected_out:
                    $coupon_goods_array = explode(',', trim($v['goods_ids'], ','));
                    $least_money = 0;
                    $is_support = false;
                    $judge_res = $v['goods_type'] == CouponDict::selected;
                    foreach ($this->goods_list as $v_goods) {
                        if (in_array($v_goods['goods_id'], $coupon_goods_array) == $judge_res) {
                            $least_money += $v_goods['goods_money'];
                            $is_support = true;
                        }
                    }
                    if ($is_support && $v['at_least'] <= $least_money) {
                        $coupon_array[] = $v;
                    }
                    break;
                //指定分类可用/不可用优惠券
                case CouponDict::category_selected:
                case CouponDict::category_selected_out:
                    $category_leaf_ids = $goods_category_model->getGoodsCategoryLeafIds($v['goods_ids'])['data'];
                    $least_money = 0;
                    $is_support = false;
                    foreach ($this->goods_list as $v_goods) {
                        $goods_category_ids = explode(',', trim($v_goods['category_id'], ','));
                        $array_intersect = array_intersect($category_leaf_ids, $goods_category_ids);
                        if ($v['goods_type'] == CouponDict::category_selected) {
                            $judge_res = count($array_intersect) > 0;
                        } else {
                            $judge_res = count($array_intersect) == 0;
                        }
                        if ($judge_res) {
                            $least_money += $v_goods['goods_money'];
                            $is_support = true;
                        }
                    }
                    if ($is_support && $v['at_least'] <= $least_money) {
                        $coupon_array[] = $v;
                    }
                    break;
            }
        }
        if (!empty($coupon_array)) {
            array_multisort(array_column($coupon_array, 'money'), SORT_DESC, $coupon_array);
        }

        return $coupon_array;
    }

    /**
     * 使用优惠券
     * @return void
     * @throws Exception
     */
    public function useCoupon()
    {
        if ($this->coupon_id > 0 && $this->coupon_money > 0) {
            //优惠券处理方案
            $member_coupon_model = new Coupon();
            $coupon_use_result = $member_coupon_model->useCoupon($this->coupon_id, $this->member_id, $this->order_id); //使用优惠券
            if ($coupon_use_result['code'] < 0) {
                throw new OrderException('COUPON_ERROR');
            }
        }
    }

    /**
     * 优惠券活动
     * @return true
     */
    public function couponPromotion()
    {
        $coupon_money = 0;
        $coupon_id = $this->param['coupon']['coupon_id'] ?? 0;
        if ($coupon_id > 0) {
            //查询优惠券信息,计算优惠券费用
            $coupon_model = new Coupon();
            $coupon_info = $coupon_model->getCouponInfo(
                    [
                        ['coupon_id', '=', $coupon_id],
                        ['site_id', '=', $this->site_id],
                        ['use_channel', '<>', 'offline'],
                        ['', 'exp', Db::raw("use_store = 'all' or FIND_IN_SET({$this->store_id}, use_store)")],
                    ], '*')['data'] ?? [];
            $is_coupon = false;
            $coupon_goods_money = 0;
            $goods_list = $this->goods_list;

            if (empty($coupon_info)) {
                $this->setError(1, '优惠券不存在！');
            } else if ($coupon_info['member_id'] == $this->member_id && $coupon_info['state'] == 1) {
                $goods_category_model = new GoodsCategoryModel();
                $coupon_goods_list = [];
                switch ($coupon_info['goods_type']) {
                    //全场通用优惠券
                    case CouponDict::all:
                        if ($coupon_info['at_least'] <= $this->goods_money) {
                            $is_coupon = true;
                        } else {
                            $this->setError(1, '优惠券不可用！');
                        }
                        $coupon_goods_money = $this->goods_money;
                        $coupon_goods_list = $goods_list;
                        $goods_list = [];
                        break;
                    //指定商品可用/不可用
                    case CouponDict::selected:
                    case CouponDict::selected_out:
                        // 指定商品
                        $coupon_goods_ids = explode(',', $coupon_info['goods_ids']);
                        $temp_money = 0;
                        $is_support = false;
                        $judge_res = $coupon_info['goods_type'] == CouponDict::selected;
                        foreach ($goods_list as $goods_k => $goods_v) {
                            if (in_array($goods_v['goods_id'], $coupon_goods_ids) == $judge_res) {
                                $temp_money += $goods_v['goods_money'];
                                $coupon_goods_list[] = $goods_v;
                                unset($goods_list[$goods_k]);
                                $is_support = true;
                            }
                        }
                        if ($is_support && $temp_money >= $coupon_info['at_least']) {
                            $is_coupon = true;
                        }
                        $coupon_goods_money = $temp_money;
                        break;
                    //指定分类可用/不可用
                    case CouponDict::category_selected:
                    case CouponDict::category_selected_out:
                        // 指定商品
                        $coupon_category_ids = $goods_category_model->getGoodsCategoryLeafIds($coupon_info['goods_ids'])['data'];
                        $temp_money = 0;
                        $is_support = false;
                        foreach ($goods_list as $goods_k => $goods_v) {
                            $goods_category_ids = explode(',', trim($goods_v['category_id'], ','));
                            $array_intersect = array_intersect($coupon_category_ids, $goods_category_ids);
                            if ($coupon_info['goods_type'] == CouponDict::category_selected) {
                                $judge_res = count($array_intersect) > 0;
                            } else {
                                $judge_res = count($array_intersect) == 0;
                            }
                            if ($judge_res) {
                                $temp_money += $goods_v['goods_money'];
                                $coupon_goods_list[] = $goods_v;
                                unset($goods_list[$goods_k]);
                                $is_support = true;
                            }
                        }
                        if ($is_support && $temp_money >= $coupon_info['at_least']) {
                            $is_coupon = true;
                        }
                        $coupon_goods_money = $temp_money;
                        break;
                }
            }

            if ($is_coupon) {
                $coupon_money = 0;
                if ($coupon_info['type'] == 'reward') {//满减优惠券
                    $coupon_money = min($coupon_info['money'], $coupon_goods_money);
                } else if ($coupon_info['type'] == 'divideticket') {//瓜分优惠券
                    $coupon_money = min($coupon_info['money'], $coupon_goods_money);
                } else if ($coupon_info['type'] == 'discount') {//折扣优惠券
                    //计算折扣优惠金额
                    $coupon_money = $coupon_goods_money * (10 - $coupon_info['discount']) / 10;
                    $coupon_money = $coupon_money > $coupon_info['discount_limit'] && $coupon_info['discount_limit'] != 0 ? $coupon_info['discount_limit'] : $coupon_money;
                    $coupon_money = min($coupon_money, $coupon_goods_money);
                    $coupon_money = round($coupon_money, 2);
                }
                //计算订单项的金额
                $temp_goods_list = $this->distributionGoodsCouponMoney($coupon_goods_list, $coupon_goods_money, $coupon_money);
                $goods_list = array_merge($goods_list, $temp_goods_list);
                $this->goods_list = $goods_list;
            } else {
                $this->setError(1, '优惠券不可用！');
            }
        }
        if ($coupon_money > 0) {
            if ($coupon_money > $this->order_money) {
                $coupon_money = $this->order_money;
            }
            $this->order_money -= $coupon_money;
            $this->coupon_money = $coupon_money;
            if ($coupon_id > 0) {
                $this->coupon_id = $coupon_id;
            }
        }
        return true;
    }

    /**
     * 按比例摊派优惠券优惠
     * @param $goods_list
     * @param $goods_money
     * @param $coupon_money
     * @return mixed
     */
    public function distributionGoodsCouponMoney($goods_list, $goods_money, &$coupon_money)
    {
        $temp_coupon_money = $coupon_money;
        $last_key = count($goods_list) - 1;
        foreach ($goods_list as $k => $v) {
            if ($last_key != $k) {
                $item_coupon_money = round($v['real_goods_money'] / $goods_money * $coupon_money, 2);
            } else {
                $item_coupon_money = $temp_coupon_money;
            }
            $item_coupon_money = min($item_coupon_money, $v['real_goods_money']);
            $temp_coupon_money -= $item_coupon_money;
            $goods_list[$k]['coupon_money'] = $item_coupon_money;
            $goods_list[$k]['real_goods_money'] -= $item_coupon_money; //真实订单项金额
        }
        // 如果优惠券没有可抵扣金额
        if ($temp_coupon_money == $coupon_money) $coupon_money = 0;
        return $goods_list;
    }
    /****************************************************************************** 订单优惠券 end *****************************************************************************/
    /****************************************************************************** 积分 start *****************************************************************************/
    /**
     * 扣除积分
     * @return void
     */
    public function usePoint()
    {
        if ($this->is_point && $this->point > 0) {
            $member_account_model = new MemberAccount();
            $point_result = $member_account_model->addMemberAccount($this->site_id, $this->member_id, AccountDict::point, -$this->point, 'pointcash', $this->order_id, '订单消费扣除');
            if ($point_result['code'] < 0) {
                throw new OrderException('积分余额不足');
            }
        }
    }

    /**
     * 获取订单最大可用积分
     * @return true
     */
    public function getMaxUsablePoint()
    {
        $point = 0;
        // 获取积分抵现配置
        $point_config = $this->config('point');
        $config = ['is_use' => $point_config['is_use']];
        $config = array_merge($config, $point_config['value']);

        $order_money = $this->delivery_money > 0 ? $this->order_money - $this->delivery_money : $this->order_money;
        if ($config['is_use']) {
            if ($config['is_limit'] == 1 && $order_money < $config['limit']) {
                $this->max_usable_point = $point;
                return true;
            }
            $deduction_money = $order_money;
            if ($config['is_limit_use'] == 1) {
                if ($config['type'] == 0) {
                    $deduction_money = $config['max_use'];
                } else {
                    $ratio = $config['max_use'] / 100;
                    $deduction_money = round(($order_money * $ratio), 2);
                }
                if ($deduction_money > $order_money) {
                    $deduction_money = $order_money;
                }
            }
            $max_point = round($deduction_money * $config['cash_rate']);
            $point = min($max_point, $this->member_account['point']);
        }

        $this->max_usable_point = $point;
        $this->point = $point;
        return true;
    }

    /**
     * 计算积分优惠
     * @return void
     */
    public function calculatePoint()
    {
        $config = $this->config('point')['value'] ?? [];
        if ($this->param['is_point'] && $this->max_usable_point > 0) {
            $point_money = round(($this->max_usable_point * (1 / $config['cash_rate'])), 2);
            if ($point_money > $this->order_money) {
                $point_money = $this->order_money;
            }
            $this->is_point = 1;
            $this->point = $this->max_usable_point;
            $this->order_money -= $point_money;
            $this->point_money = $point_money;
            //计算订单项积分
            $this->distributionGoodsPoint();
        }
    }

    /**
     * 按比例摊派积分
     * @return true
     */
    public function distributionGoodsPoint()
    {
        $temp_point = $this->point;
        $temp_point_money = $this->point_money;
        $last_key = count($this->goods_list) - 1;
        foreach ($this->goods_list as $k => &$v) {
            if ($last_key != $k) {
                $use_point = round($v['goods_money'] / $this->goods_money * $this->point);
                $item_point_money = round($v['goods_money'] / $this->goods_money * $this->point_money, 2);
            } else {
                $use_point = $temp_point;
                $item_point_money = $temp_point_money;
            }
            $temp_point -= $use_point;
            $temp_point_money -= $item_point_money;
            $v['use_point'] = $use_point;
            $v['point_money'] = $item_point_money;
            $real_goods_money = $v['real_goods_money'] - $item_point_money;
            $real_goods_money = max($real_goods_money, 0);
            $v['real_goods_money'] = $real_goods_money; //真实订单项金额
        }
        return true;
    }

    /****************************************************************************** 积分 end *****************************************************************************/
    /****************************************************************************** 满减 start *****************************************************************************/
    /**
     * 满减优惠
     * @return true
     */
    public function manjianPromotion()
    {
        //先查询全部商品的满减套餐  进行中
        $manjian_model = new Manjian();
        $all_info = $manjian_model->getManjianInfo([['manjian_type', '=', 1], ['site_id', '=', $this->site_id], ['status', '=', 1]], 'manjian_name,type,goods_ids,rule_json,manjian_id')['data'];
        $goods_list = $this->goods_list;

        //存在全场满减(不考虑部分满减情况)
        if (!empty($all_info)) {
            $discount_array = $this->getManjianDiscountMoney($all_info);
            $all_info['discount_array'] = $discount_array;
            $all_info['rule'] = json_decode($all_info['rule_json'], true);
            //判断有没有优惠
            $temp_goods_list = $this->distributionGoodsDiscount($goods_list, $this->goods_money, $discount_array['real_discount_money'], isset($discount_array['rule']['free_shipping']));
            $goods_list = $temp_goods_list;

            $manjian_list[] = $all_info;

            $discount_money = $discount_array['real_discount_money'];
            $this->goods_list = $goods_list;
            $this->promotion_money += $discount_money;

            if (!empty($discount_array['rule'])) {
                $this->manjian_rule_list[] = [
                    'manjian_info' => $all_info,
                    'rule' => $discount_array['rule'],
                    'sku_ids' => ''
                ];
                $this->promotion['manjian'] = $manjian_list;
            }
        } else {
            $goods_ids = array_unique(array_column($this->goods_list, 'goods_id'));

            $manjian_condition = array(
                ['goods_id', 'in', $goods_ids],
                ['status', '=', 1]
            );
            $manjian_goods_list = $manjian_model->getManjianGoodsList($manjian_condition, 'manjian_id')['data'];
            if (!empty($manjian_goods_list)) {
                $discount_money = 0;
                $manjian_goods_list = array_column($manjian_goods_list, 'manjian_id');
                $manjian_goods_list = array_unique($manjian_goods_list); //去重
                sort($manjian_goods_list);
                $manjian_list_result = $manjian_model->getManjianList([['manjian_id', 'in', $manjian_goods_list], ['status', '=', 1]]);
                $manjian_list = $manjian_list_result['data'];
                $show_manjian_list = [];
                foreach ($manjian_list as $k => $v) {
                    $manjian_goods_ids = explode(',', $v['goods_ids']);
                    $item_goods_data = [
                        'goods_money' => 0,
                        'goods_num' => 0
                    ];
                    $item_goods_list = [];
                    $sku_ids = [];
                    foreach ($goods_list as $goods_k => $goods_item) {
                        if (in_array($goods_item['goods_id'], $manjian_goods_ids)) {
                            $item_goods_data['goods_money'] += $goods_item['goods_money'];
                            $item_goods_data['goods_num'] += $goods_item['num'];
                            $item_goods_list[] = $goods_item;
                            $sku_ids[] = $goods_item['sku_id'];
                            unset($goods_list[$goods_k]);
                        }
                    }
                    $discount_array = $this->getManjianDiscountMoney($v, $item_goods_list);
                    $temp_goods_list = $this->distributionGoodsDiscount($item_goods_list, $item_goods_data['goods_money'], $discount_array['real_discount_money'], isset($discount_array['rule']['free_shipping']), $sku_ids);
                    $goods_list = array_merge($goods_list, $temp_goods_list);
                    $manjian_list[$k]['rule'] = json_decode($v['rule_json'], true);
                    $manjian_list[$k]['discount_array'] = $discount_array;
                    $discount_money += $discount_array['real_discount_money'];
                    if (!empty($discount_array['rule'])) {
                        $this->manjian_rule_list[] = [
                            'manjian_info' => $v,
                            'rule' => $discount_array['rule'],
                            'sku_ids' => $sku_ids
                        ];
                        //只显示符合条件的满减活动
                        $show_manjian_list[] = $v;
                    }
                }
                $this->promotion['manjian'] = $show_manjian_list;
                $this->goods_list = $goods_list;
                $this->promotion_money += $discount_money;
            }
        }
        if (!empty($this->promotion['manjian'])) {
            foreach ($this->promotion['manjian'] as &$v) {
                $discount_array = $v['discount_array'] ?? [];
                if ($discount_array) {
                    $rule = $discount_array['rule'];
                    if ($rule) {
                        $coupon_ids = $rule['coupon'] ?? '';
                        if ($coupon_ids) {
                            $coupon_ids = explode(',', $coupon_ids);
                            $coupon_num_arr = explode(',', $rule['coupon_num']);
                            $coupon_list = (new Coupon())->getCouponTypeList([['coupon_type_id', 'in', $coupon_ids]])['data'] ?? [];
//                            $coupon_list = array_column($coupon_list, null, 'coupon_type_id');
                            foreach ($coupon_list as &$coupon_v) {
                                $item_coupon_type_id = $coupon_v['coupon_type_id'];
                                $coupon_v['give_num'] = $coupon_num_arr[array_search($item_coupon_type_id, $coupon_ids)] ?? 1;
                            }
                            $v['discount_array']['rule']['coupon_list'] = $coupon_list;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 满减优惠金额
     * @param $manjian_info
     * @return array
     */
    public function getManjianDiscountMoney($manjian_info, $promotion_goods = [])
    {
        if (empty($promotion_goods)) {
            $goods_money = $this->goods_money;
            $value = $manjian_info['type'] == 0 ? $this->goods_money : $this->goods_num;
        } else {
            $goods_money = array_sum(array_column($promotion_goods, 'goods_money'));
            $goods_num = array_sum(array_column($promotion_goods, 'num'));
            $value = $manjian_info['type'] == 0 ? $goods_money : $goods_num;
        }
        //阶梯计算优惠
        $rule_item = json_decode($manjian_info['rule_json'], true);
        $discount_money = 0;
        $money = 0;
        $rule = []; // 符合条件的优惠规则
        array_multisort(array_column($rule_item, 'limit'), SORT_ASC, $rule_item); //排序，根据num 排序
        foreach ($rule_item as $k => $v) {
            if ($value >= $v['limit']) {
                $rule = $v;
                if (isset($v['discount_money'])) {
                    $discount_money = $v['discount_money'];
                    $money = $v['limit'];
                }
            }
        }
        $real_discount_money = min($discount_money, $goods_money);
        return ['discount_money' => $discount_money, 'money' => $money, 'real_discount_money' => $real_discount_money, 'rule' => $rule];
    }


    /**
     * 处理商品满减
     * @param $goods_list
     * @param $goods_money
     * @param $discount_money
     * @param bool $is_free_shipping
     * @param array $sku_ids
     * @return mixed
     */
    public function distributionGoodsDiscount($goods_list, $goods_money, $discount_money, $is_free_shipping = false, $sku_ids = [])
    {
        $temp_discount_money = $discount_money;
        $last_key = count($goods_list) - 1;
        foreach ($goods_list as $k => $v) {
            if ($last_key != $k) {
                $item_discount_money = round($v['goods_money'] / $goods_money * $discount_money, 2);
            } else {
                $item_discount_money = $temp_discount_money;
            }
            $item_discount_money = min($item_discount_money, $v['real_goods_money']);
            $temp_discount_money -= $item_discount_money;
            $goods_list[$k]['promotion_money'] += $item_discount_money;
            $goods_list[$k]['real_goods_money'] -= $item_discount_money; //真实订单项金额
            // 满减送包邮
            if ($is_free_shipping) {
                if (empty($sku_ids) || in_array($v['sku_id'], $sku_ids)) {
                    $goods_list[$k]['is_free_shipping'] = 1;
                }
            }
        }
        return $goods_list;
    }

    /**
     * 记录满减
     * @return true
     */
    public function createManjian()
    {
        if (!empty($this->manjian_rule_list)) {
            $mansong_data = [];
            foreach ($this->manjian_rule_list as $item) {
                // 检测是否有赠送内容
                if (isset($item['rule']['point']) || isset($item['rule']['coupon'])) {
                    $mansong_data[] = [
                        'manjian_id' => $item['manjian_info']['manjian_id'],
                        'site_id' => $this->site_id,
                        'manjian_name' => $item['manjian_info']['manjian_name'],
                        'point' => isset($item['rule']['point']) ? round($item['rule']['point']) : 0,
                        'coupon' => $item['rule']['coupon'] ?? 0,
                        'coupon_num' => $item['rule']['coupon_num'] ?? '',
                        'order_id' => $this->order_id,
                        'member_id' => $this->member_id,
                        'order_sku_ids' => !empty($item['sku_ids']) ? implode($item['sku_ids']) : '',
                    ];
                }
            }
            if (!empty($mansong_data)) {
                model('promotion_mansong_record')->addList($mansong_data);
            }
        }
        return true;
    }
    /****************************************************************************** 满减 end *****************************************************************************/


    /****************************************************************************** 商品次卡 start *****************************************************************************/
    /**
     * 查询商品可用次卡
     * @return true
     */
    public function getMemberGoodsCardPromotion()
    {
        if (addon_is_exit('cardservice', $this->site_id)) {
            $member_card = new \addon\cardservice\model\MemberCard();
            $common_card = [];
            foreach ($this->goods_list as &$goods_item) {
                $sku_id = $goods_item['sku_id'];
                $condition = [
                    ['mgci.member_id', '=', $this->member_id],
                    ['mgci.sku_id', '=', $sku_id],
                    ['mgc.status', '=', 1],
                    ['', 'exp', Db::raw("( (mgc.card_type = 'timercard') OR (mgc.card_type = 'oncecard' AND mgci.num > mgci.use_num) OR (mgc.card_type = 'commoncard' AND mgc.total_num > mgc.total_use_num) )")]
                ];
                $card_ids = array_filter(array_map(function ($item) {
                    if ($item['total_use_num'] >= $item['total_num']) return $item['card_id'];
                }, $common_card));
                if (!empty($card_ids)) $condition[] = ['mgci.card_id', 'not in', $card_ids];

                // 查询可用的卡项
                $card_list = $member_card->getCartItemList($condition, 'mgci.item_id,mgci.card_id,mgci.num,mgci.use_num,mgci.member_verify_id,mgc.end_time,mgc.total_num,mgc.total_use_num,mgc.card_type,mgc.goods_name', '', 'mgci', [
                    ['member_goods_card mgc', 'mgc.card_id = mgci.card_id', 'inner'],
                ])['data'];
                if (!empty($card_list)) {
                    $card_item_id = $this->param['member_goods_card'] && isset($this->param['member_goods_card'][$sku_id]) ? $this->param['member_goods_card'][$sku_id] : 0;
                    $card_list = array_column($card_list, null, 'item_id');
                    // 抵扣判断
                    if (isset($card_list[$card_item_id])) {
                        $card_item = $card_list[$card_item_id];
                        $card_id = $card_item['card_id'];
                        if ($card_item['card_type'] == 'commoncard') {
                            if (isset($common_card[$card_id])) {
                                $card_item['num'] = $common_card[$card_id]['total_num'] - $common_card[$card_id]['total_use_num'];
                            } else {
                                $card_item['num'] = $card_item['total_num'] - $card_item['total_use_num'];
                            }
                        } else if ($card_item['card_type'] == 'timecard') {
                            $card_item['num'] = $goods_item['num'];
                        } else {
                            $card_item['num'] -= $card_item['use_num'];
                        }
                        $num = min($card_item['num'], $goods_item['num']);
                        $promotion_money = round($goods_item['price'] * $num, 2);
                        //定义商品项的属性
                        $goods_item['promotion_money'] += $promotion_money;
                        $goods_item['card_promotion_money'] = $promotion_money;
                        $goods_item['real_goods_money'] = round($goods_item['real_goods_money'] - $promotion_money, 2);
                        $goods_item['card_use_num'] = $num;
                        // 针对通卡进行处理
                        if ($card_item['card_type'] == 'commoncard') {
                            if (isset($common_card[$card_id])) {
                                $common_card[$card_id]['total_use_num'] += $num;
                            } else {
                                $common_card[$card_id] = [
                                    'card_id' => $card_id,
                                    'total_num' => $card_item['total_num'],
                                    'total_use_num' => $card_item['total_use_num'] + $num
                                ];
                            }
                        }
                        $this->promotion_money += $promotion_money;
                    } else {
                        unset($this->param['member_goods_card'][$sku_id]);
                    }
                    $goods_item['member_card_list'] = $card_list;
                }
            }
            //使用的会员卡
            $this->member_goods_card = $this->param['member_goods_card'] ?? [];
        }
        return true;
    }

    /**
     * 使用次卡
     * @return true
     */
    public function useCard()
    {
        $this->getOrderGoodsList();
        foreach ($this->order_goods_list as $k => $v) {
            // 使用次卡
            if ($v['card_item_id']) {
                $card_use_res = (new \addon\cardservice\model\MemberCard())->cardUse([
                    'item_id' => $v['card_item_id'],
                    'num' => $this->goods_list[$k]['card_use_num'],
                    'type' => 'order',
                    'relation_id' => $v['order_goods_id'],
                    'store_id' => $this->store_id
                ]);
                if ($card_use_res['code'] != 0) throw new OrderException($card_use_res['message']);

            }
        }
        return true;
    }


    /**
     * 获取会员卡商品价格
     * @param $goods_sku_info
     * @return array
     */
    public function getMemberCardGoodsPrice($goods_sku_info)
    {
        $res = [
            'discount_price' => 0, // 折扣价（默认等于单价）
            'member_price' => 0, // 会员价
            'price' => 0 // 最低价格
        ];
        $res['discount_price'] = $goods_sku_info['discount_price'];
        $res['price'] = $goods_sku_info['discount_price'];
        if (!addon_is_exit('memberprice') || empty($this->recommend_member_card)) return $this->success($res);
        $level_id = $this->recommend_member_card['level_id'];
        if ($goods_sku_info['is_consume_discount']) {
            if ($goods_sku_info['discount_config'] == 1) {
                // 自定义优惠
                $goods_sku_info['member_price'] = json_decode($goods_sku_info['member_price'], true);
                $value = $goods_sku_info['member_price'][$goods_sku_info['discount_method']][$level_id] ?? 0;
                switch ($goods_sku_info['discount_method']) {
                    case 'discount':
                        // 打折
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            $res['member_price'] = number_format($goods_sku_info['price'] * $value / 10, 2, '.', '');
                        }
                        break;
                    case 'manjian':
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            // 满减
                            $res['member_price'] = number_format($goods_sku_info['price'] - $value, 2, '.', '');
                        }
                        break;
                    case 'fixed_price':
                        if ($value == 0) {
                            $res['member_price'] = $goods_sku_info['price'];
                        } else {
                            // 指定价格
                            $res['member_price'] = number_format($value, 2, '.', '');
                        }
                        break;
                }
            } else {
                // 默认按会员享受折扣计算
                $res['member_price'] = number_format($goods_sku_info['price'] * $this->recommend_member_card['consume_discount'] / 100, 2, '.', '');
            }
            if ($res['member_price'] < $res['price']) {
                $res['price'] = $res['member_price'];
            }
        }
        return $this->success($res);
    }
    /****************************************************************************** 商品次卡 end *****************************************************************************/


    /****************************************************************************** 超级会员卡 end *****************************************************************************/

    /**
     * 获取推荐会员卡
     * @return true
     */
    public function getRecommendMemberCard()
    {
        if (!empty($this->member_account)) {
            if (addon_is_exit('supermember', $this->site_id)) {
                if (!$this->member_account['member_level_type']) {
                    $store_id = $this->param['store_id'] ?? 0;
                    //todo  门店线上不参与推荐会员卡关联购买
//                    if (addon_is_exit('store') && $store_id > 0) {
                    $member_card_model = new MemberCard();
                    $recommend_member_card = $member_card_model->getRecommendMemberCard($this->site_id)['data'] ?? [];
                    if (!empty($recommend_member_card)) {
                        $recommend_member_card['discount_money'] = 0;
                        $recommend_member_card['charge_rule'] = json_decode($recommend_member_card['charge_rule'], true);
                        $this->recommend_member_card = $recommend_member_card;
                    }
                }
//                }
            }
            //是否使用推荐会员卡
            $this->recommend_member_card_data['is_open_card'] = $this->param['is_open_card'] ?? 0;
        }
        return true;
    }

    /**
     * 计算会员卡开卡金额
     * @return true
     */
    public function calculateMemberCardMoney()
    {
        $money = 0;
        $is_open_card = $this->recommend_member_card_data['is_open_card'] ?? 0;
        if (!empty($this->recommend_member_card) && $is_open_card) {
            $charge_rule = $this->recommend_member_card['charge_rule'];
            $member_card_unit = $this->param['member_card_unit'];
            $this->member_card_money = $charge_rule[$member_card_unit] ?? 0;
            $this->recommend_member_card_data['member_card_unit'] = $member_card_unit;
        }
        return true;
    }

    /**
     * 同步创建会员卡订单
     * @return true
     */
    public function createMemberCard()
    {
        if (!empty($this->recommend_member_card) && $this->recommend_member_card_data['is_open_card']) {
            $member_level_order = new MemberLevelOrder();
            $member_card_unit = $this->recommend_member_card_data['member_card_unit'];
            $level_order_result = $member_level_order->create(
                ['out_trade_no' => $this->out_trade_no,
                    'member_id' => $this->member_id,
                    'site_id' => $this->site_id,
                    'level_id' => $this->recommend_member_card['level_id'],
                    'period_unit' => $member_card_unit
                ]
            );
            if ($level_order_result['code'] < 0) throw new OrderException($level_order_result['message']);
            $level_order = $level_order_result['data'];
            model('order')->update(['member_card_order' => $level_order['order_id']], [['order_id', '=', $this->order_id]]);
        }
        return true;
    }
    /****************************************************************************** 超级会员卡 end *****************************************************************************/
}
