<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use app\model\web\Config as ConfigModel;
use extend\QRcode as QRcodeExtend;

/**
 * 二维码生成类
 */
class Qrcode extends BaseModel
{
    public function createQrcode(array $param)
    {
        try {
            $checkpath_result = $this->checkPath($param[ 'qrcode_path' ]);
            if ($checkpath_result[ "code" ] != 0) return $checkpath_result;

            $urlParam = '';
            if (!empty($param[ 'data' ])) {
                foreach ($param[ 'data' ] as $key => $value) {
                    if ($urlParam == '') $urlParam .= '?' . $key . '=' . $value;
                    else $urlParam .= '&' . $key . '=' . $value;
                }
            }

            $domain = getH5Domain();
            if ($param[ 'app_type' ] == 'pc') {
                $config_model = new ConfigModel();
                $domain = $config_model->getPcDomainName()[ 'data' ][ 'value' ][ 'domain_name_pc' ];
            }

            $url = $domain . $param[ 'page' ] . $urlParam;

            $filename = $param[ 'qrcode_path' ] . '/' . $param[ 'qrcode_name' ] . '_' . $param[ 'app_type' ] . '.png';

            if ($param[ 'type' ] == 'create') {
                delFile($filename);
            }

            QRcodeExtend::png($url, $filename, 'L', $param[ 'qrcode_size' ] ?? 16, 1);
            return $this->success([ 'type' => 'h5', 'path' => $filename, 'url' => $url ]);
        } catch (\Exception $e) {
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

    /**
     * 生成base64格式二维码
     * @param $text
     * @return array
     */
    public function createBase64Qrcode($text)
    {
        ob_start();
        QRcodeExtend::png($text, false, 'L', 4, 1);
        $image_string = base64_encode(ob_get_contents());
        ob_end_clean();
        $base_64 = "data:image/png;base64,".$image_string;
        return $this->success($base_64);
    }
}