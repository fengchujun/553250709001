<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\api\controller;

use addon\fenxiao\model\FenxiaoGoods;
use addon\groupbuy\model\Groupbuy as GroupbuyModel;
use addon\groupbuy\model\Poster;
use addon\supermember\model\MemberCard as MemberCardModel;
use app\api\controller\BaseApi;
use app\model\goods\GoodsApi;
use app\model\member\Member as MemberModel;
use think\facade\Db;

/**
 * 团购商品
 */
class Goods extends BaseApi
{
    /**
     * 【PC端在用】基础信息
     */
    public function info()
    {
        $groupbuy_id = $this->params['groupbuy_id'] ?? 0;
        $sku_id = $this->params['sku_id'] ?? 0;
        if (empty($groupbuy_id)) {
            return $this->response($this->error('', 'REQUEST_GROUPBUY_ID'));
        }
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }

        $groupbuy_model = new GroupbuyModel();
        $condition = [
            ['sku.sku_id', '=', $sku_id],
            ['pg.groupbuy_id', '=', $groupbuy_id],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $info = $groupbuy_model->getGroupbuyGoodsDetail($condition);
        return $this->response($info);
    }

    /**
     * 团购商品详情信息
     */
    public function detail()
    {
        $groupbuy_id = $this->params['groupbuy_id'] ?? 0;
        if (empty($groupbuy_id)) {
            return $this->response($this->error('', 'REQUEST_GROUPBUY_ID'));
        }

        $groupbuy_model = new GroupbuyModel();
        $condition = [
            ['pg.groupbuy_id', '=', $groupbuy_id],
            ['pg.site_id', '=', $this->site_id],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $goods_sku_detail = $groupbuy_model->getGroupbuyGoodsDetail($condition)['data'];
        $this->checkToken();
        //用户已登录
        if ($this->member_id > 0) {
            $member_model = new MemberModel();
            $member_info = $member_model->getMemberInfo([['member_id', '=', $this->member_id], ['site_id', '=', $this->site_id]], 'username,is_fenxiao,province_id,city_id,district_id,community_id,address,full_address,longitude,latitude,member_code')['data'];
            if (!empty($member_info)) {
                // 分销佣金详情
                if (addon_is_exit('fenxiao')) {
                    $fenxiao_goods_model = new FenxiaoGoods();
                    $fenxiao_detail_res = $fenxiao_goods_model->getGoodsFenxiaoDetailInApi([
                        'goods_sku_detail' => $goods_sku_detail,
                        'member_info' => $member_info,
                    ], $this->member_id, $this->site_id, $goods_sku_detail['groupbuy_price']);
                    $goods_sku_detail['fenxiao_detail'] = $fenxiao_detail_res['goods_sku_detail']['fenxiao_detail'];
                }
            }
        }
        if (empty($goods_sku_detail)) return $this->response($this->error());
        $res['goods_sku_detail'] = $goods_sku_detail;
        // 处理公共数据
        $goods_api = new GoodsApi();
        $goods_api->handleGoodsDetailData($res['goods_sku_detail'], $this->member_id, $this->site_id);

        return $this->response($this->success($res));
    }

    /**
     * 查询商品SKU集合
     * @return false|string
     */
    public function goodsSku()
    {
        $goods_id = $this->params['goods_id'] ?? 0;
        $groupbuy_id = $this->params['groupbuy_id'] ?? 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($groupbuy_id)) {
            return $this->response($this->error('', 'REQUEST_GROUPBUY_ID'));
        }
        $groupbuy_model = new GroupbuyModel();
        $condition = [
            ['pg.groupbuy_id', '=', $groupbuy_id],
            ['pg.site_id', '=', $this->site_id],
            ['g.goods_id', '=', $goods_id],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $list = $groupbuy_model->getGroupbuyGoodsSkuList($condition, null);
        return $this->response($list);
    }

    public function page()
    {
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $goods_id_arr = $this->params['goods_id_arr'] ?? '';//goods_id数组

        $condition = [
            ['pg.status', '=', 2],// 状态（1未开始  2进行中  3已结束）
            ['g.goods_stock', '>', 0],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0],
            ['sku.site_id', '=', $this->site_id]
        ];
        //线上销售
        $condition[] = ['', 'exp', Db::raw("(g.sale_channel = 'all' OR g.sale_channel = 'online')")];

        if (!empty($goods_id_arr)) {
            $condition[] = ['sku.goods_id', 'in', $goods_id_arr];
        }

        $groupbuy_model = new GroupbuyModel();
        $list = $groupbuy_model->getGroupbuyGoodsPageList($condition, $page, $page_size, 'pg.groupbuy_id desc');

        return $this->response($list);
    }

    public function lists()
    {
        $num = $this->params['num'] ?? 0;
        $goods_id_arr = $this->params['goods_id_arr'] ?? '';

        $condition = [
            ['pg.status', '=', 2],// 状态（1未开始  2进行中  3已结束）
            ['g.goods_stock', '>', 0],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0],
            ['sku.site_id', '=', $this->site_id]
        ];

        if (!empty($goods_id_arr)) {
            $condition[] = ['sku.goods_id', 'in', $goods_id_arr];
        }

        $groupbuy_model = new GroupbuyModel();
        $list = $groupbuy_model->getGroupbuyGoodsList($condition, '', 'pg.groupbuy_id desc', $num);

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        $this->checkToken();

        $promotion_type = 'groupbuy';
        $qrcode_param = json_decode($this->params['qrcode_param'], true);
        $qrcode_param['source_member'] = $this->member_id;
        $qrcode_param['id'] = $qrcode_param['groupbuy_id'] ?? 0;
        unset($qrcode_param['groupbuy_id']);
        $poster = new Poster();
        $res = $poster->goods($this->params['app_type'], $this->params['page'], $qrcode_param, $promotion_type, $this->site_id);
        return $this->response($res);
    }
}