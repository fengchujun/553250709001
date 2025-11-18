<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\divideticket\model\share;

use addon\divideticket\model\Divideticket as DivideticketModel;
use addon\divideticket\model\DivideticketFriendsGroup as DivideticketFriendsGroupModel;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '好友瓜分卷列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_DIVIDE_TICKET_LIST',
            'path' => [ '/pages_promotion/divideticket/list' ],
            'method_prefix' => 'divideTicketList',
        ],
        [
            'title' => '好友瓜分卷分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_DIVIDE_TICKET_DETAIL',
            'path' => [ '/pages_promotion/divideticket/index' ],
            'method_prefix' => 'divideTicketDetail',
        ],
    ];

    protected $sort = 20;

    /**
     * 好友瓜分卷分享数据
     * @param $param
     * @return array
     */
    protected function divideTicketListShareData($param)
    {
        //跳转路径
        $link = $this->getShareLink($param);
        $config_data = $this->divideTicketListShareConfig($param)[ 'value' ];

        $data = [
            'link' => $link,
            'desc' => $config_data[ 'desc' ],
            'imgUrl' => $config_data[ 'imgUrl' ],
            'title' => $config_data[ 'title' ]
        ];
        return [
            'permission' => [
                'hideOptionMenu' => false,
                'hideMenuItems' => [],
            ],
            'data' => $data,//分享内容
        ];
    }

    /**
     * 好友瓜分卷列表分享配置
     * @param $param
     * @return array
     */
    public function divideTicketListShareConfig($param)
    {
        $site_id = $param[ 'site_id' ];
        $config = $param[ 'config' ];

        $config_model = new ConfigModel();
        $data = $config_model->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', $config[ 'config_key' ] ] ])[ 'data' ];
        if (empty($data[ 'value' ])) {
            $data[ 'value' ] = [
                'title' => "好友瓜分卷列表",
                'desc' => "喊好友\n一起来瓜分",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/divideticket/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 好友瓜分卷分享数据
     * @param $param
     * @return array
     */
    protected function divideTicketDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ];
        $member_id = $param[ 'member_id' ];
        //链接参数
        parse_str(parse_url($param[ 'url' ])[ 'query' ] ?? '', $query);
        if (isset($query[ 'coupon_id' ])) {
            $coupon_id = $query[ 'coupon_id' ];
            $divideticket_model = new DivideticketModel();
            $condition = [
                [ 'coupon_id', '=', $coupon_id ],
                [ 'site_id', '=', $site_id ],
            ];
            $divide_ticket_info = $divideticket_model->getDivideticketInfo($condition)[ 'data' ];
            if (!empty($divide_ticket_info)) {
                $link = $this->getShareLink($param);
                if (!empty($member_id)) {
                    $group_model = new DivideticketFriendsGroupModel();
                    $group_info = $group_model->getDivideticketFriendsGroupInfo([
                        [ 'promotion_id', '=', $coupon_id ],
                        [ '', 'exp', \think\facade\Db::raw("FIND_IN_SET({$member_id}, group_member_ids)") ],
                    ], 'group_id')[ 'data' ];
                    if (!empty($group_info)) {
                        $group_id = $group_info[ 'group_id' ];
                        $page_path = explode('?', $param[ 'url' ])[ 0 ];
                        $link = "{$page_path}?coupon_id={$coupon_id}&group_id={$group_id}&inviter_id={$member_id}";
                    }
                }

                $imgUrl = $divide_ticket_info[ 'image' ];
                if (empty($imgUrl)) $imgUrl = $this->getDefaultShareIcon();
                $data = [
                    'link' => $link,
                    'desc' => "仅差一人，即可瓜分{$divide_ticket_info['money']}元优惠劵",
                    'imgUrl' => $imgUrl,
                    'title' => $divide_ticket_info[ 'name' ],
                ];
                return [
                    'permission' => [
                        'hideOptionMenu' => false,
                        'hideMenuItems' => [],
                    ],
                    'data' => $data,//分享内容
                ];
            }
        }
    }
}
