<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\live\event;

use addon\live\model\Room;

/**
 * 轮询更新直播间状态
 */
class LiveRoomStatus
{

    /**
     * 轮询更新直播间状态
     * @param $param
     */
    public function handle($param)
    {
        $room = new Room();
        $room->updateRoomStatus($param[ 'relate_id' ]);
    }
}