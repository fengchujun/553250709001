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

use think\facade\Cache;

/**
 * 迁移分销相关数据（分销商、分销商等级）
 */
class Fenxiao extends Upgrade
{
    /**
     * 同步分销商数据
     * @param $page_index
     * @param $page_size
     */
    public function getFenxiaoList($page_index, $page_size)
    {
        try {
            model('fenxiao')->startTrans();

            $field = 'np.promoter_id,np.promoter_shop_name,np.uid,np.promoter_level,np.parent_promoter,np.regidter_time,np.audit_time,np.lock_time,npl.level_name';
            $join = [
                [ 'nfx_promoter_level npl', 'np.promoter_level = npl.level_id', 'left' ],
            ];
            // 查询v3分销商表
            $list = $this->getPageList('nfx_promoter', [ [ 'is_audit', '=', '1' ] ], $field, $page_index, $page_size, 'np', $join);
            $data = [];
            if (!empty($list)) {
                if ($page_index == 1) {
                    // 首次清空分销商表
                    $prefix = config('database')['connections']['mysql']['prefix'];
                    model('fenxiao')->execute("TRUNCATE TABLE {$prefix}fenxiao");
                }
                foreach ($list as $item) {
                    // 分销商编号
                    $time_str = date('YmdHi');
                    $max_no = Cache::get('fenxiao_no_' . $time_str);
                    if (empty($max_no)) {
                        $max_no = 1;
                    } else {
                        $max_no += 1;
                    }
                    $fenxiao_no = $time_str . sprintf('%04d', $max_no);
                    // 上上级分销商id
                    $grand_parent = 0;
                    if (!empty($item[ 'parent_promoter' ])) {
                        $parent_promoter_info = $this->getInfo('nfx_promoter', [ [ 'promoter_id', '=', $item[ 'parent_promoter' ] ] ], 'parent_promoter');
                        if (!empty($parent_promoter_info) && !empty($parent_promoter_info[ 'parent_promoter' ])) {
                            $grand_parent = $parent_promoter_info[ 'parent_promoter' ];
                        }
                    }
                    // 查询分销商账户数据
                    $account_info = $this->getInfo('nfx_user_account', [ [ 'uid', '=', $item[ 'uid' ] ] ], 'commission,commission_cash,commission_withdraw');
                    $data[] = [
                        'fenxiao_id' => $item['promoter_id'],
                        'site_id' => 1,
                        'fenxiao_no' => $fenxiao_no,
                        'fenxiao_name' => $item['promoter_shop_name'],
                        'member_id' => $item['uid'],
                        'level_id' => $item['promoter_level'],
                        'level_name' => $item['level_name'],
                        'parent' => $item['parent_promoter'],
                        'grand_parent' => $grand_parent,
                        'account' => $account_info['commission_cash'] ?? 0,
                        'account_withdraw' => $account_info['commission_withdraw'] ?? 0,
                        'total_commission' => $account_info['commission'] ?? 0,
                        'create_time' => $item['regidter_time'],
                        'audit_time' => $item['audit_time'],
                        'lock_time' => $item['lock_time']
                    ];
                }
            }
            // 添加到v4分销商表
            model('fenxiao')->addList($data);
            model('fenxiao')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('fenxiao')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的分销商的数量
     */
    public function getFenxiaoCount()
    {
        return $this->getCount('nfx_promoter', [ [ 'is_audit', '=', '1' ] ], 'promoter_id');
    }

    /**
     * 同步待审核分销商数据
     * @param $page_index
     * @param $page_size
     */
    public function getFenxiaoApplyList($page_index, $page_size)
    {
        try {
            model('fenxiao_apply')->startTrans();

            $field = 'np.promoter_shop_name,np.parent_promoter,np.uid,np.promoter_level,np.regidter_time,npl.level_name,su.user_tel,su.nick_name,su.user_headimg,su.reg_time,su.user_name';
            $join = [
                [ 'nfx_promoter_level npl', 'np.promoter_level = npl.level_id', 'left' ],
                [ 'sys_user su', 'su.uid = np.uid', 'left' ]
            ];
            // 查询v3分销商表
            $list = $this->getPageList('nfx_promoter', [ [ 'is_audit', '=', '0' ] ], $field, $page_index, $page_size, 'np', $join);

            $data = [];
            if (!empty($list)) {
                if ($page_index == 1) {
                    // 首次清空分销商申请表
                    $prefix = config('database')['connections']['mysql']['prefix'];
                    model('fenxiao_apply')->execute("TRUNCATE TABLE {$prefix}fenxiao_apply");
                }
                foreach ($list as $item) {
                    $user_info = $this->getInfo('sys_user', [ [ 'uid', '=', $item[ 'uid' ] ] ]);
                    if ($user_info) {
                        $data[] = [
                            'site_id' => 1,
                            'fenxiao_name' => $item['promoter_shop_name'],
                            'parent' => $item['parent_promoter'],
                            'member_id' => $item['uid'],
                            'mobile' => $item['user_tel'] ?? 0,
                            'nickname' => $item['nick_name'] ?? $item['user_name'],
                            'headimg' => $item['user_headimg'],
                            'level_id' => $item['promoter_level'],
                            'level_name' => $item['level_name'],
                            'reg_time' => $item['reg_time'],
                            'create_time' => $item['regidter_time']
                        ];
                    }
                }
            }
            // 添加到v4分销商申请表
            model('fenxiao_apply')->addList($data);
            model('fenxiao_apply')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('fenxiao_apply')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的待审核分销商的数量
     */
    public function getFenxiaoApplyCount()
    {
        return $this->getCount('nfx_promoter', [ [ 'is_audit', '=', '0' ] ], 'promoter_id');
    }

    /**
     * 同步分销商等级数据
     */
    public function getFenxiaoLevelList($page_index, $page_size)
    {
        try {
            model('fenxiao_level')->startTrans();

            if ($page_index == 1) {
                $field = 'level_id,level_name,level_0,level_1,level_2,level_money,create_time';
                // 查询v3分销商表
                $list = $this->getList('nfx_promoter_level', [], $field, 'level_money asc');
                $data = [];
                if (!empty($list)) {
                    // 首次清空分销商申请表
                    $prefix = config('database')['connections']['mysql']['prefix'];
                    model('fenxiao_level')->execute("TRUNCATE TABLE {$prefix}fenxiao_level");

                    foreach ($list as $key => $item) {
                        $data[] = [
                            'level_id' => $item['level_id'],
                            'level_num' => ($key + 1),
                            'site_id' => 1,
                            'level_name' => $item['level_name'],
                            'one_rate' => $item['level_0'],
                            'two_rate' => $item['level_1'],
                            'three_rate' => $item['level_2'],
                            'upgrade_type' => 1,
                            'order_money' => $item['level_money'],
                            'create_time' => $item['create_time'],
                            'status' => 1
                        ];
                    }

                    // 添加到v4分销商等级表
                    model('fenxiao_level')->addList($data);
                }

                // 添加默认分销商等级
                $default_level = [
                    'site_id' => 1,
                    'level_name' => '默认等级',
                    'level_num' => 0,
                    'one_rate' => 0,
                    'two_rate' => 0,
                    'three_rate' => 0,
                    'upgrade_type' => 1,
                    'order_money' => 0,
                    'create_time' => time(),
                    'status' => 1,
                    'is_default' => 1
                ];
                model('fenxiao_level')->add($default_level);
            }

            model('fenxiao_level')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('fenxiao_level')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取需要同步的分销商等级的数量
     */
    public function getFenxiaoLevelCount()
    {
        return $this->getCount('nfx_promoter_level', [], 'level_id');
    }
}