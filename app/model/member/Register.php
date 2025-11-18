<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\member;

use addon\coupon\model\CouponType;
use addon\wechat\model\Message as WechatMessage;
use app\model\BaseModel;
use app\model\message\Sms;
use addon\coupon\model\Coupon;
use app\model\system\Stat;
use think\facade\Queue;

/**
 * 注册
 * Class Register
 * @package app\model\member
 */
class Register extends BaseModel
{

    /**
     * 用户名密码注册(必传username， password),之前检测重复性,判断用户名是否为手机，邮箱
     * @param $data
     * @return array|mixed
     */
    public function usernameRegister($data)
    {
        $examine_username_exit = $this->usernameExist($data['username'], $data['site_id']);
        if ($examine_username_exit) return $this->error('', '用户名已存在');

        $nickname = $data['username']; // 默认昵称为用户名
        if (isset($data['nickname']) && !empty($data['nickname'])) {
            $nickname = preg_replace_callback('/./u', function(array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            }, $data['nickname']);
        }
        $data['nickname'] = $nickname;
        return $this->memberRegister($data);
    }

    /**
     * 手机号密码注册(必传mobile， password),之前检测重复性
     * @param $data
     * @return array|mixed
     */
    public function mobileRegister($data)
    {
        $examine_mobile_exit = $this->mobileExist($data['mobile'], $data['site_id']);
        if ($examine_mobile_exit) return $this->error('', '手机号已存在');
        $data['username'] = $data['username'] ?? $this->createRandUsername($data['site_id']);
        $nickname = substr($data['mobile'], 0, 3).'****'.substr($data['mobile'], 7);
        if (isset($data['nickname']) && !empty($data['nickname'])) {
            $nickname = preg_replace_callback('/./u', function(array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            }, $data['nickname']);
        }
        $data['nickname'] = $nickname;
        return $this->memberRegister($data);
    }

    /**
     * 第三方注册
     * @param $data
     * @return array
     */
    public function authRegister($data)
    {
        $data['username'] = $this->createRandUsername($data['site_id']);
        $nickname = $data['username'];
        if (isset($data['nickName']) && !empty($data['nickName'])) {
            $nickname = preg_replace_callback('/./u', function(array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            }, $data['nickName']);
        }
        $data['nickname'] = $nickname;
        return $this->memberRegister($data);
    }

    /**
     * 会员注册方法
     * @param $data
     * @return array
     */
    private function memberRegister($data)
    {
        //会员注册相关奖励
        $award_array = [
            'point' => 0,
            'balance' => 0,
            'growth' => 0,
            'coupon' => 0,
            'coupon_list' => [],
            'coupon_list_config'=>[]
        ];
        $reg_award_array = event("MemberRegisterAward", ['site_id' => $data['site_id']]);
        if (!empty($reg_award_array)) {
            foreach ($reg_award_array as $k => $v) {
                $award_array['point'] += $v['point'] ?? 0;
                $award_array['balance'] += $v['balance'] ?? 0;
                $award_array['growth'] += $v['growth'] ?? 0;
                $coupon_list = $v['coupon_list'] ?? [];
                if (!empty($coupon_list)) {
                    $award_array['coupon_list'] = empty($award_array['coupon_list']) ? $coupon_list : array_merge($award_array['coupon_list'], $v['coupon_list']);
                    $award_array['coupon_list_config'] = array_column($v['coupon_new'] ?? [],'num','id');
                }
            }
        }
        //查询会员等级相关(根据会员获取的成长值)
        $member_level = new MemberLevel();
        $member_level_info = $member_level->getFirstMemberLevel([['site_id', '=', $data['site_id']], ['level_type', '=', 0], ['growth', '<=', $award_array['growth']]], '*', 'growth desc')['data'];
        //查询会员等级相关奖励
        $data_reg = [
            'site_id' => $data['site_id'],
            'source_member' => $data['source_member'] ?? 0,
            'username' => $data['username'],
            'nickname' => $data['nickname'],
            'mobile' => $data['mobile'] ?? '',
            'password' => isset($data['password']) && !empty($data['password']) ? data_md5($data['password']) : '',
            'qq_openid' => $data['qq_openid'] ?? '',
            'wx_openid' => $data['wx_openid'] ?? '',
            'weapp_openid' => $data['weapp_openid'] ?? '',
            'wx_unionid' => $data['wx_unionid'] ?? '',
            'ali_openid' => $data['ali_openid'] ?? '',
            'baidu_openid' => $data['baidu_openid'] ?? '',
            'toutiao_openid' => $data['toutiao_openid'] ?? '',
            'headimg' => $data['avatarUrl'] ?? '',
            'member_level' => !empty($member_level_info) ? $member_level_info['level_id'] : 0,
            'member_level_name' => !empty($member_level_info) ? $member_level_info['level_name'] : '',
            'is_member' => !empty($member_level_info) ? 1 : 0,
            'member_time' => !empty($member_level_info) ? time() : 0,
            'reg_time' => time(),
            'login_time' => time(),
            'last_login_time' => time(),
            'is_edit_username' => 1,
            'last_visit_time' => time(),
            'login_type' => $data['app_type'] ?? '',
            'login_type_name' => $data['app_type_name'] ?? '',
        ];
        $member_id = model("member")->add($data_reg);

        if ($member_id) {
            if (!empty($member_level_info)) {
                $award_array['point'] += $member_level_info['send_point'];
                $award_array['balance'] += $member_level_info['send_balance'];
                //获取优惠券信息
                if (!empty($member_level_info['send_coupon'])) {
                    //优惠券字段
                    $coupon_field = '*';

                    $model = new CouponType();
                    $coupon = $model->getCouponTypeList([['coupon_type_id', 'in', $member_level_info['send_coupon']]], $coupon_field);
                    $member_level_info['coupon_list'] = $coupon['data'];
                }

                $coupon_list = $member_level_info['coupon_list'] ?? [];
                if (!empty($coupon_list)) {
                    $award_array['coupon_list'] = empty($award_array['coupon_list']) ? $coupon_list : array_merge($award_array['coupon_list'], $v['coupon_list']);
                }

            }
            //会员注册奖励积分，优惠券，红包
            $data_reg['member_id'] = $member_id;
            $member_account_model = new MemberAccount();
            $member_account_model->addMemberAccountInRegister($data_reg, $award_array);
            //给用户发放优惠券
            if (!empty($award_array['coupon_list'])) {
                $coupon_model = new Coupon();
                $coupon_data = [];
                foreach($award_array['coupon_list'] as $val){
                    $coupon_data[] = ['coupon_type_id' => $val['coupon_type_id'], 'num' => $award_array['coupon_list_config'][$val['coupon_type_id']] ?? 1];
                }
                $coupon_model->giveCoupon($coupon_data, $data['site_id'], $member_id, Coupon::GET_TYPE_ACTIVITY_GIVE);
            }
            //会员注册成功后续事件
            Queue::push('app\job\MemberRegisterAfter', $data_reg);

        }

        return $this->success($member_id);
    }

    /**
     * 会员注册成功后续事件
     * @param $data_reg
     */
    public function memberRegisterAfter($data_reg)
    {
        event("MemberRegister", ['member_id' => $data_reg['member_id'], 'site_id' => $data_reg['site_id']]);
        //添加统计
        $stat = new Stat();
        $stat->switchStat(['type' => 'add_member', 'data' => ['member_count' => 1, 'site_id' => $data_reg['site_id']]]);
        $this->pullHeadimg($data_reg);
    }

    /**
     * 生成随机用户名
     * @param $site_id
     * @return string
     */
    private function createRandUsername($site_id)
    {
        $usernamer = 'u_' . random_keys(10);
        $count = model('member')->getCount([['username', '=', $usernamer], ['site_id', '=', $site_id]]);
        if ($count) {
            $usernamer = $this->createRandUsername($site_id);
            return $usernamer;
        } else {
            return $usernamer;
        }
    }

    /**
     * 重置用户微信openid
     * @param $data
     * @return array
     */
    public function wxopenidBind($data)
    {
        $res = model("member")->update(['wx_openid' => $data['wx_openid']], [['member_id', '=', $data['member_id']], ['site_id', '=', $data['site_id']], ['is_delete', '=', 0]]);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 检测用户存在性(用户名)
     * @param $username
     * @param $site_id
     * @return int
     */
    public function usernameExist($username, $site_id)
    {
        $member_info = model("member")->getInfo([
            ['username|mobile', '=', $username],
            ['site_id', '=', $site_id],
            ['is_delete', '=', 0]
        ], 'member_id');
        if (!empty($member_info)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 检测用户存在性(用户名) 存在返回1
     * @param $mobile
     * @param $site_id
     * @return int
     */
    public function mobileExist($mobile, $site_id)
    {
        $member_info = model("member")->getInfo([
            ['mobile', '=', $mobile],
            ['site_id', '=', $site_id],
            ['is_delete', '=', 0]], 'member_id');
        if (!empty($member_info)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 检测用户存在性(wx_openid) 存在返回1 新增2021.06.18
     * @param $mobile
     * @param $site_id
     * @return int
     */
    public function openidExist($mobile, $site_id)
    {
        $member_info = model("member")->getInfo([
            ['mobile', '=', $mobile],
            ['site_id', '=', $site_id],
            ['is_delete', '=', 0]
        ], 'wx_openid');
        if (!empty($member_info['wx_openid'])) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 获取用户ID 新增2021.06.18
     * @param $mobile
     * @param $site_id
     * @return int
     */
    public function getMemberId($mobile, $site_id)
    {
        $member_info = model("member")->getInfo([
            ['mobile', '=', $mobile],
            ['site_id', '=', $site_id],
            ['is_delete', '=', 0]
        ], 'member_id');
        if (!empty($member_info)) {
            return $member_info['member_id'];
        } else {
            return 0;
        }
    }

    /**
     * 注册发送验证码
     * @param $data
     * @return array|mixed|void
     */
    public function registerCode($data)
    {
        //发送短信
        $sms_model = new Sms();
        $var_parse = array (
            "code" => $data["code"],//验证码
        );
        $data["sms_account"] = $data["mobile"] ?? '';//手机号
        $data["var_parse"] = $var_parse;
        $sms_result = $sms_model->sendMessage($data);
        if ($sms_result["code"] < 0)
            return $sms_result;

        return $this->success();
    }

    /**
     * 注册成功通知
     * @param $data
     * @return array|mixed|void
     */
    public function registerSuccess($data)
    {

        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([["member_id", "=", $data["member_id"]]], "username,mobile,email,reg_time,wx_openid,last_login_type,nickname");
        $member_info = $member_info_result["data"];
        $name = $member_info["nickname"] == '' ? $member_info["mobile"] : $member_info["nickname"];
        //发送短信
        $var_parse = [
            "shopname" => replaceSpecialChar($data['site_info']['site_name']),   //商城名称
            "username" => replaceSpecialChar($name),    //会员名称
        ];
        $data["sms_account"] = $member_info["mobile"] ?? '';//手机号
        $data["var_parse"] = $var_parse;
        $sms_model = new Sms();
        $sms_result = $sms_model->sendMessage($data);
//        if ($sms_result["code"] < 0) return $sms_result;

        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];

        $data["template_data"] = [
            'keyword1' => $member_info["nickname"],
            'keyword2' => time_to_date($member_info["reg_time"]),
        ];
        $data["page"] = '';
        $wechat_model->sendMessage($data);

        return $this->success();
    }

    /**
     * 拉取用户头像
     * @param unknown $info
     */
    private function pullHeadimg($data)
    {
        if (!empty($data['headimg']) && is_url($data['headimg'])) {
            $url = __ROOT__ . '/api/member/pullheadimg?member_id=' . $data['member_id'];
            http($url, 1);
        }
    }
}