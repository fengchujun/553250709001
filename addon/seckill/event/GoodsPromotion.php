<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\event;

use addon\seckill\model\Seckill;
use app\model\goods\Goods as GoodsModel;

/**
 * 商品营销活动信息
 */
class GoodsPromotion
{

    /**
     * 商品营销活动信息
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if (isset($param[ 'goods_sku_detail' ])) {
            $goods_info = $param[ 'goods_sku_detail' ];
            if (!empty($goods_info[ 'promotion_addon' ])) {
                $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
                if (!empty($promotion_addon[ 'seckill' ])) {
                    return [
                        'promotion_type' => 'seckill',
                        'promotion_name' => '限时秒杀',
                        'id' => $promotion_addon[ 'seckill' ]
                    ];
                }
            }

        } else {
            if (empty($param[ 'goods_id' ])) return [];
            $goods_model = new GoodsModel();
            $goods_info = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $param[ 'goods_id' ] ] ], 'promotion_addon')[ 'data' ];
            if (!empty($goods_info[ 'promotion_addon' ])) {
                $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
                if (!empty($promotion_addon[ 'seckill' ])) {
                    $seckill_model = new Seckill();
                    $goods_detail = $seckill_model->getSeckillInfo($promotion_addon[ 'seckill' ])[ 'data' ];
                    if (!empty($goods_detail)) {
                        $time = time() - strtotime(date('Y-m-d'), time());
                        if ($time > $goods_detail[ 'seckill_start_time' ] && $time < $goods_detail[ 'seckill_end_time' ]) {
                            $goods_detail[ 'promotion_type' ] = 'seckill';
                            $goods_detail[ 'promotion_name' ] = '限时秒杀';
                            return $goods_detail;
                        }
                    }
                }
            }
            return [];
        }
    }
}