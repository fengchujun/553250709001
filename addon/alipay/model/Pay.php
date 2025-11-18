<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\alipay\model;

use addon\alipay\data\sdk\AopClient;
use addon\alipay\data\sdk\request\AlipayFundTransToaccountTransferRequest;
use addon\alipay\data\sdk\request\AlipayTradeAppPayRequest;
use addon\alipay\data\sdk\request\AlipayTradeCloseRequest;
use addon\alipay\data\sdk\request\AlipayTradeCreateRequest;
use addon\alipay\data\sdk\request\AlipayTradePagePayRequest;
use addon\alipay\data\sdk\request\AlipayTradeRefundRequest;
use addon\alipay\data\sdk\request\AlipayTradeWapPayRequest;
use addon\alipay\data\sdk\request\AlipayTradePrecreateRequest;
use addon\alipay\data\sdk\request\AlipayTradePayRequest;
use addon\alipay\data\sdk\request\AlipayTradeQueryRequest;
use app\model\BaseModel;
use app\model\system\Cron;
use app\model\system\Pay as PayCommon;
use addon\alipay\data\sdk\request\AlipayFundTransUniTransferRequest;
use addon\alipay\data\sdk\AopCertClient;
use app\model\system\Pay as PayModel;
use addon\aliapp\model\Config as AliappConfig;
use think\facade\Log;

/**
 * 支付宝支付配置
 */
class Pay extends BaseModel
{

    public $aop;

    private $is_aliapp = 0;

    /**
     *
     * @param $site_id
     * @param int $is_aliapp 是否是小程序
     */
    function __construct($site_id, $is_aliapp = 0)
    {
        $this->is_aliapp = $is_aliapp;

        try {
            // 获取支付宝支付参数(统一支付到平台账户)
            if ($is_aliapp) {
                $config_info = ( new AliappConfig() )->getAliappConfig($site_id)[ 'data' ][ 'value' ];
            } else {
                $config_info = ( new Config() )->getPayConfig($site_id)[ 'data' ][ 'value' ];
            }

            if (!empty($config_info)) {
                $countersign_type = $config_info[ 'countersign_type' ] ?? 0;

                if ($countersign_type == 1) {
                    $appCertPath = $config_info[ "public_key_crt" ] ?? "";
                    $alipayCertPath = $config_info[ "alipay_public_key_crt" ] ?? "";
                    $rootCertPath = $config_info[ "alipay_with_crt" ] ?? "";

                    $this->aop = new AopCertClient();
                    //调用getPublicKey从支付宝公钥证书中提取公钥
                    $this->aop->alipayrsaPublicKey = $this->aop->getPublicKey($alipayCertPath);
                    //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
                    $this->aop->isCheckAlipayPublicCert = false;
                    //调用getCertSN获取证书序列号
                    $this->aop->appCertSN = $this->aop->getCertSN($appCertPath);
                    //调用getRootCertSN获取支付宝根证书序列号
                    $this->aop->alipayRootCertSN = $this->aop->getRootCertSN($rootCertPath);

                } else {
                    // 获取支付宝支付参数(统一支付到平台账户)
                    $this->aop = new AopClient();
                    $this->aop->alipayrsaPublicKey = $config_info[ 'public_key' ] ?? "";
                    $this->aop->alipayPublicKey = $config_info[ 'alipay_public_key' ] ?? "";
                }
                $this->aop->appId = $config_info[ "app_id" ] ?? "";
                $this->aop->rsaPrivateKey = $config_info[ 'private_key' ] ?? "";
                $this->aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $this->aop->apiVersion = '1.0';
                $this->aop->signType = 'RSA2';
                $this->aop->postCharset = 'UTF-8';
                $this->aop->format = 'json';

            }
//            else{
//                return $this->error('', '支付宝支付未配置');
//            }

        } catch (\Exception $e) {
            return $this->error('', '支付宝配置错误');
        }
    }

    /**
     * 生成支付
     * @param $param
     * @return array
     */
    public function pay($param)
    {
        //构造要请求的参数数组，无需改动
        $parameter = array (
            "out_trade_no" => $param[ "out_trade_no" ],
            "subject" => str_sub($param[ "pay_body" ], 15),
            "total_amount" => (float) $param[ "pay_money" ],
            "body" => str_sub($param[ "pay_body" ], 60),
            "product_code" => 'FAST_INSTANT_TRADE_PAY',
        );

        switch ( $param[ "app_type" ] ) {
            case "h5":
                $request = new AlipayTradeWapPayRequest();
                break;
            case "pc":
                $request = new AlipayTradePagePayRequest();
                break;
            case "app":
                $request = new AlipayTradeAppPayRequest();
                break;
            case 'wechat':
                $request = new AlipayTradeWapPayRequest();
                break;
            case 'cashier':
                $request = new AlipayTradePrecreateRequest();
                break;
            case 'aliapp':
                $parameter[ 'product_code' ] = 'FACE_TO_FACE_PAYMENT';

                $member_info = model('member')->getInfo([ [ "member_id", "=", $param[ "member_id" ] ] ], 'ali_openid');
                if (empty($member_info)) return $this->error(-1, '未获取到会员信息');

                $parameter[ 'buyer_id' ] = $member_info[ 'ali_openid' ];
                $request = new AlipayTradeCreateRequest();
                break;
        }

        $parameter = json_encode($parameter);
        $request->setBizContent($parameter);
        $request->SetReturnUrl($param[ "return_url" ]);
        $request->SetNotifyUrl($param[ "notify_url" ]);

        //清除绑定商户数据
        $pay_model = new PayModel();
        $clear_res = $pay_model->clearMchPay($param[ "out_trade_no" ], 'alipay');
        if($clear_res['code'] < 0) return $clear_res;

        try {
            if ($param[ "app_type" ] == 'h5' || $param[ "app_type" ] == 'wechat' || $param[ "app_type" ] == 'pc') {
                $result = $this->aop->pageExecute($request, 'get');
                $pay_model->bindMchPay($param[ "out_trade_no" ], [
                    "pay_type" => 'alipay',
                    "is_aliapp" => $this->is_aliapp,
                ]);
                return $this->success([
                    'type' => 'url',
                    'data' => $result
                ]);
            } elseif ($param[ "app_type" ] == 'app') {
                $result = $this->aop->sdkExecute($request);
                if (strpos(get_class($this->aop), 'AopClient') !== false) {
                    $pay_model->bindMchPay($param[ "out_trade_no" ], [
                        "pay_type" => 'alipay',
                        "is_aliapp" => $this->is_aliapp,
                    ]);
                    return $this->success([
                        'type' => 'url',
                        'data' => $result
                    ]);
                }
            } else {
                $result = $this->aop->execute($request);
                if($result !== false){
                    $pay_model->bindMchPay($param[ "out_trade_no" ], [
                        "pay_type" => 'alipay',
                        "is_aliapp" => $this->is_aliapp,
                    ]);
                }
            }
            if ($result === false) return $this->error('', '支付宝发起支付失败');
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            switch ( $param[ "app_type" ] ) {
                case 'cashier':
                    return $this->success([
                        'type' => 'qrcode',
                        'data' => [
                            'qrcode' => $result->$responseNode->qr_code
                        ]
                    ]);
                    break;
                case 'aliapp':
                    return $this->success([
                        'type' => 'data',
                        'data' => [
                            'orderInfo' => $result->$responseNode->trade_no
                        ]
                    ]);
                    break;
                default:
                    return $this->success();
            }
        } else {
            return $this->error("", $result->$responseNode->sub_msg);
        }
    }

    /**
     * 支付关闭
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function close($param)
    {
        $pay_order_result = $this->get($param[ "out_trade_no" ]);
        if($pay_order_result['code'] >= 0 && $pay_order_result['data']['trade_status'] != 'TRADE_CLOSED'){
            if(in_array($pay_order_result['data']['trade_status'], ['TRADE_SUCCESS','TRADE_FINISHED'])){
                return $this->error([ 'is_paid' => 1, 'pay_type' => 'alipay'], '支付宝已支付不可关闭');
            }
            //关闭请求
            $parameter = array (
                "out_trade_no" => $param[ "out_trade_no" ]
            );
            $request = new AlipayTradeCloseRequest();
            $request->setBizContent(json_encode($parameter));
            $result = $this->aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            $sub_code = $result->$responseNode->sub_code;
            if($resultCode != 10000 && in_array($sub_code, ['ACQ.TRADE_STATUS_ERROR', 'ACQ.REASON_TRADE_STATUS_INVALID', 'ACQ.REASON_ILLEGAL_STATUS'])){
                return $this->error(null, $result->$responseNode->sub_msg);
            }
        }
        return $this->success();
    }

    /**
     * 支付宝支付原路返回
     * @param array $param 支付参数
     * @return array
     * @throws \think\Exception
     */
    public function refund($param)
    {
        $pay_info = $param[ "pay_info" ];
        $refund_no = $param[ "refund_no" ];
        $out_trade_no = $pay_info[ "trade_no" ] ?? '';
        $refund_fee = $param[ "refund_fee" ];
        $parameter = array (
            'trade_no' => $out_trade_no,
            'refund_amount' => sprintf("%.2f", $refund_fee),
            'out_request_no' => $refund_no
        );
        // 建立请求
        $request = new AlipayTradeRefundRequest ();
        $request->setBizContent(json_encode($parameter));
        $result = $this->aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return $this->success();
        } else {
            return $this->error("", $result->$responseNode->sub_msg);
        }
    }

    /**
     * 支付宝转账
     * @param $param
     * @return array
     */
    public function payTransfer($param)
    {
        try {
            $config_model = new Config();
            $config_result = $config_model->getPayConfig($param[ 'site_id' ]);
            if ($config_result[ 'code' ] < 0) return $config_result;
            $config = $config_result[ 'data' ][ 'value' ];
            if (empty($config)) return $this->error([], '未配置支付宝支付');
            if (!$config[ 'transfer_status' ]) return $this->error([], '未启用支付宝转账');

            $parameter = [
                'out_biz_no' => $param[ 'out_trade_no' ],
                'payee_type' => 'ALIPAY_LOGONID',
                'payee_account' => $param[ "account_number" ],
                'amount' => sprintf("%.2f", $param[ 'amount' ]),
                'payee_real_name' => $param[ "real_name" ],
                'remark' => $param[ "desc" ]
            ];
            // 建立请求
            $request = new AlipayFundTransToaccountTransferRequest();
            $request->setBizContent(json_encode($parameter));
            $result = $this->aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if (!empty($resultCode) && $resultCode == 10000) {
                return $this->success([
                    'out_trade_no' => $result->$responseNode->out_biz_no, // 商户交易号
                    'payment_no' => $result->$responseNode->order_id, // 微信付款单号
                    'payment_time' => date_to_time($result->$responseNode->pay_date) // 付款成功时间
                ]);
            } else {
                return $this->error([], $result->$responseNode->sub_msg);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 异步完成支付
     * @param $param
     */
    public function payNotify()
    {

//        Log::write('pay_notifiy_log:alipay:'.json_encode(input()), 'notice');
        try {
            $res = $this->aop->rsaCheckV1($_POST, $this->aop->alipayrsaPublicKey, $this->aop->signType);
            if ($res) { // 验证成功
                $out_trade_no = $_POST[ 'out_trade_no' ];
                // 支付宝交易号
                $trade_no = $_POST[ 'trade_no' ];
                // 交易状态
                $trade_status = $_POST[ 'trade_status' ];
                $pay_common = new PayCommon();
                if ($trade_status == "TRADE_SUCCESS") {
                    $retval = $pay_common->onlinePay($out_trade_no, "alipay", $trade_no, "alipay");
                }
                echo "success";
            } else {
                // 验证失败
                echo "fail";
            }
        } catch (\Exception $e) {
            echo "fail";
        }
    }

    public function payNewTransfer($param)
    {
        try {
            $config_model = new Config();
            $config_result = $config_model->getPayConfig($param[ 'site_id' ]);
            if ($config_result[ 'code' ] < 0) return $config_result;
            $config = $config_result[ 'data' ][ 'value' ];
            if (empty($config)) return $this->error([], '未配置支付宝支付');
            if (!$config[ 'transfer_status' ]) return $this->error([], '未启用支付宝转账');

            $parameter = [
                'out_biz_no' => $param[ 'out_trade_no' ],
                'trans_amount' => sprintf("%.2f", $param[ 'amount' ]),
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene' => 'DIRECT_TRANSFER',
                'order_title' => '支付宝转账',
                'remark' => $param[ "desc" ],
                'payee_info' => [
                    'identity' => $param[ "account_number" ],
                    'identity_type' => "ALIPAY_LOGON_ID",
                    'name' => $param[ "real_name" ]
                ]
            ];

            // 建立请求
            $request = new AlipayFundTransUniTransferRequest();
            $request->setBizContent(json_encode($parameter));
            $result = $this->aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if (!empty($resultCode) && $resultCode == 10000) {
                return $this->success([
                    'out_trade_no' => $result->$responseNode->out_biz_no, // 商户交易号
                    'payment_no' => $result->$responseNode->order_id, // 微信付款单号
                    'payment_time' => date_to_time($result->$responseNode->trans_date) // 付款成功时间
                ]);
            } else {
                return $this->error([], $result->$responseNode->sub_msg);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 付款码支付
     * @param $param
     * @return array|mixed|void
     */
    public function micropay($param)
    {
        try {
            $pay_model = new PayModel();

            //清空绑定支付数据
            $clear_res = $pay_model->clearMchPay($param[ "out_trade_no" ], 'alipay');
            if($clear_res['code'] < 0) return $clear_res;
            //绑定支付数据
            $pay_model->bindMchPay($param[ "out_trade_no" ], [
                "pay_type" => 'alipay',
                "is_aliapp" => $this->is_aliapp,
            ]);

            //构造要请求的参数数组，无需改动
            $parameter = array (
                "out_trade_no" => $param[ "out_trade_no" ],
                "subject" => str_sub($param[ "pay_body" ], 15),
                "total_amount" => (float) $param[ "pay_money" ],
                "scene" => "bar_code",
                "auth_code" => $param[ 'auth_code' ],
            );
            $parameter = json_encode($parameter);
            $request = new AlipayTradePayRequest();
            $request->setBizContent($parameter);
            $result = $this->aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            Log::write('支付宝—付款码支付，result：' . json_encode($result));
            Log::write('支付宝—付款码支付，resultCode：' . json_encode($resultCode));
            if (!empty($resultCode)) {
                if ($resultCode == 10000) {
                    return $pay_model->onlinePay($param[ 'out_trade_no' ], 'alipay', $result->$responseNode->trade_no, 'alipay');
                } else if ($resultCode == 10003) {
                    // 等待用户付款
                    ( new Cron() )->addCron(1, 0, "查询付款码支付结果", "PayOrderQuery", time() + 3, $param[ 'id' ]);
                    return $this->error([], $result->$responseNode->sub_msg);
                } else {
                    return $this->error([], $result->$responseNode->sub_msg);
                }
            } else {
                return $this->error([], $result->$responseNode->sub_msg);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    // todo 查询交易信息【AlipayTradeQueryRequest】 https://opendocs.alipay.com/open/194/106039?pathHash=5b8cf9e6
    public function orderQuery($param)
    {
        try {
            //构造要请求的参数数组，无需改动
            $parameter = array (
                "out_trade_no" => $param[ "out_trade_no" ],
            );
            $parameter = json_encode($parameter);
            $request = new AlipayTradeQueryRequest();
            $request->setBizContent($parameter);
            $result = $this->aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            Log::write('alipay_orderQuery' . json_encode($result));
            Log::write('alipay_orderQuery_$resultCode' . json_encode($resultCode));
            if (!empty($resultCode) && $resultCode == 10000) {
                if ($result->$responseNode->trade_status == 'TRADE_SUCCESS') {
                    $pay_common = new PayModel();
                    return $res = $pay_common->onlinePay($param[ 'out_trade_no' ], 'alipay', $result->$responseNode->trade_no, 'alipay');
                } else {
                    $cron_model = new Cron();
                    $cron_model->deleteCron([ [ 'event', '=', 'PayOrderQuery' ], [ 'relate_id', '=', $param[ 'id' ] ] ]);
                    $cron_model->addCron(1, 0, "查询付款码支付结果", "PayOrderQuery", time() + 3, $param[ 'id' ]);
                }

            } else {
                return $this->error([], $result->$responseNode->sub_msg);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 查询订单信息
     * @param $out_trade_no
     * @return array
     * @throws \think\Exception
     */
    public function get($out_trade_no)
    {
        $parameter = array (
            "out_trade_no" => $out_trade_no
        );
        // 建立请求
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent(json_encode($parameter));
        $result = $this->aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if (!empty($resultCode) && $resultCode == 10000) {
            return $this->success(json_decode(json_encode($result->$responseNode), true));
        } else {
            return $this->error([], $result->$responseNode->sub_msg);
        }
    }
}