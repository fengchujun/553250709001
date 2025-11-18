<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\coupon\model;

use addon\coupon\dict\CouponDict;
use app\model\BaseModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\system\Stat;

/**
 * 优惠券
 */
class Coupon extends BaseModel
{
    //优惠券来源方式
    const GET_TYPE_MERCHANT_GIVE = 1;
    const GET_TYPE_MEMBER_LEAD = 2;
    const GET_TYPE_ACTIVITY_GIVE = 3;

    /**
     * 获取优惠券来源方式
     * @param string $type
     * @return array|mixed|string
     */
    public function getCouponGetType($type = '')
    {
        $get_type = [
            self::GET_TYPE_MEMBER_LEAD => '直接领取',
            self::GET_TYPE_MERCHANT_GIVE => '商家发放',
            self::GET_TYPE_ACTIVITY_GIVE => '活动发放',
        ];
        $event = event('CouponGetType');
        if (!empty($event)) {
            foreach ($event as $k => $v) {
                $get_type[ array_keys($v)[ 0 ] ] = array_values($v)[ 0 ];
            }
        }
        if ($type) return $get_type[ $type ] ?? '';
        else return $get_type;
    }

    /**
     * 获取编码
     */
    public function getCode()
    {
        return random_keys(8);
    }

    /**
     * 领取优惠券
     * @param $coupon_type_id
     * @param $site_id
     * @param $member_id
     * @param $get_type
     * @param int $is_stock
     * @param int $is_limit
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function receiveCoupon($coupon_type_id, $site_id, $member_id, $get_type, $is_stock = 0, $is_limit = 1)
    {
        // 用户已领取数量
        if (empty($member_id)) {
            return $this->error([], '请先进行登录！');
        }
        $coupon_type_info = model('promotion_coupon_type')->getInfo([ 'coupon_type_id' => $coupon_type_id, 'site_id' => $site_id ]);
        if (!empty($coupon_type_info)) {
            if ($coupon_type_info[ 'count' ] != -1 || $is_stock == 0) {
                if ($coupon_type_info[ 'count' ] == $coupon_type_info[ 'lead_count' ]) {
                    return $this->error([ 'type' => 'out' ], '来迟了该优惠券已被领取完了！');
                }
            }
            if ($coupon_type_info[ 'max_fetch' ] != 0 && $get_type == 2) {
                //限制领取
                $member_receive_num = model('promotion_coupon')->getCount([
                    'coupon_type_id' => $coupon_type_id,
                    'member_id' => $member_id,
                    'get_type' => 2
                ]);
                if ($member_receive_num >= $coupon_type_info[ 'max_fetch' ] && $is_limit == 1) {
                    return $this->error([ 'type' => 'limit' ], '该优惠券领取已达到上限！');
                }
            }

            //只有正在进行中的优惠券可以添加或者发送领取)
            if ($coupon_type_info[ 'status' ] != 1) {
                return $this->error([ 'type' => 'expire' ], '该优惠券活动已结束！');
            }

            $data = [
                'coupon_type_id' => $coupon_type_id,
                'site_id' => $site_id,
                'coupon_code' => $this->getCode(),
                'member_id' => $member_id,
                'money' => $coupon_type_info[ 'money' ],
                'state' => CouponDict::normal,
                'get_type' => $get_type,
                'goods_type' => $coupon_type_info[ 'goods_type' ],
                'fetch_time' => time(),
                'coupon_name' => $coupon_type_info[ 'coupon_name' ],
                'at_least' => $coupon_type_info[ 'at_least' ],
                'type' => $coupon_type_info[ 'type' ],
                'discount' => $coupon_type_info[ 'discount' ],
                'discount_limit' => $coupon_type_info[ 'discount_limit' ],
                'goods_ids' => $coupon_type_info[ 'goods_ids' ],
                //适用于门店
                'use_channel' => $coupon_type_info[ 'use_channel' ],
                'use_store' => $coupon_type_info[ 'use_store' ],
            ];

            if ($coupon_type_info[ 'validity_type' ] == 0) {
                $data[ 'end_time' ] = $coupon_type_info[ 'end_time' ];
            } elseif ($coupon_type_info[ 'validity_type' ] == 1) {
                $data[ 'end_time' ] = (time() + $coupon_type_info[ 'fixed_term' ] * 86400);
            }
            $res = model('promotion_coupon')->add($data);
            if ($is_stock == 0) {
                model('promotion_coupon_type')->setInc([ [ 'coupon_type_id', '=', $coupon_type_id ] ], 'lead_count');
            }
            $stat_model = new Stat();
            $stat_model->switchStat([ 'type' => 'receive_coupon', 'data' => [
                'site_id' => $site_id,
                'coupon_id' => $res
            ] ]);
            return $this->success([]);
        } else {
            return $this->error([], '未查找到该优惠券！');
        }
    }

    /**
     * 发放优惠券
     * @param array $coupon_data [ ['coupon_type_id' => xx, 'num' => xx ] ]
     * @param int $site_id
     * @param int $member_id
     * @param $get_type 获取类型
     * @param $related_id
     * @return array
     */
    public function giveCoupon(array $coupon_data, int $site_id, int $member_id, $get_type = self::GET_TYPE_MERCHANT_GIVE, $related_id = 0)
    {
        if(empty($member_id)) return $this->error(null, '会员id不可为空');
        if(empty($coupon_data)) return $this->error(null, '发放优惠券数据不可为空');
        try {
            $coupon_list = [];
            $coupon_type_list = model('promotion_coupon_type')->getColumn([
                [ 'coupon_type_id', 'in', array_column($coupon_data, 'coupon_type_id') ],
                [ 'site_id', '=', $site_id ],
                [ 'status', '=', 1 ],
            ], '*', 'coupon_type_id');
            $coupon_type_update_data = [];
            foreach ($coupon_data as $item) {
                $item_coupon_type_id = $item[ 'coupon_type_id' ];
                $item_num = $item[ 'num' ];
                $coupon_type_info = $coupon_type_list[ $item_coupon_type_id ] ?? null;
                if (empty($coupon_type_info)) {
                    return $this->error(null, '优惠券数据有误');
                }
                //直接领取限制
                if($get_type == self::GET_TYPE_MEMBER_LEAD){
                    if($coupon_type_info[ 'is_show' ] == 0){
                        return $this->error('', '该优惠券不可直接领取');
                    }
                    if($coupon_type_info[ 'count' ] != -1 && $coupon_type_info[ 'lead_count' ] >= $coupon_type_info[ 'count' ]){
                        return $this->error('', '优惠券已被领取完了');
                    }
                    if($coupon_type_info[ 'max_fetch' ] != 0){
                        $member_receive_num = model('promotion_coupon')->getCount([
                            'coupon_type_id' => $item_coupon_type_id,
                            'member_id' => $member_id,
                            'get_type' => 2
                        ]);
                        if ($member_receive_num >= $coupon_type_info[ 'max_fetch' ]) {
                            return $this->error('', '优惠券领取已达上限');
                        }
                    }
                }
                //有效期
                if ($coupon_type_info[ 'validity_type' ] == 0) {
                    $end_time = $coupon_type_info[ 'end_time' ];
                } elseif ($coupon_type_info[ 'validity_type' ] == 1) {
                    $end_time = (time() + $coupon_type_info[ 'fixed_term' ] * 86400);
                } else{
                    $end_time = 0;
                }
                $data = [
                    'coupon_type_id' => $item_coupon_type_id,
                    'site_id' => $site_id,
                    'coupon_code' => $this->getCode(),
                    'member_id' => $member_id,
                    'money' => $coupon_type_info[ 'money' ],
                    'state' => CouponDict::normal,
                    'get_type' => $get_type,
                    'goods_type' => $coupon_type_info[ 'goods_type' ],
                    'fetch_time' => time(),
                    'coupon_name' => $coupon_type_info[ 'coupon_name' ],
                    'at_least' => $coupon_type_info[ 'at_least' ],
                    'type' => $coupon_type_info[ 'type' ],
                    'discount' => $coupon_type_info[ 'discount' ],
                    'discount_limit' => $coupon_type_info[ 'discount_limit' ],
                    'goods_ids' => $coupon_type_info[ 'goods_ids' ],
                    'related_id' => $related_id,
                    'end_time' => $end_time,
                    'use_channel' => $coupon_type_info[ 'use_channel' ],//适用场景 线上线下
                    'use_store' => $coupon_type_info[ 'use_store' ],//适用于门店
                ];
                for ($i = 0; $i < $item_num; $i++) {
                    $data[ 'coupon_code' ] = $this->getCode();
                    $coupon_list[] = $data;
                }
                //领取数量数据
                $coupon_type_update_data[ $item_num ][] = $item_coupon_type_id;
            }
            $res = model('promotion_coupon')->addList($coupon_list);
            //批量修改数量 直接领取和发放修改字段不一样
            $inc_field = $get_type == self::GET_TYPE_MEMBER_LEAD ? 'lead_count' : 'give_count';
            foreach ($coupon_type_update_data as $k => $v) {
                model('promotion_coupon_type')->setInc([ [ 'coupon_type_id', 'in', $v ] ], $inc_field, $k);
            }
            return $this->success($res);
        } catch (\Exception $e) {
            return $this->error([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ], '发放失败');
        }
    }

    /**
     * 使用优惠券
     * @param $coupon_id
     * @param $member_id
     * @param $use_order_id
     * @return array
     */
    public function useCoupon($coupon_id, $member_id, $use_order_id)
    {
        $data = [ 'use_order_id' => $use_order_id, 'use_time' => time(), 'state' => CouponDict::used ];
        $condition = [
            [ 'coupon_id', '=', $coupon_id ],
            [ 'member_id', '=', $member_id ],
            [ 'state', '=', CouponDict::normal ]
        ];
        $result = model('promotion_coupon')->update($data, $condition);
        //累赠使用数
        $info = model('promotion_coupon')->getInfo([ [ 'coupon_id', '=', $coupon_id ] ]);
        model('promotion_coupon_type')->setInc([ [ 'coupon_type_id', '=', $info[ 'coupon_type_id' ] ] ], 'used_count');
        return $this->success($result);
    }

    /**
     * 退还优惠券
     * @param $coupon_id
     * @param $member_id
     * @return array
     */
    public function refundCoupon($coupon_id, $member_id)
    {
        //获取优惠券信息
        $condition = [
            [ 'pc.coupon_id', '=', $coupon_id ],
            [ 'pc.member_id', '=', $member_id ],
            [ 'pc.state', '=', CouponDict::used ]
        ];

        $field = 'pct.validity_type,pc.end_time, pc.coupon_type_id';
        $alias = 'pc';
        $join = [
            [ 'promotion_coupon_type pct', 'pc.coupon_type_id = pct.coupon_type_id', 'left' ]
        ];
        $info = model('promotion_coupon')->getInfo($condition, $field, $alias, $join);
        if (empty($info)) {
            return $this->success();
        }

        $data = [ 'use_time' => 0, 'state' => CouponDict::normal ];

        // 判断优惠券是否过期，有效期类型：0：固定时间，1：领取之日起x天有效，2：长期有效
        if ($info[ 'validity_type' ] == 0) {
            // 固定时间
            if ($info[ 'end_time' ] <= time()) {
                $data[ 'state' ] = CouponDict::expire;
            }
        }

        $result = model('promotion_coupon')->update($data, [ [ 'coupon_id', '=', $coupon_id ], [ 'member_id', '=', $member_id ], [ 'state', '=', CouponDict::used ] ]);
        //累减使用数
        model('promotion_coupon_type')->setDec([ [ 'coupon_type_id', '=', $info[ 'coupon_type_id' ] ] ], 'used_count');
        return $this->success($result);
    }

    /**
     * 获取优惠券信息
     * @param $condition $coupon_code 优惠券编码
     * @param $field
     * @return array
     */
    public function getCouponInfo($condition, $field)
    {
        $info = model('promotion_coupon')->getInfo($condition, $field);
        $coupon_type_model = new CouponType();
        $info = $coupon_type_model->getCouponSubData($info);
        return $this->success($info);
    }

    /**
     * 获取优惠券数量
     * @param $condition $coupon_code 优惠券编码
     * @return array
     */
    public function getCouponCount($condition)
    {
        $info = model('promotion_coupon')->getCount($condition);
        return $this->success($info);
    }

    /**
     * 获取优惠券列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCouponList($condition = [], $field = true, $order = '', $limit = null, $alias = 'a', $join = [], $group = null)
    {
        $list = model('promotion_coupon')->getList($condition, $field, $order, $alias, $join, $group, $limit);
        $coupon_type_model = new CouponType();
        foreach($list as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    /**
     * 获取优惠券列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getCouponPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'fetch_time desc', $field = 'coupon_id,type,discount,coupon_type_id,coupon_name,site_id,coupon_code,member_id,use_order_id,at_least,money,state,get_type,fetch_time,use_time,end_time', $alias = 'a', $join = [])
    {
        $list = model('promotion_coupon')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        $coupon_type_model = new CouponType();
        foreach($list['list'] as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    /**
     * 获取会员优惠券列表
     * @param $condition
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getMemberCouponPageList($condition, $page = 1, $page_size = PAGE_LIST_ROWS)
    {
        $field = 'npc.coupon_name,npc.type,npc.use_order_id,npc.coupon_id,npc.coupon_type_id,npc.site_id,npc.coupon_code,npc.member_id,npc.discount_limit,
        npc.at_least,npc.money,npc.discount,npc.state,npc.get_type,npc.fetch_time,npc.use_time,npc.end_time,mem.nickname,on.order_no,npc.end_time,mem.nickname,mem.headimg,mem.mobile,
        npc.use_channel, npc.use_store, npc.goods_type';
        $alias = 'npc';
        $join = [
            [
                'member mem',
                'npc.member_id = mem.member_id',
                'inner'
            ],
            [
                'order on',
                'npc.use_order_id = on.order_id',
                'left'
            ],
        ];
        $list = model('promotion_coupon')->pageList($condition, $field, 'fetch_time desc', $page, $page_size, $alias, $join);
        $coupon_type_model = new CouponType();
        foreach($list['list'] as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    /**
     * 获取优惠券信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCouponTypeInfo($condition, $field = 'coupon_type_id,site_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term,status,type,discount,use_channel, use_store')
    {
        $info = model('promotion_coupon_type')->getInfo($condition, $field);
        $coupon_type_model = new CouponType();
        $info = $coupon_type_model->getCouponSubData($info);
        return $this->success($info);
    }

    /**
     * 获取优惠券列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCouponTypeList($condition = [], $field = true, $order = '', $limit = null, $alias = '', $join = [])
    {
        $list = model('promotion_coupon_type')->getList($condition, $field, $order, $alias, $join, '', $limit);
        $coupon_type_model = new CouponType();
        foreach($list as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    /**
     * 获取优惠券分页列表
     * @param $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCouponTypePageList($condition, $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'coupon_type_id desc', $field = '*', $alias = '', $join = [])
    {
        $list = model('promotion_coupon_type')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        $coupon_type_model = new CouponType();
        foreach($list['list'] as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    /**
     * 获取会员已领取优惠券
     * @param $member_id
     * @param $state
     * @param int $site_id
     * @param int $money
     * @param string $order
     * @return array
     */
    public function getMemberCouponList($member_id, $state, $site_id = 0, $money = 0, $order = 'fetch_time desc')
    {
        $condition = [
            [ 'member_id', '=', $member_id ],
            [ 'state', '=', $state ],
//            [ "end_time", ">", time()]
        ];
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($money > 0) {
//            $condition[] = [ "at_least", "=", 0 ];
            $condition[] = [ 'at_least', '<=', $money ];
        }
        $list = model('promotion_coupon')->getList($condition, '*', $order, '', '', '', 0);
        $coupon_type_model = new CouponType();
        foreach($list as &$val){
            $val = $coupon_type_model->getCouponSubData($val);
        }
        return $this->success($list);
    }

    public function getMemberCouponCount($condition)
    {
        $list = model('promotion_coupon')->getCount($condition);
        return $this->success($list);
    }

    /**
     * 增加库存
     * @param $param
     * @return array
     */
    public function incStock($param)
    {
        $condition = [
            [ 'coupon_type_id', '=', $param[ 'coupon_type_id' ] ]
        ];
        $num = $param[ 'num' ];
        $coupon_info = model('promotion_coupon_type')->getInfo($condition, 'count,lead_count');
        if (empty($coupon_info))
            return $this->error(-1, '');

        //更新优惠券库存
        $result = model('promotion_coupon_type')->setDec($condition, 'lead_count', $num);
        return $this->success($result);
    }

    /**
     * 减少库存
     * @param $param
     * @return array
     */
    public function decStock($param)
    {
        $condition = [
            [ 'coupon_type_id', '=', $param[ 'coupon_type_id' ] ]
        ];
        $num = $param[ 'num' ];
        $coupon_info = model('promotion_coupon_type')->getInfo($condition, 'count,lead_count');
        if (empty($coupon_info))
            return $this->error(-1, '找不到优惠券！');

        //编辑sku库存
        if ($coupon_info[ 'count' ] != -1) {
            if (($coupon_info[ 'count' ] - $coupon_info[ 'lead_count' ]) < $num)
                return $this->error(-1, '优惠券库存不足！');
        }

        $result = model('promotion_coupon_type')->setInc($condition, 'lead_count', $num);
        if ($result === false)
            return $this->error();

        return $this->success($result);
    }

    /**
     * 定时关闭
     * @return mixed
     */
    public function cronCouponEnd()
    {
        return model('promotion_coupon')->update([ 'state' => CouponDict::expire ], [ [ 'state', '=', CouponDict::normal ], [ 'end_time', '>', 0 ], [ 'end_time', '<=', time() ] ]);
    }

    /**
     * 核验会员是否还可以领用某一张优惠券
     * @param $params
     * @return array
     */
    public function checkMemberReceiveCoupon($params)
    {
        $member_id = $params[ 'member_id' ];//会员id
        $coupon_type_info = $params[ 'coupon_type_info' ];
        $site_id = $params[ 'site_id' ];
        $coupon_type_id = $params[ 'coupon_type_id' ] ?? 0;
        if ($coupon_type_id > 0) {
            $coupon_type_info = model('promotion_coupon_type')->getInfo([ 'coupon_type_id' => $coupon_type_id, 'site_id' => $site_id ]);
        }
        if (!empty($coupon_type_info)) {
            $coupon_type_id = $coupon_type_info[ 'coupon_type_id' ] ?? 0;
            if ($coupon_type_info[ 'count' ] != -1 && $coupon_type_info[ 'is_show' ] == 1) {
                if ($coupon_type_info[ 'count' ] == $coupon_type_info[ 'lead_count' ]) {
                    return $this->error('', '来迟了该优惠券已被领取完了！');
                }
            }
            if ($coupon_type_info[ 'max_fetch' ] != 0) {
                //限制领取
                $member_receive_num = model('promotion_coupon')->getCount([
                    'coupon_type_id' => $coupon_type_id,
                    'member_id' => $member_id,
                    'get_type' => 2
                ]);
                if ($member_receive_num >= $coupon_type_info[ 'max_fetch' ]) {
                    return $this->error('', '该优惠券领取已达到上限！');
                }
            }
            //只有正在进行中的优惠券可以添加或者发送领取)
            if ($coupon_type_info[ 'status' ] != 1) {
                return $this->error('', '该优惠券已过期！');
            }
        }
        return $this->success();
    }

    /**
     * 获取商品可领用优惠券
     * @param $goods_sku_detail_array
     * @param $member_id
     * @param $site_id
     */
    public function getGoodsCanReceiveCouponInApi($goods_sku_detail_array, $member_id, $site_id, $store_id = null)
    {
        $goods_sku_detail = $goods_sku_detail_array[ 'goods_sku_detail' ];
        $condition = [
            [ 'site_id', '=', $site_id ],
            [ 'status', '=', 1 ],
            [ 'is_show', '=', 1 ],
        ];
        //查询正在进行的优惠券活动
        $field = 'count,lead_count,coupon_type_id,coupon_type_id as type_id,type,site_id,coupon_name,money,discount,max_fetch,at_least,end_time,validity_type,fixed_term,goods_type,discount_limit,goods_ids,use_store';
        $coupon_type_list = model('promotion_coupon_type')->getList($condition, $field);
        //查询会员领用的优惠券数量
        if ($member_id != 0) {
            $member_coupon_num = model('promotion_coupon')->getList([
                [ 'member_id', '=', $member_id ]
            ], 'coupon_type_id,count(coupon_type_id) as member_coupon_num,get_type', 'money desc', 'a', [], 'coupon_type_id');
            if (!empty($member_coupon_num)) {
                $key = array_column($member_coupon_num, 'coupon_type_id');
                $member_coupon_num = array_combine($key, $member_coupon_num);
            } else {
                $member_coupon_num = [];
            }

        }

        $goods_category_model = new GoodsCategoryModel();
        $real_category_ids = $goods_category_model->getGoodsCategoryLeafIds($goods_sku_detail['category_id'])['data'];

        foreach ($coupon_type_list as $k => $v) {
            if ($v[ 'goods_type' ] == CouponDict::selected) {
                //指定商品可用
                $goods_id_array = explode(',', $v[ 'goods_ids' ]);
                if (!in_array($goods_sku_detail[ 'goods_id' ], $goods_id_array)) {
                    unset($coupon_type_list[ $k ]);
                    continue;
                }
            }
            if ($v[ 'goods_type' ] == CouponDict::selected_out) {
                //指定商品不可用
                $goods_id_array = explode(',', $v[ 'goods_ids' ]);
                if (in_array($goods_sku_detail[ 'goods_id' ], $goods_id_array)) {
                    unset($coupon_type_list[ $k ]);
                    continue;
                }
            }
            if($v['goods_type'] == CouponDict::category_selected){
                //指定分类可用
                $array_intersect = array_intersect($real_category_ids, explode(',', $v['goods_ids']));
                if(empty($array_intersect)){
                    unset($coupon_type_list[ $k ]);
                    continue;
                }
            }
            if($v['goods_type'] == CouponDict::category_selected_out){
                //指定分类不可用
                $array_intersect = array_intersect($real_category_ids, explode(',', $v['goods_ids']));
                if(!empty($array_intersect)){
                    unset($coupon_type_list[ $k ]);
                    continue;
                }
            }
            if(!empty($store_id) && $v['use_store'] != 'all' && strpos($v['use_store'], ",{$store_id},") === false){
                //指定那些门店可用
                unset($coupon_type_list[ $k ]);
                continue;
            }

            if ($member_id != 0) {
                $coupon_rec_num = 0;
                if (!empty($member_coupon_num[ $v[ 'coupon_type_id' ] ])) {
                    $coupon_rec_num = $member_coupon_num[ $v[ 'coupon_type_id' ] ][ 'member_coupon_num' ];

                    if ($member_coupon_num[ $v[ 'coupon_type_id' ] ][ 'get_type' ] == 2) {
                        $count = model('promotion_coupon')->getCount([
                            [ 'member_id', '=', $member_id ],
                            [ 'get_type', '=', 2 ],
                            [ 'coupon_type_id', '=', $v[ 'coupon_type_id' ] ],
                            [ 'end_time', '>', time() ],
                        ]);
                        if($count == 0) {
                            $coupon_type_list[ $k ][ 'received_type' ] = 'expire';
                        }
                    }
                }
                //控制领用数量
                if ($v[ 'count' ] == $v[ 'lead_count' ]) {
                    unset($coupon_type_list[ $k ]);
                } elseif ($v[ 'max_fetch' ] != 0 && $coupon_rec_num >= $v[ 'max_fetch' ]) {
                    // 已领取
                    unset($coupon_type_list[ $k ]);
                }
            }
        }
        $coupon_type_list = array_values($coupon_type_list);
        $goods_sku_detail_array[ 'goods_sku_detail' ][ 'coupon_list' ] = $coupon_type_list;
        return $goods_sku_detail_array;
    }

    /**
     * 判断优惠券是否可用
     * @param $coupon_info
     * @param $goods_list
     * @param $goods_money
     * @return bool
     */
    public function judgeCouponAvailable($coupon_info, $goods_list, $goods_money)
    {
        $goods_category_model = new GoodsCategoryModel();
        $is_available = false;
        switch ($coupon_info['goods_type']) {
            //全场优惠券
            case CouponDict::all:
                if ($coupon_info['at_least'] <= $goods_money) {
                    $is_available = true;
                }
                break;
            //指定商品可用/不可用优惠券
            case CouponDict::selected:
            case CouponDict::selected_out:
                $coupon_goods_array = explode(',', trim($coupon_info['goods_ids'], ','));
                $least_money = 0;
                $is_support = false;
                $judge_res = $coupon_info['goods_type'] == CouponDict::selected;
                foreach ($goods_list as $v_goods) {
                    if (in_array($v_goods['goods_id'], $coupon_goods_array) == $judge_res) {
                        $least_money += $v_goods['goods_money'];
                        $is_support = true;
                    }
                }
                if ($is_support && $coupon_info['at_least'] <= $least_money) {
                    $is_available = true;
                }
                break;
            //指定分类可用/不可用优惠券
            case CouponDict::category_selected:
            case CouponDict::category_selected_out:
                $category_leaf_ids = $goods_category_model->getGoodsCategoryLeafIds($coupon_info['goods_ids'])['data'];
                $least_money = 0;
                $is_support = false;
                foreach ($goods_list as $v_goods) {
                    $goods_category_ids = explode(',', trim($v_goods['category_id'], ','));
                    $array_intersect = array_intersect($category_leaf_ids, $goods_category_ids);
                    if ($coupon_info['goods_type'] == CouponDict::category_selected) {
                        $judge_res = count($array_intersect) > 0;
                    } else {
                        $judge_res = count($array_intersect) == 0;
                    }
                    if ($judge_res) {
                        $least_money += $v_goods['goods_money'];
                        $is_support = true;
                    }
                }
                if ($is_support && $coupon_info['at_least'] <= $least_money) {
                    $is_available = true;
                }
                break;
        }

        return $is_available;
    }
}