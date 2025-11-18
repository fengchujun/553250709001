<?php


namespace addon\giftcard\model\card;


use addon\giftcard\model\giftcard\CardStat;
use addon\giftcard\model\giftcard\GiftCard;
use addon\giftcard\model\membercard\MemberCard;
use think\facade\Cache;

/**
 * 实体卡(线下)
 * Class GiftCardRecords
 * @package addon\giftcard\model\records
 */
class RealCard extends Card
{

    public function addCard($params)
    {
        $source = $params[ 'source' ] ?? '';

        $card_right_goods_type = $params[ 'card_right_goods_type' ];
        $card_right_goods_count = $params[ 'card_right_goods_count' ];
        $insert_data = array (
            'card_name' => $params[ 'card_name' ] ?? '',
            'card_cover' => $params[ 'card_cover' ] ?? '',
            'card_right_goods_type' => $card_right_goods_type,
            'card_right_goods_count' => $card_right_goods_count,
            'card_cdk' => $params[ 'card_cdk' ],
            'status' => 'to_activate',
            'card_import_id' => $params[ 'import_id' ]
        );
        $params[ 'card_type' ] = 'real';
        $params[ 'insert_data' ] = $insert_data;

        //批量生成卡号
        $giftcard_model = new Giftcard();
        $card_no_res = $giftcard_model->createCardNo($params['giftcard_id'], 1);
        if($card_no_res['code'] < 0) return $card_no_res;
        $card_no = $card_no_res['data'][0];

        $params[ 'card_no' ] = $card_no;
        $result = $this->addCardItem($params);

        if ($result[ 'code' ] >= 0) {
            $card_id = $result[ 'data' ];
            ( new CardLog() )->add([
                'card_id' => $card_id,
                'type' => 'create',
                'operator_type' => 'shop',//todo  暂时是确定的
                'operator_data' => $params[ 'operator_data' ],
                'type_id' => $params[ 'import_id' ]
            ]);
        }
        return $result;
    }

    /**
     * 制卡
     * @param $params
     * @return array
     */
    public function cdkLog($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $import_id = $params[ 'import_id' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $card_import_model = new CardImport();
        $import_condition = array (
            [ 'import_id', '=', $import_id ],
        );
        if ($site_id > 0) {
            $import_condition[] = [ 'site_id', '=', $site_id ];
        }
        $import_info = $card_import_model->getCardImportInfo($import_condition)[ 'data' ] ?? [];
        if (empty($import_info))
            return $this->error();

        $type = $import_info[ 'type' ];
        $giftcard_id = $import_info[ 'giftcard_id' ];
        $card_type = $import_info[ 'card_type' ];
        $giftcard_model = new GiftCard();
        $condition = [ [ 'giftcard_id', '=', $giftcard_id ] ];
        $info = $giftcard_model->getGiftcardInfo($condition)[ 'data' ] ?? [];
        if (empty($info))
            return $this->error();

        $info[ 'operator_data' ] = $params[ 'operator_data' ];
        if ($card_type != 'real') {
            return $this->error('', '该礼品不支持制卡');
        }

        if (empty($type) && !in_array($type, [ 'auto', 'manual', 'import' ]))
            return $this->error();

        $data = array (
            'site_id' => $info[ 'site_id' ],
            'card_type' => $info[ 'card_type' ],
            'giftcard_id' => $info[ 'giftcard_id' ],
            'create_time' => time(),
            'card_right_type' => $info[ 'card_right_type' ],
            'valid_time' => $this->getValidityTime($info),
            'balance' => $info[ 'balance' ] ?? 0,
            'card_right_goods_type' => $info[ 'card_right_goods_type' ] ?? '',
            'card_right_goods_count' => $info[ 'card_right_goods_count' ] ?? '',
            'card_name' => $info[ 'card_name' ] ?? '',
            'card_cover' => $info[ 'card_cover' ] ?? '',
            'status' => 'to_activate',
            'card_import_id' => $import_id
        );
        $info[ 'card_data' ] = $data;
        $info[ 'import_id' ] = $import_id;
        switch ( $type ) {
            case 'auto':
                $info[ 'num' ] = $import_info[ 'total_count' ] ?? 0;
                $result = $this->createCdk($info);
                break;
            case 'manual':
                $card_cdk = $params[ 'card_cdk' ];
                $info[ 'card_cdk' ] = $card_cdk;
                $result = $this->addCdk($info);
                break;
            case 'import':
                $info[ 'num' ] = $import_info[ 'total_count' ];
                $result = $this->importCdk($info);
                break;
        }
        //制卡统计
        //( new CardStat() )->stat([ 'stat_type' => 'create', 'giftcard_id' => $giftcard_id, 'num' => $result[ 'data' ][ 'success_count' ] ?? 0 ]);
        return $result;
    }

    public function addCdk($params)
    {
        $result = $this->addCard($params);
        $total_count = 1;
        if ($result[ 'code' ] < 0) {
            //生成卡密失败后,继续还是退出
            $fail_count = 1;
            $success_count = 0;
            $error = $result[ 'message' ];
        } else {
            $fail_count = 0;
            $success_count = 1;
        }
        $import_id = $params[ 'import_id' ] ?? 0;
        if ($import_id > 0) {
            model('giftcard_card_import')->setInc([ [ 'import_id', '=', $import_id ] ], 'imported_count', 1);
            $card_import_model = new CardImport();
            $card_import_model->update([
                'import_time' => time(),
//                'total_count' => $total_count,
                'fail_count' => $fail_count,
                'success_count' => $success_count,
                'card_cdk' => $params[ 'card_cdk' ],
                'error' => $error ?? ''
            ], [ [ 'import_id', '=', $import_id ] ]);
        }

        return $this->success([ 'total_count' => $total_count, 'fail_count' => $fail_count, 'success_count' => $success_count, 'error' => $error ?? '' ]);
    }

    public function createCdk($params)
    {
        $num = $params[ 'num' ];//生成卡密数量(一般上限一次1000个)
        $num_dict = '0123456789';
        $latter_dict = 'abcdefghijklmnopqrstuvwxyz';
        $big_latter_dict = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $cdk_length = $params[ 'cdk_length' ];
        $card_prefix = $params[ 'card_prefix' ];
        $card_suffix = $params[ 'card_suffix' ];
//        $cdk_prefix_length = strlen($cdk_prefix);
//        $cdk_suffix_length = strlen($cdk_suffix);
        // $length = $cdk_length - $cdk_prefix_length - $cdk_suffix_length;
        $length = $cdk_length;
        $cdk_type = $params[ 'cdk_type' ];
        $dict = '';
        if (strstr($cdk_type, '0-9')) {
            $dict .= $num_dict;
        }
        if (strstr($cdk_type, 'a-z')) {
            $dict .= $latter_dict;
        }
        if (strstr($cdk_type, 'A-Z')) {
            $dict .= $big_latter_dict;
        }
        $dict_len = strlen($dict) - 1;

        $start_num = 1;

        $total_count = $num;
        $fail_count = 0;
        $success_count = 0;
        $import_id = $params[ 'import_id' ] ?? 0;
        $import_info = model('giftcard_card_import')->getInfo([ [ 'import_id', '=', $import_id ] ], "*");
        Cache::set("card_import_log_" . $import_id, $import_info);

        $common_data = $params[ 'card_data' ];

        //批量生成卡号
        $giftcard_model = new Giftcard();
        $card_no_res = $giftcard_model->createCardNo($params['giftcard_id'], $num);
        if($card_no_res['code'] < 0) return $card_no_res;
        $card_no_arr = $card_no_res['data'];

        $insert_data = array ();
        while ($start_num <= $num) {
            $randstr = '';
            for ($i = 0; $i < $length; $i++) {
                $temp_num = mt_rand(0, $dict_len);
                $randstr .= $dict[ $temp_num ];
            }
            $card_cdk = $randstr;

            $success_count++;
            $item_data = $common_data;
            $item_data[ 'card_no' ] = array_shift($card_no_arr);
            $item_data[ 'card_cdk' ] = $card_cdk;
            $insert_data[] = $item_data;
            if (( $start_num % 100 ) == 0) {
                model('giftcard_card')->addList($insert_data);
                model('giftcard_card_import')->update([ 'imported_count' => $start_num ], [ [ 'import_id', '=', $import_id ] ]);
                $insert_data = [];
            } else if ($start_num >= $num) {
                model('giftcard_card')->addList($insert_data);
                model('giftcard_card_import')->update([ 'imported_count' => $start_num ], [ [ 'import_id', '=', $import_id ] ]);
                $insert_data = [];
            }
            $start_num++;
        }
        model('giftcard_card_import')->update([ 'imported_count' => $import_info[ 'total_count' ] ], [ [ 'import_id', '=', $import_id ] ]);
        if ($import_id > 0) {
            $card_import_model = new CardImport();
            $card_import_model->update([
                'cdk_length' => $cdk_length,
                'card_prefix' => $card_prefix,
                'card_suffix' => $card_suffix,
                'cdk_type' => $cdk_type,
                'import_time' => time(),
//                'total_count' => $total_count,
                'fail_count' => $fail_count,
                'success_count' => $success_count,
            ], [ [ 'import_id', '=', $import_id ] ]);
        }

        return $this->success([ 'total_count' => $total_count, 'fail_count' => $fail_count, 'success_count' => $success_count ]);
    }

    public function importCdk($params)
    {
        //之后可以配合匿名函数封装公共函数
        $total_count = 0;
        $fail_count = 0;
        $success_count = 0;
        $import_id = $params[ 'import_id' ] ?? 0;
        $file_path = 'upload/giftcard/giftcard_card_import' . $import_id . '.csv';
        $import_info = model('giftcard_card_import')->getInfo([ [ 'import_id', '=', $import_id ] ], "*");
        Cache::set("card_import_log_" . $import_id, $import_info);
        $common_data = $params[ 'card_data' ];
        $card_no_array = [];
        foreach (getCsvRow($file_path) as $row) {
            if (!empty($row)) {
                $total_count++;
                if ($total_count > 1) {
                    $card_cdk = $row[ 1 ] ?? '';
                    $card_no = $row[ 0 ] ?? '';
                    $card_cdk = trim($card_cdk);
                    $card_no = trim($card_no);
                    if (!empty($card_no) && !empty($card_cdk)) {
                        $params[ 'card_cdk' ] = $card_cdk;
                        $params[ 'card_no' ] = $card_no;
//                        $result = $this->addCard($params);
//                        if ($result['code'] < 0) {
//                            //生成卡密失败后,继续还是退出
//                            $fail_count++;
//                        } else {
//                            $success_count++;
//                        }
                        $item_data = $common_data;
                        $item_data[ 'card_no' ] = $card_no;
                        $card_no_array[] = $card_no;
                        $item_data[ 'card_cdk' ] = $card_cdk;
                        $insert_data[] = $item_data;
                        if (( $total_count % 100 ) == 0) {
                            $column = model('giftcard_card')->getColumn([ [ 'card_no', 'in', $card_no_array ] ], 'card_no');
                            $column = array_unique($column);
                            foreach ($insert_data as $k => $v) {
                                if (in_array($v[ 'card_no' ], $column)) {
                                    unset($insert_data[ $k ]);
                                    $fail_count++;
                                }
                            }
                            model('giftcard_card')->addList($insert_data);
                            model('giftcard_card_import')->update([ 'imported_count' => $total_count ], [ [ 'import_id', '=', $import_id ] ]);
                            $success_count += count($insert_data);
                            $insert_data = [];
                            $card_no_array = [];
                        }
                    } else {
                        $fail_count++;
                    }
                }
            }
        }
        $column = model('giftcard_card')->getColumn([ [ 'card_no', 'in', $card_no_array ] ], 'card_no');
        $column = array_unique($column);
        foreach ($insert_data as $k => $v) {
            if (in_array($v[ 'card_no' ], $column)) {
                unset($insert_data[ $k ]);
                $fail_count++;
            }
        }
        $success_count += count($insert_data);
        //最后一次补充提交
        model('giftcard_card')->addList($insert_data);
        model('giftcard_card_import')->update([ 'imported_count' => $import_info[ 'total_count' ] ], [ [ 'import_id', '=', $import_id ] ]);

        if ($import_id > 0) {

            $original_name = Cache::get('giftcard/giftcard_card_import_name' . $import_id);//文件原名
            $card_import_model = new CardImport();
            $card_import_model->update([
                'import_time' => time(),
//                'total_count' => $total_count,
                'fail_count' => $fail_count,
                'success_count' => $success_count,
                'file_name' => $original_name
            ], [ [ 'import_id', '=', $import_id ] ]);
        }
        return $this->success([ 'total_count' => $total_count, 'fail_count' => $fail_count, 'success_count' => $success_count ]);

    }

    /**
     * 会员激活卡密
     * @param $params
     * @return array
     */
    public function memberCardActivate($params)
    {
        $card_no = $params[ 'card_no' ];
        $card_cdk = $params[ 'card_cdk' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_id = $params[ 'member_id' ];
        $card_condition = array (
            [ 'gc.card_no', '=', $card_no ],
            [ 'gc.card_cdk', '=', $card_cdk ],
            [ 'gc.member_id', '=', 0 ],
            [ 'g.is_delete', '=', 0 ],
        );
        if ($site_id > 0) {
            $card_condition[] = [ 'gc.site_id', '=', $site_id ];
        }
        $card_info = $this->getCardInfo($card_condition, 'gc.*,g.is_delete,g.status as giftcard_status', 'gc', [
                [ 'giftcard g', 'gc.giftcard_id = g.giftcard_id', 'inner' ]
            ])[ 'data' ] ?? [];

        if (empty($card_info))
            return $this->error([], '当前卡密无效或已被激活');

        if (empty($card_info[ 'giftcard_status' ]))
            return $this->error([], '当前礼品卡已下架');

        $card_id = $card_info[ 'card_id' ];
        $result = $this->activate([ 'card_id' => $card_id, 'member_id' => $member_id, 'site_id' => $site_id ]);
        return $result;
    }

    /**
     * 激活卡密
     * @param $params
     */
    public function activate($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $member_id = $params[ 'member_id' ];
        $card_id = $params[ 'card_id' ];
        $card_condition = array (
            [ 'card_id', '=', $card_id ],
            [ 'member_id', '=', 0 ]
        );
        if ($site_id > 0) {
            $card_condition[] = [ 'site_id', '=', $site_id ];
        }
        $card_model = new Card();
        $card_info = $card_model->getCardInfo($card_condition)[ 'data' ] ?? [];
        if (empty($card_info))
            return $this->error();

        $giftcard_model = new GiftCard();
        $giftcard_id = $card_info[ 'giftcard_id' ];
        $condition = [
            [ 'giftcard_id', '=', $giftcard_id ]
        ];
        $info = $giftcard_model->getGiftcardInfo($condition)[ 'data' ] ?? [];
        if (empty($info))
            return $this->error();

        $data = array (
            'card_type' => $info[ 'card_type' ],
            'card_right_type' => $info[ 'card_right_type' ],
            'valid_time' => $this->getValidityTime($info),
            'balance' => $info[ 'balance' ] ?? 0,
            'card_right_goods_type' => $info[ 'card_right_goods_type' ] ?? '',
            'card_right_goods_count' => $info[ 'card_right_goods_count' ] ?? '',
            'card_name' => $info[ 'card_name' ] ?? '',
            'card_cover' => $info[ 'card_cover' ] ?? ''
        );
        model('giftcard_card')->update($data, $card_condition);

        //需要更新卡项数据的
        if ($info[ 'card_right_type' ] == 'goods') {
            $goods_list = $giftcard_model->getGiftcardGoodsList([ [ 'giftcard_id', '=', $giftcard_id ] ], 'cg.*,gs.sku_name,gs.sku_image,gs.goods_name,gs.sku_no,gs.price', '', 'cg', [
                    [ 'goods_sku gs', 'cg.sku_id=gs.sku_id', 'inner' ]
                ])[ 'data' ] ?? [];
            foreach ($goods_list as $k => $v) {
                if ($info[ 'card_right_goods_type' ] == 'all') {
                    $goods_list[ $k ][ 'num' ] = 0;
                } else {
                    $goods_list[ $k ][ 'num' ] = $v[ 'goods_num' ];
                }
            }
        } else {
            $goods_list = array (
                [
                    'site_id' => $info[ 'site_id' ],
                    'giftcard_id' => $giftcard_id,
                    'sku_name' => $info[ 'card_name' ],
                    'sku_image' => $info[ 'card_cover' ],
                    'goods_name' => $info[ 'card_name' ],
                    'balance' => $info[ 'balance' ],//储值余额
                    'total_balance' => $info[ 'balance' ],
                    'total_num' => 1,//购买数量
                ]
            );
        }

        foreach ($goods_list as $k => $v) {
            $v[ 'card_id' ] = $card_id;
            $v[ 'giftcard_id' ] = $giftcard_id;
            $v[ 'card_right_type' ] = $info[ 'card_right_type' ];
            $this->addCardItemGoods($v);
        }
        $data = array (
            'status' => 'to_use',
            'init_member_id' => $member_id,
            'member_id' => $member_id,
            'activate_time' => time()
        );
        model('giftcard_card')->update($data, $card_condition);
        //生成会员所属记录
        $member_card_model = new MemberCard();
        $card_params = array (
            'site_id' => $site_id,
            'form_member_id' => 0,
            'member_id' => $member_id,
            'card_id' => $card_id,
            'source' => '',
        );
        $result = $member_card_model->addMemberCard($card_params);

        //数据统计
        ( new CardStat() )->stat(array_merge($params, [ 'stat_type' => 'activate' ]));
        return $result;
    }

}
