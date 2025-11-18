<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\shop\controller;

use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use addon\giftcard\model\giftcard\Category as GiftCardCategoryModel;

/**
 * 礼品卡记录控制器
 */
class GiftcardRecords extends Giftcard
{
    /**
     * 兑换卡列表
     * @return array|mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $card_type = input('card_type', '');
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $status = input('status', 'all');
            $category_id = input('category_id', 0);
            $condition = array (
                [ 'site_id', '=', $this->site_id ]
            );
            if (!empty($search_text)) {
                $condition[] = [ 'card_name', 'like', '%' . $search_text . '%' ];
            }
            if ($status != 'all') {
                $condition[] = [ 'status', '=', $status ];
            }
            if (!empty($card_type)) {
                $condition[] = [ 'card_type', '=', $card_type ];
            }
            if ($category_id > 0) {
                $condition[] = [ 'category_id', '=', $category_id ];
            }
            $giftcard_model = new GiftCardModel();
            $list = $giftcard_model->getGiftcardDetailPageList($condition, $page, $page_size);
            return $list;
        } else {
            return $this->fetch('giftcard/lists');
        }
    }

    public function add()
    {
        if (request()->isJson()) {
            $goods_sku_list = input('goods_sku_list', '');
            $goods_sku_list = empty($goods_sku_list) ? [] : json_decode($goods_sku_list, true);
            $data = [
                'card_name' => input('card_name', ''),
                'card_count' => input('card_count', 0),
                'card_cover' => input('card_cover', ''),
                'cdk_length' => input('cdk_length', 0),
                'cdk_prefix' => input('cdk_prefix', ''),
                'cdk_suffix' => input('cdk_suffix', ''),
                'cdk_type' => input('cdk_type', ''),
                'card_right_type' => input('card_right_type', ''),
                'card_right_goods_type' => input('card_right_goods_type', ''),
                'card_right_goods_count' => input('card_right_goods_count', ''),
                'card_price' => input('card_price', 0),
                'balance' => input('balance', 0),
//                'sale_num' => input('sale_num', 0),
                'sort' => input('sort', 0),
                'validity_type' => input('validity_type', ''),
                'validity_time' => input('validity_time', 0),
                'validity_day' => input('validity_day', 0),
                'status' => input('status', 0),
                'media_id' => input('media_id', 0),
                'category_id' => input('category_id', 0),
                'card_type' => input('card_type', ''),
                'is_allow_transfer' => input('is_allow_transfer', 0),
                'site_id' => $this->site_id,
                'goods_sku_list' => $goods_sku_list
            ];
            $giftcard_model = new GiftCardModel();
            $result = $giftcard_model->addGiftCard($data);
            return $result;
        } else {
            $this->assign('category_list', ( new GiftCardCategoryModel() )->getList([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? []);
            return $this->fetch('giftcard/add');
        }
    }

    /**
     * 编辑礼品卡活动
     */
    public function edit()
    {
        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        if (request()->isJson()) {
            $goods_sku_list = input('goods_sku_list', '');
            $goods_sku_list = empty($goods_sku_list) ? [] : json_decode($goods_sku_list, true);
            $data = [
                'card_name' => input('card_name', ''),
                'card_count' => input('card_count', 0),
                'card_cover' => input('card_cover', ''),
                'cdk_length' => input('cdk_length', 0),
                'cdk_prefix' => input('cdk_prefix', ''),
                'cdk_suffix' => input('cdk_suffix', ''),
                'cdk_type' => input('cdk_type', ''),
                'card_right_type' => input('card_right_type', ''),
                'card_right_goods_type' => input('card_right_goods_type', ''),
                'card_right_goods_count' => input('card_right_goods_count', ''),
                'card_price' => input('card_price', 0),
                'balance' => input('balance', 0),
//                'sale_num' => input('sale_num', 0),
                'sort' => input('sort', 0),
                'validity_type' => input('validity_type', ''),
                'validity_time' => input('validity_time', 0),
                'validity_day' => input('validity_day', 0),
                'status' => input('status', 0),
                'media_id' => input('media_id', 0),
                'category_id' => input('category_id', 0),
                'is_allow_transfer' => input('is_allow_transfer', 0),
                'site_id' => $this->site_id,
                'goods_sku_list' => $goods_sku_list,
                'giftcard_id' => $giftcard_id
            ];
//
            $result = $giftcard_model->editGiftCard($data);
            return $result;
        } else {

            $this->assign('category_list', ( new GiftCardCategoryModel() )->getList([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? []);
            $detail = $giftcard_model->getGiftcardDetail([ 'giftcard_id' => $giftcard_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
            $this->assign('detail', $detail);
            return $this->fetch('giftcard/edit');
        }
    }


    /**
     * 活动详情
     */
    public function detail()
    {
        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        $detail = $giftcard_model->getGiftcardDetail([ 'giftcard_id' => $giftcard_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
        $this->assign('detail', $detail);
        return $this->fetch('giftcard/detail');
    }

    /**
     * 删除
     * @return mixed
     */
    public function delete()
    {
        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        $result = $giftcard_model->deleteGiftcard([ [ 'giftcard_id', '=', $giftcard_id ], [ 'site_id', '=', $this->site_id ] ]);

        return $result;
    }

    public function sort()
    {
        $giftcard_id = input('giftcard_id', 0);
        $sort = input('sort', 0);
        $giftcard_model = new GiftCardModel();
        $result = $giftcard_model->modifyGiftcardSort($sort, [ [ 'giftcard_id', '=', $giftcard_id ], [ 'site_id', '=', $this->site_id ] ]);
        return $result;
    }
}