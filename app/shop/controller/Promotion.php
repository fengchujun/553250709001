<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use addon\coupon\model\Coupon;
use app\model\member\MemberAccount;
use app\model\order\Order;
use app\model\system\Addon;
use app\model\system\AddonQuick;
use app\model\system\Menu;
use app\model\system\Promotion as PromotionModel;
use app\model\system\User as UserModel;
use think\App;

/**
 * 营销
 * Class Promotion
 * @package app\shop\controller
 */
class Promotion extends BaseShop
{
    protected $addons = [];

    /**
     * 获取插件
     */
    protected function getAddons()
    {
        if ($this->user_info[ 'is_admin' ] || $this->group_info[ 'is_system' ] == 1) {
            $this->addons = [];
        } else {
            $field = 'addon';
            $menu_model = new Menu();
            $menu_array = "'".str_replace(',',"','", $this->group_info[ 'menu_array' ])."'";
            $menu_list = $menu_model->getMenuList([
                [ 'app_module', '=', $this->app_module ],
                ['', 'exp', \think\facade\Db::raw("name in ({$menu_array}) or is_control = 0")]
            ], $field)['data'];
            $this->addons = array_unique(array_column($menu_list, 'addon'));
        }
        return $this->addons;
    }
    
    /**
     * 营销概况
     */
    public function index()
    {
        $this->getAddons();
        $promotion_model = new PromotionModel();

        $length = input('length', 0);
        $start_time = date('Y-m-01', strtotime($length . ' month'));
        $end_time = date('Y-m-d', strtotime("$start_time +1 month -1 day"));
        $start_time = strtotime($start_time . ' 00:00:00');
        $end_time = strtotime($end_time . ' 23:59:59');

        $this->assign('month', date('Y/m', $start_time));
        $this->assign('days', date('t', $start_time));
        $this->assign('start_time', $start_time);

        //营销配置
        $promotion_config = $promotion_model->getPromotionConfig($start_time, $end_time, $this->site_id, $this->addons)['data'];
        $promotion_config = $this->dealWithRedirect($promotion_config);
        $this->assign('promotion_config', $promotion_config);
        //营销活动
        $all_promotion = array_column($promotion_model->getSitePromotions($this->site_id, $this->addons), null, 'name');
        $all_promotion = array_filter(array_map(function($item) {
            if ($item[ 'show_type' ] == 'shop' || $item[ 'show_type' ] == 'member') return $item;
        }, $all_promotion));
        $all_promotion = $this->dealWithRedirect($all_promotion);
        $this->assign('all_promotion', $all_promotion);


        return $this->fetch('promotion/index');
    }

    /**
     * 营销活动
     * @return mixed
     */
    public function market()
    {
        $this->getAddons();
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getSitePromotions($this->site_id, $this->addons);
        $promotions = $this->dealWithRedirect($promotions);
        $this->assign('promotion', $promotions);

        $user_info = $this->user_info;
        $this->assign('user_info', $user_info);

        $addon_quick_model = new AddonQuick();

        //店铺促销
        $shop_addon = $addon_quick_model->getAddonQuickByAddonType($promotions, 'shop');
        $this->assign('shop_addon', $shop_addon);

        $member_addon = $addon_quick_model->getAddonQuickByAddonType($promotions, 'member');
        $this->assign('member_addon', $member_addon);

        $addon_model = new Addon();
        $value = $addon_model->getAddonQuickMenuConfig($this->site_id, $this->app_module)[ 'data' ][ 'value' ];
        $promotion_addon = $value[ 'promotion' ];

        $this->assign('common_addon', $promotion_addon);

        return $this->fetch('promotion/market');
    }

    /**
     * 会员营销
     * @return mixed
     */
    public function member()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getSitePromotions($this->site_id);
        $addon_quick_model = new AddonQuick();
        $addon = $addon_quick_model->getAddonQuickByAddonType($promotions, 'member');
        $this->assign('tool_addon', $addon);
        $user_info = $this->user_info;
        $this->assign('user_info', $user_info);
        $this->assign('promotion', $promotions);
        return $this->fetch('promotion/member');
    }

    /**
     * 营销工具
     * @return mixed
     */
    public function tool()
    {
        $this->getAddons();
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions($this->addons);
        $promotions['shop'] = $this->dealWithRedirect($promotions['shop']);
        $this->assign('promotion', $promotions[ 'shop' ]);

        $addon_quick_model = new AddonQuick();
        $addon = $addon_quick_model->getAddonQuickByAddonType($promotions[ 'shop' ], 'tool');
        $this->assign('tool_addon', $addon);

        $user_info = $this->user_info;
        $this->assign('user_info', $user_info);

        $addon_model = new Addon();
        $value = $addon_model->getAddonQuickMenuConfig($this->site_id, $this->app_module)[ 'data' ][ 'value' ];
        $tool_addon = $value[ 'tool' ];

        $this->assign('common_addon', $tool_addon);
        return $this->fetch('promotion/tool');
    }

    public function summary()
    {
        if (request()->isJson()) {
            $coupon_model = new Coupon();
            $order_model = new Order();
            $account_model = new MemberAccount();

            $promotion = event('ShowPromotion', [ 'count' => 1, 'site_id' => $this->site_id ]);
            $promotion = array_map(function($item) {
                if (isset($item[ 'shop' ]) && !empty($item[ 'shop' ]) && isset($item[ 'shop' ][ 0 ][ 'summary' ]) && !empty($item[ 'shop' ][ 0 ][ 'summary' ])) return $item[ 'shop' ][ 0 ][ 'summary' ][ 'count' ];
            }, $promotion);

            $data = [
                'promotion_num' => array_sum($promotion),
                'coupon_total_count' => $coupon_model->getMemberCouponCount([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ],
                'coupon_used_count' => $coupon_model->getMemberCouponCount([ [ 'site_id', '=', $this->site_id ], [ 'state', '=', 2 ] ])[ 'data' ],
                'buyer_num' => $order_model->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'promotion_type', '<>', '' ] ], 'order_id', 'a', null, 'member_id')[ 'data' ],
                'deal_num' => $order_model->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'promotion_type', '<>', '' ], [ 'pay_status', '=', 1 ] ], 'order_id', 'a', null, 'member_id')[ 'data' ],
                'order_num' => $order_model->getOrderCount([ [ 'site_id', '=', $this->site_id ], [ 'promotion_type', '<>', '' ], [ 'pay_status', '=', 1 ] ], 'order_id')[ 'data' ],
                'order_money' => $order_model->getOrderMoneySum([ [ 'site_id', '=', $this->site_id ], [ 'promotion_type', '<>', '' ], [ 'pay_status', '=', 1 ] ])[ 'data' ],
                'grant_point' => round($account_model->getMemberAccountSum([ [ 'site_id', '=', $this->site_id ], [ 'account_data', '>', 0 ], [ 'from_type', 'not in', [ 'adjust', 'refund', 'pointexchangerefund', 'presale_refund' ] ] ], 'account_data')[ 'data' ])
            ];

            return success(0, '', $data);
        }
    }

    /**
     * 常用功能设置
     */
    public function commonAddonSetting()
    {
        if (request()->isJson()) {
            $addon_model = new Addon();
            $res = $addon_model->setAddonQuickMenuConfig([
                'site_id' => $this->site_id,
                'app_module' => $this->app_module,
                'addon' => input('addon', ''),
                'type' => input('type', 'promotion')
            ]);
            return $res;
        }
    }

    /**
     * 活动专区页配置
     * @return mixed
     */
    public function zoneConfig()
    {
        $promotion_model = new PromotionModel();
        if (request()->isJson()) {
            $data = [
                'name' => input('name', ''),
                'title' => input('title', ''),
                'bg_color' => input('bg_color', ''), // 背景色
            ];
            $res = $promotion_model->setPromotionZoneConfig($data, $this->site_id, $this->app_module);
            return $res;
        } else {
            $promotion_zone_list = event('PromotionZoneConfig');
            $this->assign('promotion_zone_list', $promotion_zone_list);

            $promotion_config_list = []; // 活动专区页面配置列表
            $config = []; // 第一个活动页面配置

            if (!empty($promotion_zone_list)) {
                foreach ($promotion_zone_list as $k => $v) {
                    $promotion_config_list[ $v[ 'name' ] ] = $promotion_model->getPromotionZoneConfig($v[ 'name' ], $this->site_id, $this->app_module)[ 'data' ][ 'value' ];
                    if ($k == 0) {
                        $config = $promotion_config_list[ $v[ 'name' ] ];
                    }
                }
            }

            $this->assign('config', $config);
            $this->assign('promotion_config_list', $promotion_config_list);

            return $this->fetch('promotion/zone_config');
        }
    }

    /**
     * 营销统计
     * @return array
     */
    public function getPromotionStat()
    {
        $promotion_model = new PromotionModel();
        $length = input('length', 0);
        $start_time = date('Y-m-01', strtotime($length . ' month'));
        $end_time = date('Y-m-d', strtotime("$start_time +1 month -1 day"));
        $start_time = strtotime($start_time . ' 00:00:00');
        $end_time = strtotime($end_time . ' 23:59:59');
        $promotion = $promotion_model->getPromotionStat($start_time, $end_time, $this->site_id);
        return $promotion;
    }

    /**
     * 营销信息
     * @return array
     */
    public function getPromotion()
    {
        $this->getAddons();
        $length = input('length', 0);
        $start_time = date('Y-m-01', strtotime($length . ' month'));
        $end_time = date('Y-m-d', strtotime("$start_time +1 month -1 day"));
        $start_time = strtotime($start_time . ' 00:00:00');
        $end_time = strtotime($end_time . ' 23:59:59');

        $promotion_model = new PromotionModel();
        $summary = $promotion_model->getPromotionSummary($start_time, $end_time, $this->site_id, $this->addons)[ 'data' ];

        return success(0, '', [
            'month' => date('Y/m', $start_time),
            'days' => (int) date('t', $start_time),
            'start_time' => $start_time,
            'data' => $summary
        ]);
    }

    public function dealWithRedirect($promotions)
    {
        if(!($this->user_info['is_admin'] == 1 || $this->group_info['is_system'] == 1)){
            $user_model = new UserModel();
            foreach($promotions as $key=>$promotion){
                if(in_array($promotion['name'], ['coupon', 'bargain', 'pintuan', 'blindbox', 'fenxiao', 'giftcard', 'jielong', 'pinfan', 'presale', 'seckill', 'form', 'live', 'notes', 'printer', 'virtualevaluation'])){
                    $check_res = $user_model->checkAndGetRedirectUrl(['url' => $promotion['url'], 'app_module' => $this->app_module], $this->group_info);
                    if($check_res['redirect_url']){
                        $promotions[$key]['url'] = $check_res[ 'redirect_url' ];
                    }
                }
            }
        }
        return $promotions;
    }
}