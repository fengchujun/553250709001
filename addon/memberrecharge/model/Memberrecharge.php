<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\model;

use addon\coupon\model\CouponType;
use app\model\BaseModel;
use think\facade\Cache;
use app\model\system\Config as ConfigModel;
use app\model\upload\Upload;

/**
 * 会员充值
 */
class Memberrecharge extends BaseModel
{

    /**
     * 添加套餐
     * @param $data
     * @return array
     */
    public function addMemberRecharge($data)
    {
        $data[ 'create_time' ] = time();
        $data[ 'status' ] = 1;

        $res = model('member_recharge')->add($data);
        Cache::tag("member_recharge")->clear();
        return $this->success($res);
    }

    /**
     * 编辑套餐
     * @param array $condition
     * @param $data
     * @return array
     */
    public function editMemberRecharge($condition, $data)
    {
        $data[ 'update_time' ] = time();
        $recharge_info = model('member_recharge')->getInfo($condition);
        if (!empty($recharge_info[ 'cover_img' ]) && !empty($data[ 'cover_img' ]) && $recharge_info[ 'cover_img' ] != $data[ 'cover_img' ]) {
            $upload_model = new Upload();
            $upload_model->deletePic($recharge_info[ 'cover_img' ], $recharge_info[ 'site_id' ]);
        }

        $res = model('member_recharge')->update($data, $condition);
        Cache::tag("member_recharge")->clear();
        return $this->success($res);
    }

    /**
     * 删除套餐详情
     * @param array $condition
     * @return mixed
     */
    public function deleteMemberRecharge($condition = [])
    {
        $recharge_info = model('member_recharge')->getInfo($condition);
        if (!empty($recharge_info[ 'cover_img' ])) {
            $upload_model = new Upload();
            $upload_model->deletePic($recharge_info[ 'cover_img' ], $recharge_info[ 'site_id' ]);
        }
        $res = model('member_recharge')->delete($condition);
        Cache::tag("member_recharge")->clear();
        return $this->success($res);
    }

    /**
     * 套餐详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberRechargeInfo($condition = [], $field = '*')
    {
        $recharge = model('member_recharge')->getInfo($condition, $field);
        if ($recharge) {
            //获取优惠券信息
            if ($recharge[ 'coupon_id' ]) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term,type,discount';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $recharge[ 'coupon_id' ] ] ], $coupon_field);
                $recharge[ 'coupon_list' ] = $coupon[ 'data' ];
            }
        }
        Cache::tag("member_recharge")->clear();
        return $this->success($recharge);
    }

    /**
     * 套餐分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberRechargePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_recharge')->pageList($condition, $field, $order, $page, $page_size);

        Cache::tag("member_recharge")->clear();
        return $this->success($list);
    }

    /**
     * 套餐列表
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberRechargeList($condition = [], $order = '', $field = '*')
    {
        $list = model('member_recharge')->getList($condition, $field, $order);
        return $this->success($list);
    }

    /**
     * 设置会员充值配置
     * @param $data
     * @param $is_use
     * @param $site_id
     * @return array
     */
    public function setConfig($data, $is_use, $site_id)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '会员充值配置', $is_use, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'MEMBER_RECHARGE_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取会员充值配置
     * @param $site_id
     * @return array
     */
    public function getConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'MEMBER_RECHARGE_CONFIG' ] ]);
        return $res;
    }

}