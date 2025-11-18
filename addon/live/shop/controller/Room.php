<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\live\shop\controller;

use addon\live\model\Live;
use app\model\upload\Upload;
use app\shop\controller\BaseShop;
use addon\live\model\Room as RoomModel;
use addon\live\model\Goods as GoodsModel;
use think\App;

/**
 * 直播间
 */
class Room extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'LIVE_IMG' => __ROOT__ . '/addon/live/shop/view/public/img',
        ];
        parent::__construct($app);
    }

    /**
     * 直播间列表
     * @return array|mixed
     */
    public function index()
    {
        if (request()->isJson()) {
            $room = new RoomModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                'site_id' => $this->site_id
            ];
            $data = $room->getRoomPageList($condition, '*', 'roomid desc', $page, $page_size);
            return $data;
        } else {

            return $this->fetch("room/index");
        }
    }

    /**
     * 同步直播间
     * @return array
     */
    public function sync()
    {
        if (request()->isJson()) {
            $room = new RoomModel();
            $start = input('start', 0);
            $res = $room->syncLiveRoom($start, 20, $this->site_id);
            return $res;
        }
    }

    /**
     * 添加直播间
     */
    public function add()
    {
        if (request()->isJson()) {
            $room = new RoomModel();
            $data = [
                'name' => input('name', ''),
                'coverImg' => input('coverImg', ''),
                'startTime' => strtotime(input('startTime', '')),
                'endTime' => strtotime(input('endTime', '')),
                'anchorName' => input('anchorName', ''),
                'anchorWechat' => input('anchorWechat', ''),
                'shareImg' => input('shareImg', ''),
                'feedsImg' => input('feedsImg', ''),
                'type' => input('type', 0),
                'screenType' => 0,
                'closeLike' => input('closeLike', 1),
                'closeGoods' => input('closeGoods', 1),
                'closeComment' => input('closeComment', 1),
                'closeReplay' => input('closeReplay', 1),
                'closeKf' => input('closeKf', 1)
            ];
            $res = $room->createRoom($data, $this->site_id);
            return $res;
        }
        return $this->fetch("room/add");
    }

    /**
     * 添加图片素材
     */
    public function addImageMedia()
    {
        if (request()->isJson()) {
            $upload_model = new Upload($this->site_id, $this->app_module);
            $thumb_type = input("thumb", "");
            $name = input("name", "");
            $param = array (
                "thumb_type" => "",
                "name" => "file",
                "watermark" => 0,
                "cloud" => 0
            );
            $path = "common/images/" . date("Ymd") . '/';
            $result = $upload_model->setPath($path)->image($param);
            if ($result[ 'code' ] < 0) return $result;

            $live = new Live($this->site_id);
            $media_result = $live->addImageMedia($result[ 'data' ][ 'pic_path' ]);
            if ($media_result[ 'code' ] < 0) return $media_result;

            return success(0, '上传成功', [ 'pic_info' => $result[ 'data' ], 'media_info' => $media_result[ 'data' ] ]);
        }
    }

    /**
     * 运营
     */
    public function operate()
    {
        $room = new RoomModel();
        if (request()->isJson()) {
            $id = input('id', '');
            $anchor_img = input('anchor_img', '-1');
            $banner = input('banner', '-1');
            $data = [];
            if ($anchor_img != '-1') $data = [ 'anchor_img' => $anchor_img ];
            if ($banner != '-1') $data = [ 'banner' => $banner ];
            $res = $room->updateRoomInfo($data, [ [ 'site_id', '=', $this->site_id ], [ 'id', '=', $id ] ]);
            return $res;
        }
        $id = input('id', '');
        $room_info = $room->getRoomInfo([ [ 'site_id', '=', $this->site_id ], [ 'id', '=', $id ] ]);

        if (empty($room_info[ 'data' ])) $this->error('未获取到直播间信息');
        $this->assign('room_info', $room_info[ 'data' ]);
        return $this->fetch("room/operate");
    }

    /**
     * 查询商品
     */
    public function getGoodsPageList()
    {
        if (request()->isJson()) {
            $goods = new GoodsModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $sku_id = input('sku_id', '');
            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'status', '=', 2 ]
            ];
            if (!empty($sku_id)) $condition[] = [ 'sku_id', 'not in', explode(',', $sku_id) ];
            $data = $goods->getGoodsPageList($condition, '*', 'id desc', $page, $page_size);
            return $data;
        }
        $ids = input('ids', '');
        $this->assign('ids', $ids);
        return $this->fetch("room/goods_select");
    }

    /**
     * 添加商品到直播间
     * @return array
     */
    public function addGoods()
    {
        if (request()->isJson()) {
            $room = new RoomModel();
            $room_id = input('room_id', '');
            $data = input('data', '');
            $res = $room->addGoods($this->site_id, $room_id, $data);
            return $res;
        }
    }

    /**
     * 删除直播间
     * @return mixed
     */
    public function delete()
    {
        if (request()->isJson()) {
            $room = new RoomModel();
            $room_ids = input('room_ids', '');
            $res = $room->deleteRoom($this->site_id, $room_ids);
            return $res;
        }
    }
}