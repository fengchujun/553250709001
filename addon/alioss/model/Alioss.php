<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\alioss\model;

use app\model\BaseModel;
use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 阿里云OSS上传
 */
class Alioss extends BaseModel
{

    /**
     * 字节组上传
     * @param $data
     * @param $key
     * @return array
     */
    public function put($param)
    {
        $data = $param['data'];
        $key = $param['key'];
        $config_model = new Config();
        $config_result = $config_model->getAliossConfig();
        $config = $config_result['data'];

        if ($config['is_use'] == 1) {
            $config = $config['value'];
            $access_key_id = $config['access_key_id'];
            $access_key_secret = $config['access_key_secret'];
            $bucket = $config['bucket'];
            $endpoint = $config['endpoint'];
            try {
                $ossClient = new OssClient($access_key_id, $access_key_secret, $endpoint);

                $result = $ossClient->putObject($bucket, $key, $data);
                $is_domain = $config[ 'is_domain' ] ?? 0;
                $path = $is_domain > 0 ? $config[ 'domain' ] . '/' . $key : $result['info']['url'];
                $data = array (
                    'path' => $path,
//                    "path" => $result["info"]["url"],
                    'domain' => $endpoint,
                    'bucket' => $bucket
                );
                return $this->success($data);
            } catch (OssException $e) {
                return $this->error('', $e->getErrorMessage());
            }

        }
    }

    /**
     * 设置阿里云OSS参数配置
     * @param unknown $filePath 上传图片路径
     * @param unknown $key 上传到阿里云后保存的文件名
     */
    public function putFile($param)
    {
        $file_path = $param['file_path'];
        $key = $param['key'];
        $config_model = new Config();
        $config = $config_model->getAliossConfig()['data'];
        if ($config['is_use'] == 1) {
            $config = $config['value'];
            $access_key_id = $config['access_key_id'];
            $access_key_secret = $config['access_key_secret'];
            $bucket = $config['bucket'];
            //要上传的空间
            $endpoint = $config['endpoint'];
            try {
                $ossClient = new OssClient($access_key_id, $access_key_secret, $endpoint);
                $result = $ossClient->uploadFile($bucket, $key, $file_path);
                $is_domain = $config[ 'is_domain' ] ?? 0;
                $path = $is_domain > 0 ? $config[ 'domain' ] . '/' . $key : $result['info']['url'];
                $path = str_replace('http://', 'https://', $path);
                //返回图片的完整URL
                $data = array (
//                    "path" => $this->subEndpoint($endpoint, $bucket)."/". $key,
                    'path' => $path,
                    'domain' => $endpoint,
                    'bucket' => $bucket
                );
                return $this->success($data);
            } catch (\Exception $e) {
                return $this->error('', $e->getMessage());
            }
        }
    }

    public function subEndpoint($endpoint, $bucket)
    {
        if (strpos($endpoint, 'http://') === 0) {
            $temp = 'http://';
        } else {
            $temp = 'https://';
        }
        $temp_array = explode($temp, $endpoint);
        return $temp . $bucket . '.' . $temp_array[ 1 ];
    }

    /**
     * @param $file_path
     * @return array
     * 删除阿里云图片
     */
    public function deleteAlbumPic($file_path, $prefix)
    {
        $config_model = new Config();
        $config_result = $config_model->getAliossConfig();
        $config = $config_result['data'];

        if (!empty($config)) {
            $config = $config['value'];
            $access_key_id = $config['access_key_id'];
            $access_key_secret = $config['access_key_secret'];
            $bucket = $config['bucket'];
            //要上传的空间
            $endpoint = $config['endpoint'];
            try {
                $ossClient = new OssClient($access_key_id, $access_key_secret, $endpoint);
                $ossClient->deleteObject($bucket, str_replace($prefix . '/', '', $file_path));
                $ossClient->deleteObject($bucket, str_replace($prefix . '/', '', img($file_path, 'big')));
                $ossClient->deleteObject($bucket, str_replace($prefix . '/', '', img($file_path, 'mid')));
                $ossClient->deleteObject($bucket, str_replace($prefix . '/', '', img($file_path, 'small')));

                return $this->success();
            } catch (OssException $e) {
                return $this->error('', $e->getErrorMessage());
            }
        }

    }

}