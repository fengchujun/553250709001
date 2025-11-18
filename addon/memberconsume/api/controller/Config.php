<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 * @author : niuteam
 * @date : 2022.8.8
 * @version : v5.0.0.1
 */

namespace addon\memberconsume\api\controller;

use app\api\controller\BaseApi;
use addon\memberconsume\model\Consume;


/**
 * 消费奖励
 * Class Config
 * @package addon\memberconsume\api\controller
 */
class Config extends BaseApi
{
    public function info()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $out_trade_no = $this->params['out_trade_no'] ?? '';
        $consume_model = new Consume();
        $reward_list = $consume_model->getConsumeRecordList([['out_trade_no', '=', $out_trade_no]]);

        $res = [
           'is_reward' => 0,
           'point_num' => 0,
           'growth_num' => 0,
           'coupon_list' => [],
        ];

        foreach($reward_list as $item){
            $res['is_reward'] = 1;
            switch($item['type']){
                case 'point':
                    $res['point_num'] += $item['value'];
                    break;
                case 'growth':
                    $res['growth_num'] += $item['value'];
                    break;
                case 'coupon':
                    $res['coupon_list'][] = [
                        'coupon_type_id' => $item['value'],
                        'coupon_content' => $item['remark'],
                    ];
                    break;
            }
        }

        return $this->response($this->success($res));
    }
}