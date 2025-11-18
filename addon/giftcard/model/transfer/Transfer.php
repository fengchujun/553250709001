<?php


namespace addon\giftcard\model\transfer;


use addon\giftcard\model\card\Card;
use addon\giftcard\model\card\CardLog;
use addon\giftcard\model\membercard\MemberCard;
use app\model\BaseModel;


class Transfer extends BaseModel
{


    /**
     * 赠送后的操作
     * @param $params
     * @return array
     */
    public function transfer($params)
    {
        $blessing_id = $params[ 'blessing_id' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $blessing_model = new Blessing();
        $blessing_condition = array (
            [ 'blessing_id', '=', $blessing_id ],
            [ 'status', '=', 0 ]
        );
        if ($site_id > 0) {
            $blessing_condition[] = [ 'site_id', '=', $site_id ];
        }
        $blessing_info = $blessing_model->getMemberCardBlessingInfo($blessing_condition)[ 'data' ] ?? [];
        if (empty($blessing_info))
            return $this->error([], '礼品卡祝福已被领取或已失效');

        //todo  后续必然增加队列控制
        $member_card_id = $blessing_info[ 'member_card_id' ];
        $member_card_model = new MemberCard();
        $member_card_condition = array (
            [ 'member_card_id', '=', $member_card_id ],
            [ 'is_transfer', '=', 0 ]
        );
        if ($site_id > 0) {
            $member_card_condition[] = [ 'site_id', '=', $site_id ];
        }

        $member_card_info = $member_card_model->getMemberCardInfo($member_card_condition)[ 'data' ] ?? [];
        if (empty($member_card_info))
            return $this->error([], '当前礼品卡不存在或已被领取！');

        $from_member_id = $member_card_info[ 'member_id' ];
        $card_id = $member_card_info[ 'card_id' ];

        $member_id = $params[ 'member_id' ];
        $condition = array (
            [ 'card_id', '=', $card_id ],
            [ 'member_id', '=', $from_member_id ]
        );
        if ($from_member_id == $member_id)
            return $this->error([], '不能自己领取自己的礼品卡！');

        $card_model = new Card();
        $card_info = $card_model->getCardInfo($condition)[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error([], '当前礼品卡不存在或已被领取！');

        $is_allow_transfer = $card_info[ 'is_allow_transfer' ];
        if ($is_allow_transfer == 0)
            return $this->error([], '当前礼品卡不允许转赠！');

        if ($card_info[ 'status' ] != 'to_use')
            return $this->error([], '只有待使用的礼品卡才允许转赠！');

        //将原所属者改变
        $transfer_params = array (
            'card_id' => $card_id,
            'member_id' => $from_member_id,
            'to_member_id' => $member_id,
            'member_card_id' => $member_card_id
        );
        $result = $member_card_model->memberCardTransfer($transfer_params);
        if ($result[ 'code' ] < 0)
            return $result;

        $set_params = array (
            'blessing_id' => $blessing_id,
            'to_member_id' => $member_id
        );
        $result = $blessing_model->setBlessingToMember($set_params);
        if ($result[ 'code' ] < 0)
            return $result;

        $card_params = array (
            'site_id' => $params[ 'site_id' ],
            'from_member_id' => $from_member_id,
            'member_id' => $member_id,
            'card_id' => $card_id,
            'source' => 'gift',
        );
        $result = $member_card_model->addMemberCard($card_params);
        ( new CardLog() )->add([
            'card_id' => $card_id,
            'type' => 'transfer',
            'operator_type' => 'member',//todo  暂时是确定的
            'operator' => $member_id,
            'type_id' => $result[ 'data' ]
        ]);
        return $result;
    }

}
