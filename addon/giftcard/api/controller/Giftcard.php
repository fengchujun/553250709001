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

use addon\giftcard\model\giftcard\Category as CategoryModel;
use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use addon\giftcard\model\giftcard\Media;
use app\api\controller\BaseApi;
use app\model\shop\Shop;
use think\facade\Db;

/**
 * 礼品卡
 */
class Giftcard extends BaseApi
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
            [ 'status', '=', '1' ],
            [ 'is_delete', '=', '0' ],
            [ 'card_type', '=', 'virtual' ],
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
        $order = Db::raw('field(media_id, ' . $detail[ 'media_ids' ] . ')');
        $detail[ 'media_list' ] = $media_model->getList([ [ 'media_id', 'in', $detail[ 'media_ids' ] ] ], '*', $order)[ 'data' ] ?? [];
        $detail[ 'mobile' ] = ( new Shop() )->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'mobile')[ 'data' ][ 'mobile' ] ?? '';
        $detail[ 'giftcard_desc' ] = $giftcard_model->giftcardDesc($detail) ?? '';

        return $this->response($this->success($detail));
    }

    /**
     * 分类下礼品卡
     */
    public function giftcardListByCategory()
    {
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
        );

        if (!empty($search_text)) {
            $condition[] = [ 'category_name', 'like', '%' . $search_text . '%' ];
        }

        $category_model = new CategoryModel();
        $list = $category_model->getList($condition, '*', 'sort desc');

        $giftcard_model = new GiftCardModel();
        $giftcard_list = $giftcard_model->getGiftcardDetailList([
            [ 'site_id', '=', $this->site_id ],
            [ 'status', '=', 1 ],
            [ 'is_delete', '=', '0' ],
            [ 'card_type', '=', 'virtual' ],
        ], '*', 'sort desc');

        foreach ($list[ 'data' ] as $k => $v) {
            $list[ 'data' ][ $k ][ 'giftcard_list' ] = [];
            foreach ($giftcard_list[ 'data' ] as $key => $val) {
                if ($val[ 'category_id' ] == $v[ 'category_id' ]) {
                    $list[ 'data' ][ $k ][ 'giftcard_list' ][] = $val;
                }
            }
            if (empty($list[ 'data' ][ $k ][ 'giftcard_list' ])) unset($list[ 'data' ][ $k ]);
            ksort($list[ 'data' ]);
        }
        return $this->response($list);
    }

}