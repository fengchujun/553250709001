<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\qiniu\model;

use app\model\BaseModel;
// 引入鉴权类
use \Qiniu\Auth;
// 引入上传类
use Qiniu\Storage\BucketManager;
use \Qiniu\Storage\UploadManager;

/**
 * 七牛云上传
 */
class Qiniu extends BaseModel
{

    /**
     * 字节组上传
     * @param $data
     * @param $key
     * @return array
     */
    public function put($param)
    {
        $data = $param[ "data" ];
        $key = $param[ "key" ];
        $config_model = new Config();
        $config_result = $config_model->getQiniuConfig();
        $config = $config_result[ "data" ];

        if ($config[ "is_use" ] == 1) {
            $config = $config[ "value" ];
            $accessKey = $config[ "access_key" ];
            $secretKey = $config[ "secret_key" ];
            $bucket = $config[ "bucket" ];
            $auth = new Auth($accessKey, $secretKey);
            $token = $auth->uploadToken($bucket);
            ( new BucketManager($auth) )->delete($bucket, $key);
            $uploadMgr = new UploadManager();
            //----------------------------------------upload demo1 ----------------------------------------
            // 上传字符串到七牛
            list($ret, $err) = $uploadMgr->put($token, $key, $data);
            if ($err !== null) {
                return $this->error('', $err->getResponse()->error);
            } else {
                //返回图片的完整URL
                $domain = $config[ "domain" ];//自定义域名
                $data = array (
                    "path" => $domain . "/" . $key,
                    "domain" => $domain,
                    "bucket" => $bucket
                );
                return $this->success($data);
            }
        }
    }

    /**
     * 设置七牛参数配置
     * @param unknown $filePath 上传图片路径
     * @param unknown $key 上传到七牛后保存的文件名
     */
    public function putFile($param)
    {
        $file_path = $param[ "file_path" ];
        $key = $param[ "key" ];
        $config_model = new Config();
        $config = $config_model->getQiniuConfig()[ "data" ];
        if ($config[ "is_use" ] == 1) {
            $config = $config[ "value" ];
            $accessKey = $config[ "access_key" ];
            $secretKey = $config[ "secret_key" ];
            $bucket = $config[ "bucket" ];
            $auth = new Auth($accessKey, $secretKey);
            ( new BucketManager($auth) )->delete($bucket, $key);
            //要上传的空间
            $token = $auth->uploadToken($bucket);
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $file_path);
            if ($err !== null) {
                return $this->error('', $err->getResponse()->error);
            } else {
                //返回图片的完整URL
                $domain = $config[ "domain" ];//自定义域名
                $data = array (
                    "path" => $domain . "/" . $key,
                    "domain" => $domain,
                    "bucket" => $bucket
                );
                return $this->success($data);
            }
        }
    }

    /**
     * @param $file_path
     * 删除七牛云图片
     */
    public function deleteAlbumPic($file_path, $prefix)
    {
        $config_model = new Config();
        $config_result = $config_model->getQiniuConfig();
        $config = $config_result[ "data" ];
        if (!empty($config)) {
            $config = $config[ "value" ];
            $accessKey = $config[ "access_key" ];
            $secretKey = $config[ "secret_key" ];
            $bucket = $config[ "bucket" ];

            $auth = new Auth($accessKey, $secretKey);
//            $prefix    = substr($file_path,0,strripos($file_path, "/"));
//            dump(str_replace($prefix."/", "",$file_path));
            ( new BucketManager($auth) )->delete($bucket, str_replace($prefix . "/", "", $file_path));
            ( new BucketManager($auth) )->delete($bucket, str_replace($prefix . "/", "", img($file_path, 'big')));
            ( new BucketManager($auth) )->delete($bucket, str_replace($prefix . "/", "", img($file_path, 'mid')));
            ( new BucketManager($auth) )->delete($bucket, str_replace($prefix . "/", "", img($file_path, 'small')));
        }
    }

}