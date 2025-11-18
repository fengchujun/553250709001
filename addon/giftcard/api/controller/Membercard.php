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
use addon\giftcard\model\membercard\MemberCard as MemberCardModel;
use addon\giftcard\model\membercard\Poster;
use addon\giftcard\model\transfer\Blessing;
use app\api\controller\BaseApi;
use app\model\shop\Shop;
use addon\giftcard\model\card\CardUse as CardUseModel;

/**
 * 礼品卡
 */
class Membercard extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $is_transfer = $this->params[ 'is_transfer' ] ?? 'all';
        $status = $this->params[ 'status' ] ?? 'all';
        $source = $this->params[ 'source' ] ?? 'all';
        $order = $this->params[ 'order' ] ?? 'get_time';
        $order_id = $this->params[ 'order_id' ] ?? 0;

        $condition = array (
            [ 'gc.site_id', '=', $this->site_id ],
            [ 'gmc.member_id', '=', $this->member_id ]
        );
        if ($status != 'all') {
            $condition[] = [ 'gc.status', '=', $status ];
        }
        if ($is_transfer != 'all') {
            $condition[] = [ 'gmc.is_transfer', '=', $is_transfer ];
        }

        if ($source != 'all') {
            $condition[] = [ 'gmc.source', '=', $source ];
        }
        if (!empty($order_id)) {
            $condition[] = [ 'gc.order_id', '=', $order_id ];
            $condition[] = [ 'gmc.source', '=', 'order' ];
        }

        $member_card_model = new MemberCardModel();
        $order_by = 'gmc.' . $order . ' desc';

        $list = $member_card_model->getMemberCardDetailPageList($condition, $page, $page_size, $order_by);
        return $this->response($list);
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $token = $this->checkToken();
//        if ($token['code'] < 0) return $this->response($token);

        $member_card_id = $this->params[ 'member_card_id' ] ?? 0;
        $member_card_model = new MemberCardModel();
        $params = array (
            'site_id' => $this->site_id,
//            'member_id' => $this->member_id,
            'member_card_id' => $member_card_id
        );
        $detail = $member_card_model->getMemberCardDetail($params)[ 'data' ] ?? [];
        if (!empty($detail)) {
            $detail[ 'is_self' ] = $detail[ 'member_id' ] == $this->member_id ? 1 : 0;
            $detail[ 'mobile' ] = ( new Shop() )->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'mobile')[ 'data' ][ 'mobile' ] ?? '';
            $giftcard_model = new GiftCardModel();
            $giftcard_info = $giftcard_model->getGiftcardInfo([ [ 'giftcard_id', '=', $detail[ 'giftcard_id' ] ] ], 'instruction')[ 'data' ] ?? [];
            $detail[ 'giftcard_desc' ] = $giftcard_model->giftcardDesc($detail) ?? '';
            $detail[ 'instruction' ] = $giftcard_info[ 'instruction' ] ?? '';

            if ($detail[ 'status' ] == 'used') {
                $card_use_model = new CardUseModel();
                $card_use_condition = array (
                    [ 'card_id', '=', $detail[ 'card_id' ] ]
                );
                $card_use_info = $card_use_model->getCardUseRecordsInfo($card_use_condition, 'order_id')[ 'data' ] ?? [];
                if (!empty($card_use_info)) {
                    $detail[ 'use_order_id' ] = $card_use_info[ 'order_id' ];
                }
            }
            if ($detail[ 'to_member_id' ] > 0) {
                $blessing_model = new Blessing();
                $blessing_info = $blessing_model->getMemberCardBlessingInfo([ [ 'card_id', '=', $detail[ 'card_id' ] ], [ 'to_member_id', '=', $detail[ 'to_member_id' ] ] ], 'blessing')[ 'data' ] ?? [];
                $detail[ 'blessing' ] = $blessing_info[ 'blessing' ] ?? '';
            }

        }

        return $this->response($this->success($detail));
    }

    /**
     * 祝福
     * @return false|string
     */
    public function blessing()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);
        $member_card_id = $this->params[ 'member_card_id' ] ?? 0;
        $blessing = $this->params[ 'blessing' ] ?? '';
        $blessing_model = new Blessing();
        $params = array (
            'blessing' => $blessing,
            'member_id' => $this->member_id,
            'member_card_id' => $member_card_id,
            'site_id' => $this->site_id
        );
        $result = $blessing_model->blessing($params);
        return $this->response($result);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'giftcard';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'source_member' ] = $this->member_id;
        $poster = new Poster();
        $res = $poster->poster($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }

}