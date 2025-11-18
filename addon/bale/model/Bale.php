<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\bale\model;

use app\model\BaseModel;
use app\model\system\Cron;

/**
 * 微信小程序配置
 */
class Bale extends BaseModel
{
    /**
     * 添加一口价活动
     * @param $data
     * @param $sku_ids
     * @return array
     */
    public function addBale($param)
    {
        if (empty($param[ 'sku_ids' ])) return $this->error([], '请选择参与活动的商品');

        $sku_id_array = explode(',', $param[ 'sku_ids' ]);
        foreach ($sku_id_array as $k => $v) {

            $sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $v ] ], 'is_virtual');
            if ($sku_info['is_virtual'] == 1) {
                return $this->error([], '不能包含虚拟商品');
            }
        }

        $data = [
            'site_id' => $param[ 'site_id' ],
            'name' => $param[ 'name' ],
            'num' => $param[ 'num' ],
            'price' => $param[ 'price' ],
            'goods_ids' => ',' . $param[ 'goods_ids' ] . ',',
            'sku_ids' => ',' . $param[ 'sku_ids' ] . ',',
            'start_time' => $param[ 'start_time' ],
            'end_time' => $param[ 'end_time' ],
            'create_time' => time(),
            'status' => 0,
            'shipping_fee_type' => $param[ 'shipping_fee_type' ]
        ];
        if ($param[ 'start_time' ] <= time()) {
            $data[ 'status' ] = 1;
        }
        $bale_id = model('promotion_bale')->add($data);

        $cron = new Cron();
        if ($data[ 'status' ] == 1) {
            $cron->addCron(1, 0, '打包一口价活动关闭', 'CloseBale', $data[ 'end_time' ], $bale_id);
        } else {
            $cron->addCron(1, 0, '打包一口价活动开启', 'OpenBale', $data[ 'start_time' ], $bale_id);
            $cron->addCron(1, 0, '打包一口价活动关闭', 'CloseBale', $data[ 'end_time' ], $bale_id);
        }
        return $this->success($bale_id);
    }

    /**
     * 编辑一口价活动
     * @param $param
     */
    public function editBale($param)
    {
        if (empty($param[ 'sku_ids' ])) return $this->error([], '请选择参与活动的商品');

        $sku_id_array = explode(',', $param[ 'sku_ids' ]);
        foreach ($sku_id_array as $k => $v) {

            $sku_info = model('goods_sku')->getInfo([ [ 'sku_id', '=', $v ] ], 'is_virtual');
            if ($sku_info['is_virtual'] == 1) {
                return $this->error([], '不能包含虚拟商品');
            }
        }

        $data = [
            'site_id' => $param[ 'site_id' ],
            'name' => $param[ 'name' ],
            'num' => $param[ 'num' ],
            'price' => $param[ 'price' ],
            'goods_ids' => ',' . $param[ 'goods_ids' ] . ',',
            'sku_ids' => ',' . $param[ 'sku_ids' ] . ',',
            'start_time' => $param[ 'start_time' ],
            'end_time' => $param[ 'end_time' ],
            'shipping_fee_type' => $param[ 'shipping_fee_type' ]
        ];
        if ($param[ 'start_time' ] < time()) {
            $data[ 'status' ] = 1;
        } else {
            $data[ 'status' ] = 0;
        }
        $res = model('promotion_bale')->update($data, [ [ 'bale_id', '=', $param[ 'bale_id' ] ], [ 'site_id', '=', $param[ 'site_id' ] ] ]);

        $cron = new Cron();
        if ($data[ 'status' ] == 1) {
            $cron->deleteCron([ [ 'event', '=', 'OpenBale' ], [ 'relate_id', '=', $param[ 'bale_id' ] ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseBale' ], [ 'relate_id', '=', $param[ 'bale_id' ] ] ]);

            $cron->addCron(1, 0, '打包一口价活动关闭', 'CloseBale', $data[ 'end_time' ], $param[ 'bale_id' ]);
        } else {
            $cron->deleteCron([ [ 'event', '=', 'OpenBale' ], [ 'relate_id', '=', $param[ 'bale_id' ] ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseBale' ], [ 'relate_id', '=', $param[ 'bale_id' ] ] ]);

            $cron->addCron(1, 0, '打包一口价活动开启', 'OpenBale', $data[ 'start_time' ], $param[ 'bale_id' ]);
            $cron->addCron(1, 0, '打包一口价活动关闭', 'CloseBale', $data[ 'end_time' ], $param[ 'bale_id' ]);
        }
        return $this->success($res);
    }

    /**
     * 删除活动
     * @param $bale_id
     * @param $site_id
     * @return array
     */
    public function deleteBale($bale_id, $site_id)
    {
        $info = model('promotion_bale')->getInfo([ [ 'bale_id', '=', $bale_id ], [ 'site_id', '=', $site_id ] ], 'status');
        if (empty($info)) {
            return $this->success();
        }
        if ($info[ 'status' ] != 2) {
            return $this->error('', '请先关闭活动后，在进行删除');
        }

        $res = model('promotion_bale')->delete([ [ 'bale_id', '=', $bale_id ], [ 'site_id', '=', $site_id ] ]);
        return $this->success($res);
    }

    /**
     * 开启活动
     * @param $bale_id
     * @return array
     */
    public function cronOpenBale($bale_id)
    {
        $info = model('promotion_bale')->getInfo([ [ 'bale_id', '=', $bale_id ] ], 'status');
        if (empty($info)) {
            return $this->error('', '活动不存在');
        }
        if ($info[ 'status' ] == 1) {
            return $this->success();
        }

        $res = model('promotion_bale')->update([ 'status' => 1 ], [ [ 'bale_id', '=', $bale_id ] ]);
        return $this->success($res);
    }

    /**
     * 关闭活动
     * @param $bale_id
     * @return array
     */
    public function cronCloseBale($bale_id)
    {
        $info = model('promotion_bale')->getInfo([ [ 'bale_id', '=', $bale_id ] ], 'status');
        if (empty($info)) {
            return $this->error('', '活动不存在');
        }
        if ($info[ 'status' ] == 2) {
            return $this->success();
        }

        $res = model('promotion_bale')->update([ 'status' => 2 ], [ [ 'bale_id', '=', $bale_id ] ]);
        return $this->success($res);
    }

    /**
     * 获取一口价活动信息
     * @param array $where
     * @param bool $field
     * @param string $alias
     * @param null $join
     * @return array
     */
    public function getBaleInfo($where = [], $field = true, $alias = 'a', $join = null)
    {
        $info = model('promotion_bale')->getInfo($where, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取一口价活动详情
     * @param $id
     * @param $site_id
     * @return array
     */
    public function getBaleDetail($id, $site_id)
    {
        $info = model('promotion_bale')->getInfo([ [ 'bale_id', '=', $id ], [ 'site_id', '=', $site_id ] ], '*');
        if (!empty($info)) {

            $alias = 'gs';
            $condition = [
                [ 'gs.sku_id', 'in', explode(',', substr($info[ 'sku_ids' ], 1, -1)) ],
                [ 'gs.site_id', '=', $site_id ],
            ];

            $join = [
                [ 'goods g', 'gs.goods_id = g.goods_id', 'inner' ]
            ];

            $field = 'gs.goods_id,gs.sku_id,gs.sku_name,gs.price,gs.discount_price,gs.stock
            ,gs.sku_image,gs.goods_name,g.goods_spec_format,g.goods_state
            ,gs.promotion_type,g.goods_image,gs.spec_name
            ,gs.max_buy,gs.min_buy,gs.unit,gs.is_limit,gs.limit_type
            ,gs.goods_spec_format as goods_sku_spec_format,gs.sku_spec_format';
            $goods_list = model('goods_sku')->getList($condition, $field, 'g.sort,g.create_time desc', $alias, $join);

            foreach ($goods_list as $k => $v) {

                $goods_list[ $k ][ 'stock' ] = numberFormat($goods_list[ $k ][ 'stock' ]);

                $field = 'gs.goods_id,gs.sku_id,g.goods_image,gs.sku_name,gs.sku_spec_format,gs.price,gs.discount_price,gs.promotion_type,gs.stock,gs.sku_image,gs.sku_images,gs.goods_spec_format,gs.unit';
                $join = [
                    [ 'goods g', 'g.goods_id = gs.goods_id', 'inner' ],
                ];
                $goods_list[ $k ][ 'sku_list' ] = model('goods_sku')->getList([ [ 'gs.goods_id', '=', $v[ 'goods_id' ] ], [ 'gs.site_id', '=', $site_id ], [ 'gs.is_delete', '=', 0 ] ], $field, 'gs.sku_id asc', 'gs', $join);
                if (!empty($goods_list[ $k ][ 'sku_list' ])) {
                    foreach ($goods_list[ $k ][ 'sku_list' ] as $ck => $cv) {
                        $goods_list[ $k ][ 'sku_list' ][ $ck ][ 'stock' ] = numberFormat($goods_list[ $k ][ 'sku_list' ][ $ck ][ 'stock' ]);
                    }
                }

            }

            $info[ 'sku_list' ] = $goods_list;
            $info[ 'sku_list_count' ] = count($goods_list);
        }
        return $this->success($info);
    }

    /**
     * 获取一口价活动详情
     * @param $id
     * @param $site_id
     * @return array
     */
    public function getEditBaleData($id, $site_id)
    {
        $info = model('promotion_bale')->getInfo([ [ 'bale_id', '=', $id ], [ 'site_id', '=', $site_id ] ], '*');
        if (!empty($info)) {

            $alias = 'gs';
            $condition = [
                [ 'gs.sku_id', 'in', explode(',', substr($info[ 'sku_ids' ], 1, -1)) ],
                [ 'gs.site_id', '=', $site_id ],
            ];

            $join = [
                [ 'goods g', 'gs.goods_id = g.goods_id', 'inner' ]
            ];

            $field = 'gs.goods_id,gs.sku_id,gs.sku_name,gs.price,gs.discount_price,gs.stock
            ,gs.sku_image,gs.goods_name,g.goods_spec_format,g.goods_state
            ,gs.promotion_type,g.goods_image
            ,gs.max_buy,gs.min_buy,gs.unit,gs.is_limit,gs.limit_type
            ,gs.goods_spec_format as goods_sku_spec_format,gs.sku_spec_format';
            $sku_list = model('goods_sku')->getList($condition, $field, 'g.sort,g.create_time desc', $alias, $join);

            foreach ($sku_list as $k => $v) {

                $sku_list[ $k ][ 'stock' ] = numberFormat($sku_list[ $k ][ 'stock' ]);

                $field = 'gs.goods_id,gs.sku_id,g.goods_image,gs.sku_name,gs.sku_spec_format,gs.price,gs.discount_price,gs.promotion_type,gs.stock,gs.sku_image,gs.sku_images,gs.goods_spec_format,gs.unit';
                $join = [
                    [ 'goods g', 'g.goods_id = gs.goods_id', 'inner' ],
                ];
                $sku_list[ $k ][ 'sku_list' ] = model('goods_sku')->getList([ [ 'gs.goods_id', '=', $v[ 'goods_id' ] ], [ 'gs.site_id', '=', $site_id ], [ 'gs.is_delete', '=', 0 ] ], $field, 'gs.sku_id asc', 'gs', $join);
                if (!empty($sku_list[ $k ][ 'sku_list' ])) {
                    foreach ($sku_list[ $k ][ 'sku_list' ] as $ck => $cv) {
                        $sku_list[ $k ][ 'sku_list' ][ $ck ][ 'stock' ] = numberFormat($sku_list[ $k ][ 'sku_list' ][ $ck ][ 'stock' ]);
                    }
                }

            }

            $info[ 'sku_list' ] = $sku_list;
            $info[ 'sku_list_count' ] = count($sku_list);
        }
        return $this->success($info);
    }

    /**
     * 获取分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getBalePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('promotion_bale')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 关闭活动
     * @param $bale_id
     * @return array
     */
    public function closeBale($bale_id)
    {
        $res = model('promotion_bale')->update([ 'status' => 2 ], [ [ 'bale_id', '=', $bale_id ] ]);
        $cron = new Cron();
        if ($res == 1) {
            $cron->deleteCron([ [ 'event', '=', 'CloseBale' ], [ 'relate_id', '=', $bale_id ] ]);
        }
        return $this->success($res);
    }

    /**
     * 活动推广链接
     * @param $page
     * @param $qrcode_param
     * @param string $promotion_type
     * @param $site_id
     * @return array
     */
    public function urlQrcode($page, $qrcode_param, $promotion_type, $app_type, $site_id)
    {
        $params = [
            'site_id' => $site_id,
            'data' => $qrcode_param,
            'page' => $page,
            'promotion_type' => $promotion_type,
            'h5_path' => $page . '?id=' . $qrcode_param[ 'id' ],
            'app_type' => $app_type,
            'qrcode_path' => 'upload/qrcode/bale',
            'qrcode_name' => 'bale_qrcode_' . $promotion_type . '_' . $qrcode_param[ 'id' ] . '_' . $site_id
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }
}
