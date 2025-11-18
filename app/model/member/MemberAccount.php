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

use app\dict\member_account\AccountDict;
use app\model\BaseModel;
use app\model\message\Message;
use app\model\message\Sms;
use addon\wechat\model\Message as WechatMessage;
use app\model\member\Member as MemberModel;
use addon\weapp\model\Message as WeappMessage;
use think\facade\Db;

/**
 * 会员账户
 */
class MemberAccount extends BaseModel
{
    //账户类型
    private $account_type = [
        'balance' => '储值余额',
        'balance_money' => '现金余额',
        'point' => '积分',
        'growth' => '成长值'
    ];

    //来源类型
    public $from_type = [];

    public function __construct()
    {
        $event_from_type = event('MemberAccountFromType', '');
        $from_type = [];
        foreach ($event_from_type as $info) {

            if (isset($info[ 'balance' ])) {
                $balance = array_keys($info[ 'balance' ]);
                $from_type[ 'balance' ][ $balance[ 0 ] ] = $info[ 'balance' ][ $balance[ 0 ] ];
            }

            if (isset($info[ 'point' ])) {
                $point = array_keys($info[ 'point' ]);
                $from_type[ 'point' ][ $point[ 0 ] ] = $info[ 'point' ][ $point[ 0 ] ];
            }

            if (isset($info[ 'growth' ])) {
                $growth = array_keys($info[ 'growth' ]);
                $from_type[ 'growth' ][ $growth[ 0 ] ] = $info[ 'growth' ][ $growth[ 0 ] ];
            }

            if (isset($info[ 'balance_money' ])) {
                $balance_money = array_keys($info[ 'balance_money' ]);
                $from_type[ 'balance_money' ][ $balance_money[ 0 ] ] = $info[ 'balance_money' ][ $balance_money[ 0 ] ];
            }
        }
        $from_type['point']['register'] = ['type_name' => '注册', 'type_url' => ''];
        $from_type['balance']['register'] = ['type_name' => '注册', 'type_url' => ''];
        $from_type['growth']['register'] = ['type_name' => '注册', 'type_url' => ''];
        $from_type[ 'balance' ][ 'adjust' ] = [ 'type_name' => '调整', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'adjust' ] = [ 'type_name' => '调整', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'order' ] = [ 'type_name' => '消费', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'order' ] = [ 'type_name' => '消费', 'type_url' => '' ];
        $from_type[ 'point' ][ 'order' ] = [ 'type_name' => '消费', 'type_url' => '' ];

        $from_type[ 'point' ][ 'adjust' ] = [ 'type_name' => '调整', 'type_url' => '' ];
        $from_type[ 'growth' ][ 'adjust' ] = [ 'type_name' => '调整', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'upgrade' ] = [ 'type_name' => '升级', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'upgrade' ] = [ 'type_name' => '升级', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'membercode' ] = [ 'type_name' => '会员码扣款', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'membercode' ] = [ 'type_name' => '会员码扣款', 'type_url' => '' ];

        $from_type[ 'point' ][ 'upgrade' ] = [ 'type_name' => '升级', 'type_url' => '' ];
        $from_type[ 'growth' ][ 'upgrade' ] = [ 'type_name' => '升级', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'refund' ] = [ 'type_name' => '退还', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'refund' ] = [ 'type_name' => '退还', 'type_url' => '' ];
        $from_type[ 'point' ][ 'refund' ] = [ 'type_name' => '退还', 'type_url' => '' ];
        $from_type[ 'point' ][ 'pointexchangerefund' ] = [ 'type_name' => '积分兑换退还', 'type_url' => '' ];



        $from_type[ 'balance' ][ 'memberlevel' ] = [ 'type_name' => '开卡', 'type_url' => '' ];
        $from_type[ 'point' ][ 'memberlevel' ] = [ 'type_name' => '开卡', 'type_url' => '' ];

        $from_type[ 'balance_money' ][ 'birthdaygift' ] = [ 'type_name' => '生日有礼', 'type_url' => '' ];
        $from_type[ 'balance' ][ 'birthdaygift' ] = [ 'type_name' => '生日有礼', 'type_url' => '' ];
        $from_type[ 'point' ][ 'birthdaygift' ] = [ 'type_name' => '生日有礼', 'type_url' => '' ];

        $from_type[ 'balance_money' ][ 'scenefestival' ] = [ 'type_name' => '节日有礼', 'type_url' => '' ];
        $from_type[ 'balance' ][ 'scenefestival' ] = [ 'type_name' => '节日有礼', 'type_url' => '' ];
        $from_type[ 'point' ][ 'scenefestival' ] = [ 'type_name' => '节日有礼', 'type_url' => '' ];

        $from_type[ 'balance_money' ][ 'pinfan' ] = [ 'type_name' => '拼团返利', 'type_url' => '' ];
        $from_type[ 'balance' ][ 'pinfan' ] = [ 'type_name' => '拼团返利', 'type_url' => '' ];
        $from_type[ 'point' ][ 'pinfan' ] = [ 'type_name' => '拼团返利', 'type_url' => '' ];

        $from_type[ 'balance_money' ][ 'withdraw' ] = [ 'type_name' => '提现', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'withdraw_fail' ] = [ 'type_name' => '提现失败', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'giftcard' ] = [ 'type_name' => '礼品卡', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'giftcard' ] = [ 'type_name' => '礼品卡', 'type_url' => '' ];
        $from_type[ 'point' ][ 'giftcard' ] = [ 'type_name' => '礼品卡', 'type_url' => '' ];

        $from_type[ 'balance' ][ 'hongbao' ] = [ 'type_name' => '裂变红包', 'type_url' => '' ];
        $from_type[ 'balance_money' ][ 'hongbao' ] = [ 'type_name' => '裂变红包', 'type_url' => '' ];

        $from_type[ 'point' ][ 'point_expire' ] = [ 'type_name' => '积分到期', 'type_url' => '' ];
        $from_type[ 'point' ][ 'point_cancel' ] = [ 'type_name' => '积分取消', 'type_url' => '' ];
        $from_type[ 'point' ][ 'point_set_zero' ] = [ 'type_name' => '积分清零', 'type_url' => '' ];

        $this->from_type = $from_type;
    }

    /**
     * 获取账户类型
     */
    public function getAccountType()
    {
        return $this->account_type;
    }

    /**
     * 获取来源类型
     */
    public function getFromType()
    {
        return $this->from_type;
    }

    /**
     * 添加会员账户数据
     * @param $site_id
     * @param $member_id
     * @param $account_type
     * @param $account_data
     * @param $from_type
     * @param $relate_tag
     * @param $remark
     * @param int $related_id
     * @return array
     */
    public function addMemberAccount($site_id, $member_id, $account_type, $account_data, $from_type, $relate_tag, $remark, $related_id = 0)
    {
        model('member_account')->startTrans();
        try {
            $msg = '';
            if ($account_type == AccountDict::balance) {
                $msg = '账户余额';
            } elseif ($account_type == AccountDict::point) {
                $msg = '账户积分';
            } elseif ($account_type == AccountDict::growth) {
                $msg = '账户成长值';
            }

            //账户检测
            $member_account = Db::name("member")->where([
                [ 'member_id', '=', $member_id ],
                [ 'site_id', '=', $site_id ]
            ])->field($account_type . ', username, mobile, nickname, email')->lock(true)->find();
            $account_new_data = round((float) $member_account[ $account_type ] + (float) $account_data, 2);
            if($account_new_data < 0){
                if(in_array($from_type,['point_cancel', 'point_expire'])){
                    $account_data = -$member_account[ $account_type ];
                    $remark = $this->from_type[AccountDict::point][$from_type]['type_name']."：" . $member_account[ $account_type ];
                    $account_new_data = 0;
                }else{
                    model('member_account')->rollback();
                    return $this->error('', $msg.'不可为负数');
                }
            }
            if($account_new_data > 99999999){
                return $this->error('', $msg.'不可超出99999999');
            }
            //添加记录
            $type_info = $this->from_type[ $account_type ][ $from_type ];

            $data = array (
                'site_id' => $site_id,
                'member_id' => $member_id,
                'account_type' => $account_type,
                'account_data' => $account_data,
                'from_type' => $from_type,
                'type_name' => $type_info[ 'type_name' ],
                'type_tag' => $relate_tag,
                'create_time' => time(),
                'username' => $member_account[ 'username' ],
                'mobile' => $member_account[ 'mobile' ],
                'email' => $member_account[ 'email' ],
                'remark' => $remark,
                'related_id' => $related_id,
            );

            model('member_account')->add($data);
            //账户更新
            model('member')->update([
                $account_type => $account_new_data
            ], [
                'member_id' => $member_id
            ]);
            event('AddMemberAccount', $data);
            model('member_account')->commit();
            //发送消息通知(余额变动通知)

            $account_type_arr = [
                'balance' => 'USER_BALANCE_CHANGE_NOTICE',
                'balance_money' => 'USER_BALANCE_CHANGE_NOTICE',
                'point' => 'USER_POINT_CHANGE_NOTICE'
            ];
            if (array_key_exists($account_type, $account_type_arr)) {
                $data[ 'keywords' ] = $account_type_arr[$account_type];
                $message_model = new Message();
                $message_model->sendMessage($data);
            }
            return $this->success([ 'member_id' => $member_id, $account_type => sprintf("%.2f", $account_new_data) ]);
        } catch (\Exception $e) {
            model('member_account')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 订单支付处理会员相关账户业务(余额，可提现余额)
     * @param $order_pay_array
     * @return array|void
     */
    public function addMemberAccountInOrderPay($order_pay_array)
    {
        $pay_info = $order_pay_array['pay_info'];
        $order_info = $order_pay_array['order_info'];
        $member_info = $order_pay_array['member_info'];

        //订单余额账户处理
        if (( $pay_info[ 'balance' ] || $pay_info[ 'balance_money' ] ) && $order_info[ 'order_money' ]) {

            //修改整体业务
            model('member_account')->startTrans();
            try{
                //账户变动
                $data_member_account = [
                    'balance' => $member_info['balance'] - $pay_info['balance'],
                    'balance_money' => $member_info['balance_money'] - $pay_info['balance_money'],
                    'balance_lock' => $member_info['balance_lock'] - $pay_info['balance'],
                    'balance_money_lock' => $member_info['balance_money_lock'] - $pay_info['balance_money']
                ];
                model("member")->update($data_member_account, [['member_id', '=', $order_info['member_id']]]);

                //账户记录
                $account_log = [];
                if($pay_info[ 'balance' ] > 0)
                {
                    $account_log[] = [
                        'site_id' => $order_info['site_id'],
                        'member_id' => $member_info['member_id'],
                        'account_type' => 'balance',
                        'account_data' => $pay_info['balance'] * -1,
                        'from_type' => 'order',
                        'type_name' =>  $this->from_type[ 'balance' ][ 'order' ]['type_name'],
                        'create_time' => time(),
                        'username' => $member_info['username'],
                        'mobile' => $member_info['mobile'],
                        'email' => '',
                        'remark' => '订单消费扣除',
                        'related_id' => $order_info['order_id']
                    ];
                }
                if($pay_info[ 'balance_money' ] > 0)
                {
                    $account_log[] = [
                        'site_id' => $order_info['site_id'],
                        'member_id' => $member_info['member_id'],
                        'account_type' => 'balance',
                        'account_data' => $pay_info['balance_money'] * -1,
                        'from_type' => 'order',
                        'type_name' =>  $this->from_type[ 'balance_money' ][ 'order' ]['type_name'],
                        'create_time' => time(),
                        'username' => $member_info['username'],
                        'mobile' => $member_info['mobile'],
                        'email' => '',
                        'remark' => '订单消费扣除',
                        'related_id' => $order_info['order_id']
                    ];
                }
                if(!empty($account_log))
                {
                    model('member_account')->addList($account_log);

                    $total_balance = $pay_info[ 'balance' ] + $pay_info[ 'balance_money' ];
                    if ($total_balance > 0) model('order')->update([ 'balance_money' => $total_balance, 'pay_money' => ( $order_info['pay_money'] - $total_balance ) ], [ [ 'order_id', '=', $order_info['order_id'] ] ]);
                }
                model('member_account')->commit();
                //发送消息通知(余额变动通知)
                if ($pay_info['balance_money'] + $pay_info['balance'] > 0) {
                    $message_data =  [
                        'site_id' => $order_info['site_id'],
                        'member_id' => $member_info['member_id'],
                        'account_type' => 'balance',
                        'account_data' => ($pay_info['balance_money'] + $pay_info['balance']) * -1,
                        'from_type' => 'order',
                        'type_name' =>  $this->from_type[ 'balance' ][ 'order' ]['type_name'],
                        'create_time' => time(),
                        'username' => $member_info['username'],
                        'mobile' => $member_info['mobile'],
                        'email' => '',
                        'remark' => '订单消费扣除',
                        'related_id' => $order_info['order_id'],
                        'keywords' => 'USER_BALANCE_CHANGE_NOTICE',
                    ];
                    $message_model = new Message();
                    $message_model->sendMessage($message_data);
                }

                return $this->success();
            }catch (\Exception $e)
            {
                model('member_account')->rollback();
                return $this->error('', $e->getMessage());
            }





        }
    }

    /**
     * 添加会员账户，用于会员注册与添加
     * @param $member_data
     * @param $account_data
     */
    public function addMemberAccountInRegister($member_data, $account_data)
    {
        $list = [];
        if($account_data['point'] > 0)
        {
            $list[] = array (
                'site_id' => $member_data['site_id'],
                'member_id' => $member_data['member_id'],
                'account_type' => 'point',
                'account_data' => $account_data['point'],
                'from_type' => 'register',
                'type_name' => '注册',
                'type_tag' => "会员注册奖励积分:".$account_data['point'],
                'create_time' => time(),
                'username' => $member_data[ 'username' ],
                'mobile' => $member_data[ 'mobile' ],
                'email' => '',
                'remark' => '会员注册奖励积分',
                'related_id' => 0,
            );
        }

        if($account_data['balance'] > 0)
        {
            $list[] = array (
                'site_id' => $member_data['site_id'],
                'member_id' => $member_data['member_id'],
                'account_type' => 'balance',
                'account_data' => $account_data['balance'],
                'from_type' => 'register',
                'type_name' => '注册',
                'type_tag' => "会员注册奖励余额:".$account_data['balance'],
                'create_time' => time(),
                'username' => $member_data[ 'username' ],
                'mobile' => $member_data[ 'mobile' ],
                'email' => '',
                'remark' => '会员注册奖励余额',
                'related_id' => 0,
            );
        }

        if($account_data['growth'] > 0)
        {
            $list[] = array (
                'site_id' => $member_data['site_id'],
                'member_id' => $member_data['member_id'],
                'account_type' => 'growth',
                'account_data' => $account_data['growth'],
                'from_type' => 'register',
                'type_name' => '注册',
                'type_tag' => "会员注册奖励成长值:".$account_data['growth'],
                'create_time' => time(),
                'username' => $member_data[ 'username' ],
                'mobile' => $member_data[ 'mobile' ],
                'email' => '',
                'remark' => '会员注册奖励成长值',
                'related_id' => 0,
            );
        }
        model("member_account")->addList($list);
        $account = [
            'point' => $account_data['point'],
            'balance' => $account_data['balance'],
            'growth' => $account_data['growth']
        ];
        model("member")->update($account, [['member_id', '=', $member_data['member_id']]]);


    }

    /**
     * 获取账户分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array|\multitype
     */
    public function getMemberAccountPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc,id desc', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('member_account')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取账户列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array|\multitype
     */
    public function getMemberAccountList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('member_account')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取账户总额
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getMemberAccountSum($where = [], $field = '', $alias = 'a', $join = null)
    {
        $sum = model('member_account')->getSum($where, $field, $alias, $join);
        return $this->success($sum);
    }

    /**
     * api接口获取用户积分信息
     * @param $member_id
     * @return array
     */
    public function getMemberAccountPointInApi($member_id)
    {
        //当前可用积分
        $point = model("member")->getInfo([['member_id', '=', $member_id]], 'point');
        //累计获取积分
        $point_all = model("member_account")->getSum([['member_id', '=', $member_id], ['account_type', '=', 'point'], ['account_data', '>', 0]], 'account_data');
        //累计消费积分
        $point_use = $point_all - $point['point'];
        //今日获取积分
        $start_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $point_today = model("member_account")->getSum([['member_id', '=', $member_id], ['account_data', '>', 0],['account_type', '=', 'point'], [ 'create_time', 'between', [ $start_time, time() ] ] ], 'account_data');
        return $this->success([
            'point' => $point['point'],
            'point_all' => $point_all,
            'point_use' => $point_use,
            'point_today' => $point_today
        ]);

    }

    /**
     * 会员账户余额变动通知
     * @param $data
     */
    public function messageAccountChangeNotice($data)
    {
        //发送短信
        $sms_model = new Sms();

        $member_model = new MemberModel();
        $member_info_result = $member_model->getMemberInfo([ [ "member_id", "=", $data[ "member_id" ] ] ]);
        $member_info = $member_info_result[ "data" ];

        $remark = $data[ 'remark' ] == '' ? $data[ 'type_name' ] : $data[ 'remark' ];
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]/u', $remark, $matches);

        $username = empty($member_info[ "nickname" ]) ? $member_info[ "mobile" ] : $member_info[ "nickname" ];
        $username = !empty($username) ? $username : $member_info[ "username" ];
        $var_parse = array (
            'username' => str_replace(' ', '', $username),//会员名
            'balance' => $member_info[ 'balance' ],
            'balance_money' => $member_info[ 'balance_money' ],
            'change_money' => $data['account_data'],
        );
        $data[ "sms_account" ] = $member_info[ "mobile" ];//手机号
        $data[ "var_parse" ] = $var_parse;
        $sms_model->sendMessage($data);

        // 【弃用，暂无模板信息，无法使用，等待后续微信支持后开发】绑定微信公众号才发送
//        if (!empty($member_info) && !empty($member_info["wx_openid"])) {
//            $money = abs($data['account_data']);
//            $wechat_model = new WechatMessage();
//            $data["openid"] = $member_info["wx_openid"];
//            $data["template_data"] = [
//                'keyword1' => $data['type_name'],
//                'keyword2' => $data['account_data'] > 0 ? '+￥'.$money : '-￥'.$money,
//                'keyword3' => '￥'.($member_info['balance'] + $member_info['balance_money']),
//                'remark' => $data['remark'],
//            ];
//            $data["page"] = "";
//            $wechat_model->sendMessage($data);
//        }

        //发送订阅消息
        if (!empty($member_info) && !empty($member_info[ "weapp_openid" ])) {
            $weapp_model = new WeappMessage();
            $data[ "openid" ] = $member_info[ "weapp_openid" ];
            $data[ "template_data" ] = [
                'amount6' => [
                    'value' => $data[ 'account_data' ]
                ],
                'phrase7' => [
                    'value' => $data[ 'type_name' ]
                ],
                'time8' => [
                    'value' => time_to_date(time())
                ]
            ];
            $data[ "page" ] = "";
            $weapp_model->sendMessage($data);
        }

    }

    /**
     * 获取会员账户月份数据
     * @param $condition
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberAccountMonthData($condition)
    {
        $list = Db::name('member_account')
            ->where($condition)
            ->field("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m') as month")
            ->order('id desc')
            ->group('month')
            ->select()
            ->toArray();
        $data = array_column($list, 'month');
        return $this->success($data);
    }


    /**
     * 会员积分变动通知
     */
    public function messagePointChangeNotice($data)
    {

        //return $this->success();
        //发送短信
        $sms_model = new Sms();

        $member_model = new MemberModel();
        $member_info_result = $member_model->getMemberInfo([ [ "member_id", "=", $data[ "member_id" ] ] ]);
        $member_info = $member_info_result[ "data" ];

        $remark = $data[ 'remark' ] == '' ? $data[ 'type_name' ] : $data[ 'remark' ];
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]/u', $remark, $matches);

        $username = empty($member_info[ "nickname" ]) ? $member_info[ "mobile" ] : $member_info[ "nickname" ];
        $username = !empty($username) ? $username : $member_info[ "username" ];
        $var_parse = array (
            'username' => str_replace(' ', '', $username),//会员名
            'point' => $member_info[ 'point' ],
            'change_point' => $data['account_data'],
        );
        $data[ "sms_account" ] = $member_info[ "mobile" ];//手机号
        $data[ "var_parse" ] = $var_parse;

        //短信通知
        $sms_model->sendMessage($data);

        //公众号消息模版
        if (!empty($member_info) && !empty($member_info["wx_openid"])) {
            $money = abs($data['account_data']);
            $wechat_model = new WechatMessage();
            $data["openid"] = $member_info["wx_openid"];
            $data["template_data"] = [
                'keyword1' => $data['type_name'],
                'keyword2' => $data['account_data'] > 0 ? '+'.$money : '-'.$money,
                'keyword3' => $member_info['point'],
                'remark' => $data['remark'],
            ];
            $data["page"] = "";
            $wechat_model->sendMessage($data);
        }
    }
}