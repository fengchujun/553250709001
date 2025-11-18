<?php


namespace addon\giftcard\model\transfer;


use addon\giftcard\model\card\Card;
use addon\giftcard\model\membercard\MemberCard;
use app\model\BaseModel;
use app\model\member\Member;


class Blessing extends BaseModel
{


    /**
     * 祝福语
     */
    public function blessing($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_id = $params[ 'member_id' ];
        $member_card_id = $params[ 'member_card_id' ];
        $condition = array (
            [ 'member_id', '=', $member_id ],
            [ 'member_card_id', '=', $member_card_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $member_card_model = new MemberCard();
        $member_card_info = $member_card_model->getMemberCardInfo($condition)[ 'data' ] ?? [];
        if (empty($member_card_info))
            return $this->error([], '礼品卡不存在或已转赠');

        $is_transfer = $member_card_info[ 'is_transfer' ];
        if ($is_transfer == 1)
            return $this->error([], '礼品卡不存在或已转赠');
        $blessing_id = model('giftcard_card_blessing')->update([ 'status' => 1 ], $condition);

        $site_id = $member_card_info[ 'site_id' ];
        $card_id = $member_card_info[ 'card_id' ];
        $code = substr(md5(microtime(true) . $blessing_id), 0, 6);
        $data = array (
            'blessing' => $params[ 'blessing' ],
            'member_id' => $member_id,
            'member_card_id' => $member_card_id,
            'site_id' => $site_id,
            'card_id' => $card_id,
            'create_time' => time(),
            'no' => $code
        );
        model('giftcard_card_blessing')->add($data);
        return $this->success($code);
    }


    /**
     * 使祝福语失效
     * @param $condition
     * @return array
     */
    public function blessingToVoid($condition)
    {
        model('giftcard_card_blessing')->update([ 'status' => 1 ], $condition);
        return $this->success();
    }

    /**
     * 祝福语详情
     * @param $params
     * @return array
     */
    public function getBlessingDetail($params)
    {
        $no = $params[ 'no' ] ?? '';
        $site_id = $params[ 'site_id' ] ?? 0;
        $blessing_id = $params[ 'blessing_id' ] ?? 0;
        $member_id = $params[ 'member_id' ] ?? 0;
        $condition = array (
            [ 'no', '=', $no ]
        );
        if ($blessing_id > 0) {
            $condition[] = [ 'blessing_id', '=', $blessing_id ];
        }
        if (!empty($code)) {
            $condition[] = [ 'code', '=', $code ];
        }
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $info = model('giftcard_card_blessing')->getInfo($condition);
        if (empty($info))
            return $this->error([], '礼品卡祝福不存在或已失效');

        $member_model = new Member();
        if ($info[ 'to_member_id' ] > 0) {
            $to_member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $info[ 'to_member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
            $info[ 'to_member_nickname' ] = $to_member_info[ 'nickname' ];
            $info[ 'to_member_headimg' ] = $to_member_info[ 'headimg' ];
        }

        if ($info[ 'member_id' ] > 0) {
            $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $info[ 'member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
            $info[ 'member_nickname' ] = $member_info[ 'nickname' ];
            $info[ 'member_headimg' ] = $member_info[ 'headimg' ];
        }
        $info[ 'is_self' ] = $member_id == $info[ 'member_id' ] ? 1 : 0;
        $card_id = $info[ 'card_id' ];
        $card_model = new Card();
        $card_info = $card_model->getCardDetail([ 'card_id' => $card_id ])[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error([], '礼品卡祝福不存在或已失效');

        $member_card_model = new MemberCard();
        $member_card_info = $member_card_model->getMemberCardInfo([ [ 'member_card_id', '=', $info[ 'member_card_id' ] ] ])[ 'data' ] ?? [];
        $info[ 'is_transfer' ] = $member_card_info[ 'is_transfer' ];
        return $this->success(array_merge($card_info, $info));
    }


    public function setBlessingToMember($params)
    {
        $blessing_id = $params[ 'blessing_id' ] ?? 0;
        $site_id = $params[ 'site_id' ] ?? 0;
        $to_member_id = $params[ 'to_member_id' ] ?? 0;
        $condition = array (
            [ 'blessing_id', '=', $blessing_id ]
        );
        $data = array (
            'to_member_id' => $to_member_id,
            'to_time' => time(),
            'status' => 2
        );
        model('giftcard_card_blessing')->update($data, $condition);
        return $this->success();
    }

    /**
     * 获取会员礼品卡祝福信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getMemberCardBlessingInfo($condition, $field = '*')
    {
        $info = model('giftcard_card_blessing')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取会员礼品卡祝福列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getMemberCardBlessingList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_card_blessing')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取会员礼品卡祝福分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberCardBlessingPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_card_blessing')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }
}
