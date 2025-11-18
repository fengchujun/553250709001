<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\model;

use app\model\BaseModel;
use app\model\system\Cron;
use app\model\system\Pay as PayCommon;
use app\model\upload\Upload;
use EasyWeChat\Factory;
use think\facade\Log;

/**
 * 微信支付v2支付
 * 版本 1.0.4
 */
class V2 extends BaseModel
{
    /**
     * 微信支付配置
     */
    private $config;

    /**
     * 支付实例
     * @var
     */
    private $app;

    public function __construct($config)
    {
        $this->config = $config;

        $this->app = Factory::payment([
            'app_id' => $config[ 'appid' ],        //应用id
            'mch_id' => $config[ "mch_id" ] ?? '',       //商户号
            'key' => $config[ "pay_signkey" ] ?? '',          // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => realpath($config[ "apiclient_cert" ]) ?? '', // apiclient_cert.pem XXX: 绝对路径！！！！
            'key_path' => realpath($config[ "apiclient_key" ]) ?? '',   // apiclient_key.pem XXX: 绝对路径！！！！
            'notify_url' => '',// 你也可以在下单时单独设置来想覆盖它
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'permission' => 0777,
                'file' => 'runtime/log/wechat/easywechat.logs',
            ],
            'sandbox' => false, // 设置为 false 或注释则关闭沙箱模式
        ]);
    }

    /**
     * 支付
     * @param array $param
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pay(array $param)
    {
        $data = [
            'body' => str_sub($param[ "pay_body" ], 15),
            'out_trade_no' => $param[ "out_trade_no" ],
            'total_fee' => $param[ "pay_money" ] * 100,
            'notify_url' => $param[ "notify_url" ],
            'trade_type' => $param[ "trade_type" ] == 'APPLET' ? 'JSAPI' : $param[ "trade_type" ]
        ];
        if ($param[ 'trade_type' ] == 'JSAPI' || $param[ 'trade_type' ] == 'APPLET') $data[ 'openid' ] = $param[ 'openid' ];
        $result = $this->app->order->unify($data);

        if ($result[ "return_code" ] == 'FAIL') return $this->error([], $result[ "return_msg" ]);
        if ($result[ "result_code" ] == 'FAIL') return $this->error([], $result[ "err_code_des" ]);

        $return = [];
        switch ( $param[ "trade_type" ] ) {
            case 'JSAPI' ://微信支付 或小程序支付
                $return = [
                    "type" => "jsapi",
                    "data" => $this->app->jssdk->sdkConfig($result[ 'prepay_id' ], false)
                ];
                break;
            case 'APPLET' ://微信支付 或小程序支付
                $return = [
                    "type" => "jsapi",
                    "data" => $this->app->jssdk->bridgeConfig($result[ 'prepay_id' ], false)
                ];
                break;
            case 'NATIVE' :
                $upload_model = new Upload($param[ 'site_id' ]);
                $qrcode_result = $upload_model->qrcode($result[ 'code_url' ]);
                $return = [
                    "type" => "qrcode",
                    "qrcode" => $qrcode_result[ 'data' ] ?? ''
                ];
                break;
            case 'MWEB' ://H5支付
                $return = [
                    "type" => "url",
                    "url" => $result[ 'mweb_url' ]
                ];
                break;
            case 'APP' :
                $config = $this->app->jssdk->appConfig($result[ 'prepay_id' ]);
                $return = [
                    "type" => "app",
                    "data" => $config
                ];
                break;
        }
        return $this->success($return);
    }

    /**
     * 异步回调
     */
    public function payNotify()
    {
        $response = $this->app->handlePaidNotify(function($message, $fail) {
            $pay_common = new PayCommon();
            if ($message[ 'return_code' ] === 'SUCCESS') {
                // return_code 表示通信状态，不代表支付状态
                if ($message[ 'result_code' ] === 'SUCCESS') {
                    // 判断支付金额是否等于支付单据的金额
                    $pay_info = $pay_common->getPayInfo($message[ 'out_trade_no' ])[ 'data' ];
                    if (empty($pay_info)) return $fail('通信失败，请稍后再通知我');
                    if ($message[ 'total_fee' ] != round($pay_info[ 'pay_money' ] * 100)) return;
                    // 用户是否支付成功
                    $pay_common->onlinePay($message[ 'out_trade_no' ], "wechatpay", $message[ "transaction_id" ], "wechatpay");
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }
            return true;
        });
        $response->send();
        return $response;
    }

    /**
     * 关闭支付单据
     * @param array $param
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function payClose(array $param)
    {
        $query_result = $this->app->order->queryByOutTradeNumber($param[ "out_trade_no" ]);
        if($query_result['return_code'] == 'SUCCESS' && $query_result['result_code'] == 'SUCCESS' && $query_result['trade_state'] != 'CLOSED'){
            if($query_result['trade_state'] == 'SUCCESS'){
                return $this->error([ 'is_paid' => 1, 'pay_type' => 'wechatpay'], '微信已支付不可关闭');
            }
            $close_result = $this->app->order->close($param[ "out_trade_no" ]);
            if($close_result['return_code'] != 'SUCCESS' || $close_result['result_code'] != 'SUCCESS'){
                return $this->error($close_result, $close_result[ 'return_msg' ]);
            }
        }
        return $this->success();
    }

    /**
     * 申请退款
     * @param array $param
     */
    public function refund(array $param)
    {
        $pay_info = $param[ "pay_info" ];
        $refund_no = $param[ "refund_no" ];
        $total_fee = round($pay_info[ "pay_money" ] * 100);
        $refund_fee = round($param[ "refund_fee" ] * 100);

        $result = $this->app->refund->byOutTradeNumber($pay_info[ "out_trade_no" ], $refund_no, $total_fee, $refund_fee, []);
        //调用失败
        if ($result[ "return_code" ] == 'FAIL') return $this->error([], $result[ "return_msg" ]);
        if ($result[ "result_code" ] == 'FAIL') return $this->error([], $result[ "err_code_des" ]);

        return $this->success();
    }

    /**
     * 转账
     * @param array $param
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transfer(array $param)
    {
        $data = [
            'partner_trade_no' => $param[ 'out_trade_no' ], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $param[ 'account_number' ],
            'check_name' => 'FORCE_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => $param[ 'real_name' ], // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => round($param[ 'amount' ] * 100, 2), // 转账金额
            'desc' => $param[ 'desc' ]
        ];
        $res = $this->app->transfer->toBalance($data);
        if ($res[ 'return_code' ] == 'SUCCESS') {
            if ($res[ 'result_code' ] == 'SUCCESS') {
                return $this->success([
                    'out_trade_no' => $res[ 'partner_trade_no' ], // 商户交易号
                    'payment_no' => $res[ 'payment_no' ], // 微信付款单号
                    'payment_time' => $res[ 'payment_time' ] // 付款成功时间
                ]);
            } else {
                return $this->error([], $res[ 'err_code_des' ]);
            }
        } else {
            return $this->error([], $res[ 'return_msg' ]);
        }
    }

    /**
     * 付款码支付
     * @param array $param
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function micropay(array $param)
    {
        $data = [
            'body' => str_sub($param[ "pay_body" ], 15),
            'out_trade_no' => $param[ "out_trade_no" ],
            'total_fee' => $param[ "pay_money" ] * 100,
            'auth_code' => $param[ "auth_code" ]
        ];
        $result = $this->app->base->pay($data);
        if ($result[ 'return_code' ] == 'FAIL') {
            return $this->error([], $result[ 'return_msg' ]);
        }
        if ($result[ 'result_code' ] == 'FAIL') {
            if ($result[ 'err_code' ] != 'USERPAYING') {
                ( new Cron() )->addCron(1, 0, "查询付款码支付结果", "PayOrderQuery", time() + 3, $param[ 'id' ]);
            }
            return $this->error($result, $result[ 'err_code_des' ]);
        }

        return $this->success($result);
    }

    /**
     * 查询订单支付结果
     * @param $pay_info
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function orderQuery($pay_info)
    {
        $result = $this->app->order->queryByOutTradeNumber($pay_info[ 'out_trade_no' ]);
        Log::write('查询微信支付订单' . json_encode($result));
        if ($result[ 'return_code' ] == 'FAIL') {
            return $this->error([], $result[ 'return_msg' ]);
        }
        if ($result[ 'result_code' ] == 'SUCCESS') {
            if ($result[ 'trade_state' ] == 'SUCCESS') {
                ( new PayCommon() )->onlinePay($pay_info[ 'out_trade_no' ], 'wechatpay', $result[ 'transaction_id' ], 'wechatpay');
            } else if (in_array($result[ 'trade_state' ], [ 'NOTPAY', 'USERPAYING', 'ACCEPT' ])) {
                $cron_model = new Cron();
                $cron_model->deleteCron([ [ 'event', '=', 'PayOrderQuery' ], [ 'relate_id', '=', $pay_info[ 'id' ] ] ]);
                $cron_model->addCron(1, 0, "查询付款码支付结果", "PayOrderQuery", time() + 3, $pay_info[ 'id' ]);
            }else if($result[ 'trade_state' ] == 'PAYERROR'){
                (new PayCommon())->edit(['pay_status' => PayCommon::PAY_STATUS_CANCEL], ['out_trade_no' => $pay_info[ "out_trade_no" ] ]);
            }
        }
        return $this->success($result);
    }
}
