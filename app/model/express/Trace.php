<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\express;


use app\model\BaseModel;

/**
 * 物流配送
 */
class Trace extends BaseModel
{

    /**
     * 物流跟踪信息
     * @param $code
     * @param $company_id
     * @param $site_id
     * @param $mobile
     * @return array|mixed|void
     */
    public function trace($code, $company_id, $site_id, $mobile)
    {
//        $result = array(
//            'success' => true,//成功与否
//            'reason' => '',//错误原因
//            'status' => '1',//物流状态:0-无轨迹,1-已揽收, 2-在途中 201-到达派件城市，3-签收,4-问题件
//            'status_name' => '已揽收',//物流状态名称:0-无轨迹,1-已揽收, 2-在途中 201-到达派件城市，3-签收,4-问题件,
//            'shipper_code' => 'SH',//快递公司编码
//            'logistic_code' => $code,//物流运单号
//            'list' => array(
//                [
//                    'datetime' => '2015-03-08 01:15:00',
//                    'remark' => '离开广州市 发往北京市（经转）',
//                ]
//            )
//        );
        $express_company_model = new ExpressCompanyTemplate();
        $company_info_result = $express_company_model->getExpressCompanyTemplateInfo([ [ 'company_id', '=', $company_id ] ]);
        if (empty($company_info_result[ 'data' ]))
            return $this->success([ 'success' => false, 'reason' => '物流公司信息不完整！' ]);

        $company_info_result[ 'data' ][ 'site_id' ] = $site_id;
        $result = $this->getTrace([ 'code' => $code, 'express_no_data' => $company_info_result[ 'data' ], 'mobile' => $mobile ]);

        if (empty($result)) {
            $data = [ 'success' => false, 'reason' => '抱歉，没有启用的物流方式' ];
            return $this->success($data);
        }
        if ($result[ 'code' ] < 0 || empty($result[ 'data' ])) {
            $data = [ 'success' => false, 'reason' => $result['message'] ?? '抱歉，暂无物流记录' ];
            return $this->success($data);
        }

        return $result;
    }

    public function getTrace($data)
    {
        $express_no_data = $data[ "express_no_data" ];

        $kd100_model = new Kd100();
        $kd100_config = $kd100_model->getKd100Config($express_no_data[ "site_id" ]);

        if ($kd100_config[ "data" ][ "is_use" ]) {
            $express_no = $express_no_data[ "express_no_kd100" ];
            return $kd100_model->trace($data[ "code" ], $express_no, $data[ 'mobile' ], $express_no_data[ "site_id" ]);
        }

        $kdbird_model = new Kdbird();
        $kdbird_config = $kdbird_model->getKdbirdConfig($express_no_data[ "site_id" ]);

        if ($kdbird_config[ "data" ][ "is_use" ]) {
            $express_no = $express_no_data[ "express_no" ];
            return $kdbird_model->trace($data[ "code" ], $express_no, $express_no_data[ "site_id" ], $data[ 'mobile' ]);
        }
    }
}