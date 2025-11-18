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

use app\exception\ApiException;
use app\model\BaseModel;
use app\model\system\Cron;
use app\model\system\Pay as PayCommon;
use app\model\system\PayTransfer;
use app\model\upload\Upload;
use think\exception\HttpException;
use think\facade\Cache;
use think\facade\Log;
use WeChatPay\Builder;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Formatter;
use WeChatPay\Util\PemUtil;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use addon\wechatpay\model\Config as WechatPayConfig;

/**
 * 微信支付v3支付
 * 版本 1.0.4
 */
class V3 extends BaseModel
{
    /**
     * 应用实例
     * @var \WeChatPay\BuilderChainable
     */
    private $app;

    // 平台证书实例
    private $plateform_certificate_instance;

    // 平台证书序列号
    private $plateform_certificate_serial;

    /**
     * 微信支付配置
     */
    private $config;


    public function __construct($config)
    {
        $this->config = $config;

        $merchant_certificate_instance = PemUtil::loadCertificate(realpath($config['apiclient_cert']));
        // 证书序列号
        $merchant_certificate_serial = PemUtil::parseCertificateSerialNo($merchant_certificate_instance);
       // var_dump($this->config);
        //平台证书和序列号处理
        //plateform_cert 是系统自动生成的 plateform_certificate 是直接上传的
        if (empty($this->config['plateform_certificate_serial'])) {
            // 检测平台证书是否存在
            if (empty($config['plateform_cert']) || !is_file($config['plateform_cert'])) {
                $create_res = $this->certificates();
                if ($create_res['code'] != 0) throw new ApiException(-1, $create_res['message'] ?? "微信支付配置错误");
                // 保存平台证书
                $this->config['plateform_cert'] = $create_res['data']['cert_path'];
                (new Config())->setPayConfig($this->config, $this->config['site_id']);
            }
            // 加载平台证书
            $this->plateform_certificate_instance = PemUtil::loadCertificate(realpath($this->config['plateform_cert']));
            // 平台证书序列号
            $this->plateform_certificate_serial = PemUtil::parseCertificateSerialNo($this->plateform_certificate_instance);
        } else {
            // 加载平台证书
            $this->plateform_certificate_instance = file_get_contents(realpath($this->config['plateform_certificate']));
            // 平台证书序列号
            $this->plateform_certificate_serial = $this->config['plateform_certificate_serial'];
            // 具体业务有很多需要用这个字段，值与新字段保持相同
            $this->config[ 'plateform_cert' ] = $this->config[ 'plateform_certificate' ];
        }

        $this->app = Builder::factory([
            // 商户号
            'mchid' => $config['mch_id'],
            // 商户证书序列号
            'serial' => $merchant_certificate_serial,
            // 商户API私钥
            'privateKey' => PemUtil::loadPrivateKey(realpath($config['apiclient_key'])),
            'certs' => [
                $this->plateform_certificate_serial => $this->plateform_certificate_instance
            ]
        ]);
    }

    /**
     * 生成平台证书
     */
    private function certificates()
    {
        try {
            $merchant_certificate_instance = PemUtil::loadCertificate(realpath($this->config['apiclient_cert']));
            // 证书序列号
            $merchant_certificate_serial = PemUtil::parseCertificateSerialNo($merchant_certificate_instance);

            $certs = ['any' => null];
            $app = Builder::factory([
                // 商户号
                'mchid' => $this->config['mch_id'],
                // 商户证书序列号
                'serial' => $merchant_certificate_serial,
                // 商户API私钥
                'privateKey' => PemUtil::loadPrivateKey(realpath($this->config['apiclient_key'])),
                'certs' => &$certs
            ]);

            $stack = $app->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
            $stack->after('verifier', Middleware::mapResponse(self::certsInjector($this->config['v3_pay_signkey'], $certs)), 'injector');
            $stack->before('verifier', Middleware::mapResponse(self::certsRecorder((string)dirname($this->config['apiclient_key']), $certs)), 'recorder');

            $param = [
                'url' => '/v3/certificates',
                'timestamp' => (string)Formatter::timestamp(),
                'noncestr' => uniqid()
            ];
            $resp = $app->chain("v3/certificates")
                ->get([
                    'headers' => [
                        'Authorization' => Rsa::sign(
                            Formatter::joinedByLineFeed(...array_values($param)),
                            Rsa::from('file://' . realpath($this->config['apiclient_key']))
                        )
                    ]
                ]);
            $result = json_decode($resp->getBody()->getContents(), true);
            $file_path = dirname($this->config['apiclient_key']) . '/plateform_cert.pem';
            return $this->success(['cert_path' => $file_path]);
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                return $this->error($result, $result['message']);
            } else {
                return $this->error([], $e->getMessage());
            }
        }
    }

    private static function certsInjector(string $apiv3Key, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($apiv3Key, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{encrypt_certificate:object{serial_no:string,nonce:string,associated_data:string}}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_map(static function ($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
            }, $data);

            return $response;
        };
    }

    private static function certsRecorder(string $outputDir, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($outputDir, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{effective_time:string,expire_time:string:serial_no:string}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_walk($data, static function ($row, $index, $certs) use ($outputDir) {
                $serialNo = $row->serial_no;
                $outpath = $outputDir . \DIRECTORY_SEPARATOR . 'plateform_cert.pem';
                \file_put_contents($outpath, $certs[$serialNo]);
            }, $certs);

            return $response;
        };
    }

    /**
     * 支付
     * @param array $param
     * @return array
     */
    public function pay(array $param)
    {
        $self = $this;
        $site_id = $param['site_id'];
        $data = [
            'json' => [
                'appid' => $this->config['appid'],
                'mchid' => $this->config['mch_id'],
                'description' => str_sub($param["pay_body"], 15),
                'out_trade_no' => $param["out_trade_no"],
                'notify_url' => $param["notify_url"],
                'amount' => [
                    'total' => round($param["pay_money"] * 100)
                ]
            ]
        ];
        switch ($param["trade_type"]) {
            case 'JSAPI':
                $data['json']['payer'] = ['openid' => $param['openid']];
                $data['trade_type'] = 'jsapi';
                $data['callback'] = function ($result) use ($self) {
                    return success(0, '', [
                        "type" => "jsapi",
                        "data" => $self->jsskdConfig($result['prepay_id'])
                    ]);
                };
                break;
            case 'APPLET':
                $data['json']['payer'] = ['openid' => $param['openid']];
                $data['trade_type'] = 'jsapi';
                $data['callback'] = function ($result) use ($self) {
                    return success(0, '', [
                        "type" => "jsapi",
                        "data" => $self->jsskdConfig($result['prepay_id'])
                    ]);
                };
                break;
            case 'NATIVE':
                $data['trade_type'] = 'native';
                $data['callback'] = function ($result) use ($site_id) {
                    $upload_model = new Upload($site_id);
                    $qrcode_result = $upload_model->qrcode($result['code_url']);
                    return success(0, '', [
                        "type" => "qrcode",
                        "qrcode" => $qrcode_result['data'] ?? ''
                    ]);
                };
                break;
            case 'MWEB':
                $data['trade_type'] = 'h5';
                $data['json']['scene_info'] = [
                    'payer_client_ip' => request()->ip(),
                    'h5_info' => [
                        'type' => 'Wap'
                    ]
                ];
                $data['callback'] = function ($result) {
                    return success(0, '', [
                        "type" => "url",
                        "url" => $result['h5_url']
                    ]);
                };
                break;
            case 'APP':
                $data['trade_type'] = 'app';
                $data['callback'] = function ($result) use ($self) {
                    return success(0, '', [
                        "type" => "app",
                        "data" => $self->appConfig($result['prepay_id'])
                    ]);
                };
                break;
        }

        $result = $this->unify($data);
        if ($result['code'] != 0) return $result;

        $result = $data['callback']($result['data']);
        return $result;
    }

    /**
     * 统一下单接口
     * @param array $param
     */
    public function unify(array $param)
    {
        try {
            $resp = $this->app->chain('v3/pay/transactions/' . $param['trade_type'])->post([
                'json' => $param['json'],
                'headers' => [
                    'Wechatpay-Serial' => $this->plateform_certificate_serial
                ]
            ]);
            $result = json_decode($resp->getBody()->getContents(), true);
            return $this->success($result);
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                $result = json_decode($e->getMessage(), true);
                return $this->error($result, $result['message']);
            } else {
                return $this->error([], $e->getMessage());
            }
        }
    }

    /**
     * 生成支付配置
     * @param string $prepay_id
     * @return array
     */
    private function jsskdConfig(string $prepay_id)
    {
        $param = [
            'appId' => $this->config['appid'],
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr' => uniqid(),
            'package' => "prepay_id=$prepay_id"
        ];
        $param += ['paySign' => Rsa::sign(
            Formatter::joinedByLineFeed(...array_values($param)),
            Rsa::from('file://' . realpath($this->config['apiclient_key']))
        ), 'signType' => 'RSA'];
        return $param;
    }

    /**
     * 生成支付配置
     * @param string $prepay_id
     * @return array
     */
    private function appConfig(string $prepay_id)
    {
        $param = [
            'appid' => $this->config['appid'],
            'timestamp' => (string)Formatter::timestamp(),
            'noncestr' => uniqid(),
            'prepayid' => $prepay_id
        ];
        $param += [
            'sign' => Rsa::sign(
                Formatter::joinedByLineFeed(...array_values($param)),
                Rsa::from('file://' . realpath($this->config['apiclient_key']))
            ),
            'package' => 'Sign=WXPay',
            'partnerid' => $this->config['mch_id']
        ];
        return $param;
    }

    /**
     * 异步回调
     */
    public function payNotify()
    {
        $inWechatpaySignature = request()->header('Wechatpay-Signature'); // 从请求头中拿到 签名
        $inWechatpayTimestamp = request()->header('Wechatpay-Timestamp'); // 从请求头中拿到 时间戳
        $inWechatpaySerial = request()->header('Wechatpay-Serial');  // 从请求头中拿到 时间戳
        $inWechatpayNonce = request()->header('Wechatpay-Nonce'); // 从请求头中拿到 时间戳
        $inBody = file_get_contents('php://input');

        $platformPublicKeyInstance = Rsa::from('file://' . realpath($this->config['plateform_cert']), Rsa::KEY_TYPE_PUBLIC);

        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, file_get_contents('php://input')),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $this->config['v3_pay_signkey'], $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $message = json_decode($inBodyResource, true);
            Log::write('message' . $inBodyResource);
            // 交易状态为成功
            if (isset($message['trade_state']) && $message['trade_state'] == 'SUCCESS') {
                if (isset($message['out_trade_no'])) {
                    $pay_common = new PayCommon();
                    $pay_info = $pay_common->getPayInfo($message['out_trade_no'])['data'];
                    if (empty($pay_info)) return;
                    if ($message['amount']['total'] != round($pay_info['pay_money'] * 100)) return;
                    // 用户是否支付成功
                    $pay_common->onlinePay($message['out_trade_no'], "wechatpay", $message["transaction_id"], "wechatpay");
                    header('', '', 200);
                }
            } else {
                $this->payNotifyFail(501, 'FAIL', '没有trade_state字段或不是SUCCESS');
            }
        } else {
            $this->payNotifyFail(401, 'FAIL', '验签失败');
        }
    }

    /**
     * 支付回调失败处理
     * @param $response_code
     * @param $code
     * @param $message
     */
    protected function payNotifyFail($response_code, $code, $message)
    {
        //记录日志
        Log::write('V3支付回调失败');
        Log::write(['response_code' => $response_code, 'code' => $code, 'message' => $message]);

        // 设置HTTP响应头
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($response_code); // 或400等适当的状态码
        // 构造错误响应
        $errorResponse = [
            'code' => $code,
            'message' => $message
        ];
        // 输出JSON响应
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 支付单据关闭
     * @param array $param
     * @return array
     */
    public function payClose(array $param)
    {
        $get_res = $this->get($param['out_trade_no']);
        if ($get_res['code'] >= 0 && $get_res['data']['trade_state'] != 'CLOSED') {
            if ($get_res['data']['trade_state'] == 'SUCCESS') {
                return $this->error(['is_paid' => 1, 'pay_type' => 'wechatpay'], '微信已支付不可关闭');
            }
            try {
                $resp = $this->app->chain("v3/pay/transactions/out-trade-no/{$param['out_trade_no']}/close")->post([
                    'json' => [
                        'mchid' => $this->config['mch_id']
                    ]
                ]);
                $result = json_decode($resp->getBody()->getContents(), true);
                return $this->success($result);
            } catch (\Exception $e) {
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                    return $this->error($result, $result['message']);
                }
                return $this->error(['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], $e->getMessage());
            }
        }
        return $this->success();
    }

    /**
     * 申请退款
     * @param array $param
     * @return array
     */
    public function refund(array $param)
    {
        $pay_info = $param["pay_info"];

        try {
            $resp = $this->app->chain("v3/refund/domestic/refunds")->post([
                'json' => [
                    'out_trade_no' => $pay_info['out_trade_no'],
                    'out_refund_no' => $param['refund_no'],
                    'notify_url' => addon_url("pay/pay/refundnotify"),
                    'amount' => [
                        'refund' => round($param['refund_fee'] * 100),
                        'total' => round($pay_info['pay_money'] * 100),
                        'currency' => $param['currency'] ?? 'CNY'
                    ]
                ]
            ]);
            $result = json_decode($resp->getBody()->getContents(), true);
            if (isset($result['status']) && ($result['status'] == 'SUCCESS' || $result['status'] == 'PROCESSING'))
                return $this->success($result);
            else return $this->success($result, '退款异常');
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                return $this->error($result, $result['message']);
            } else {
                return $this->error([], $e->getMessage());
            }
        }
    }


    /**
     * 转账
     * @param array $param
     * @return array|mixed
     */
    public function transfer(array $param)
    {
         if($this->config['transfer_v3_type'] == WechatPayConfig::TRANSFER_V3_TYPE_SHOP){
             return $this->transferByShop($param);
         }else{
             return $this->transferByUser($param);
         }
    }


    /**
     * 商户直接转账
     * @param array $param
     * @return mixed
     */
    public function transferByShop(array $param)
    {
        $data = [
            'appid' => $this->config['appid'],
            'out_batch_no' => $param['out_trade_no'],
            'batch_name' => '客户提现转账',
            'batch_remark' => '客户提现转账提现交易号' . $param['out_trade_no'],
            'total_amount' => round($param['amount'] * 100),
            'total_num' => 1,
            'transfer_detail_list' => [
                [
                    'out_detail_no' => $param['out_trade_no'],
                    'transfer_amount' => round($param['amount'] * 100, 2),
                    'transfer_remark' => $param['desc'],
                    'openid' => $param['account_number'],
                    'user_name' => $this->encryptor($param['real_name'])
                ]
            ]
        ];
        \think\facade\Log::write('转账发起数据');
        \think\facade\Log::write($data);
        $this->app->chain('v3/transfer/batches')
            ->postAsync([
                'json' => $data,
                'headers' => [
                    'Wechatpay-Serial' => $this->plateform_certificate_serial
                ]
            ])->then(static function ($response) use (&$result) {
                $pay_transfer_model = new PayTransfer();
                $result = json_decode($response->getBody()->getContents(), true);
                \think\facade\Log::write('转账成功返回');
                \think\facade\Log::write($result);
                $result = $pay_transfer_model->success([
                    'status' => $pay_transfer_model::STATUS_IN_PROCESS,
                ]);
            })->otherwise(static function ($exception) use (&$result) {
                if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
                    $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
                    \think\facade\Log::write('转账其他返回');
                    \think\facade\Log::write($result);
                    $pay_transfer_model = new PayTransfer();
                    if (isset($result['batch_status'])) {
                        if (in_array($result['batch_status'], ['ACCEPTED', 'PROCESSING'])) {
                            $result = $pay_transfer_model->success([
                                'status' => $pay_transfer_model::STATUS_IN_PROCESS,
                            ]);
                        } else if ($result['batch_status'] == 'FINISHED') {
                            $result = $pay_transfer_model->success([
                                'status' => $pay_transfer_model::STATUS_SUCCESS,
                            ]);
                        } else {
                            $result = $pay_transfer_model->error($result, '转账失败');
                        }
                    } else {
                        $result = error(-1, '转账错误：' . $result['message'] ?? '未知错误', $result);
                    }
                } else {
                    $result = error(-1, '转账报错：' . $exception->getMessage());
                }
            })->wait();
        return $result;
    }

    /**
     * 查询转账明细
     * @param string $out_batch_no
     * @param string $out_detail_no
     * @return array
     */
    public function transferDetail(string $out_batch_no, string $out_detail_no): array
    {
        try {
            $resp = $this->app->chain("v3/transfer/batches/out-batch-no/{$out_batch_no}/details/out-detail-no/{$out_detail_no}")
                ->get();
            $result = json_decode($resp->getBody()->getContents(), true);
            \think\facade\Log::write('转账查询正常返回');
            \think\facade\Log::write($result);
            return $this->success($result);
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                \think\facade\Log::write('转账查询其他返回');
                \think\facade\Log::write($result);
                if (isset($result['detail_status'])) {
                    return $this->success($result);
                } else {
                    return $this->error($result, $result['message'] ?? '未知错误');
                }
            } else {
                return $this->error([], $e->getMessage());
            }
        }
    }

    /**
     * 加密数据
     * @param string $str
     * @return string
     */
    public function encryptor(string $str)
    {
        $publicKey = $this->plateform_certificate_instance;
        // 加密方法
        $encryptor = function ($msg) use ($publicKey) {
            return Rsa::encrypt($msg, $publicKey);
        };
        return $encryptor($str);
    }

    /**
     * 获取转账结果
     * @param $withdraw_info
     * @return array
     */
    public function getTransferResult($withdraw_info)
    {
        $pay_transfer_model = new PayTransfer();
        $result = $this->transferDetail($withdraw_info['out_trade_no'], $withdraw_info['out_trade_no']);
        if ($result['code'] == 0) {
            switch ($result['data']['detail_status']) {
                case 'SUCCESS':
                    $status = $pay_transfer_model::STATUS_SUCCESS;
                    break;
                case 'FAIL':
                    $status = $pay_transfer_model::STATUS_FAIL;
                    $reason_config = [
                        'ACCOUNT_FROZEN' => '账户冻结',
                        'REAL_NAME_CHECK_FAIL' => '用户未实名',
                        'NAME_NOT_CORRECT' => '用户姓名校验失败',
                        'OPENID_INVALID' => 'Openid校验失败',
                        'TRANSFER_QUOTA_EXCEED' => '超过用户单笔收款额度',
                        'DAY_RECEIVED_QUOTA_EXCEED' => '超过用户单日收款额度',
                        'MONTH_RECEIVED_QUOTA_EXCEED' => '超过用户单月收款额度',
                        'DAY_RECEIVED_COUNT_EXCEED' => '超过用户单日收款次数',
                        'PRODUCT_AUTH_CHECK_FAIL' => '产品权限校验失败',
                        'OVERDUE_CLOSE' => '转账关闭',
                        'ID_CARD_NOT_CORRECT' => '用户身份证校验失败',
                        'ACCOUNT_NOT_EXIST' => '用户账户不存在',
                        'TRANSFER_RISK' => '转账存在风险',
                        'REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED' => '用户账户收款受限，请引导用户在微信支付查看详情',
                        'RECEIVE_ACCOUNT_NOT_PERMMIT' => '未配置该用户为转账收款人',
                        'PAYER_ACCOUNT_ABNORMAL' => '商户账户付款受限，可前往商户平台-违约记录获取解除功能限制指引',
                        'PAYEE_ACCOUNT_ABNORMAL' => '用户账户收款异常，请引导用户完善其在微信支付的身份信息以继续收款',
                        'MERCHANT_REJECT' => '商家拒绝',
                    ];
                    $reason_code = $result['data']['fail_reason'] ?? '';
                    $fail_reason = $reason_config[$reason_code] ?? $reason_code;
                    break;
                default:
                    $status = $pay_transfer_model::STATUS_IN_PROCESS;
            }
            return $this->success([
                'status' => $status,
                'fail_reason' => $fail_reason ?? '',
                'reason_code' => $reason_code ?? '',
            ]);
        } else {
            return $result;
        }
    }

    /**
     * 查询订单信息
     * @param $out_trade_no
     * @return array
     */
    public function get($out_trade_no)
    {
        try {
            $resp = $this->app->chain("/v3/pay/transactions/out-trade-no/{$out_trade_no}")->get();
            $result = json_decode($resp->getBody()->getContents(), true);
            return $this->success($result);
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $result = json_decode($e->getResponse()->getBody()->getContents(), true);
                return $this->error($result, $result['message']);
            } else {
                return $this->error([], $e->getMessage());
            }
        }
    }

    /**
     * 会员转账下单
     * @param array $param
     */

    public function transferByUser(array $param)
    {
        $data = [
            'appid' => $this->config['appid'],
            'out_bill_no' => $param['out_trade_no'],
            'transfer_scene_id' => $this->config[$param['from_type'].'_code'],
            'openid' => $param['account_number'],
            'user_name' => $param['amount']  >= 2000 ? $this->encryptor($param['real_name']) : '',
            'transfer_amount' => round($param['amount'] * 100),
            'transfer_remark' => '客户提现转账',
            'notify_url' => addon_url("wechatpay://api/transfer/notify"),
            'user_recv_perception'=> $this->config[$param['from_type'].'_recv'] ?? '',
            'transfer_scene_report_infos' => $this->config[$param['from_type'].'_info'],
        ];

        Log::write('会员发起转账数据');
        Log::write($data);

        try {
            $resp = $this->app
                ->chain('/v3/fund-app/mch-transfer/transfer-bills')
                ->postAsync([
                    'json' => $data,
                    'headers' => [
                        'Wechatpay-Serial' => $this->plateform_certificate_serial
                    ]
                ])->then(static function ($response) use (&$result) {

                    $result = json_decode($response->getBody()->getContents(), true);
                    \think\facade\Log::write('申请转账返回');
                    \think\facade\Log::write($result);

                })->otherwise(static function ($exception) use (&$result) {
                    if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
                        $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
                        \think\facade\Log::write('申请转账返回异常');
                        \think\facade\Log::write($result);
                        $result =   error(-1,   '转账异常：' .$result['message'] ?? '');
                    } else {
                        $result =   error(-1, '转账异常：' . $exception->getMessage());
                    }
                })->wait();

            if(isset($result['code']) && $result['code'] != 0){
                return  $result;
            }
            //状态为待转账
            $result['status'] = PayTransfer::STATUS_WAIT;
            return $this->success($result);

        } catch (\Exception $e) {
            \think\facade\Log::write('会员提现转账失败,原因'.$e->getMessage());
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 撤销转账
     * @param $out_bill_no
     */
    public function transferCancel($out_bill_no){

        Log::write('会员撤销转账');
        Log::write($out_bill_no);

        try {
            $resp = $this->app
                ->chain('/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/'.$out_bill_no.'/cancel')
                ->postAsync()
                ->then(static function ($response) use (&$result) {
                    $result = json_decode($response->getBody()->getContents(), true);
                    Log::write('会员撤销转账数据返回');
                    Log::write($result);
                })->otherwise(static function ($exception) use (&$result) {
                    if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
                        $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
                        Log::write('撤销转账返回异常');
                        Log::write($result);
                    } else {
                        $result = error(-1, '撤销转账报错：' . $exception->getMessage());
                    }
                })->wait();

            return $this->success($result);

        } catch (\Exception $e) {
            Log::write('会员撤销转账失败,原因'.$e->getMessage());
            return $this->error([], $e->getMessage());
        }
    }


    /**
     * 会员提现回调
     * @throws \Exception
     */

    public function transferNotify(){

        $inWechatpaySignature = request()->header('Wechatpay-Signature'); // 从请求头中拿到 签名
        $inWechatpayTimestamp = request()->header('Wechatpay-Timestamp'); // 从请求头中拿到 时间戳
        $inWechatpaySerial = request()->header('Wechatpay-Serial');  // 从请求头中拿到 时间戳
        $inWechatpayNonce = request()->header('Wechatpay-Nonce'); // 从请求头中拿到 时间戳
        $inBody = file_get_contents('php://input');

        Log::write('transferNotifyHeader'.json_encode(request()->header()));
        Log::write('transferNotifyBody' . $inBody);

        $platformPublicKeyInstance = Rsa::from('file://' . realpath($this->config['plateform_cert']), Rsa::KEY_TYPE_PUBLIC);

        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, file_get_contents('php://input')),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $this->config['v3_pay_signkey'], $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $message = json_decode($inBodyResource, true);
            Log::write('transferNotifyMessage' . $inBodyResource);
            // 交易状态为成功

            $pay_transfer_model = new PayTransfer();
            if (isset($message['state']) && $message['state'] == 'SUCCESS') {
                if (isset($message['out_bill_no'])) {
                    $transfer_info = $pay_transfer_model->getTransferInfo([['out_trade_no', '=', $message['out_bill_no']]],'id')['data'];
                    $update_result = $pay_transfer_model->updateStatus(['status' => PayTransfer::STATUS_SUCCESS, 'result' => $message], $transfer_info['id']);
                    Log::write('transferNotify' . json_encode(['transfer_info'=>$transfer_info,'update_result'=>$update_result]));
                    header('', '', 200);
                }
            } else {
                if (isset($message['out_bill_no'])) {
                    $transfer_info = $pay_transfer_model->getTransferInfo([['out_trade_no', '=', $message['out_bill_no']]],'id')['data'];
                    $update_result = $pay_transfer_model->updateStatus(['status' => PayTransfer::STATUS_FAIL, 'result' => $message], $transfer_info['id']);
                    Log::write('transferNotify' . json_encode(['transfer_info'=>$transfer_info,'update_result'=>$update_result]));
                }

                throw new HttpException(500, '失败', null, [], 'FAIL');
            }
        } else {
            throw new HttpException(500, '失败', null, [], 'FAIL');
        }
    }
}