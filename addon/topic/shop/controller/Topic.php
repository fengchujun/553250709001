<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\shop\controller;

use app\shop\controller\BaseShop;
use addon\topic\model\Topic as TopicModel;

/**
 * 专题活动
 * @author Administrator
 *
 */
class Topic extends BaseShop
{
    /**
     * 专题活动列表
     */
    public function lists()
    {
        if (request()->isJson()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $topic_name = input('topic_name', '');

            $condition[] = [ 'site_id', '=', $this->site_id ];
            if ($topic_name) {
                $condition[] = [ 'topic_name', 'like', '%' . $topic_name . '%' ];
            }
            $status = input('status', '');
            if ($status !== '') {
                $condition[] = [ 'status', '=', $status ];
            }
            $order = 'create_time desc';
            $field = '*';

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');

            if ($start_time && !$end_time) {
                $condition[] = [ 'end_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'start_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $start_timestamp = date_to_time($start_time);
                $end_timestamp = date_to_time($end_time);
                $sql = "start_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or end_time between {$start_timestamp} and {$end_timestamp}";
                $sql .= " or (start_time <= {$start_timestamp} and end_time >= {$end_timestamp})";
                $condition[] = [ '', 'exp', \think\facade\Db::raw($sql) ];
            }

            $topic_model = new TopicModel();
            $res = $topic_model->getTopicPageList($condition, $page, $page_size, $order, $field);
            return $res;
        } else {
            return $this->fetch("topic/lists");
        }
    }

    /**
     * 添加专题活动
     */
    public function add()
    {
        if (request()->isJson()) {
            $topic_name = input("topic_name", '');
            $start_time = input("start_time", 0);
            $end_time = input("end_time", 0);
            $remark = input("remark", '');
            $topic_adv = input("topic_adv", '');
            $bg_color = input("bg_color", '#ffffff');
            $goods = input("goods", '{}');
            $topic_model = new TopicModel();
            $data = array (
                'site_id' => $this->site_id,
                "topic_name" => $topic_name,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "remark" => $remark,
                "topic_adv" => $topic_adv,
                'bg_color' => $bg_color,
                'goods' => json_decode($goods, true),
                'create_time' => time()
            );
            $res = $topic_model->addTopic($data);
            return $res;
        } else {
            return $this->fetch("topic/add");
        }
    }

    /**
     * 编辑专题活动
     */
    public function edit()
    {
        $topic_id = input("topic_id", '');
        $topic_model = new TopicModel();
        if (request()->isJson()) {
            $topic_name = input("topic_name", '');
            $start_time = input("start_time", 0);
            $end_time = input("end_time", 0);
            $remark = input("remark", '');
            $topic_adv = input("topic_adv", '');
            $bg_color = input("bg_color", '#ffffff');
            $goods = input("goods", '{}');
            $del_id = input('del_id', '');
            $data = array (
                "topic_name" => $topic_name,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "remark" => $remark,
                "topic_adv" => $topic_adv,
                "topic_id" => $topic_id,
                'bg_color' => $bg_color,
                'goods' => json_decode($goods, true),
                'modify_time' => time()
            );
            $res = $topic_model->editTopic($data, $this->site_id, $del_id);
            return $res;
        } else {
            $condition = array (
                [ "topic_id", "=", $topic_id ]
            );
            $topic_info_result = $topic_model->getTopicDetail($condition);
            if (empty($topic_info_result[ 'data' ])) $this->error('未获取到活动数据', href_url('topic://shop/topic/lists'));
            $this->assign("info", $topic_info_result[ "data" ]);
            $this->assign('sku_ids', implode(',', array_column($topic_info_result[ 'data' ][ 'goods_list' ], 'sku_id')));
            return $this->fetch("topic/edit");
        }
    }

    /**
     * 查看专题活动
     */
    public function detail()
    {
        $topic_id = input("topic_id", '');
        $topic_model = new TopicModel();

        $condition = array (
            [ "topic_id", "=", $topic_id ]
        );
        $topic_info_result = $topic_model->getTopicDetail($condition);
        if (empty($topic_info_result[ 'data' ])) $this->error('未获取到活动数据', href_url('topic://shop/topic/lists'));
        $this->assign("info", $topic_info_result[ "data" ]);
        $this->assign('sku_ids', implode(',', array_column($topic_info_result[ 'data' ][ 'goods_list' ], 'sku_id')));
        return $this->fetch("topic/detail");
    }

    /**
     * 删除专题活动
     */
    public function delete()
    {
        $topic_id = input("topic_id", '');
        $topic_model = new TopicModel();
        $res = $topic_model->deleteTopic($topic_id, $this->site_id);
        return $res;
    }

    /**
     * 删除专题活动(批量)
     */
    public function deleteAll(){
        if (request()->isJson()) {
            $topic_id = input("topic_id", '');
            $topic_model = new TopicModel();
            foreach ($topic_id as $k => $v){
                $res = $topic_model->deleteTopic($v, $this->site_id);
            }
            return $res;
        }
    }
}