<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\api\controller;

use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use addon\giftcard\model\giftcard\Media;
use app\api\controller\BaseApi;
use app\model\shop\Shop;

/**
 * 礼品卡
 */
class GiftcardRecords extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $card_type = $this->params['card_type'] ?? '';
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $search_text = $this->params['search_text'] ?? '';
        $category_id = $this->params['category_id'] ?? 0;

        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'status', '', '1' ]
        );
        if (!empty($search_text)) {
            $condition[] = [ 'card_name', 'like', '%' . $search_text . '%' ];
        }

        if (!empty($card_type)) {
            $condition[] = [ 'card_type', '=', $card_type ];
        }
        if ($category_id > 0) {
            $condition[] = [ 'category_id', '=', $category_id ];
        }
        $giftcard_model = new GiftCardModel();
        $list = $giftcard_model->getGiftcardDetailPageList($condition, $page, $page_size, 'sort desc');
        return $this->response($list);
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $giftcard_id = $this->params['giftcard_id'] ?? 0;
        $giftcard_model = new GiftCardModel();
        $media_model = new Media();
        $detail = $giftcard_model->getGiftcardDetail([ 'giftcard_id' => $giftcard_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
        $detail[ 'media_list' ] = $media_model->getList([ [ 'media_id', 'in', $detail[ 'media_ids' ] ] ])[ 'data' ] ?? [];
        $detail[ 'mobile' ] = ( new Shop() )->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'mobile')[ 'data' ][ 'mobile' ] ?? '';
        $detail[ 'giftcard_desc' ] = $giftcard_model->giftcardDesc($detail) ?? '';

        return $this->response($this->success($detail));
    }

}