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


use addon\giftcard\model\card\Card as CardModel;
use addon\giftcard\model\card\CardImport as CardImportModel;
use addon\giftcard\model\card\CardLog;
use addon\giftcard\model\card\CardOperation;
use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use addon\giftcard\model\membercard\MemberCard;

/**
 * 礼品卡控制器
 */
class Card extends Giftcard
{


    /**
     * 兑换卡列表
     * @return array|mixed
     */
    public function lists()
    {
        $giftcard_id = input('giftcard_id', 0);
        $import_id = input('import_id', 0);
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $status = input('status', 'all');
            $import_name = input('import_name', '');
            $condition = array (
                [ 'gc.site_id', '=', $this->site_id ],
            );
            if (!empty($search_text)) {
                $condition[] = [ 'gc.card_no', 'like', '%' . $search_text . '%' ];
            }
            if ($status != 'all') {
                $condition[] = [ 'gc.status', '=', $status ];
            }
            if (!empty($card_type)) {
                $condition[] = [ 'gc.card_type', '=', $card_type ];
            }
            if ($giftcard_id > 0) {
                $condition[] = [ 'gc.giftcard_id', '=', $giftcard_id ];
            }

            if ($import_name) {
                $import_model = new CardImportModel();
                $import_ids = $import_model->getCardImportColumn([ [ 'name', '=', $import_name ] ], 'import_id')[ 'data' ] ?? [];
                if (!empty($import_id)) {
                    $import_id = array_merge($import_ids, [ $import_id, '-1' ]);
                } else {
                    $import_id = array_merge($import_ids, [ -1 ]);
                }
            }

            if (!empty($import_id)) {
                $condition[] = [ 'gc.import_id', 'in', $import_id ];
            }

            $card_model = new CardModel();
            $list = $card_model->getCardPageList($condition, $page, $page_size, 'gc.card_id desc', 'gc.*,go.order_no', 'gc', [
                [ 'giftcard_order go', 'gc.order_id=go.order_id', 'left' ]
            ])[ 'data' ];
            foreach ($list[ 'list' ] as $k => $v) {
                $list[ 'list' ][ $k ] = $card_model->tran($v);
            }
            return $card_model->success($list);
        } else {
            $this->assign('import_id', $import_id);
            $this->assign('giftcard_id', $giftcard_id);
            $giftcard_info = ( new GiftCardModel() )->getGiftcardInfo([ [ 'giftcard_id', '=', $giftcard_id ] ], 'card_type')[ 'data' ] ?? [];
            $this->assign('status_list', ( new CardModel() )->getStatusList($giftcard_info[ 'card_type' ]));
            return $this->fetch('card/lists');
        }
    }


    /**
     * 详情
     */
    public function detail()
    {
        $card_id = input('card_id', 0);
        $card_model = new CardModel();
        $member_card = new MemberCard();
        $detail = $card_model->getCardDetail([ 'card_id' => $card_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
        if (empty($detail))
            $this->error('找不到礼品卡项');
        $detail[ 'status_name' ] = $card_model->getStatusList($detail[ 'card_type' ])[ $detail[ 'status' ] ] ?? '';
        $this->assign('detail', $detail);
        $member_card_list = $member_card->getMemberCardDetailList([ [ 'gmc.card_id', '=', $card_id ], [ 'gmc.site_id', '=', $this->site_id ] ])[ 'data' ] ?? [];

        $this->assign('member_card_list', $member_card_list);
        $card_log_model = new CardLog();
        $card_log_condition = array (
            [ 'card_id', '=', $card_id ],
            [ 'site_id', '=', $this->site_id ]
        );
        $card_log_list = $card_log_model->getCardLogList($card_log_condition, '*', 'create_time desc')[ 'data' ] ?? [];
        $this->assign('card_log_list', $card_log_list);
        return $this->fetch('card/detail');
    }

    /**
     * 删除
     * @return array|mixed
     */
    public function delete()
    {
        $card_id = input('card_id', 0);
        $card_model = new CardModel();
        $params = array (
            'site_id' => $this->site_id,
            'card_id' => $card_id
        );
        $result = $card_model->delete($params);
        return $result;
    }

    /**
     * 卡作废
     * @return array
     */
    public function invalid()
    {
        $card_id = input('card_id', 0);
        $card_operation_model = new CardOperation();
        $params = array (
            'site_id' => $this->site_id,
            'card_id' => $card_id,
            'operator_data' => $this->user_info
        );
        $result = $card_operation_model->cardInvalid($params);
        return $result;

    }
}