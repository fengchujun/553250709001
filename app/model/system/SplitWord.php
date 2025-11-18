<?php

namespace app\model\system;
use app\model\BaseModel;
use extend\BaiDuApi;
use think\facade\Log;

class SplitWord extends BaseModel
{
     protected $table = 'split_word';

    /**
     * 获取拆词
     * @param $keyword
     * @return array
     */
     public function getSplitWord($keyword): array
     {
          $result = $this->getInfo([['keyword','=',$keyword]]);
          if($result['code'] == 0 && !empty($result['data']['result'])){
              return $this->success(json_decode($result['data']['result']));
          }

          $config_model = new \app\model\web\Config();
          $config = $config_model->getSplitWordConfig()['data']['value'];
          if($config['is_open'] == 0){
              return $this->success([$keyword]);
          }
          $result = (new BaiduApi($config))->splitWords($keyword);
          if (!empty($result)){
              $this->addSplitWord($keyword,$result);
              return $this->success($result);
          }
          return $this->success([$keyword]);
     }


     public function getInfo($condition,$field = '*'): array
     {
         $result = model($this->table)->getInfo($condition,$field);
         if(empty($result)){
             return $this->error('暂无数据');
         }
         return $this->success($result);
     }

     public function addSplitWord($keyword,$result): array
     {
         $result = model($this->table)->add([
             'keyword' => $keyword,
             'result' => json_encode($result,JSON_UNESCAPED_UNICODE),
             'add_time'=>date('Y-m-d H:i:s')
         ]);
         if(empty($result)){
             return $this->error('添加失败');
         }
         return $this->success($result);
     }

}