<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bargain\api\controller;

use app\api\controller\BaseApi;
use addon\bargain\model\Bargain as BargainModel;
use app\model\order\OrderCommon;

/**
 * 砍价
 */
class Bargain extends BaseApi
{

    /**
     * 获取我的砍价详情
     */
    public function info()
    {
        $token = $this->checkToken();

        $id = $this->params[ 'id' ] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $condition = [
            [ 'launch_id', '=', $id ],
            [ 'site_id', '=', $this->site_id ],
        ];
        $bargain = new BargainModel();
        $data = $bargain->getBargainLaunchDetail($condition, 'bargain_id,bargain_type,buy_type,curr_num,curr_price,floor_price,end_time,goods_id,headimg,launch_id,member_id,nickname,order_id,price,site_id,sku_id,sku_image,sku_name,start_time,status');
        if ($data[ 'code' ] == 0) {
            if ($token[ 'code' ] == 0) {
                $bargain_goods_info = $bargain->getBargainGoodsDetail([ [ 'pbg.sku_id', '=', $data[ 'data' ][ 'sku_id' ] ], [ 'pb.status', '=', '1' ] ], 'pbg.bargain_stock,pb.status,g.goods_content')[ 'data' ];
                $data[ 'data' ][ 'bargain_stock' ] = $bargain_goods_info[ 'bargain_stock' ];
                $data[ 'data' ][ 'bargain_status' ] = $bargain_goods_info[ 'status' ];
                $data[ 'data' ][ 'goods_content' ] = $bargain_goods_info[ 'goods_content' ];
                if ($data[ 'data' ][ 'member_id' ] == $this->member_id) {
                    $data[ 'data' ][ 'self' ] = 1;
                } else {
                    $data[ 'data' ][ 'self' ] = 0;
                    $record_info = $bargain->getBargainRecordInfo([ [ 'launch_id', '=', $id ], [ 'member_id', '=', $this->member_id ] ], 'id');
                    $data[ 'data' ][ 'cut' ] = empty($record_info[ 'data' ]) ? 0 : 1;
                }
            } else {
                $data[ 'data' ][ 'self' ] = 0;
                $data[ 'data' ][ 'cut' ] = 0;
            }
        }
        return $this->response($data);
    }

    /**
     * 获取我的砍价分页列表
     */
    public function launchPage()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $status = $this->params[ 'status' ] ?? 'all';

        $condition = [
            [ 'pbl.site_id', '=', $this->site_id ],
            [ 'pbl.member_id', '=', $this->member_id ]
        ];
        if ($status != 'all') {
            $condition[] = [ 'pbl.status', '=', $status ];
        }
        $bargain = new BargainModel();
        $field = 'pbl.*, pb.status as bargain_status';
        $data = $bargain->getBargainLaunchPageList($condition, $field, 'pbl.launch_id desc', $page, $page_size, 'pbl', [
            [ 'promotion_bargain pb', 'pbl.bargain_id = pb.bargain_id', 'inner' ]
        ]);
        return $this->response($data);
    }

    /**
     * 发起砍价
     * @return false|string
     */
    public function launch()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params[ 'id' ] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->launch($id, $this->member_id, $this->site_id, $this->store_id);
        return $this->response($res);
    }

    /**
     * 砍价
     */
    public function bargain()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = $this->params[ 'id' ] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->bargain($id, $this->member_id, $this->site_id);

        return $this->response($res);
    }

    /**
     * 获取砍价记录
     * @return false|string
     */
    public function record()
    {
        $id = $this->params[ 'id' ] ?? 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;

        $condition = [
            [ 'launch_id', '=', $id ]
        ];

        $bargain = new BargainModel();
        $data = $bargain->getBargainRecordPageList($condition, '*', 'id desc', $page, $page_size);

        return $this->response($data);
    }

    /**
     * 砍价详情
     * @return false|string|void
     */
    public function detail()
    {
        $bargain_id = $this->params[ 'bargain_id' ] ?? 0;
        $launch_id = $this->params[ 'launch_id' ] ?? 0;
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $token = $this->checkToken();
        $bargain = new BargainModel();
        $condition = [
            [ 'pb.bargain_id', '=', $bargain_id ],
            [ 'pbg.site_id', '=', $this->site_id ],
//            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $bargain->getBargainGoodsDetail($condition, '')[ 'data' ];

        $bargain->bargainBrowseInc([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $this->site_id ] ]);

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (empty($goods_sku_detail)) return $this->response($this->error($res));

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $bargain->getGoodsSpecFormat($bargain_id, $this->site_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        $launch_info = [];
        if ($launch_id) {
            $condition = [
                [ 'launch_id', '=', $launch_id ],
                [ 'site_id', '=', $this->site_id ],
            ];
            $launch_info = $bargain->getBargainLaunchDetail($condition, 'bargain_id,bargain_type,buy_type,curr_num,curr_price,floor_price,end_time,goods_id,headimg,launch_id,member_id,nickname,order_id,price,site_id,sku_id,sku_image,sku_name,start_time,status')[ 'data' ] ?? [];
        } else {
            if ($token[ 'code' ] == 0) {
                $launch_info = $bargain->getBargainLaunchDetail([
                        [ 'bargain_id', '=', $goods_sku_detail[ 'bargain_id' ] ],
                        [ 'sku_id', '=', $goods_sku_detail[ 'sku_id' ] ],
                        [ 'member_id', '=', $this->member_id ],
                        [ 'status', '=', 0 ]
                    ], 'bargain_id,bargain_type,buy_type,curr_num,curr_price,floor_price,end_time,goods_id,headimg,launch_id,member_id,nickname,order_id,price,site_id,sku_id,sku_image,sku_name,start_time,status')[ 'data' ] ?? [];
            }
        }

        if ($launch_info) {
            $launch_info[ 'pay_status' ] = 0;
            if ($launch_info[ 'order_id' ]) {
                $order = new OrderCommon();
                $order_info = $order->getOrderInfo([ [ 'order_id', '=', $launch_info[ 'order_id' ] ], [ 'site_id', '=', $this->site_id ] ], 'order_status, pay_status')[ 'data' ] ?? [];
                $launch_info[ 'pay_status' ] = $order_info[ 'pay_status' ] ?? 0;
                $launch_info[ 'order_status' ] = $order_info[ 'order_status' ];
//                if($launch_info['order_status'] == OrderCommon::ORDER_CLOSE) $launch_info['order_id'] = 0;
            }
        }

        if (!empty($launch_info)) {
            if ($token[ 'code' ] == 0) {
                if ($launch_info[ 'member_id' ] == $this->member_id) {
                    $launch_info[ 'self' ] = 1;
                    $record_info = $bargain->getBargainRecordInfo([ [ 'launch_id', '=', $launch_info[ 'launch_id' ] ], [ 'member_id', '=', $this->member_id ] ], 'money');
                    $launch_info[ 'my_bargain_money' ] = $record_info[ 'data' ][ 'money' ] ?? 0;
                } else {
                    $launch_info[ 'self' ] = 0;
                    $record_info = $bargain->getBargainRecordInfo([ [ 'launch_id', '=', $launch_info[ 'launch_id' ] ], [ 'member_id', '=', $this->member_id ] ], 'id');
                    $launch_info[ 'cut' ] = empty($record_info[ 'data' ]) ? 0 : 1;
                }
            } else {
                $launch_info[ 'self' ] = 0;
                $launch_info[ 'cut' ] = 0;
            }
        }

        $res[ 'launch_info' ] = $launch_info;

        //已砍成功的人
        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'bargain_id', '=', $bargain_id ],
            [ 'status', '=', 1 ]
        ];
        $launch_list = $bargain->getBargainLaunchList($condition, 'status,curr_price,nickname,headimg,end_time', 'launch_id desc', '', '', '', 20);
        $res[ 'launch_list' ] = $launch_list;

        return $this->response($this->success($res));
    }

    public function browse()
    {
        $token = $this->checkToken();
        $bargain_id = $this->params[ 'bargain_id' ] ?? 0;
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->bargainBrowseInc([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $this->site_id ] ]);
        return $this->response($res);
    }

    public function share()
    {
        $token = $this->checkToken();
        $bargain_id = $this->params[ 'bargain_id' ] ?? 0;
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->bargainShareInc([ [ 'bargain_id', '=', $bargain_id ], [ 'site_id', '=', $this->site_id ] ]);
        return $this->response($res);
    }

}