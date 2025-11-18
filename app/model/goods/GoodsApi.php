<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\goods;

use addon\bundling\model\Bundling;
use addon\coupon\model\Coupon;
use addon\fenxiao\model\FenxiaoGoods;
use addon\manjian\model\Manjian as ManjianModel;
use addon\supermember\model\MemberCard as MemberCardModel;
use app\dict\goods\GoodsDict;
use app\model\BaseModel;
use app\model\express\Config as ExpressConfig;
use app\model\goods\GoodsCollect as GoodsCollectModel;
use app\model\goods\GoodsEvaluate as GoodsEvaluateModel;
use app\model\web\Config as ConfigModel;

/**
 * 商品
 */
class GoodsApi extends BaseModel
{


    /**
     * api请求获取商品详情
     * @param $site_id
     * @param $sku_id
     * @param $goods_id
     * @param $member_id
     * @param $store_id
     * @param $store_data
     * @return array
     */
    public function getGoodsSkuDetail($site_id, $sku_id, $goods_id, $member_id, $store_id, $store_data, $sale_channel = 'all')
    {
        $goods_model = new Goods();
        if (empty($sku_id) && !empty($goods_id)) {
            $goods_sku_id = model("goods")->getInfo([['goods_id', '=', $goods_id]], 'sku_id');
            if (empty($goods_sku_id)) {
                return $this->error("商品不存在");
            }
            $sku_id = $goods_sku_id['sku_id'];
        }

        $condition = [
            ['gs.sku_id', '=', $sku_id],
            ['gs.is_delete', '=', 0],
            ['g.sale_channel', 'in', $sale_channel],
        ];

        $field = 'gs.goods_id,gs.sku_id,gs.qr_id,gs.goods_name,gs.sku_name,gs.is_consume_discount,gs.discount_config,gs.discount_method,gs.sku_spec_format,gs.price,gs.market_price,gs.member_price,gs.discount_price,gs.promotion_type,gs.start_time
        ,gs.end_time,gs.stock,gs.click_num,(g.sale_num + g.virtual_sale) as sale_num,gs.collect_num,gs.sku_image,gs.sku_images
        ,gs.goods_content,gs.goods_state,gs.is_free_shipping,gs.goods_spec_format,gs.goods_attr_format,gs.introduction,gs.unit,gs.video_url
        ,gs.is_virtual,gs.goods_service_ids,gs.max_buy,gs.min_buy,gs.is_limit,gs.limit_type,gs.support_trade_type,g.goods_image,g.keywords,g.stock_show,g.sale_show,g.market_price_show,g.promotion_addon,g.barrage_show,g.evaluate,g.goods_class,g.sale_store,g.sale_channel,g.label_name,g.category_id,g.is_fenxiao,g.fenxiao_type,gs.fenxiao_price';
        $join = [
            ['goods g', 'g.goods_id = gs.goods_id', 'inner']
        ];
        // 如果是连锁运营模式
        if ($store_data['config']['store_business'] == 'store') {
            $join[] = ['store_goods_sku sgs', 'gs.sku_id = sgs.sku_id and sgs.store_id=' . $store_id, 'left'];

            $field .= ',IFNULL(sgs.status, 0) as store_goods_status';

            $field = str_replace('gs.price', 'IFNULL(IF(g.is_unify_price = 1,gs.price,sgs.price), gs.price) as price', $field);
            $field = str_replace('gs.discount_price', 'IFNULL(IF(g.is_unify_price = 1,gs.discount_price,sgs.price), gs.discount_price) as discount_price', $field);
            if ($store_data['store_info']['stock_type'] == 'store') {
                $field = str_replace('gs.stock', 'IFNULL(sgs.stock, 0) as stock', $field);
            }
        }
        $goods_sku_detail = $goods_model->getGoodsSkuInfo($condition, $field, 'gs', $join)['data'];
        if (empty($goods_sku_detail)) {
            return $this->success(['goods_sku_detail' => null]);
        }

        // 处理商品支持的配送方式
        if ($store_data['config']['store_business'] == 'store') {
            $support_trade_type = explode(',', $goods_sku_detail['support_trade_type']);
            $support_trade_type = array_map(function ($item) use ($store_data) {
                if ($item == 'express' && $store_data['store_info']['is_express']) return $item;
                elseif ($item == 'local' && $store_data['store_info']['is_o2o']) return $item;
                elseif ($item == 'store' && $store_data['store_info']['is_pickup']) return $item;
            }, $support_trade_type);
            $goods_sku_detail['support_trade_type'] = implode(',', array_filter($support_trade_type));

            // 销售渠道设置为线上销售时门店商品状态为1
            if ($goods_sku_detail['sale_channel'] == 'online') {
                $goods_sku_detail['store_goods_status'] = 1;
            }
        }

        //库存转换处理
        $goods_model = new Goods();
        $goods_sku_detail = $goods_model->goodsStockTransform([$goods_sku_detail], $store_id, $store_data['config']['store_business'])[0];

        $goods_sku_detail['purchased_num'] = 0; // 该商品已购数量
        $res['goods_sku_detail'] = $goods_sku_detail;

        //判断预售(单独查询详情跳转)
        $is_join_presale = event('IsJoinPresale', ['sku_id' => $goods_sku_detail['sku_id']], true);
        if (!empty($is_join_presale) && $is_join_presale['code'] == 0) {
            $res['goods_sku_detail'] = array_merge($res['goods_sku_detail'], $is_join_presale['data']);
            $this->handleGoodsDetailData($res['goods_sku_detail'], $member_id, $site_id);
            return $this->success($res);
        }

        //用户已登录
        if ($member_id > 0) {
            $member_info = model("member")->getInfo([['member_id', '=', $member_id]], 'member_id,is_fenxiao,fenxiao_id,member_level,member_level_type');
            if (!empty($member_info)) {
                $res['member_info'] = $member_info;

                //解析会员价
                $res['goods_sku_detail']['member_price_config'] = json_decode($res['goods_sku_detail']['member_price'], true);
                $res = $this->getGoodsPrice($res);
                //商品限购查询购买数量
                if ($goods_sku_detail['is_limit'] && $goods_sku_detail['limit_type'] == 2 && $goods_sku_detail['max_buy'] > 0) {
                    $res['goods_sku_detail']['purchased_num'] = $goods_model->getGoodsPurchasedNum($goods_sku_detail['goods_id'], $member_id);
                }
                //推荐会员卡
                if (addon_is_exit('supermember')) {
                    if ($member_info['member_level_type'] == 0) {
                        $card_model = new MemberCardModel();
                        $card_recommend = $card_model->getRecommendMemberCard($site_id);
                        if (!empty($card_recommend['data'])) {
                            $res['goods_sku_detail']['membercard'] = $card_recommend['data'];
                            $res = $this->getMemberCardGoodsPrice($res);
                        }
                    }
                }
                // 分销佣金详情
                if (addon_is_exit('fenxiao')) {
                    $fenxiao_goods_model = new FenxiaoGoods();
                    $res = $fenxiao_goods_model->getGoodsFenxiaoDetailInApi($res, $member_id, $site_id);
                }


            }
        } else {
            $res['goods_sku_detail']['member_price'] = '';
        }
        // 查询当前商品参与的营销活动信息
        $goods_promotion = event('GoodsPromotion', ['goods_id' => $goods_sku_detail['goods_id'], 'sku_id' => $goods_sku_detail['sku_id'], 'goods_sku_detail' => $res['goods_sku_detail']]);
        $res['goods_sku_detail']['goods_promotion'] = $goods_promotion;
        //查询卡项商品
        if (addon_is_exit('cardservice') && $goods_sku_detail['goods_class'] == GoodsDict::card) {
            $res = $this->getGoodsCardDetail($res);
        }
        //查询领用优惠券
        if (addon_is_exit('coupon')) {
            $coupon = new Coupon();
            $store_id = $store_data['config']['store_business'] == 'store' ? $store_data['store_info']['store_id'] : 0;
            $res = $coupon->getGoodsCanReceiveCouponInApi($res, $member_id, $site_id, $store_id);
        }

        // 组合套餐，连锁门店没有营销活动
        if (addon_is_exit('bundling') && $store_data['config']['store_business'] == 'shop') {
            $bundling = new Bundling();
            $res = $bundling->getBundlingGoodsInApi($res);
        }
        if (addon_is_exit('manjian')) {
            $manjian_model = new ManjianModel();
            $res['goods_sku_detail']['manjian'] = $manjian_model->getGoodsManjianInfo($goods_sku_detail['goods_id'], $site_id)['data'];
        }

        $this->handleGoodsDetailData($res['goods_sku_detail'], $member_id, $site_id);

        return $this->success($res);

    }

    public function getGoodsSkuList($site_id, $goods_id, $member_id, $store_id, $store_data)
    {
        $goods = new Goods();
        $condition = [
            ['gs.goods_id', '=', $goods_id],
            ['gs.site_id', '=', $site_id]
        ];
        $field = 'gs.sku_id,gs.goods_id,g.goods_image,gs.is_consume_discount,gs.discount_config,gs.discount_method,gs.sku_name,gs.sku_spec_format,gs.price,gs.discount_price,gs.promotion_type,gs.end_time,gs.stock,gs.sku_image,gs.sku_images,gs.goods_spec_format,gs.max_buy,gs.min_buy,gs.is_limit,gs.limit_type,gs.support_trade_type,gs.market_price,g.goods_state,gs.member_price,gs.max_buy,g.is_fenxiao,g.fenxiao_type,gs.fenxiao_price,g.promotion_addon';
        $join = [
            ['goods g', 'g.goods_id = gs.goods_id', 'inner']
        ];

        // 如果是连锁运营模式
        if ($store_data['config']['store_business'] == 'store') {
            $join[] = ['store_goods_sku sgs', 'gs.sku_id = sgs.sku_id and sgs.store_id=' . $store_id, 'left'];
            $field = str_replace('gs.price', 'IFNULL(IF(g.is_unify_price = 1,gs.price,sgs.price), gs.price) as price', $field);
            $field = str_replace('gs.discount_price', 'IFNULL(IF(g.is_unify_price = 1,gs.discount_price,sgs.price), gs.discount_price) as discount_price', $field);
            if ($store_data['store_info']['stock_type'] == 'store') {
                $field = str_replace('gs.stock', 'IFNULL(sgs.stock, 0) as stock', $field);
            }
        }
        $list = $goods->getGoodsSkuList($condition, $field, 'gs.sku_id asc', null, 'gs', $join);
        $goods_sku_list = $list['data'];

        //库存转换处理
        $goods_model = new Goods();
        $goods_sku_list = $goods_model->goodsStockTransform($goods_sku_list, $store_id, $store_data['config']['store_business']);

        if ($member_id > 0) {
            //会员信息
            $member_info = model("member")->getInfo([['member_id', '=', $member_id]], 'member_id,is_fenxiao,fenxiao_id,member_level,member_level_type');
            $addon_suppermember_is_exist = addon_is_exit('supermember');
            if ($addon_suppermember_is_exist) {
                if ($member_info['member_level_type'] == 0) {
                    $card_model = new MemberCardModel();
                    $card_recommend = $card_model->getRecommendMemberCard($site_id);
                }
            }
            //会员已购买商品数量
            $buy_num = $goods->getGoodsPurchasedNum($goods_id, $member_id);

            //商品营销活动
            //组装数据
            foreach ($goods_sku_list as $k => $v) {
                $goods_sku_list[$k]['member_info'] = $member_info;
                $goods_sku_list[$k]['member_price_config'] = json_decode($v['member_price'], true);
                $goods_sku_list[$k]['member_price'] = '';
                //商品限购
                if ($v['is_limit'] && $v['limit_type'] == 2 && $v['max_buy'] > 0) {
                    $goods_sku_list[$k]['purchased_num'] = $buy_num;
                }
                //超级会员卡
                if ($addon_suppermember_is_exist) {
                    if ($member_info['member_level_type'] == 0) {
                        $goods_sku_list[$k]['membercard'] = $card_recommend['data'];
                    }
                }
            }
            //处理商品会员价
            $goods_sku_list = $this->getGoodsSkuListPrice($goods_sku_list);
            //处理商品推荐会员卡价格
            $goods_sku_list = $this->getMemberCardGoodsSkuListPrice($goods_sku_list);

            // 分销佣金详情
            if (addon_is_exit('fenxiao')) {
                $fenxiao_goods_model = new FenxiaoGoods();
                $goods_sku_list = $fenxiao_goods_model->getGoodsSkuListFenxiaoInApi($goods_sku_list, $member_id, $site_id);
            }

        }
        //营销活动
        $goods_promotion = event('GoodsPromotion', ['goods_id' => $goods_id, 'sku_id' => $goods_sku_list[0]['sku_id'], 'goods_sku_detail' => $goods_sku_list[0]]);

        if (addon_is_exit('presale')) {
            //预售
            $presale_list = event('IsJoinPresale', ['goods_id' => $goods_id], true)['data'];
            if (!empty($presale_list)) {
                $presale_list = array_column($presale_list, null, 'sku_id');
            }
        }

        //常规情况组装列表数据
        foreach ($goods_sku_list as $k => $v) {
            if (!empty($goods_promotion)) {
                $goods_sku_list[$k]['goods_promotion'] = $goods_promotion;
            }
            if (addon_is_exit('presale')) {
                $presale_sku = $presale_list[$v['sku_id']] ?? [];
                if (!empty($presale_sku)) {
                    $goods_sku_list[$k]['promotion_type'] = 'presale';
                    $goods_sku_list[$k]['presale_id'] = $presale_sku['presale_id'];
                }
            }
        }
        return $this->success($goods_sku_list);
    }

    /**
     * 获取商品sku列表实际价格
     * @param $goods_sku_list
     * @return mixed
     */
    private function getGoodsSkuListPrice($goods_sku_list)
    {
        if (!addon_is_exit("memberprice")) return $goods_sku_list;
        $goods_sku_info = $goods_sku_list[0];
        //商品不参与会员消费折扣
        if (!$goods_sku_info['is_consume_discount']) return $goods_sku_list;
        $member_info = $goods_sku_list[0]['member_info'];
        //非会员
        if (empty($member_info['member_level'])) return $goods_sku_list;
        $member_level = model("member_level")->getInfo([['level_id', '=', $member_info['member_level']]], 'level_id,consume_discount,level_type,is_recommend');
        $member_info['consume_discount'] = $member_level['consume_discount'];
        $member_info['member_level_info'] = $member_level;
        foreach ($goods_sku_list as $k => $sku_info) {
            $member_price = 0;
            if ($goods_sku_info['discount_config'] == 1) {
                // 自定义优惠
                $value = isset($sku_info['member_price_config'][$sku_info['discount_method']][$member_info['member_level']]) ? $sku_info['member_price_config'][$goods_sku_info['discount_method']][$member_info['member_level']] : 0;
                switch ($sku_info['discount_method']) {
                    case "discount":
                        // 打折
                        if ($value == 0) {
                            $member_price = $sku_info['price'];
                        } else {
                            $member_price = number_format($sku_info['price'] * $value / 10, 2, '.', '');
                        }
                        break;

                    case "manjian":
                        if ($value == 0) {
                            $member_price = $goods_sku_info['price'];
                        } else {
                            $member_price = number_format($sku_info['price'] - $value, 2, '.', '');
                        }
                        break;
                    case "fixed_price":
                        if ($value == 0) {
                            $member_price = $sku_info['price'];
                        } else {
                            // 指定价格
                            $member_price = number_format($value, 2, '.', '');
                        }
                        break;
                }
            } else {
                // 默认按会员享受折扣计算
                $member_price = number_format($sku_info['price'] * $member_info['consume_discount'] / 100, 2, '.', '');
            }
            if ($member_price < $sku_info['price']) {
                $sku_info['price'] = $member_price;
            }
            $sku_info['member_price'] = $member_price;
            $sku_info['member_info'] = $member_info;
            $goods_sku_list[$k] = $sku_info;
        }
        return $goods_sku_list;
    }

    /**
     * 获取推荐会员卡对应商品价格
     * @param $goods_sku_list
     * @return array
     */
    public function getMemberCardGoodsSkuListPrice($goods_sku_list)
    {
        $goods_sku_info = $goods_sku_list[0];
        //商品不参与会员消费折扣
        if (!$goods_sku_info['is_consume_discount']) return $goods_sku_list;
        $member_card = $goods_sku_list[0]['membercard'] ?? [];
        if (!$member_card) return $goods_sku_list;
        foreach ($goods_sku_list as $k => $sku_info) {
            $member_price = 0;
            if ($goods_sku_info['discount_config'] == 1) {
                // 自定义优惠
                $value = $sku_info['member_price_config'][$sku_info['discount_method']][$member_card['level_id']] ?? 0;
                switch ($sku_info['discount_method']) {
                    case "discount":
                        // 打折
                        if ($value == 0) {
                            $member_price = $sku_info['price'];
                        } else {
                            $member_price = number_format($sku_info['price'] * $value / 10, 2, '.', '');
                        }
                        break;
                    case "manjian":
                        if ($value == 0) {
                            $member_price = $sku_info['price'];
                        } else {
                            // 满减
                            $member_price = number_format($sku_info['price'] - $value, 2, '.', '');
                        }
                        break;
                    case "fixed_price":
                        if ($value == 0) {
                            $member_price = $sku_info['price'];
                        } else {
                            // 指定价格
                            $member_price = number_format($value, 2, '.', '');
                        }
                        break;
                }
            } else {
                // 默认按会员享受折扣计算
                $member_price = number_format($sku_info['price'] * $member_card['consume_discount'] / 100, 2, '.', '');
            }
            $goods_sku_list[$k]['membercard']['member_price'] = $member_price;
        }
        return $goods_sku_list;
    }

    /**
     * 获取商品信息
     * @param $goods_sku_detail_array
     * @return array
     */
    public function getGoodsCardDetail($goods_sku_detail_array)
    {
        $condition = [
            ['goods_id', '=', $goods_sku_detail_array['goods_sku_detail']['goods_id']]
        ];
        $field = 'card_type,renew_price,recharge_money,common_num,discount_goods_type,discount,validity_type,validity_day,validity_time';

        $goods_sku_detail_array['goods_sku_detail']['card_info'] = model('goods_card')->getInfo($condition, $field);
        $sku_condition = [
            ['gci.card_goods_id', '=', $goods_sku_detail_array['goods_sku_detail']['goods_id']],
        ];
        $sku_field = 'gci.goods_id,gci.sku_id,gci.num,gci.discount,gs.sku_name,gs.price,gs.sku_image,gs.goods_class_name';
        $sku_join = [['goods_sku gs', 'gs.sku_id = gci.sku_id', 'inner']];

        $goods_sku_detail_array['goods_sku_detail']['card_info']['relation_goods'] = model('goods_card_item')->getList($sku_condition, $sku_field, 'gci.id asc', 'gci', $sku_join);
        return $goods_sku_detail_array;

    }

    /**
     * 处理商品详情公共数据
     * @param $data
     * @param $member_id
     * @param $site_id
     */
    public function handleGoodsDetailData(&$data, $member_id, $site_id)
    {
        $goods = new Goods();

        if (!empty($data['sku_images'])) $data['sku_images_list'] = $goods->getGoodsImage($data['sku_images'], $site_id)['data'] ?? [];
        if (!empty($data['sku_image'])) $data['sku_image_list'] = $goods->getGoodsImage($data['sku_image'], $site_id)['data'] ?? [];
        if (!empty($data['goods_image'])) $data['goods_image_list'] = $goods->getGoodsImage($data['goods_image'], $site_id)['data'] ?? [];

        // 商品服务
        if (!empty($data['goods_service_ids'])) {
            $goods_service = new GoodsService();
            $data['goods_service'] = $goods_service->getServiceList([['site_id', '=', $site_id], ['id', 'in', $data['goods_service_ids']]], 'service_name,desc,icon')['data'];
        } else
            $data['goods_service'] = [];

        // 商品详情配置
        $config_model = new ConfigModel();
        $data['config'] = $config_model->getGoodsDetailConfig($site_id)['data']['value'];

        if ($data['is_virtual'] == 0) {
            $data['express_type'] = (new ExpressConfig())->getEnabledExpressType($site_id);
        }

        // 获取用户是否关注
        if ($member_id != 0) {
            $goods_collect_model = new GoodsCollectModel();
            $data['is_collect'] = $goods_collect_model->getIsCollect($data['goods_id'], $member_id)['data'];
        } else {
            $data['is_collect'] = 0;
        }

        // 评价查询
        $goods_evaluate_model = new GoodsEvaluateModel();
        $condition = [
            ['is_show', '=', 1],
            ['is_audit', '=', 1],
            ['goods_id', '=', $data['goods_id']]
        ];
        $field = 'evaluate_id,content,images,explain_first,member_name,member_headimg,is_anonymous,again_content,again_images,again_explain,create_time,again_time,scores';
        $order = "create_time desc";
        $data['evaluate_list'] = $goods_evaluate_model->getSecondEvaluateInfo($condition, $field, $order)['data'];
        $data['evaluate_count'] = $goods_evaluate_model->getEvaluateCount($condition)['data'];
        $config_model = new \app\model\order\Config();
        $data['evaluate_config'] = $config_model->getOrderEvaluateConfig($site_id)['data']['value'];
    }

    /**
     * api请求获取商品价格（计算折扣价与会员价）
     * @param $goods_sku_detail_array
     * @return mixed
     */
    private function getGoodsPrice($goods_sku_detail_array)
    {

        if (!addon_is_exit("memberprice")) return $goods_sku_detail_array;
        $goods_sku_info = $goods_sku_detail_array['goods_sku_detail'];
        $member_info = $goods_sku_detail_array['member_info'];
        $member_price = 0;

        if ($goods_sku_info['is_consume_discount']) {

            if (!empty($member_info['member_level'])) {
                $member_level = model("member_level")->getInfo([['level_id', '=', $member_info['member_level']]], 'level_id,consume_discount,level_type,is_recommend');
                $member_info['consume_discount'] = $member_level['consume_discount'];
                $member_info['member_level_info'] = $member_level;
            }

            if (!empty($member_info['member_level'])) {
                if ($goods_sku_info['discount_config'] == 1) {
                    // 自定义优惠
                    $value = $goods_sku_info['member_price_config'][$goods_sku_info['discount_method']][$member_info['member_level']] ?? 0;
                    switch ($goods_sku_info['discount_method']) {
                        case "discount":
                            // 打折
                            if ($value == 0) {
                                $member_price = $goods_sku_info['price'];
                            } else {
                                $member_price = number_format($goods_sku_info['price'] * $value / 10, 2, '.', '');
                            }
                            break;
                        case "manjian":
                            if ($value == 0) {
                                $member_price = $goods_sku_info['price'];
                            } else {
                                $member_price = number_format($goods_sku_info['price'] - $value, 2, '.', '');
                            }
                            break;
                        case "fixed_price":
                            if ($value == 0) {
                                $member_price = $goods_sku_info['price'];
                            } else
                                // 指定价格
                                $member_price = number_format($value, 2, '.', '');
                            break;
                    }
                } else {
                    // 默认按会员享受折扣计算
                    $member_price = number_format($goods_sku_info['price'] * $member_info['consume_discount'] / 100, 2, '.', '');
                }
                if ($member_price < $goods_sku_info['price']) {
                    $goods_sku_info['price'] = $member_price;
                }
            }
        }
        $goods_sku_info['member_price'] = $member_price;
        $goods_sku_detail_array['goods_sku_detail'] = $goods_sku_info;
        $goods_sku_detail_array['member_info'] = $member_info;
        return $goods_sku_detail_array;
    }

    /**
     * 获取推荐会员卡对应商品价格
     * @param $goods_sku_detail_array
     * @return array
     */
    public function getMemberCardGoodsPrice($goods_sku_detail_array)
    {
        if (!addon_is_exit("memberprice")) return $goods_sku_detail_array;

        $goods_sku_info = $goods_sku_detail_array['goods_sku_detail'];
        $member_card = $goods_sku_detail_array['goods_sku_detail']['membercard'];

        $member_price = 0;
        $price = $goods_sku_info['discount_price'];

        if ($goods_sku_info['is_consume_discount']) {
            if ($goods_sku_info['discount_config'] == 1) {
                // 自定义优惠
                $value = $goods_sku_info['member_price_config'][$goods_sku_info['discount_method']][$member_card['level_id']] ?? 0;
                switch ($goods_sku_info['discount_method']) {
                    case "discount":
                        // 打折
                        if ($value == 0) {
                            $member_price = $goods_sku_info['discount_price'];
                        } else {
                            $member_price = number_format($goods_sku_info['discount_price'] * $value / 10, 2, '.', '');
                        }
                        break;
                    case "manjian":
                        if ($value == 0) {
                            $member_price = $goods_sku_info['discount_price'];
                        } else {
                            // 满减
                            $member_price = number_format($goods_sku_info['discount_price'] - $value, 2, '.', '');
                        }
                        break;
                    case "fixed_price":
                        if ($value == 0) {
                            $member_price = $goods_sku_info['discount_price'];
                        } else {
                            // 指定价格
                            $member_price = number_format($value, 2, '.', '');
                        }
                        break;
                }
            } else {
                // 默认按会员享受折扣计算
                $member_price = number_format($goods_sku_info['discount_price'] * $member_card['consume_discount'] / 100, 2, '.', '');
            }
            if ($member_price < $price) {
                $price = $member_price;
            }
        }
        $member_card['member_price'] = $price;
        $goods_sku_detail_array['goods_sku_detail']['membercard'] = $member_card;
        return $goods_sku_detail_array;
    }
}