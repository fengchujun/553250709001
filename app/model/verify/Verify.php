<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\verify;

use addon\cardservice\model\MemberCard;
use addon\weapp\event\DecryptData;
use app\dict\goods\GoodsDict;
use app\model\BaseModel;
use app\model\order\OrderRefund as OrderRefundModel;
use app\model\verify\Verify as VerifyModel;

/**
 * 核销编码管理
 */
class Verify extends BaseModel
{
    //核销码状态，由于设计上的缺陷，对应is_verify字段
    const STATUS_NOT_VERIFY = 0;
    const STATUS_IS_VERIFY = 1;
    const STATUS_REFUNDED = 2;
    const STATUS_EXPIRED = -1;

    static function getStatus($key = null)
    {
        $arr = [
            [
                'id' => self::STATUS_NOT_VERIFY,
                'name' => '待核销',
            ],
            [
                'id' => self::STATUS_IS_VERIFY,
                'name' => '已核销',
            ],
            [
                'id' => self::STATUS_REFUNDED,
                'name' => '已退款',
            ],
            [
                'id' => self::STATUS_EXPIRED,
                'name' => '已过期',
            ],
        ];
        if (isset($arr[0][$key])) {
            $arr = array_column($arr, null, $key);
        }
        return $arr;
    }

    public $verifyFrom = [
        'shop' => '商家后台',
        'store' => '门店后台',
        'mobile' => '手机端',
    ];

    /**
     * 获取核销类型
     */
    public function getVerifyType()
    {
        $verify_type = event("VerifyType", []);
        $type = [
            'pickup' => [
                'name' => '订单自提',
            ],
            'virtualgoods' => [
                'name' => '虚拟商品',
            ]
        ];
        foreach ($verify_type as $k => $v) {
            $type = array_merge($type, $v);
        }
        return $type;
    }

    /**
     * 添加待核销记录
     * @param $type
     * @param $site_id
     * @param $site_name
     * @param $content_array
     * @param int $expire_time
     * @param int $verify_total_count
     * @param int $store_id
     * @param int $member_id
     * @return array
     */
    public function addVerify($type, $site_id, $site_name, $content_array, $expire_time = 0, $verify_total_count = 1, $store_id = 0, $member_id = 0)
    {
        $code = $this->getCode();
        $type_array = $this->getVerifyType();
        $data = [
            'site_id' => $site_id,
            'site_name' => $site_name,
            'verify_code' => $code,
            'verify_type' => $type,
            'verify_type_name' => $type_array[$type]['name'],
            'verify_content_json' => json_encode($content_array, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'expire_time' => $expire_time,
            'verify_total_count' => $verify_total_count,
            'store_id' => $store_id,
            'member_id' => $member_id
        ];
        $res = model("verify")->add($data);
        return $this->success(['verify_code' => $code, 'verify_id' => $res]);
    }

    /**
     * 编辑待核销记录
     * @param $data
     * @param $condition
     * @return array
     */
    public function editVerify($data, $condition)
    {

        $res = model("verify")->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 获取code值
     */
    public function getCode()
    {
        return random_keys(12);
    }

    /**
     * 执行核销
     * @param $verifier_info
     * @param $code
     * @return array|mixed|void
     */
    public function verify($verifier_info, $code)
    {
        $verifier_info['store_id'] = $verifier_info['store_id'] ?? 0;

        model('verify')->startTrans();

        try {
            $verify_info = model("verify")->getInfo([['verify_code', '=', $code]], 'id, site_id, verify_code, verify_type, verify_type_name, verify_content_json, verifier_id, verifier_name, is_verify, expire_time, verify_total_count, verify_use_num, store_id');
            if (empty($verify_info)) {
                model('verify')->rollback();
                return $this->error();
            }
            if ($verify_info['is_verify'] == 0) {
                if ($verify_info['expire_time'] > 0 && $verify_info['expire_time'] < time()) {
                    model('verify')->rollback();
                    return $this->error('', '核销码已过期');
                }
                $check_store_res = $this->checkStore($verify_info, $verifier_info['store_id']);
                if ($check_store_res['code'] < 0) {
                    model('verify')->rollback();
                    return $check_store_res;
                }
                $verify_total_count = $verify_info['verify_total_count'];
                $verify_use_num = $verify_info['verify_use_num'];
                $now_verify_use_num = $verify_use_num + 1;

                //开始核销
                $data_verify = [
                    'verifier_id' => $verifier_info["verifier_id"],
                    'verifier_name' => $verifier_info['verifier_name'],
                    'verify_from' => $verifier_info['verify_from'] ?? '',
                    'verify_remark' => $verifier_info['verify_remark'] ?? '',
                    'verify_use_num' => $now_verify_use_num
                ];
                if ($verify_total_count > 0 && $now_verify_use_num >= $verify_total_count) {
                    $data_verify['is_verify'] = 1;
                    $data_verify['verify_time'] = time();
                }

                $res = model("verify")->update($data_verify, [['id', '=', $verify_info['id']]]);
                $result = event('Verify', ['verify_type' => $verify_info['verify_type'], 'verify_code' => $code, 'verify_id' => $verify_info['id'], 'store_id' => $verifier_info['store_id']], true);
                if (!empty($result) && $result['code'] < 0) {
                    model('verify')->rollback();
                    return $result;
                }

                $site_id = $verify_info['site_id'];
                $verify_record_model = new VerifyRecord();
                $verify_record_data = [
                    'site_id' => $site_id,
                    'verify_code' => $code,
                    'verifier_id' => $verifier_info["verifier_id"],
                    'verifier_name' => $verifier_info['verifier_name'],
                    'verify_time' => time(),
                    'verify_from' => $verifier_info['verify_from'] ?? '',
                    'verify_remark' => $verifier_info['verify_remark'] ?? '',
                    'store_id' => $verifier_info['store_id']
                ];
                $verify_record_model->addVerifyRecord($verify_record_data);
            } else {
                model('verify')->rollback();
                return $this->error('', "IS_VERIFYED");
            }

            model('verify')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('verify')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 检测门店
     * @param $verify_info
     * @param $store_id
     * @return array|void
     */
    public function checkStore($verify_info, $store_id)
    {
        if ($store_id > 0) {
            //pickup 自提核销码
            if ($verify_info['store_id'] > 0) {
                if ($verify_info['store_id'] != $store_id) {
                    return $this->error('', '没有核销权限');
                }
            } else {
                //cardgoods 卡项核销码 id关联member_goods_card_item中的member_verify_id
                if ($verify_info['verify_type'] == 'cardgoods') {
                    $goods_info = model('member_goods_card_item')->getInfo([
                        ['item.member_verify_id', '=', $verify_info['id']],
                    ], 'g.goods_name,g.sale_store,g.goods_class', 'item', [
                        ['member_goods_card card', 'card.card_id = item.card_id', 'inner'],
                        ['goods g', 'g.goods_id = card.goods_id', 'inner'],
                    ]);
                } else {
                    //virtualgoods 虚拟核销码 verify_code关联order中的virtual_code
                    $goods_info = model('order')->getInfo([
                        ['o.virtual_code', '=', $verify_info['verify_code']],
                    ], 'g.goods_name,g.sale_store,g.goods_class', 'o', [
                        ['order_goods og', 'o.order_id = og.order_id', 'inner'],
                        ['goods g', 'og.goods_id = g.goods_id', 'inner'],
                    ]);
                }
                if (empty($goods_info)) {
                    return $this->error('', '核销商品数据缺失');
                }
                if(in_array($goods_info['goods_class'], [GoodsDict::virtual, GoodsDict::virtualcard])){
                    return $this->error('', '没有核销权限');
                }
                if ($goods_info['sale_store'] != 'all') {
                    $sale_store = explode(',', trim($goods_info['sale_store'], ','));
                    if (!in_array($store_id, $sale_store)) {
                        return $this->error('', '没有核销权限');
                    }
                }
            }
        }
        return $this->success();
    }

    /**
     * 获取核销信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getVerifyInfo($condition, $field = '*')
    {
        $res = model('verify')->getInfo($condition, $field);
        //验证是否存在
        if (!empty($res)) {

            if ($res['is_verify'] == 2) {
                return $this->error([], "订单已退款！");
            }

            $json_array = json_decode($res["verify_content_json"], true); //格式化存储数据

            $res["data"] = $json_array;
            return $this->success($res);
        } else {
            return $this->error([], "找不到核销码信息！");
        }
    }

    /**
     * 获取核销列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getVerifyList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $list = model('verify')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取核销分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getVerifyPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*', $alias = 'a', $join = [])
    {
        $list = model('verify')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        //组装数据
        $order_no_array = [];
        $member_id_array = [];
        $member_verify_id_array = [];

        foreach ($list["list"] as $k => $v) {
            $temp = json_decode($v['verify_content_json'], true);
            $member_id_array[] = $v['member_id'];
            if ($v['verify_type'] == 'pickup' || $v['verify_type'] == 'virtualgoods') {
                $order_no_array[] = $temp['remark_array'][1]['value'];
            } else {
                $list['list'][$k]['sku_image'] = $temp["item_array"][0]['img'];
                $list['list'][$k]['sku_name'] = $temp["item_array"][0]['name'];
                $member_verify_id_array[] = $v['id'];
            }
        }

        $order_nos = implode(",", array_unique($order_no_array));
        $order_list = [];
        if (!empty($order_nos)) {
            $order_list = model('order')->getList([['order_no', 'in', $order_nos]], 'order_id,order_no,member_id,name,order_name');
            if (!empty($order_list)) {
                $key = array_column($order_list, 'order_no');
                $order_list = array_combine($key, $order_list);
            }
        }

        $member_ids = implode(",", array_unique($member_id_array));
        $member_list = [];
        if (!empty($member_ids)) {
            $member_list = model('member')->getList([['member_id', 'in', $member_ids]], 'member_id,username,mobile,nickname');
            if (!empty($member_list)) {
                $key = array_column($member_list, 'member_id');
                $member_list = array_combine($key, $member_list);
            }
        }

        $member_verify_ids = implode(",", array_unique($member_verify_id_array));
        $member_verify_list = [];
        if (!empty($member_verify_ids)) {
            $member_verify_list = model('member_goods_card_item')->getList([['mgci.member_verify_id', 'in', $v['id']]], 'mgci.member_verify_id,m.username', '', 'mgci', [['member m', 'm.member_id = mgci.member_id', 'left']]);
            if (!empty($member_verify_list)) {
                $key = array_column($member_verify_list, 'member_verify_id');
                $member_verify_list = array_combine($key, $member_verify_list);
            }
        }

        $status_list = self::getStatus('id');
        foreach ($list["list"] as $k => $v) {
            $temp = json_decode($v['verify_content_json'], true);
            $list["list"][$k]["item_array"] = $temp["item_array"];
            $list["list"][$k]["remark_array"] = $temp["remark_array"];
            if ($v['verify_type'] == 'pickup' || $v['verify_type'] == 'virtualgoods') {
                $list["list"][$k]['order_no'] = $temp['remark_array'][1]['value'];
                $list['list'][$k]['order_info'] = $order_list[$temp['remark_array'][1]['value']] ?? [];
                $list['list'][$k]['name'] = $member_list[$v['member_id']]['username'] ?? '';
                $list['list'][$k]['sku_image'] = "";
                if ($v['verify_type'] == "virtualgoods") {
                    $list['list'][$k]['sku_image'] = $temp["item_array"][0]['img'] ?? '';
                }
            } else {
                $list['list'][$k]['sku_image'] = $temp["item_array"][0]['img'];
                $list['list'][$k]['sku_name'] = $temp["item_array"][0]['name'];
                $list['list'][$k]['name'] = $member_verify_list[$v['id']]['username'] ?? '';
            }
            $list['list'][$k]['member_info'] = $member_list[$v['member_id']] ?? null;
            unset($list["list"][$k]["verify_content_json"]);
            $list['list'][$k]['verifyFrom'] = $this->verifyFrom[$v['verify_from'] ?? ''] ?? '';
            $list['list'][$k]['is_verify_info'] = $status_list[$v['is_verify']] ?? null;
        }

        return $this->success($list);
    }

    /**
     * 验证数据详情
     * @param $item_array
     * @param $remark_array
     * @return array
     */
    public function getVerifyJson($item_array, $remark_array)
    {
        $json_array = array(
            "item_array" => $item_array,
            "remark_array" => $remark_array,
        );
        return $json_array;
    }

    /**
     * 检测会员是否具备当前核销码的核销权限
     * @param $member_id
     * @param $verify_code
     * @return array
     */
    public function checkMemberVerify($member_id, $verify_code)
    {
        $verify_info = model("verify")->getInfo([["verify_code", "=", $verify_code]]);
        if (empty($verify_info))
            return $this->error([], "当前核销码不存在！");

        $site_id = $verify_info["site_id"];
        //验证核销员身份
        $condition = array(
            ["member_id", "=", $member_id],
            ["site_id", "=", $site_id]
        );
        $verifier_info = model("verifier")->getInfo($condition, "verifier_id,verifier_name,store_id,verifier_type");
        if (empty($verifier_info))
            return $this->error([], "没有店铺" . $verify_info["site_name"] . "的核销权限！");

        //门店权限检测
        if ($verifier_info['verifier_type'] != 0) {
            $check_res = $this->checkStore($verify_info, $verifier_info['store_id']);
            if ($check_res['code'] < 0) return $check_res;
        }

        $temp = json_decode($verify_info['verify_content_json'], true);
        $verify_info["item_array"] = $temp["item_array"];
        $verify_info["remark_array"] = $temp["remark_array"];
        unset($verify_info["verify_content_json"]);

        $data = array(
            "verify" => $verify_info,
            "verifier" => $verifier_info,
        );
        return $this->success($data);
    }

    /**
     * 生成核销码二维码
     * @param $code
     * @param $app_type
     * @param $verify_type
     * @param $site_id
     * @param string $type
     * @return array
     */
    public function qrcode($code, $app_type, $verify_type, $site_id, $type = 'create')
    {
        $page = '/pages_tool/verification/detail';
        $params = [
            'site_id' => $site_id,
            'data' => [
                "code" => $code
            ],
            'page' => $page,
            'app_type' => $app_type,
            'promotion_type' => '',
            'h5_path' => $page . '?code=' . $code,
            'qrcode_path' => 'upload/qrcode/' . $verify_type,
            'qrcode_name' => $verify_type . '_' . $code . '_' . $site_id
        ];
        $solitaire = event('PromotionQrcode', $params, true);
        return $this->success($solitaire);
    }

    /**
     * 获取核销码字段和
     * @param $condition
     * @param $field
     * @return array
     */
    public function getVerifySum($condition, $field)
    {
        $res = model('verify')->getSum($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取核销码数量
     * @param $condition
     * @param $field
     * @return array
     */
    public function getVerifyCount($condition, $field)
    {
        $res = model('verify')->getCount($condition, $field);
        return $this->success($res);
    }


    /**
     * 核销码到期业务
     * @param $verifier_info
     * @param $code
     * @return array|mixed|void
     */
    public function verifyCodeExpire($verifier_info)
    {
        model('verify')->startTrans();
        try {
            $order_goods_info = model('order_goods')->getInfo([['order_id', '=', $verifier_info['order_id']]], 'order_no,goods_class,order_id,order_goods_id,site_id');
            //没有使用直接退款
            if ($verifier_info['verify_use_num'] == 0) {
                if (!empty($order_goods_info)) {
                    $order_refund_model = new OrderRefundModel();
                    $refund_info = $order_refund_model->getOrderGoodsRefundInfo($order_goods_info['order_goods_id'], $order_goods_info['site_id'])['data'];
                    if (!empty($refund_info)) {
                        $params = [
                            'site_id' => $order_goods_info['site_id'],
                            'app_module' => 'shop',
                            'shop_active_refund_money_type' => 1,
                            'shop_active_refund_remark' => '核销码过期,订单金额返还',
                            'user_info' => [
                                'uid' => 0,
                                'nick_name' => '系统',
                                'username' => '系统',
                            ],
                            'order_goods_id' => $order_goods_info['order_goods_id'],
                            'shop_active_refund_money' => $refund_info['order_goods_info']['refund_apply_money'] ?? 0,
                            'refund_status' => '',
                        ];
                        $refund_res = $order_refund_model->shopActiveRefund($params);
                        if ($refund_res['code'] == 0) {
                            model('verify')->commit();
                            return $this->success();
                        } else {
                            // todo  失败后 要干什么待考虑
                            model('verify')->rollback();
                            return $refund_res;
                        }
                    }
                }
            } else {
                // 没有使用的直接让变为过期状态
                if ($order_goods_info['goods_class'] == GoodsDict::virtual) {
                    $verify_goods_condition = [
                        ['order_no', '=', $order_goods_info['order_no']],
                        ['site_id', '=', $order_goods_info['site_id']]
                    ];
                    model('goods_virtual')->update(['is_veirfy' => VerifyModel::STATUS_EXPIRED], $verify_goods_condition);
                    $verify_model = new VerifyModel();
                    $verify_condition = [
                        ['verify_code', '=', $verifier_info['code']],
                        ['site_id', '=', $order_goods_info['site_id']]
                    ];
                    $verify_model->editVerify(['is_verify' => VerifyModel::STATUS_EXPIRED], $verify_condition);
                }
                //使用后直接订单完成
                $result = event('CronOrderTakeDelivery', ['relate_id' => $order_goods_info['order_id']], true);
                if ($result['code'] < 0) {
                    model('goods_virtual')->rollback();
                    return $result;
                }
            }
            model('verify')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('verify')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


}