<?php


namespace addon\giftcard\model\membercard;


use addon\giftcard\model\card\Card;
use addon\giftcard\model\transfer\Blessing;
use app\model\BaseModel;
use app\model\member\Member;


class MemberCard extends BaseModel
{

    /**
     * 生成会员所属卡记录
     * @param $params
     * @return array
     */
    public function addMemberCard($params)
    {
        $card_id = $params[ 'card_id' ];
        $member_id = $params[ 'member_id' ];
//        $condition = array(
//            ['card_id', '=', $card_id],
//            ['member_id', '=', $member_id]
//        );
//        $member_card_info = $this->getMemberCardInfo($condition)['data'] ?? [];
//        if(!empty($member_card_info)){
//            $this->deleteMemberCard($condition);
//        }
        $data = array (
            'site_id' => $params[ 'site_id' ],
            'card_id' => $card_id,
            'from_member_id' => $params[ 'from_member_id' ] ?? 0,
            'member_id' => $member_id,
//            'to_member_id' => $params['to_member_id'] ?? 0,
//            'is_transfer' => $params['is_transfer'] ?? 0,
            'source' => $params[ 'source' ],
            'get_time' => time()
        );
        $member_card_id = model('giftcard_member_card')->add($data);
        return $this->success($member_card_id);
    }

    /**
     * 赠送后的操作
     * @param $params
     * @return array
     */
    public function memberCardTransfer($params)
    {
        $card_id = $params[ 'card_id' ];
        $member_id = $params[ 'member_id' ];
        $member_card_id = $params[ 'member_card_id' ];
        $condition = array (
            [ 'card_id', '=', $card_id ],
            [ 'member_id', '=', $member_id ],
        );
        $card_model = new Card();
        $card_info = $card_model->getCardInfo($condition)[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error([], '当前礼品卡不存在或已被领取！');

        $card_goods_condition = array (
            [ 'card_id', '=', $card_id ],
        );
        $card_goods_list = $card_model->getCardGoodsList($card_goods_condition)[ 'data' ] ?? [];

        $snapshot = array (
            'card_info' => $card_info,
            'card_goods_list' => $card_goods_list
        );
        //赠送
        $data = array (
            'is_transfer' => 1,
            'to_member_id' => $params[ 'to_member_id' ],
            'transfer_time' => time(),
            'snapshot' => json_encode($snapshot)
        );
        model('giftcard_member_card')->update($data, [ [ 'member_card_id', '=', $member_card_id ] ]);
        model('giftcard_card')->update([ 'member_id' => $data[ 'to_member_id' ] ], [ [ 'card_id', '=', $card_id ] ]);

        //使祝福语失效
        $blessing_model = new Blessing();
        $blessing_model->blessingToVoid([ [ 'member_card_id', '=', $member_card_id ] ]);
        return $this->success();
    }

    /**
     * 删除
     * @param $condition
     * @return array
     */
    public function deleteMemberCard($condition)
    {
        model('giftcard_member_card')->delete($condition);
        return $this->success();
    }

    /**
     * 获取会员礼品卡信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getMemberCardInfo($condition, $field = '*')
    {
        $info = model('giftcard_member_card')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取会员礼品卡列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getMemberCardList($condition = [], $field = '*', $order = '', $limit = null, $alias = 'a', $join = [])
    {
        $list = model('giftcard_member_card')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取会员礼品卡详情列表
     * @param array $condition
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getMemberCardDetailList($condition = [], $order = 'gmc.get_time asc', $limit = null)
    {
        $alias = 'gmc';
        $join = [
            [
                'giftcard_card gc',
                'gc.card_id = gmc.card_id',
                'left'
            ],
            [
                'member m1',
                'gmc.from_member_id = m1.member_id  or  m1.member_id is null',
                'left'
            ],
            [
                'member m2',
                'gmc.to_member_id = m2.member_id  or  m2.member_id is null',
                'left'
            ],
            [
                'member m3',
                'gmc.member_id = m3.member_id  or  m3.member_id is null',
                'left'
            ],
        ];
        $field = 'gc.*,gmc.*,m1.nickname as from_nickname, m2.nickname as to_nickname, m3.nickname as member_nickname';
        $list = $this->getMemberCardList($condition, $field, $order, $limit, $alias, $join)[ 'data' ] ?? [];
        return $this->success($list);
    }

    /**
     * 获取会员礼品卡分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getMemberCardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('giftcard_member_card')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取我的卡券列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @return array
     */
    public function getMemberCardDetailPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $alias = 'gmc';
        $join = [
            [
                'giftcard_card gc',
                'gc.card_id = gmc.card_id',
                'left'
            ],
            [
                'member m1',
                'gmc.from_member_id = m1.member_id  or  m1.member_id is null',
                'left'
            ],
            [
                'member m2',
                'gmc.to_member_id = m2.member_id  or  m2.member_id is null',
                'left'
            ],
            [
                'member m3',
                'gmc.member_id = m3.member_id  or  m3.member_id is null',
                'left'
            ],
        ];
        $field = 'gc.*,gmc.*,m1.nickname as from_nickname, m2.nickname as to_nickname, m3.nickname as member_nickname';
        $list = $this->getMemberCardPageList($condition, $page, $page_size, $order, $field, $alias, $join)[ 'data' ] ?? [];
        if (!empty($list[ 'list' ])) {
            $card_model = new Card();
            foreach ($list[ 'list' ] as $k => $v) {
                if ($v[ 'is_transfer' ] == 0) {
                    $item_condition = array (
                        [ 'card_id', '=', $v[ 'card_id' ] ]
                    );
                    $card_goods_list = $card_model->getCardGoodsList($item_condition)[ 'data' ] ?? [];
                } else {
                    $snapshot = $v[ 'snapshot' ];
                    $snapshot_array = json_decode($snapshot, true);
                    $card_goods_list = $snapshot_array[ 'card_goods_list' ] ?? [];
                }
                $list[ 'list' ][ $k ][ 'card_goods_list' ] = $card_goods_list;
            }
        }
        return $this->success($list);
    }


    /**
     * 我的卡券详情
     * @param $params
     * @return array
     */
    public function getMemberCardDetail($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_id = $params[ 'member_id' ] ?? 0;
        $card_id = $params[ 'card_id' ] ?? 0;
        $member_card_id = $params[ 'member_card_id' ];
        $condition = array (
            [ 'member_card_id', '=', $member_card_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($member_id > 0) {
            $condition[] = [ 'member_id', '=', $member_id ];
        }
        if ($card_id > 0) {
            $condition[] = [ 'card_id', '=', $card_id ];
        }
        $member_card_info = $this->getMemberCardInfo($condition)[ 'data' ] ?? [];
        if (empty($member_card_info))
            return $this->error();


        $card_id = $member_card_info[ 'card_id' ];
        if ($member_card_info[ 'is_transfer' ] == 0) {
            $card_model = new Card();
            $card_info = $card_model->getCardDetail([ 'site_id' => $site_id, 'card_id' => $card_id ])[ 'data' ] ?? [];
        } else {
            $snapshot = $member_card_info[ 'snapshot' ];
            $snapshot_array = json_decode($snapshot, true);
            $card_info = $snapshot_array[ 'card_info' ];
            $card_goods_list = $snapshot_array[ 'card_goods_list' ];
            $card_info[ 'card_goods_list' ] = $card_goods_list;
        }
        $member_model = new Member();

        if ($member_card_info[ 'to_member_id' ] > 0) {
            $to_member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $member_card_info[ 'to_member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
            $card_info[ 'to_member_nickname' ] = $to_member_info[ 'nickname' ];
            $card_info[ 'to_member_headimg' ] = $to_member_info[ 'headimg' ];
        }
        if ($member_card_info[ 'from_member_id' ] > 0) {
            $from_member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $member_card_info[ 'from_member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
            $card_info[ 'from_member_nickname' ] = $from_member_info[ 'nickname' ];
            $card_info[ 'from_member_headimg' ] = $from_member_info[ 'headimg' ];
        }
        if ($member_card_info[ 'member_id' ] > 0) {
            $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $member_card_info[ 'member_id' ] ] ], 'nickname,headimg')[ 'data' ] ?? [];
            $card_info[ 'member_nickname' ] = $member_info[ 'nickname' ];
            $card_info[ 'member_headimg' ] = $member_info[ 'headimg' ];
        }
        $info = array_merge($card_info, $member_card_info);
        return $this->success($info);

    }


}
