<?php


namespace addon\giftcard\model\card;


use addon\giftcard\model\membercard\MemberCard;

/**
 * 电子卡(线上)
 * Class GiftCardRecords
 * @package addon\giftcard\model\records
 */
class VirtualCard extends Card
{

    public function addCard($params)
    {
        $source = $params[ 'source' ];

        $insert_data = array (
            'order_id' => $params[ 'order_id' ],
            'source' => $source,
            'is_allow_transfer' => $params[ 'is_allow_transfer' ],
            'init_member_id' => $params[ 'member_id' ],
            'card_name' => $params[ 'order_name' ] ?? '',
            'card_cover' => $params[ 'card_cover' ] ?? '',
            'status' => 'to_use',
        );
        $params[ 'insert_data' ] = $insert_data;
        $params[ 'card_type' ] = 'virtual';
        $result = $this->addCardItem($params);
        $card_id = $result[ 'data' ];

        //生成会员所属记录
        $member_card_model = new MemberCard();
        $card_params = array (
            'site_id' => $params[ 'site_id' ],
            'form_member_id' => 0,
            'member_id' => $params[ 'member_id' ],
            'card_id' => $card_id,
            'source' => $source,
        );
        $member_card_model->addMemberCard($card_params);

        ( new CardLog() )->add([
            'card_id' => $card_id,
            'type' => 'buy',
            'operator_type' => 'member',//todo  暂时是确定的
            'operator' => $params[ 'member_id' ],
            'type_id' => $params[ 'order_id' ]
        ]);
        return $result;
    }

}
