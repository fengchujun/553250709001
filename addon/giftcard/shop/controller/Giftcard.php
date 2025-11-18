<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\shop\controller;

use addon\giftcard\model\giftcard\Category as CategoryModel;
use addon\giftcard\model\giftcard\GiftCard as GiftCardModel;
use addon\giftcard\model\giftcard\Media as MediaModel;
use app\shop\controller\BaseShop;
use addon\giftcard\model\card\Card;
use addon\giftcard\model\giftcard\Category as GiftCardCategoryModel;
use think\App;

/**
 * 礼品卡控制器
 */
class Giftcard extends BaseShop
{
    public function __construct(App $app = null)
    {
        $this->replace = [
            'GIFTCARD_CSS' => __ROOT__ . '/addon/giftcard/shop/view/public/css',
            'GIFTCARD_JS' => __ROOT__ . '/addon/giftcard/shop/view/public/js',
            'GIFTCARD_IMG' => __ROOT__ . '/addon/giftcard/shop/view/public/img',
            'GIFTCARD_CSV' => __ROOT__ . '/addon/giftcard/shop/view/public/csv',
        ];
        parent::__construct($app);
    }

    /**
     * 兑换卡列表
     * @return array|mixed
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $status = input('status', 'all');
            $category_id = input('category_id', 0);
            $card_type = input('card_type', 'all');
            $condition = array (
                [ 'site_id', '=', $this->site_id ],
                [ 'is_delete', '=', 0 ]
            );
            if (!empty($search_text)) {
                $condition[] = [ 'card_name', 'like', '%' . $search_text . '%' ];
            }
            if (!empty($card_type) && $card_type != 'all') {
                $condition[] = [ 'card_type', '=', $card_type ];
            }
            if ($status != 'all') {
                $condition[] = [ 'status', '=', $status ];
            }
            if ($category_id > 0) {
                $condition[] = [ 'category_id', '=', $category_id ];
            }
            $giftcard_model = new GiftCardModel();
            $list = $giftcard_model->getGiftcardDetailPageListInAdmin($condition, $page, $page_size, 'giftcard_id desc')[ 'data' ];
            return $list;
        } else {
            return $this->fetch('giftcard/lists');
        }
    }

    /**
     * 添加礼品卡
     * @return array|mixed
     */
    public function add()
    {
        $card_type = input('card_type', 'virtual');
        if (request()->isJson()) {
            $goods_sku_list = input('goods_sku_list', '');
            $goods_sku_list = empty($goods_sku_list) ? [] : json_decode($goods_sku_list, true);
            $cdk_type_array = input()[ 'cdk_type' ] ?? [];
            $data = [
                'card_name' => input('card_name', ''),
                'card_count' => input('card_count', 0),
                'card_cover' => input('card_cover', ''),
                'media_ids' => input('media_ids', ''),
                'cdk_length' => input('cdk_length', 0),
                'cdk_type' => implode(',', $cdk_type_array),
                'card_prefix' => input('card_prefix', ''),
                'card_suffix' => input('card_suffix', ''),
                'card_right_type' => input('card_right_type', ''),
                'card_right_goods_type' => input('card_right_goods_type', ''),
                'card_right_goods_count' => input('card_right_goods_count', ''),
                'card_price' => input('card_price', 0),
                'balance' => input('balance', 0),
                'sort' => input('sort', 0),
                'validity_type' => input('validity_type', ''),
                'validity_time' => input('validity_time', 0),
                'validity_day' => input('validity_day', 0),
                'status' => input('status', 0),
                'category_id' => input('category_id', 0),
                'is_allow_transfer' => input('is_allow_transfer', 0),
                'site_id' => $this->site_id,
                'goods_sku_list' => $goods_sku_list,
                'desc' => input('desc', ''),
                'card_type' => input('card_type', ''),
                'instruction' => input('instruction', ''),
            ];
            $giftcard_model = new GiftCardModel();
            $result = $giftcard_model->addGiftCard($data);
            return $result;
        } else {
            $this->assign('category_list', ( new GiftCardCategoryModel() )->getList([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? []);
            $this->assign('card_right_type_list', ( new GiftCardModel )->card_right_type_list ?? []);
            $this->assign('card_type', $card_type);
            return $this->fetch('giftcard/add');
        }
    }

    /**
     * 编辑礼品卡活动
     */
    public function edit()
    {

        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        if (request()->isJson()) {
            $goods_sku_list = input('goods_sku_list', '');
            $goods_sku_list = empty($goods_sku_list) ? [] : json_decode($goods_sku_list, true);
            $cdk_type_array = input()[ 'cdk_type' ] ?? [];
            $data = [
                'card_name' => input('card_name', ''),
                'card_cover' => input('card_cover', ''),
                'media_ids' => input('media_ids', ''),
                'cdk_length' => input('cdk_length', 0),
                'cdk_type' => implode(',', $cdk_type_array),
                'card_prefix' => input('card_prefix', ''),
                'card_suffix' => input('card_suffix', ''),
                'card_right_type' => input('card_right_type', ''),
                'card_right_goods_type' => input('card_right_goods_type', ''),
                'card_right_goods_count' => input('card_right_goods_count', ''),
                'card_price' => input('card_price', 0),
                'balance' => input('balance', 0),
                'sort' => input('sort', 0),
                'validity_type' => input('validity_type', ''),
                'validity_time' => input('validity_time', 0),
                'validity_day' => input('validity_day', 0),
                'status' => input('status', 0),
                'category_id' => input('category_id', 0),
                'card_type' => input('card_type', ''),
                'is_allow_transfer' => input('is_allow_transfer', 0),
                'site_id' => $this->site_id,
                'goods_sku_list' => $goods_sku_list,
                'giftcard_id' => $giftcard_id,
                'desc' => input('desc', ''),
                'instruction' => input('instruction', ''),
            ];
            $result = $giftcard_model->editGiftCard($data);
            return $result;
        } else {
            // 分组列表
            $this->assign('category_list', ( new GiftCardCategoryModel() )->getList([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? []);
            // 详情
            $giftcard_info = $giftcard_model->getGiftcardDetail([ 'giftcard_id' => $giftcard_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
            if (empty($giftcard_info))
                $this->error('找不到礼品卡活动');
            $this->assign('giftcard_info', $giftcard_info);
            $this->assign('card_right_type_list', ( new GiftCardModel )->card_right_type_list ?? []);
            return $this->fetch('giftcard/edit');
        }
    }


    /**
     * 详情
     */
    public function detail()
    {
        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        $detail = $giftcard_model->getGiftcardDetail([ 'giftcard_id' => $giftcard_id, 'site_id' => $this->site_id ])[ 'data' ] ?? [];
        if (empty($detail)) {
            $this->error('找不到礼品卡活动');
        }

        $this->assign('category_list', ( new GiftCardCategoryModel() )->getList([ [ 'site_id', '=', $this->site_id ] ])[ 'data' ] ?? []);
        $this->assign('card_right_type_list', ( new GiftCardModel )->card_right_type_list ?? []);

        $this->assign('detail', $detail);
        $this->assign('status_list', ( new Card() )->getStatusList($detail[ 'card_type' ]));
        return $this->fetch('giftcard/detail');
    }

    /**
     * 删除
     * @return mixed
     */
    public function delete()
    {
        $giftcard_id = input('giftcard_id', 0);
        $giftcard_model = new GiftCardModel();
        $result = $giftcard_model->deleteGiftcard([ [ 'giftcard_id', '=', $giftcard_id ], [ 'site_id', '=', $this->site_id ] ]);
        return $result;
    }

    /**
     * 排序
     * @return array
     */
    public function sort()
    {
        $giftcard_id = input('giftcard_id', 0);
        $sort = input('sort', 0);
        $giftcard_model = new GiftCardModel();
        $result = $giftcard_model->modifyGiftcardSort($sort, [ [ 'giftcard_id', '=', $giftcard_id ], [ 'site_id', '=', $this->site_id ] ]);

        return $result;
    }

    public function getCategoryMedia()
    {
        if (request()->isJson()) {
            $category_id = input('category_id', '');
            if (empty($category_id)) return error('-1', '参数不能为空', '');
            $condition = [
                [ 'category_id', '=', $category_id ]
            ];
            $category_model = new CategoryModel();
            $category_info = $category_model->getInfo($condition, 'media_ids')[ 'data' ];
            $res = success();
            if (isset($category_info[ 'media_ids' ]) && !empty($category_info[ 'media_ids' ])) {
                $condition = [
                    [ 'media_id', 'in', $category_info[ 'media_ids' ] ]
                ];
                $media_model = new MediaModel();
                $res = $media_model->getList($condition);
                if (!empty($res[ 'data' ])) {
                    foreach ($res[ 'data' ] as $k => $v) {
                        $res[ 'data' ][ $k ][ 'media_path' ] = img($v[ 'media_path' ]);
                    }
                }
            }
            return $res;
        }
    }

    /**
     * 电子卡上/下架
     * @return array
     */
    public function isuse()
    {
        if (request()->isJson()) {
            $giftcard_id = input('id', '');
            $status = input('status', '');
            $giftcard_model = new GiftCardModel();
            $param = [
                'giftcard_id' => $giftcard_id,
                'status' => $status,
                'site_id' => $this->site_id
            ];
            if ($status == 0) {
                $result = $giftcard_model->giftcardOff($param);
            } elseif ($status == 1) {
                $result = $giftcard_model->giftcardOn($param);
            }

            return $result;
        }
    }

    /**
     * 实体卡批量激活/作废
     * @return array
     */
    public function editstatus()
    {
        if (request()->isJson()) {
            $giftcard_id = input('id', '');
            $status = input('status', '');
            $giftcard_model = new GiftCardModel();
            $param = [
                'giftcard_id' => $giftcard_id,
                'status' => $status,
                'site_id' => $this->site_id
            ];
            if ($status == 2) {
                $result = $giftcard_model->giftcardOff($param);
            } elseif ($status == 1) {
                $result = $giftcard_model->giftcardOn($param);
            }

            return $result;
        }
    }

}