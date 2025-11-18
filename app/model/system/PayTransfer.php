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

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 系统转账业务
 */
class PayTransfer extends BaseModel
{
    const STATUS_WAIT = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;

    /**
     * 添加
     * @param $param
     * @return array|mixed|string|void
     */
    public function add($param)
    {
        $data = [
            'out_trade_no' => $this->createOutTradeNo(),
            'real_name' => $param[ 'real_name' ],
            'amount' => $param[ 'amount' ],
            'desc' => $param['desc'],
            'transfer_type' => $param['transfer_type'],
            'account_number' => $param['account_number'],
            'site_id' => $param['site_id'],
            'is_weapp' => $param['is_weapp'],
            'member_id' => $param['member_id'],
            'from_type' => $param['from_type'],
            'status' => self::STATUS_WAIT,
            'create_time' => time(),
            'relate_tag' => $param['relate_tag'] ?? ''
        ];
        $id = model('pay_transfer')->add($data);
        return $this->success($id);
    }

    /**
     * 发起转账
     * @param $from_type
     * @param $relate_tag
     * @return array|mixed|string|void
     */
    public function transfer($from_type, $relate_tag,$transfer_check=false)
    {
        $info = model('pay_transfer')->getInfo([['from_type', '=', $from_type], ['relate_tag', '=', $relate_tag]]);
        if(empty($info)){
            return $this->error($info, '转账记录不存在');
        }

        //各自业务检测
        if($transfer_check){
            $check_res = event('PayTransferCheck', $info, true);
            if(isset($check_res['code']) && $check_res['code'] < 0){
                return $check_res;
            }
        }

        $result = event('PayTransfer', $info, true);
        if($result['code'] < 0){
            return $result;
        }

        $result = $this->updateStatus($result['data'], $info['id']);
        return $result;
    }

    /**
     * 更新状态
     * @param $result
     * @param $id
     * @return array|mixed|string|void
     */
    public function updateStatus($data, $id)
    {
        $status = $data['status'] ?? self::STATUS_SUCCESS;
        model('pay_transfer')->update([
            'status' => $status,
            'resp_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'update_time' => time(),
        ], [['id', '=', $id],['status', 'in', [self::STATUS_WAIT, self::STATUS_IN_PROCESS]]]);

        if($status == self::STATUS_WAIT){
            return $this->success($data);
        }
        if($status == self::STATUS_IN_PROCESS){
            (new Cron())->addCron(1, 0, "查询转账结果", "CronPayTransferResult", time() + 10, $id);
        }
        $info = model('pay_transfer')->getInfo([['id', '=', $id]]);
        $res = event('PayTransferNotify', $info, true);
        return $res;
    }

    /**
     * 创建支付流水号
     */
    public function createOutTradeNo($member_id = 0)
    {
        $cache = Cache::get('pay_transfer_out_trade_no' . $member_id . time());
        if (empty($cache)) {
            Cache::set('pay_transfer_out_trade_no' . $member_id . time(), 1000);
            $cache = Cache::get('pay_transfer_out_trade_no' . $member_id . time());
        } else {
            $cache = $cache + 1;
            Cache::set('pay_transfer_out_trade_no' . $member_id . time(), $cache);
        }
        $no = time() . rand(1000, 9999) . $member_id . $cache;
        return $no;
    }

    /**
     * 查询支付结果
     * @param $id
     * @return array|mixed|string|void
     */
    public function result($id)
    {
        $info = model('pay_transfer')->getInfo([['id', '=', $id]]);
        if(empty($info)){
            return $this->error($info, '转账信息有误');
        }

        if($info['status'] != self::STATUS_IN_PROCESS){
            return $this->error($info, '非转账中单据无需处理');
        }

        $result = event('PayTransferResult', $info, true);
        if(!isset($result['code']) || $result['code'] < 0){
            return $result;
        }

        return $this->updateStatus($result['data'], $id);
    }


    /**
     * 修改
     * @param $data
     * @param $where
     * @return array
     */
     public function editTransfer($data,$where)
     {
         $result = model("pay_transfer")->update($data,$where);
         return $this->success($result);
     }


    /**
     * 获取单条结果集
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getTransferInfo($condition,$field="*")
    {
        $result = model("pay_transfer")->getInfo($condition,$field);
        return $this->success($result);
    }
}