<?php

namespace extend;


class BaiDuApi
{
    protected $apiKey = '';
    protected $secretKey = '';
    const tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=%s&client_secret=%s';
    const splitUrl = 'https://aip.baidubce.com/rpc/2.0/nlp/v1/lexer?access_token=%s&charset=UTF-8';

    public function __construct($config = [])
    {
        $this->apiKey = $config['apiKey'] ?? '';
        $this->secretKey = $config['secretKey'] ?? '';
    }

    /**
     * 获取token
     */
    public function getAccessToken() {
        $url = sprintf(self::tokenUrl,$this->apiKey,$this->secretKey);
        $data = file_get_contents($url);
        $resData = json_decode($data,true);
        if(!empty($resData['access_token'])) {
            // 成功
            return ['error' => 0,'msg' => '获取数据成功','access_token' => $resData['access_token']];
        }else {
            return $resData;
        }
    }

    public function request($keyword) {
        $data = $this->getAccessToken();
        if($data['error'] == 0) {
            $accessToken = $data['access_token'];
        }else {
            return $data;
        }
        $url = sprintf(self::splitUrl,$accessToken);
        $data = http_url($url,json_encode(['text' => $keyword]),['Content-Type' => 'application/json']);
        return json_decode($data,true);
    }

    /**
     * 获取所有的拆词
     * @param $keyword
     * @return array|mixed|void
     */
    public function splitWords($keyword) {
        $result = $this->request($keyword);
        if(!is_array($result) || empty($result['items'])){
            return [];
        }
        $wordList = [];
        foreach ($result['items'] as $val) {
            foreach ($val['basic_words'] as $v) {
                $wordList[] = $v;
            }
        }
        return $wordList;
    }


}