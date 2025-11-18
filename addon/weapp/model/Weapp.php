<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\model;

use addon\wechatpay\model\Config as WechatPayModel;
use app\model\BaseModel;
use app\model\order\Order;
use app\model\shop\Shop as ShopModel;
use app\model\system\Api;
use app\model\system\Config as ConfigModel;
use EasyWeChat\Factory;
use think\facade\Cache;
use addon\weapp\model\Config as WeappConfigModel;
use addon\wxoplatform\model\Config as WxOplatformConfigModel;
use app\model\web\Config as WebConfig;
use think\facade\Log;

/**
 * 微信小程序配置
 */
class Weapp extends BaseModel
{
    private $app;//微信模型

    //小程序类型
    public $service_type = array (
        0 => "小程序",
    );

    //小程序认证类型
    public $verify_type = array (
        -1 => "未认证",
        0 => "微信认证",
    );

    //business_info 说明
    public $business_type = array (
        'open_store' => "是否开通微信门店功能",
        'open_scan' => "是否开通微信扫商品功能",
        'open_pay' => "是否开通微信支付功能",
        'open_card' => "是否开通微信卡券功能",
        'open_shake' => "是否开通微信摇一摇功能",
    );

    // 站点ID
    private $site_id;

    public function __construct($site_id = 0)
    {
        $this->site_id = $site_id;
        //微信小程序配置
        $weapp_config_model = new WeappConfigModel();
        $weapp_config = $weapp_config_model->getWeappConfig($site_id)[ "data" ][ "value" ];

        if (isset($weapp_config[ 'is_authopen' ]) && addon_is_exit('wxoplatform')) {
            $plateform_config_model = new WxOplatformConfigModel();
            $plateform_config = $plateform_config_model->getOplatformConfig()[ "data" ][ "value" ];

            $config = [
                'app_id' => $plateform_config[ "appid" ] ?? '',
                'secret' => $plateform_config[ "secret" ] ?? '',
                'token' => $plateform_config[ "token" ] ?? '',
                'aes_key' => $plateform_config[ "aes_key" ] ?? '',
                'log' => [
                    'level' => 'debug',
                    'permission' => 0777,
                    'file' => 'runtime/log/wechat/oplatform.logs',
                ],
            ];
            $open_platform = Factory::openPlatform($config);
            $this->app = $open_platform->miniProgram($weapp_config[ 'authorizer_appid' ], $weapp_config[ 'authorizer_refresh_token' ]);
        } else {
            $config = [
                'app_id' => $weapp_config[ "appid" ] ?? '',
                'secret' => $weapp_config[ "appsecret" ] ?? '',
                'response_type' => 'array',
                'log' => [
                    'level' => 'debug',
                    'permission' => 0777,
                    'file' => 'runtime/log/wechat/easywechat.logs',
                ],
            ];
            if(!empty($weapp_config[ "appid" ]) && !empty($weapp_config[ "appsecret" ])){
                $this->app = Factory::miniProgram($config);
            }
        }
    }

    /**
     * TODO
     * 根据 jsCode 获取用户 session 信息
     * @param $param
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function authCodeToOpenid($param)
    {
        try {
            $result = $this->app->auth->session($param[ 'code' ]);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->handleError($result[ 'errcode' ], $result[ 'errmsg' ]);
            } else {
                Cache::set('weapp_' . $result[ 'openid' ], $result);
                unset($result[ 'session_key' ]);
                return $this->success($result);
            }
        } catch (\Exception $e) {
            if (property_exists($e, 'formattedResponse')) {
                return $this->handleError($e->formattedResponse[ 'errcode' ], $e->formattedResponse[ 'errmsg' ]);
            }
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 生成二维码
     * @param unknown $param
     */
    public function createQrcode($param)
    {
        try {
            $checkpath_result = $this->checkPath($param[ 'qrcode_path' ]);
            if ($checkpath_result[ "code" ] != 0) return $checkpath_result;

            // scene:场景值最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~
            $scene = '';
            if (!empty($param[ 'data' ])) {
                foreach ($param[ 'data' ] as $key => $value) {
                    //防止参数过长，source_member用m代替
                    if ($key == 'source_member') {
                        $key = 'm';
                    }
                    if ($scene == '') $scene .= $key . '-' . $value;
                    else $scene .= '&' . $key . '-' . $value;
                }
            }
            $response = $this->app->app_code->getUnlimit($scene, [
                'page' => substr($param[ 'page' ], 1),
                'width' => $param['width'] ?? 120
            ]);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $filename = $param[ 'qrcode_path' ] . '/';
                $filename .= $response->saveAs($param[ 'qrcode_path' ], $param[ 'qrcode_name' ] . '_' . $param[ 'app_type' ] . '.png');
                return $this->success([ 'type' => 'weapp', 'path' => $filename ]);
            } else {
                return $this->handleError($response[ 'errcode' ], $response[ 'errmsg' ]);
            }
        } catch (\Exception $e) {
            if (property_exists($e, 'formattedResponse')) {
                return $this->handleError($e->formattedResponse[ 'errcode' ], $e->formattedResponse[ 'errmsg' ]);
            }
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 校验目录是否可写
     * @param unknown $path
     * @return multitype:number unknown |multitype:unknown
     */
    private function checkPath($path)
    {
        if (is_dir($path) || mkdir($path, intval('0755', 8), true)) {
            return $this->success();
        }
        return $this->error('', "directory {$path} creation failed");
    }
    /*************************************************************  数据统计与分析 start **************************************************************/

    /**
     * 访问日趋势
     * @param $from  格式 20170313
     * @param $to 格式 20170313
     */
    public function dailyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->dailyVisitTrend($from, $to);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }
            return $this->success($result[ "list" ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }

    }

    /**
     * 访问周趋势
     * @param $from
     * @param $to
     * @return array
     */
    public function weeklyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->weeklyVisitTrend($from, $to);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }
            return $this->success($result[ "list" ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问月趋势
     * @param $from
     * @param $to
     * @return array
     */
    public function monthlyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->monthlyVisitTrend($from, $to);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }
            return $this->success($result[ "list" ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问分布
     * @param $from
     * @param $to
     * @return array
     */
    public function visitDistribution($from, $to)
    {
        try {
            $result = $this->app->data_cube->visitDistribution($from, $to);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error($result, $result[ "errmsg" ]);
            }
            return $this->success($result[ "list" ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问页面
     * @param $from
     * @param $to
     * @return array
     */
    public function visitPage($from, $to)
    {
        try {
            $result = $this->app->data_cube->visitPage($from, $to);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }
            return $this->success($result[ "list" ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }
    /*************************************************************  数据统计与分析 end **************************************************************/

    /**
     * 下载小程序代码包
     * @param $site_id
     */
    public function download($site_id)
    {
        $source_file_path = $this->createTempPackage($site_id, 'public/weapp');
        $file_arr = getFileMap($source_file_path);

        if (!empty($file_arr)) {
            $zipname = 'upload/weapp_' . $site_id . '_' . date('Ymd') . '.zip';

            $zip = new \ZipArchive();
            $res = $zip->open($zipname, \ZipArchive::CREATE);
            if ($res === TRUE) {
                foreach ($file_arr as $file_path => $file_name) {
                    if (is_dir($file_path)) {
                        $file_path = str_replace($source_file_path . '/', '', $file_path);
                        $zip->addEmptyDir($file_path);
                    } else {
                        $zip_path = str_replace($source_file_path . '/', '', $file_path);
                        $zip->addFile($file_path, $zip_path);
                    }
                }
                $zip->close();

                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($zipname));
                header("Content-Disposition: attachment; filename=\"" . basename($zipname) . "\"");
                readfile($zipname);
                @unlink($zipname);
                deleteDir($source_file_path);
            }
        }
    }

    /**
     * 创建临时包
     * @param $site_id
     * @param $package_path
     * @param string $to_path
     * @return string
     */
    private function createTempPackage($site_id, $package_path, $to_path = '')
    {
        if (is_dir($package_path)) {
            $package = scandir($package_path);

            if (empty($to_path)) {
                $to_path = 'upload/temp/' . $site_id . '/';
                dir_mkdir($to_path);
            }

            foreach ($package as $path) {
                $temp_path = $package_path . '/' . $path;
                if (is_dir($temp_path)) {
                    if ($path == '.' || $path == '..') {//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                        continue;
                    }
                    dir_mkdir($to_path . $path);
                    $this->createTempPackage($site_id, $temp_path, $to_path . $path . '/');
                } else {
                    if (file_exists($temp_path)) {
                        copy($temp_path, $to_path . $path);
                        if (stristr($temp_path, 'common/vendor.js')) {
                            $content = file_get_contents($to_path . $path);
                            $content = $this->paramReplace($site_id, $content);
                            file_put_contents($to_path . $path, $content);
                        }
                    }
                }
            }
            return $to_path;
        }
    }

    /**
     * 参数替换
     * @param $site_id
     * @param $string
     * @return null|string|string[]
     */
    private function paramReplace($site_id, $string)
    {
        $api_model = new Api();
        $api_config = $api_model->getApiConfig()[ 'data' ];

        $web_config_model = new WebConfig();
        $web_config = $web_config_model->getMapConfig()[ 'data' ][ 'value' ];

        $socket_url = ( strstr(ROOT_URL, 'https://') === false ? str_replace('http', 'ws', ROOT_URL) : str_replace('https', 'wss', ROOT_URL) ) . '/wss';

        $patterns = [
            '/\{\{\$baseUrl\}\}/',
            '/\{\{\$imgDomain\}\}/',
            '/\{\{\$h5Domain\}\}/',
            '/\{\{\$mpKey\}\}/',
            '/\{\{\$apiSecurity\}\}/',
            '/\{\{\$publicKey\}\}/',
            '/\{\{\$webSocket\}\}/'
        ];
        $replacements = [
            ROOT_URL,
            ROOT_URL,
            ROOT_URL . '/h5',
            $web_config[ 'tencent_map_key' ] ?? '',
            $api_config[ 'is_use' ] ?? 0,
            $api_config[ 'value' ][ 'public_key' ] ?? '',
            $socket_url
        ];
        $string = preg_replace($patterns, $replacements, $string);
        return $string;
    }

    /**
     * 消息解密
     * @param array $param
     */
    public function decryptData($param = [])
    {
        try {
            $cache = Cache::get('weapp_' . $param[ 'weapp_openid' ]);
            $session_key = $cache[ 'session_key' ] ?? '';
            $result = $this->app->encryptor->decryptData($session_key, $param[ 'iv' ], $param[ 'encryptedData' ]);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->handleError($result[ 'errcode' ], $result[ 'errmsg' ]);
            }
            return $this->success($result);
        } catch (\Exception $e) {
            if (property_exists($e, 'formattedResponse')) {
                return $this->handleError($e->formattedResponse[ 'errcode' ], $e->formattedResponse[ 'errmsg' ]);
            }
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 获取用户手机号
     * @param $code
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserPhoneNumber($code)
    {
        try {
            $result = $this->app->auth->phoneNumber($code);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->handleError($result[ 'errcode' ], $result[ 'errmsg' ]);
            }
            return $this->success($result[ 'phone_info' ]);
        } catch (\Exception $e) {
            if (property_exists($e, 'formattedResponse')) {
                return $this->handleError($e->formattedResponse[ 'errcode' ], $e->formattedResponse[ 'errmsg' ]);
            }
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 获取订阅消息template_id
     * @param array $param
     */
    public function getTemplateId(array $param)
    {
        try {
            $result = $this->app->subscribe_message->addTemplate($param[ 'tid' ], $param[ 'kidList' ], $param[ 'sceneDesc' ]);
            return $result;
        } catch (\Exception $e) {
            return [ 'errcode' => -1, 'errmsg' => $e->getMessage() ];
        }
    }

    /**
     * 发送订阅消息
     * @param array $param
     * @return array
     */
    public function sendTemplateMessage(array $param)
    {
        $result = $this->app->subscribe_message->send([
            'template_id' => $param[ 'template_id' ],// 模板id
            'touser' => $param[ 'openid' ], // openid
            'page' => $param[ 'page' ], // 点击模板卡片后的跳转页面 支持带参数
            'data' => $param[ 'data' ] // 模板变量
        ]);

        if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
            return $this->error($result, $result[ "errmsg" ]);
        }
        return $this->success($result);
    }

    /**
     * 消息推送
     */
    public function relateWeixin()
    {
        $server = $this->app->server;
        $message = $server->getMessage();
        Log::write('微信小程序消息推送：' . json_encode($message));
        if (isset($message[ 'MsgType' ])) {
            switch ( $message[ 'MsgType' ] ) {
                case 'event':
                    $this->app->server->push(function($res) {
                        // 商品审核结果通知
                        if ($res[ 'Event' ] == 'open_product_spu_audit' && addon_is_exit('shopcomponent', $this->site_id)) {
                            model('shopcompoent_goods')->update([
                                'edit_status' => $res[ 'OpenProductSpuAudit' ][ 'status' ],
                                'reject_reason' => !empty($res[ 'OpenProductSpuAudit' ][ 'reject_reason' ]) ? : '',
                                'audit_time' => time()
                            ], [
                                [ 'out_product_id', '=', $res[ 'OpenProductSpuAudit' ][ 'out_product_id' ] ]
                            ]);
                        }
                        // 类目审核结果通知
                        if ($res[ 'Event' ] == 'open_product_category_audit' && addon_is_exit('shopcomponent', $this->site_id)) {
                            model('shopcompoent_category_audit')->update([
                                'status' => $res[ 'QualificationAuditResult' ][ 'status' ],
                                'reject_reason' => !empty($res[ 'QualificationAuditResult' ][ 'reject_reason' ]) ? : '',
                                'audit_time' => time()
                            ], [
                                [ 'audit_id', '=', $res[ 'QualificationAuditResult' ][ 'audit_id' ] ]
                            ]);
                        }

                        // 视频号支付订单回调
                        if ($res[ 'Event' ] == 'open_product_order_pay' && addon_is_exit('shopcomponent', $this->site_id)) {
                            event("shopcomponentNotify", $res);
                        }

                        // todo trade_manage_remind_access_api  提醒接入发货信息管理服务API
                        if ($res[ 'Event' ] == 'trade_manage_remind_access_api' && addon_is_exit('weapp', $this->site_id)) {
                            Log::write('提醒接入发货信息管理服务API（trade_manage_remind_access_api）：' . json_encode($res));
                        }

                        // todo trade_manage_remind_shipping    提醒需要上传发货信息
                        if ($res[ 'Event' ] == 'trade_manage_remind_shipping' && addon_is_exit('weapp', $this->site_id)) {
                            Log::write('提醒需要上传发货信息（trade_manage_remind_shipping）：' . json_encode($res));
                        }

                        // todo trade_manage_order_settlement   订单将要结算或已经结算
                        if ($res[ 'Event' ] == 'trade_manage_order_settlement' && addon_is_exit('weapp', $this->site_id)) {
                            Log::write('订单将要结算或已经结算（trade_manage_order_settlement）：' . json_encode($res));
                        }
                    });
                    break;
            }
        }
        $response = $this->app->server->serve();
        return $response->send();
    }

    /**
     * 检查场景值是否在支付校验范围内
     * @param $scene
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sceneCheck($scene)
    {
        try {
            $result = $this->app->mini_store->checkScene($scene);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] == 0) {
                return $this->success($result[ 'is_matched' ]);
            } else {
                return $this->error('', $result[ 'errmsg' ]);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    public function createOrder($order_info)
    {
        try {
            $result = $this->app->mini_store->addOrder($order_info);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] == 0) {
                return $this->success($result[ 'is_matched' ]);
            } else {
                return $this->error('', $result[ 'errmsg' ]);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 处理错误信息
     * @param $errcode
     * @param string $message
     * @return array
     */
    public function handleError($errcode, $message = '')
    {
        $error = require 'addon/weapp/config/weapp_error.php';
        return $this->error([], $error[ $errcode ] ?? $message);
    }

    //物流模式，发货方式枚举值
    //1、实体物流配送采用快递公司进行实体物流配送形式
    //2、同城配送
    //3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式
    //4、用户自提
    const LOGISTICS_TYPE_EXPRESS = 1;
    const LOGISTICS_TYPE_LOCAL = 2;
    const LOGISTICS_TYPE_VIRTUAL = 3;
    const LOGISTICS_TYPE_STORE = 4;

    //发货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货）
    const UNIFIED_DELIVERY = 1;
    const SPLIT_DELIVERY = 2;

    /**
     * 查询小程序是否已开通发货信息管理服务
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingIsTradeManaged()
    {
        try {
            $result = $this->app->order_shipping->isTradeManaged();
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error(false, $result[ "errmsg" ]);
            }
            return $this->success($result[ 'is_trade_managed' ]);
        } catch (\Exception $e) {
            return $this->error(false, $e->getMessage());
        }
    }

    /**
     * 查询订单列表
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingGetOrderList($data = [])
    {
        try {
            $params = [
                'pay_time_range' => [
                    'begin_time' => $data[ 'begin_time' ] ?? 0, // 起始时间，时间戳形式，不填则视为从0开始。
                    'end_time' => $data[ 'end_time' ] ?? time() // 结束时间（含），时间戳形式，不填则视为32位无符号整型的最大值。
                ],
                'page_size' => $data[ 'page_size' ] ?? 100 // 翻页时使用，返回列表的长度，默认为100。
            ];

            // 订单状态枚举：(1) 待发货；(2) 已发货；(3) 确认收货；(4) 交易完成；(5) 已退款。
            if (!empty($data[ 'order_state' ])) {
                $params[ 'order_state' ] = $data[ 'order_state' ];
            }

            // 翻页时使用，获取第一页时不用传入，如果查询结果中 has_more 字段为 true，则传入该次查询结果中返回的 last_index 字段可获取下一页。
            if (!empty($data[ 'last_index' ])) {
                $params[ 'last_index' ] = $data[ 'last_index' ];
            }

            $result = $this->app->order_shipping->getOrderList($params);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 查询订单发货状态
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingGetOrder($data = [])
    {
        try {
            $params = [];

            // 原支付交易对应的微信订单号
            if (!empty($data[ 'transaction_id' ])) {
                $params[ 'transaction_id' ] = $data[ 'transaction_id' ];
            }

            // 支付下单商户的商户号，由微信支付生成并下发
            if (!empty($data[ 'merchant_id' ])) {
                $params[ 'merchant_id' ] = $data[ 'merchant_id' ];
            }

            // 商户系统内部订单号，只能是数字、大小写字母`_-*`且在同一个商户号下唯一
            if (!empty($data[ 'merchant_trade_no' ])) {
                $params[ 'merchant_trade_no' ] = $data[ 'merchant_trade_no' ];
            }

            // 二级商户号
            if (!empty($data[ 'sub_merchant_id' ])) {
                $params[ 'sub_merchant_id' ] = $data[ 'sub_merchant_id' ];
            }

            $result = $this->app->order_shipping->getOrder($params);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }

            return $this->success($result[ 'order' ]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 设置消息跳转路径
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setMsgJumpPath()
    {
        $config = new ConfigModel();
        $config_condition = [['site_id','=',0],['app_module','=','shop'],['config_key','=','WEAPP_ORDER_SHIPPINT_CONFIG']];
        $res = $config->getConfig($config_condition);
        $config_data = $res['data']['value'];
        $jump_path = 'pages_tool/weapp/order_shipping';
        if(empty($config_data['jump_path']) || $config_data['jump_path'] != $jump_path){
            $result = $this->app->order_shipping->setMsgJumpPath($jump_path);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error($result, 'setMsgJumpPathError:'.$result[ "errmsg" ]);
            }
            $config_data['jump_path'] = $jump_path;
            $config->setConfig($config_data, '小程序发货配置', 1,  $config_condition);
        }
        return $this->success();
    }

    /**
     * 发货信息录入接口，自备数据
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingUploadShippingInfo($data = [])
    {
        try {
            //设置消息跳转路径
            $result = $this->setMsgJumpPath();
            if($result['code'] < 0) return $result;

            $pay_config = ( new WechatPayModel() )->getPayConfig($data[ 'site_id' ])[ 'data' ][ 'value' ];
            $params = [
                'order_key' => [
                    'order_number_type' => 1, // 订单单号类型，用于确认需要上传详情的订单。枚举值1，使用下单商户号和商户侧单号；枚举值2，使用微信支付单号。
                    'mchid' => $pay_config[ 'mch_id' ], // 支付下单商户的商户号，由微信支付生成并下发。
                    'out_trade_no' => $data[ 'out_trade_no' ] // 商户系统内部订单号，只能是数字、大小写字母`_-*`且在同一个商户号下唯一
                ],
                'logistics_type' => $data[ 'logistics_type' ], // 物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
                'delivery_mode' => $data[ 'delivery_mode' ], // 发货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货） 示例值: UNIFIED_DELIVERY
                // 同城配送没有物流信息，只能传一个订单
                'shipping_list' => $data[ 'shipping_list' ], // 物流信息列表，发货物流单列表，支持统一发货（单个物流单）和分拆发货（多个物流单）两种模式，多重性: [1, 10]
                'upload_time' => date("c", time()), // 上传时间，用于标识请求的先后顺序 示例值: `2022-12-15T13:29:35.120+08:00`
                'payer' => [
                    'openid' => $data[ 'weapp_openid' ] // 用户标识，用户在小程序appid下的唯一标识。 下单前需获取到用户的Openid 示例值: oUpF8uMuAJO_M2pxb1Q9zNjWeS6o 字符字节限制: [1, 128]
                ],
                'is_all_delivered' => $data[ 'is_all_delivered' ] // 分拆发货模式时必填，用于标识分拆发货模式下是否已全部发货完成，只有全部发货完成的情况下才会向用户推送发货完成通知。示例值: true/false
            ];

            Log::write('发货信息录入接口，自备数据（参数）：' . json_encode($params, JSON_UNESCAPED_UNICODE));
            $result = $this->app->order_shipping->uploadShippingInfo($params);
            Log::write('发货信息录入接口，自备数据（结果）：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error($result, 'uploadShippingInfoError:'.$result[ "errmsg" ]);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage() . ",File：" . $e->getFile() . "，line：" . $e->getLine());
        }
    }

    /**
     * todo【暂时没有用到】发货信息合单录入接口
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingUploadCombinedShippingInfo($data = [])
    {
        try {
            $params = [
                'order_key' => [
                    'order_number_type' => $data[ 'order_number_type' ] ?? 1, // 订单单号类型，用于确认需要上传详情的订单。枚举值1，使用下单商户号和商户侧单号；枚举值2，使用微信支付单号。
                ],
                'logistics_type' => $data[ 'logistics_type' ], // 物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
                'delivery_mode' => $data[ 'delivery_mode' ], // 发货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货） 示例值: UNIFIED_DELIVERY
                'sub_orders' => $data[ 'sub_orders' ], // 子单物流详情
                'upload_time' => $data[ 'upload_time' ], // 上传时间，用于标识请求的先后顺序 示例值: `2022-12-15T13:29:35.120+08:00`
                'payer' => [
                    'openid' => $data[ 'openid' ] // 用户标识，用户在小程序appid下的唯一标识。 下单前需获取到用户的Openid 示例值: oUpF8uMuAJO_M2pxb1Q9zNjWeS6o 字符字节限制: [1, 128]
                ]
            ];

            // 原支付交易对应的微信订单号
            if (!empty($data[ 'transaction_id' ])) {
                $params[ 'order_key' ][ 'transaction_id' ] = $data[ 'transaction_id' ];
            }

            // 支付下单商户的商户号，由微信支付生成并下发。
            if (!empty($data[ 'mchid' ])) {
                $params[ 'order_key' ][ 'mchid' ] = $data[ 'mchid' ];
            }

            // 商户系统内部订单号，只能是数字、大小写字母`_-*`且在同一个商户号下唯一
            if (!empty($data[ 'out_trade_no' ])) {
                $params[ 'order_key' ][ 'out_trade_no' ] = $data[ 'out_trade_no' ];
            }

            $result = $this->app->order_shipping->uploadCombinedShippingInfo($params);
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 确认收货提醒接口
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingNotifyConfirmReceive($data = [])
    {
        try {
            $pay_config = ( new WechatPayModel() )->getPayConfig($data[ 'site_id' ])[ 'data' ][ 'value' ];

            $params = [
                'merchant_id' => $pay_config[ 'mch_id' ], // 支付下单商户的商户号，由微信支付生成并下发
                'merchant_trade_no' => $data[ 'out_trade_no' ], // 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
                'received_time' => time() // 快递签收时间，时间戳形式
            ];

            Log::write('确认收货提醒接口（参数）：' . json_encode($params));
            $result = $this->app->order_shipping->notifyConfirmReceive($params);
            Log::write('确认收货提醒接口（结果）：' . json_encode($result));
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                Log::write('微信小程序确认收货提醒接口报错：' . json_encode($result));
                return $this->success([], $result[ "errmsg" ]);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 获取物流公司，运力id列表get_delivery_list
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderShippingGetDeliveryList()
    {
        $cache = Cache::get('orderShippingGetDeliveryList');
        if ($cache) return $cache;

        try {
            $result = $this->app->order_shipping->getDeliveryList();
            if (isset($result[ 'errcode' ]) && $result[ 'errcode' ] != 0) {
                return $this->error([], $result[ "errmsg" ]);
            }

            $data = $this->success($result[ 'delivery_list' ]);
            Cache::set('orderShippingGetDeliveryList', $data);
            return $data;
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 处理订单发货物品说明
     * @param $item_desc
     * @return string
     */
    public function handleOrderShippingItemDesc($item_desc)
    {
        $max_len = 90;
        $item_desc = join(';',$item_desc);
        $is_out_len = mb_strlen($item_desc) > $max_len;
        $item_desc = mb_substr($item_desc, 0, $max_len, 'UTF-8');
        if($is_out_len){
            $item_desc .= '...';
        }
        return $item_desc;
    }

}
