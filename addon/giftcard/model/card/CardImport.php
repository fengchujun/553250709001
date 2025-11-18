<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\giftcard\model\card;

use addon\giftcard\model\giftcard\GiftCard;
use app\model\member\Member;
use think\facade\Cache;
use think\facade\Db;

/**
 * 礼品卡导入类
 *
 * @author Administrator
 *
 */
class CardImport extends Card
{

    public $create_type_list = array (
        'auto' => '在线制卡',
        'import' => '导入制卡'
    );

    /**
     * 创建导入记录单据
     * @param $params
     * @return array
     */
    public function create($params)
    {
        $type = $params[ 'type' ];
        $giftcard_id = $params[ 'giftcard_id' ];
        $giftcard_model = new GiftCard();
        $condition = [ [ 'giftcard_id', '=', $giftcard_id ] ];
        $info = $giftcard_model->getGiftcardInfo($condition)[ 'data' ] ?? [];
        if (empty($info))
            return $this->error();

        $info[ 'operator_data' ] = $params[ 'operator_data' ];
        if ($info[ 'card_type' ] != 'real') {
            return $this->error('', '该礼品不支持制卡');
        }

        $data = array (
            'site_id' => $params[ 'site_id' ],
            'name' => $params[ 'name' ] ?? date('YmdHis'),
            'giftcard_id' => $params[ 'giftcard_id' ],
            'type' => $params[ 'type' ],
            'card_type' => $info[ 'card_type' ],
            'create_time' => time()
        );
        switch ( $type ) {
            case 'auto':
                $data[ 'total_count' ] = $params[ 'num' ] ?? 0;
                break;
            case 'manual':
                $data[ 'total_count' ] = 1;
                break;
            case 'import':
                $file = request()->file('file');
                $tmp_name = $file->getPathname();//获取上传缓存文件
                $fp = file($tmp_name);
                $data[ 'total_count' ] = count($fp) - 1;
                break;
        }
        $import_id = $this->add($data)[ 'data' ] ?? 0;
        if ($type == 'import') {
            $path = 'upload/giftcard/';
            if (file_exists($path) || mkdir($path, 0755, true)) {
                if (move_uploaded_file($tmp_name, $path . 'giftcard_card_import' . $import_id . '.csv')) {
                    Cache::set('giftcard/giftcard_card_import_name' . $import_id, $file->getOriginalName());
                } else {
                    return $this->error([], '导入失败');
                }
            } else {
                return $this->error([], '导入失败');
            }
        }
        return $this->success($import_id);
    }

    /**
     * 添加
     * @param $data
     * @return array
     */
    public function add($data)
    {
        $data[ 'create_time' ] = time();
        $id = model('giftcard_card_import')->add($data);
        return $this->success($id);
    }

    /**
     * 编辑
     * @param $data
     * @param $condition
     * @return array
     */
    public function update($data, $condition)
    {
        $id = model('giftcard_card_import')->update($data, $condition);
        return $this->success($id);
    }

    /**
     * 删除导入记录(todo  能做真删吗)
     * @param $condition
     * @return array
     */
    public function delete($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $import_id = $params[ 'import_id' ] ?? 0;
        $import_ids = $params[ 'import_ids' ] ?? '';
        $condition = array ();

        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        if ($import_id > 0) {
            $condition[] = [ 'import_id', '=', $import_id ];
        }
        if (!empty($import_ids)) {
            $condition[] = [ 'import_id', 'in', $import_ids ];
        }

        $list = $this->getCardImportList($condition)[ 'data' ] ?? [];
        if (empty($list))
            return $this->error();

        foreach ($list as $k => $v) {
            $item_condition = array (
                [ 'card_import_id', '=', $v[ 'import_id' ] ],
                [ 'status', '=', 'used' ]
            );
            $count = model('giftcard_card')->getCount($item_condition);
            if ($count > 0) {
                return $this->error([], '存在已使用的卡项,当前记录不允许删除');
            }
        }
        //删除制卡记录
        $res = model('giftcard_card_import')->delete($condition);
        $this->deleteOperation($params);
        return $this->success($res);
    }

    /**
     * 删除导入卡记录后续
     * @param $params
     * @return array
     */
    public function deleteOperation($params)
    {
//        $list = $params['list'];
        //会将这个记录之下的卡项全部删除
        $card_model = new Card();
        $import_id = $params[ 'import_id' ] ?? 0;
        $import_ids = $params[ 'import_ids' ] ?? 0;
        if ($import_id > 0) {
            $params[ 'card_import_id' ] = $import_id;
        }
        if (!empty($import_ids)) {
            $params[ 'card_import_ids' ] = $import_ids;
        }
        $result = $card_model->delete($params);
        return $result;
    }

    /**
     * 获取礼品卡导入记录信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCardImportInfo($condition, $field = '*')
    {
        $info = model('giftcard_card_import')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡导入记录列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getCardImportList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard_card_import')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡导入记录分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getCardImportPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_card_import')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    public function getCardImportColumn($condition, $field = '*')
    {
        $info = model('giftcard_card_import')->getColumn($condition, $field);
        return $this->success($info);
    }

    /**
     * 导出
     * @param $condition
     */
    public function export($condition)
    {
        try {
            $file_name = date('Y年m月d日-礼品卡', time()) . '.csv';
//            $file_name = date('YmdHis').'.csv';//csv文件名
            //通过分批次执行数据导出(防止内存超出配置设置的)
            set_time_limit(0);
            ini_set('memory_limit', '256M');
            //设置header头
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            //打开php数据输入缓冲区
            $fp = fopen('php://output', 'a');
//            fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // 添加 BOM
            $heade = [ '编号', '卡密', '状态', '生成时间', '激活会员', '激活时间' ];
            //将数据编码转换成GBK格式
            mb_convert_variables('GBK', 'UTF-8', $heade);
            //将数据格式化为CSV格式并写入到output流中
            fputcsv($fp, $heade);
            $member_model = new Member();

            //写入第一行表头
            Db::name('giftcard_card')->where($condition)->chunk(500, function($item_list) use ($fp, $member_model) {
                //写入导出信息
                foreach ($item_list as $k => $item_v) {
                    $item_member_id = $item_v[ 'member_id' ];
                    $item_nickname = '';
                    if ($item_member_id > 0) {
                        $item_member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $item_member_id ] ])[ 'data' ] ?? [];
                        $item_nickname = $item_member_info[ 'nickname' ];
                    }
                    $item_v = $this->tran($item_v);
                    $temp_data = [
                        (string) $item_v[ 'card_no' ] . "\t",
                        (string) $item_v[ 'card_cdk' ] . "\t",
                        (string) $item_v[ 'status_name' ] . "\t",
                        time_to_date($item_v[ 'create_time' ]) . "\t",
                        $item_nickname . "\t",
                        time_to_date($item_v[ 'activate_time' ]) . "\t",
                    ];
                    mb_convert_variables('GBK', 'UTF-8', $temp_data);
                    fputcsv($fp, $temp_data);
                    //将已经存储到csv中的变量数据销毁，释放内存
                    unset($item_v);
                }
                unset($item_list);
            });

            //关闭句柄
            fclose($fp);
            die;

        } catch (\Exception $e) {
            return $this->error([], $e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

    /**
     * 作废
     * @param $params
     * @return array
     */
    public function invalid($params)
    {
        $import_id = $params[ 'import_id' ];
        $condition = array (
            [ 'import_id', '=', $import_id ],
            [ 'status', '=', '1' ]
        );
        $data = array (
            'status' => 2,
            'invalid_time' => time(),
        );
        model('giftcard_card')->update($data, $condition);
        //应该是批量设置
        //查询未激活的卡密有多少
        $card_operation_model = new CardOperation();
        $params[ 'card_import_id' ] = $import_id;
        //批量使卡失效作废
        $card_operation_model->cardInvalid($params);

        return $this->success();
    }
}
