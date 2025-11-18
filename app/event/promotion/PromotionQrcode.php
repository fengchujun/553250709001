<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event\promotion;


use app\model\web\Config as ConfigModel;

/**
 * 营销活动二维码
 */
class PromotionQrcode
{

    /**
     * 活动展示
     * @param $params
     * @return array
     */
    public function handle($params)
    {
        $solitaire = [];

        $qrcode_all = event('Qrcode', [
            'site_id' => $params[ 'site_id' ],
            'app_type' => $params[ 'app_type' ] ?? 'all',
            'type' => 'get',
            'data' => $params[ 'data' ],
            'page' => $params[ 'page' ],
            'qrcode_path' => $params[ 'qrcode_path' ],
            'qrcode_name' => $params[ 'qrcode_name' ],
        ]);

        if (!empty($qrcode_all)) {
            foreach ($qrcode_all as $item) {
                if ($item[ 'code' ] == 0) $solitaire[ $item[ 'data' ][ 'type' ] ] = $item[ 'data' ];
            }
        }

        if (addon_is_exit('pc') == 1 && !empty($params[ 'pc_data' ]) && !empty($params[ 'pc_page' ])) {
            $pc_qrcode = event('Qrcode', [
                'site_id' => $params[ 'site_id' ],
                'app_type' => 'pc',
                'type' => 'create',
                'data' => $params[ 'pc_data' ],
                'page' => $params[ 'pc_page' ],
                'qrcode_path' => $params[ 'qrcode_path' ],
                'qrcode_name' => 'pc_' . $params[ 'qrcode_name' ],
            ], true);
            if ($pc_qrcode[ 'code' ] >= 0) {
                $solitaire[ 'pc' ][ 'path' ] = $pc_qrcode[ 'data' ][ 'path' ];
                $config_model = new ConfigModel();
                $domain_name_pc = $config_model->getPcDomainName()[ 'data' ][ 'value' ][ 'domain_name_pc' ];
                $solitaire[ 'pc' ][ 'url' ] = $domain_name_pc . $params[ 'pc_path' ];
            }
        }

        return $solitaire;
    }
}