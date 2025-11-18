<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\cards\model\share;

use app\model\games\Games;
use app\model\share\WchatShareBase as BaseModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '刮刮乐分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_GAME_CARDS_DETAIL',
            'path' => ['/pages_promotion/game/cards'],
            'method_prefix' => 'gameDetail',
        ],
    ];

    /**
     * 刮刮乐分享数据
     * @param $param
     * @return array
     */
    protected function gameDetailShareData($param)
    {
        $site_id = $param['site_id'] ?? 0;
        //跳转路径
        parse_str(parse_url($param['url'])['query'] ?? '', $query);

        if(isset($query['id'])){
            $id = $query['id'];
            $game    = new Games();
            $game_info    = $game->getGamesInfo([['game_id', '=', $id], ['site_id', '=', $site_id], ['game_type', '=', 'cards']], 'game_id,game_name,points,start_time,end_time,status,remark,no_winning_desc,no_winning_img,is_show_winner,level_id,level_name,join_type,join_frequency')['data'];
            if(!empty($game_info)){
                $title = $game_info['game_name'];
                $desc = "参与刮刮乐，赢大奖";
                $link = $this->getShareLink($param);
                $image_url = img('addon/cards/icon.png');

                $data = [
                    'title' => $title,
                    'desc' => $desc,
                    'link' => $link,
                    'imgUrl' => $image_url,
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
