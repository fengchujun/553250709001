<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\v3tov4\model;

/**
 * 迁移会员相关数据（会员、等级、标签、收货地址、商品收藏、足迹、账户流水）
 */
class Member extends Upgrade
{
    /**
     * 同步会员数据
     * @param $page_index
     * @param $page_size
     */
    public function getMemberList($page_index, $page_size)
    {
        try {
            model('member')->startTrans();

            $field = 'su.uid,su.user_name,su.user_password,su.user_headimg,su.user_tel,su.wx_openid,su.real_name,su.sex,su.location,su.nick_name,su.reg_time,su.birthday,su.wx_applet_openid,nma.point,nma.balance,nm.member_level,nm.member_label,nml.level_name,nsma.source_uid,nsma.promoter_id,nsma.is_promoter';
            $join = [
                [ 'ns_member_account nma', 'su.uid = nma.uid', 'left' ],
                [ 'ns_member nm', 'su.uid = nm.uid', 'left' ],
                [ 'ns_member_level nml', 'nm.member_level = nml.level_id', 'left' ],
                [ 'nfx_shop_member_association nsma', 'nsma.uid = nm.uid', 'left' ]
            ];
            // 查询v3会员表
            $member_list = $this->getPageList('sys_user', [ [ 'is_member', '=', '1' ] ], $field, $page_index, $page_size, 'su', $join);
            $member_data = [];
            if (!empty($member_list)) {
                if ($page_index == 1) {
                    // 首次清空会员表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('member')->execute("TRUNCATE TABLE {$prefix}member");
                }
                foreach ($member_list as $item) {
                    $member_label = '';
                    if (!empty($item[ 'member_label' ])) {
                        $label_result = $this->query("SELECT GROUP_CONCAT(nml.label_name) AS label_name FROM ns_member_label nml WHERE nml.id IN ({$item['member_label']});");
                        if (!empty($label_result[ 0 ][ 'label_name' ])) $member_label = $label_result[ 0 ][ 'label_name' ];
                    }
                    $member_data[] = [
                        'member_id' => $item['uid'],
                        'site_id' => 1,
                        'source_member' => $item['source_uid'] ?? 0,
                        'fenxiao_id' => $item['promoter_id'] ?? 0,
                        'is_fenxiao' => $item['is_promoter'] ?? 0,
                        'username' => $item['user_name'],
                        'nickname' => $item['nick_name'],
                        'mobile' => $item['user_tel'],
                        'password' => md5($item['user_password'] . 'NiuCloud'),
                        'headimg' => $item['user_headimg'],
                        'member_level' => $item['member_level'],
                        'member_level_name' => $item['level_name'],
                        'member_label' => $item['member_label'],
                        'member_label_name' => $member_label,
                        'wx_openid' => $item['wx_openid'],
                        'weapp_openid' => $item['wx_applet_openid'],
                        'realname' => $item['real_name'],
                        'sex' => $item['sex'],
                        'location' => $item['location'],
                        'birthday' => $item['birthday'],
                        'reg_time' => $item['reg_time'],
                        'point' => is_null($item['point']) ? 0 : $item['point'],
                        'balance' => is_null($item['balance']) ? 0 : $item['balance']
                    ];
                }
            }
            // 添加到v4会员表
            model('member')->addList($member_data);
            model('member')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员的数量
     */
    public function getMemberCount()
    {
        return $this->getCount('sys_user', [ [ 'is_member', '=', '1' ] ], 'uid');
    }

    /**
     * 同步会员等级数据
     * @param $page_index
     * @param $page_size
     */
    public function getMemberLevelList($page_index, $page_size)
    {
        try {
            model('member_level')->startTrans();

            $field = 'level_id,level_name,level,desc,is_default,goods_discount,give_point,give_money';
            // 查询v3会员等级表
            $level_list = $this->getPageList('ns_member_level', [], $field, $page_index, $page_size);
            $level_data = [];
            if (!empty($level_list)) {
                if ($page_index == 1) {
                    // 首次清空会员等级表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('member_level')->execute("TRUNCATE TABLE {$prefix}member_level");
                }
                foreach ($level_list as $item) {
                    $level_data[] = [
                        'level_id' => $item['level_id'],
                        'site_id' => 1,
                        'level_name' => $item['level_name'],
                        'sort' => $item['level'],
                        'remark' => $item['desc'],
                        'is_default' => $item['is_default'],
                        'consume_discount' => $item['goods_discount'] * 100,
                        'send_point' => $item['give_point'],
                        'send_balance' => $item['give_money'],
                    ];
                }
            }
            // 添加到v4会员等级表
            model('member_level')->addList($level_data);
            model('member_level')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_level')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员等级的数量
     */
    public function getMemberLevelCount()
    {
        return $this->getCount('ns_member_level', [], 'level_id');
    }

    /**
     * 同步会员标签数据
     * @param $page_index
     * @param $page_size
     */
    public function getMemberLabelList($page_index, $page_size)
    {
        try {
            model('member_label')->startTrans();

            $field = 'id,label_name,create_time,desc';
            // 查询v3会员标签表
            $label_list = $this->getPageList('ns_member_label', [], $field, $page_index, $page_size);
            $label_data = [];
            if (!empty($label_list)) {
                if ($page_index == 1) {
                    // 首次清空会员标签表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('member_level')->execute("TRUNCATE TABLE {$prefix}member_label");
                }
                foreach ($label_list as $item) {
                    $label_data[] = [
                        'label_id' => $item['id'],
                        'site_id' => 1,
                        'label_name' => $item['label_name'],
                        'create_time' => $item['create_time'],
                        'remark' => $item['desc']
                    ];
                }
            }
            // 添加到v4会员标签表
            model('member_label')->addList($label_data);
            model('member_label')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_label')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员标签的数量
     */
    public function getMemberLabelCount()
    {
        return $this->getCount('ns_member_label', [], 'id');
    }

    /**
     * 同步会员收货地址数据
     */
    public function getMemberAddressList($page_index, $page_size)
    {
        try {
            model('member_address')->startTrans();

            $join = [
                [ 'sys_province sp', 'sp.province_id = nmea.province', 'left' ],
                [ 'sys_city sc', 'sc.city_id = nmea.city', 'left' ],
                [ 'sys_district sd', 'sd.district_id = nmea.district', 'left' ],
            ];
            $field = 'nmea.id,nmea.uid,nmea.consigner,nmea.mobile,nmea.phone,nmea.address,nmea.is_default,sp.province_name,sc.city_name,sd.district_name';
            // 查询v3会员收货地址表
            $address_list = $this->getPageList('ns_member_express_address', [], $field, $page_index, $page_size, 'nmea', $join);
            $address_data = [];
            if (!empty($address_list)) {
                if ($page_index == 1) {
                    // 首次清空会员收货地址表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('member_address')->execute("TRUNCATE TABLE {$prefix}member_address");
                }
                foreach ($address_list as $item) {
                    $province_info = model('area')->getInfo([ [ 'name', 'like', '%' . $item[ 'province_name' ] . '%' ], [ 'level', '=', 1 ] ], 'id,name');
                    $city_info = model('area')->getInfo([ [ 'name', 'like', '%' . $item[ 'city_name' ] . '%' ], [ 'level', '=', 2 ] ], 'id,name');
                    $district_info = model('area')->getInfo([ [ 'name', 'like', '%' . $item[ 'district_name' ] . '%' ], [ 'level', '=', 3 ] ], 'id,name');
                    $full_address = ( $province_info[ 'name' ] ?? '' ) . ' ' . ( $city_info[ 'name' ] ?? '' ) . ' ' . ( $district_info[ 'name' ] ?? '' ) . ' ' . $item[ 'address' ];
                    $address_data[] = [
                        'id' => $item['id'],
                        'member_id' => $item['uid'],
                        'site_id' => 1,
                        'name' => $item['consigner'],
                        'mobile' => $item['mobile'],
                        'telephone' => $item['phone'],
                        'province_id' => $province_info['id'] ?? 0,
                        'city_id' => $city_info['id'] ?? 0,
                        'district_id' => $district_info['id'] ?? 0,
                        'community_id' => 0,
                        'address' => $item['address'],
                        'full_address' => $full_address,
                        'is_default' => $item['is_default']
                    ];
                }
            }
            // 添加到v4会员收货地址表
            model('member_address')->addList($address_data);
            model('member_address')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_address')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员收货地址的数量
     */
    public function getMemberAddressCount()
    {
        return $this->getCount('ns_member_express_address', [], 'id');
    }

    /**
     * 同步会员商品收藏数据
     */
    public function getMemberCollectList($page_index, $page_size)
    {
        try {
            model('goods_collect')->startTrans();

            $field = 'uid,fav_id,goods_name,goods_image,fav_time';
            // 查询v3会员收藏表
            $label_list = $this->getPageList('ns_member_favorites', [ [ 'fav_type', '=', 'goods' ] ], $field, $page_index, $page_size);
            $label_data = [];
            if (!empty($label_list)) {
                if ($page_index == 1) {
                    // 首次清空会员收藏表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('goods_collect')->execute("TRUNCATE TABLE {$prefix}goods_collect");
                }
                foreach ($label_list as $item) {
                    $label_data[] = [
                        'member_id' => $item['uid'],
                        'goods_id' => $item['fav_id'],
                        'sku_name' => $item['goods_name'],
                        'sku_image' => $item['goods_image'],
                        'create_time' => $item['fav_time'],
                        'site_id' => 1
                    ];
                }
            }
            // 添加到v4会员收藏表
            model('goods_collect')->addList($label_data);
            model('goods_collect')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods_collect')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员商品收藏的数量
     */
    public function getMemberCollectCount()
    {
        return $this->getCount('ns_member_favorites', [ [ 'fav_type', '=', 'goods' ] ], 'log_id');
    }

    /**
     * 同步会员足迹数据
     */
    public function getMemberBrowseList($page_index, $page_size)
    {
        try {
            model('goods_browse')->startTrans();

            $field = 'uid,create_time,goods_id';
            // 查询v3会员足迹表
            $list = $this->getPageList('ns_goods_browse', [], $field, $page_index, $page_size);
            $data = [];
            if (!empty($list)) {
                if ($page_index == 1) {
                    // 首次清空会员足迹表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('goods_browse')->execute("TRUNCATE TABLE {$prefix}goods_browse");
                }
                foreach ($list as $item) {
                    $sku_info = $this->getInfo('ns_goods_sku', [ [ 'goods_id', '=', $item[ 'goods_id' ] ] ], 'sku_id');
                    $data[] = [
                        'member_id' => $item['uid'],
                        'browse_time' => $item['create_time'],
                        'site_id' => 1,
                        'sku_id' => $sku_info['sku_id'] ?? 0,
                        'goods_id' => $item['goods_id']
                    ];
                }
            }
            // 添加到v4会员足迹表
            model('goods_browse')->addList($data);
            model('goods_browse')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods_browse')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员足迹的数量
     */
    public function getMemberBrowseCount()
    {
        return $this->getCount('ns_goods_browse', [], 'browse_id');
    }

    /**
     * 同步会员账户流水
     * @param $page_index
     * @param $page_size
     * @return array
     */
    public function getMemberAccountRecordList($page_index, $page_size)
    {
        try {
            model('member_account')->startTrans();

            $join = [
                [ 'sys_user su', 'su.uid = nmar.uid', 'left' ]
            ];
            $field = 'nmar.uid,nmar.account_type,nmar.number,nmar.from_type,nmar.text,nmar.create_time,su.user_name,su.user_tel';
            // 查询v3会员账户流水表
            $list = $this->getPageList('ns_member_account_records', [ [ 'account_type', 'in', [ 1, 2 ] ] ], $field, $page_index, $page_size, 'nmar', $join);
            $data = [];
            if (!empty($list)) {
                if ($page_index == 1) {
                    // 首次清空会员账户流水表
                    $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
                    model('member_account')->execute("TRUNCATE TABLE {$prefix}member_account");
                }
                foreach ($list as $item) {
                    $from_type = [ 'type' => '', 'name' => '' ];
                    $data[] = [
                        'site_id' => 1,
                        'member_id' => $item['uid'],
                        'account_type' => $item['account_type'] == 1 ? 'point' : 'balance',
                        'account_data' => $item['number'],
                        'from_type' => $from_type['type'],
                        'type_name' => $from_type['name'],
                        'remark' => $item['text'],
                        'create_time' => $item['create_time'],
                        'username' => $item['user_name'],
                        'mobile' => $item['user_tel'],
                    ];
                }
            }
            // 添加到v4会员账户流水表
            model('member_account')->addList($data);
            model('member_account')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('member_account')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的会员账户流水的数量
     */
    public function getMemberAccountRecordCount()
    {
        return $this->getCount('ns_member_account_records', [ [ 'account_type', 'in', [ 1, 2 ] ] ], 'id');
    }

}