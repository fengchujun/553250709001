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

use addon\giftcard\model\card\CardImport as CardImportModel;
use addon\giftcard\model\card\RealCard;

/**
 * 礼品卡批次控制器
 */
class Cardimport extends Giftcard
{

    /**
     * 批次列表
     * @return array|mixed
     */
    public function lists()
    {
        $giftcard_id = input('giftcard_id', 0);
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $condition = array (
                [ 'site_id', '=', $this->site_id ],
                [ 'giftcard_id', '=', $giftcard_id ],
            );

            if (!empty($search_text)) {
                $condition[] = [ 'name', 'like', '%' . $search_text . '%' ];
            }
            $card_import_model = new CardImportModel();
            $list = $card_import_model->getCardImportPageList($condition, $page, $page_size, 'create_time desc')[ 'data' ];
            foreach ($list[ 'list' ] as $k => $v) {
                $v[ 'type_name' ] = $card_import_model->create_type_list[ $v[ 'type' ] ];
                $list[ 'list' ][ $k ] = $card_import_model->tran($v);

            }
            return $card_import_model->success($list);
        } else {
            $this->assign('giftcard_id', $giftcard_id);
            return $this->fetch('cardimport/lists');
        }
    }

    /**
     * 删除
     * @return array|mixed|void
     */
    public function delete()
    {
        if (request()->isJson()) {
            $import_id = input('import_id', 0);
            $condition = array (
                'site_id' => $this->site_id,
                'import_id' => $import_id,
            );
            $card_import_model = new CardImportModel();
            $res = $card_import_model->delete($condition);
            return $res;
        }
    }

    /**
     * 作废
     * @param $params
     */
    public function invalid()
    {
        if (request()->isJson()) {
            $import_id = input('import_id', 0);
            $params = array (
                'site_id' => $this->site_id,
                'import_id' => $import_id,
                'operator_data' => $this->user_info
            );
            $card_import_model = new CardImportModel();
            $res = $card_import_model->invalid($params);
            return $res;
        }
    }

    /**
     * 导出卡密和卡编号
     */
    public function export()
    {
        $import_id = input('import_id', 0);
        $condition = array (
            [ 'site_id', '=', $this->site_id ],
            [ 'card_import_id', '=', $import_id ],
        );
        $card_import_model = new CardImportModel();
        $card_import_model->export($condition);
    }


    /**
     * 创建导入记录
     */
    public function create()
    {
        $giftcard_id = input('giftcard_id', 0);
        $type = input('type', 0);
        $num = input('num', 0);
        $card_cdk = input('card_cdk', '');
        $card_import_model = new CardImportModel();
        $result = $card_import_model->create([
            'site_id' => $this->site_id,
            'giftcard_id' => $giftcard_id,
            'type' => $type,
            'num' => $num,
            'card_cdk' => $card_cdk,
            'operator_data' => $this->user_info
        ]);
        if ($result[ 'code' ] >= 0) {
//            http(addon_url('giftcard://shop/cardimportlog/cdkLog', [ 'import_id' => $result[ 'data' ] ]), 1);
        }
        return $result;
    }

    /**
     * 导入记录
     */
    public function getCardImportInfo()
    {
        $import_id = input('import_id', 0);
        $card_import_model = new CardImportModel();
        $info = $card_import_model->getCardImportInfo([ [ 'site_id', '=', $this->site_id ], [ 'import_id', '=', $import_id ] ]);
        return $info;
    }

    /**
     * 录入卡项
     */
    public function cdkLog()
    {
        set_time_limit(0);
        $import_id = input('import_id', 0);
        $real_card_model = new RealCard();
        $result = $real_card_model->cdkLog([
            'site_id' => $this->site_id,
            'import_id' => $import_id,
            'operator_data' => $this->user_info
        ]);
        return $result;
    }
}