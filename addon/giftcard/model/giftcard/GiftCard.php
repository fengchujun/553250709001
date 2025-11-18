<?php


namespace addon\giftcard\model\giftcard;


use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Cron;
use think\facade\Db;


class GiftCard extends BaseModel
{

    public $card_type_list = array (
        'real' => '实体卡',
        'virtual' => '电子卡',
    );
    public $card_right_type_list = array (
        'balance' => '储值卡',
        'goods' => '礼品卡',
    );

    //商品权益模式
    public $card_right_goods_type_list = array (
        'all' => '总包权益模式',
        'item' => '独立权益模式'
    );

    public $status_list = array (
        0 => '下架',
        1 => '上架',
    );
    public $validity_type_list = array (
        'forever' => '永久有效',
        'day' => '购买后x天有效',
        'date' => '指定过期日期'
    );

    /**
     * 添加礼品卡活动
     * @param $params
     */
    public function addGiftCard($params)
    {
        $site_id = $params[ 'site_id' ];
        $card_name = $params[ 'card_name' ];
        $category_id = $params[ 'category_id' ];
        $media_ids = $params[ 'media_ids' ] ?? '';//一般情况下不需要
        $card_cover = $params[ 'card_cover' ];
        $validity_type = $params[ 'validity_type' ];
        $sort = $params[ 'sort' ];
        $card_count = $params[ 'card_count' ];
        $card_right_type = $params[ 'card_right_type' ];
        $card_right_goods_type = $params[ 'card_right_goods_type' ];
        $desc = $params[ 'desc' ] ?? '';

        $cartgory_model = new Category();
        $cartgory_condition = array (
            [ 'site_id', '=', $site_id ],
            [ 'category_id', '=', $category_id ]
        );
        $category_info = $cartgory_model->getInfo($cartgory_condition)[ 'data' ] ?? [];
        if (empty($category_info))
            return $this->error([], '当前分组不可用！');
        $data = array (
            'site_id' => $site_id,
            'card_name' => $card_name,
            'category_id' => $category_id,
            'media_ids' => $media_ids,
            'card_cover' => $card_cover,
            'validity_type' => $validity_type,
            'sort' => $sort,
            'create_time' => time(),
            'card_count' => $card_count,
            'card_right_type' => $card_right_type,
            'card_right_goods_type' => $card_right_goods_type,
            'desc' => $desc,
            'is_allow_transfer' => $params[ 'is_allow_transfer' ],
            'card_price' => $params[ 'card_price' ],
            'instruction' => $params[ 'instruction' ],
            'card_type' => $params[ 'card_type' ],
        );
        switch ( $validity_type ) {
            case 'forever'://永久
                $data[ 'validity_day' ] = 0;
                $data[ 'validity_time' ] = 0;
                break;
            case 'day'://几天后过期
                $validity_day = $params[ 'validity_day' ] ?? 0;
                $data[ 'validity_day' ] = $validity_day;
                $data[ 'validity_time' ] = 0;
                break;
            case 'date'://指定日期过期
                $validity_time = $params[ 'validity_time' ] ? strtotime($params[ 'validity_time' ]) : 0;
                $data[ 'validity_time' ] = $validity_time;
                $data[ 'validity_day' ] = 0;
                break;
        }

        $cdk_length = $params[ 'cdk_length' ] ?? 0;//长度
        $cdk_type = $params[ 'cdk_type' ] ?? '';//待激活卡类型
        $card_prefix = $params[ 'card_prefix' ] ?? '';//前缀
        $card_suffix = $params[ 'card_suffix' ] ?? '';//后缀

        $data[ 'cdk_length' ] = $cdk_length;
        $data[ 'card_prefix' ] = $card_prefix;
        $data[ 'card_suffix' ] = $card_suffix;
        $data[ 'cdk_type' ] = $cdk_type;

        $giftcard_id = 0;
        $card_right_type = $params[ 'card_right_type' ];
        switch ( $card_right_type ) {
            case 'balance'://储值
                $card_price = $params[ 'card_price' ] ?? 0;//购买礼品卡的单价
                $balance = $params[ 'balance' ];//储值余额数
                $data[ 'card_price' ] = $card_price;
                $data[ 'balance' ] = $balance;
                break;

            case 'goods'://商品
                $goods_sku_list = $params[ 'goods_sku_list' ];//[{"sku_id":1, 'goods_id':2, 'goods_price':10,'goods_num':30}]
                $giftcard_goods_list = array ();

                $card_right_goods_type = $params[ 'card_right_goods_type' ];//兑换商品业务形式 all 总体数量 item按照商品数量
                switch ( $card_right_goods_type ) {
                    case 'all'://设置整体共享的数量
                        $card_right_goods_count = $params[ 'card_right_goods_count' ];//可兑换的整体数量
                        $data[ 'card_right_goods_count' ] = $card_right_goods_count;
                        foreach ($goods_sku_list as $v) {
                            $giftcard_goods_list[] = [ 'site_id' => $site_id, 'goods_id' => $v[ 'goods_id' ], 'sku_id' => $v[ 'sku_id' ], 'giftcard_id' => &$giftcard_id ];
                        }
                        break;
                    case 'item'://单个每个商品的数量
                        foreach ($goods_sku_list as $v) {
                            $giftcard_goods_list[] = [ 'site_id' => $site_id, 'goods_id' => $v[ 'goods_id' ], 'sku_id' => $v[ 'sku_id' ], 'giftcard_id' => &$giftcard_id, 'goods_num' => $v[ 'goods_num' ] ];
                        }
                        break;
                }
                if (empty($giftcard_goods_list))
                    return $this->error([], '商品不存在！');
                break;
        }

        $giftcard_id = model('giftcard')->add($data);
        if (!empty($giftcard_goods_list)) {
            model('giftcard_goods')->addList($giftcard_goods_list);
        }

        $cron = new Cron();
        $cron->deleteCron([ [ 'event', '=', 'CronCardExpire' ] ]);
        $cron->addCron(2, 1, '礼品卡过期', 'CronCardExpire', time(), 0);
        return $this->success($giftcard_id);
    }

    /**
     * 编辑礼品卡活动
     * @param $params
     */
    public function editGiftCard($params)
    {

        //编辑的时候不允许修改活动类型
        $site_id = $params[ 'site_id' ];
        $card_name = $params[ 'card_name' ];
        $category_id = $params[ 'category_id' ];
        $media_ids = $params[ 'media_ids' ] ?? 0;//一般情况下不需要
        $card_cover = $params[ 'card_cover' ];
        $validity_type = $params[ 'validity_type' ];
        $giftcard_id = $params[ 'giftcard_id' ] ?? 0;
        $sort = $params[ 'sort' ];
        $card_right_type = $params[ 'card_right_type' ];
        $card_right_goods_type = $params[ 'card_right_goods_type' ];
        $desc = $params[ 'desc' ] ?? '';
        $cartgory_model = new Category();
        $cartgory_condition = array (
            [ 'site_id', '=', $site_id ],
            [ 'category_id', '=', $category_id ]
        );
        $category_info = $cartgory_model->getInfo($cartgory_condition)[ 'data' ] ?? [];
        if (empty($category_info))
            return $this->error([], '当前分组不可用！');

        $condition = array (
            [ 'site_id', '=', $site_id ],
            [ 'giftcard_id', '=', $giftcard_id ]
        );

        $data = array (
            'site_id' => $site_id,
            'card_name' => $card_name,
            //'card_count' => $card_count,
            'category_id' => $category_id,
            'media_ids' => $media_ids,
            'card_cover' => $card_cover,
            'validity_type' => $validity_type,
            'sort' => $sort,
            'card_right_type' => $card_right_type,
            'card_right_goods_type' => $card_right_goods_type,
            'update_time' => time(),
            'desc' => $desc,
            'is_allow_transfer' => $params[ 'is_allow_transfer' ],
            'instruction' => $params[ 'instruction' ],
            'card_price' => $params[ 'card_price' ]
        );
        switch ( $validity_type ) {
            case 'forever'://永久
                $data[ 'validity_day' ] = 0;
                $data[ 'validity_time' ] = 0;
                break;
            case 'day'://几天后过期
                $validity_day = $params[ 'validity_day' ] ?? 0;
                $data[ 'validity_day' ] = $validity_day;
                $data[ 'validity_time' ] = 0;
                break;
            case 'date'://指定日期过期
                $validity_time = $params[ 'validity_time' ] ? strtotime($params[ 'validity_time' ]) : 0;
                $data[ 'validity_time' ] = $validity_time;
                $data[ 'validity_day' ] = 0;
                break;
        }
        $cdk_length = $params[ 'cdk_length' ];//长度
        $cdk_type = $params[ 'cdk_type' ];//待激活卡类型
        $card_prefix = $params[ 'card_prefix' ];//前缀
        $card_suffix = $params[ 'card_suffix' ];//后缀
        $data[ 'cdk_length' ] = $cdk_length;
        $data[ 'cdk_type' ] = $cdk_type;
        $data[ 'card_prefix' ] = $card_prefix;
        $data[ 'card_suffix' ] = $card_suffix;

        $card_right_type = $params[ 'card_right_type' ];
        switch ( $card_right_type ) {
            case 'balance'://储值
                $card_price = $params[ 'card_price' ] ?? 0;//购买礼品卡的单价
                $balance = $params[ 'balance' ];//储值余额数
                $data[ 'card_price' ] = $card_price;
                $data[ 'balance' ] = $balance;
                break;
            case 'goods'://商品
                $goods_sku_list = $params[ 'goods_sku_list' ];//[{"sku_id":1, 'goods_id':2, 'goods_price':10,'goods_num':30}]
                $giftcard_goods_list = array ();
                $card_right_goods_type = $params[ 'card_right_goods_type' ];//兑换商品业务形式 all 总体数量 item按照商品数量
                switch ( $card_right_goods_type ) {
                    case 'all'://设置整体共享的数量
                        $card_right_goods_count = $params[ 'card_right_goods_count' ];//可兑换的整体数量
                        $data[ 'card_right_goods_count' ] = $card_right_goods_count;
                        foreach ($goods_sku_list as $v) {
                            $giftcard_goods_list[] = [ 'site_id' => $site_id, 'goods_id' => $v[ 'goods_id' ], 'sku_id' => $v[ 'sku_id' ], 'giftcard_id' => $giftcard_id ];
                        }
                        break;
                    case 'item'://单个每个商品的数量
                        foreach ($goods_sku_list as $v) {
                            $giftcard_goods_list[] = [ 'site_id' => $site_id, 'goods_id' => $v[ 'goods_id' ], 'sku_id' => $v[ 'sku_id' ], 'giftcard_id' => $giftcard_id, 'goods_num' => $v[ 'goods_num' ] ];
                        }
                        break;
                }
                if (empty($giftcard_goods_list))
                    return $this->error([], '商品不存在！');
                break;
        }
        //每次都创建新的活动商品关联
        model('giftcard_goods')->delete($condition);
        $giftcard_id = model('giftcard')->update($data, $condition);
        if (!empty($giftcard_goods_list)) {
            model('giftcard_goods')->addList($giftcard_goods_list);
        }

        $cron = new Cron();
        $cron->deleteCron([ [ 'event', '=', 'CronCardExpire' ] ]);
        $cron->addCron(2, 1, '礼品卡过期', 'CronCardExpire', time(), 0);
        return $this->success($giftcard_id);
    }

    /**
     * 礼品卡上架
     * @param $params
     * @return array
     */
    public function giftcardOn($params)
    {
        $giftcard_id = $params[ 'giftcard_id' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $condition = array (
            [ 'giftcard_id', '=', $giftcard_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $data = array (
            'status' => 1
        );
        $res = model('giftcard')->update($data, $condition);
        if ($res === false)
            return $this->error();
        return $this->success();
    }

    /**
     * 礼品卡下架
     * @param $params
     * @return array
     */
    public function giftcardOff($params)
    {
        $giftcard_id = $params[ 'giftcard_id' ];
        $site_id = $params[ 'site_id' ] ?? 0;
        $condition = array (
            [ 'giftcard_id', '=', $giftcard_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $data = array (
            'status' => 0
        );
        $res = model('giftcard')->update($data, $condition);
        if ($res === false)
            return $this->error();
        return $this->success();
    }

    /**
     * 修改状态
     * @param $status
     * @param $condition
     * @return array
     */
    public function modifyStatus($status, $condition)
    {
        $res = model('giftcard')->update([ 'status' => $status ], $condition);
        return $this->success($res);
    }

    /**
     * 修改礼品卡排序
     * @param $sort
     * @param $condition
     * @return array
     */
    public function modifyGiftcardSort($sort, $condition)
    {
        $data = array (
            'sort' => $sort,
            'update_time' => time()
        );
        model('giftcard')->update($data, $condition);
        return $this->success();
    }

    /**
     * 活动增加销量
     * @param $num
     * @param $condition
     * @return array
     */
    public function incSaleNum($num, $condition)
    {
        model('giftcard')->setInc($condition, 'sale_num', $num);
        return $this->success();
    }

    /**
     * 删除
     * @param $condition
     * @return array
     */
    public function deleteGiftcard($condition)
    {
        $data = array (
            'is_delete' => 1,
            'update_time' => time()
        );
        model('giftcard')->update($data, $condition);
        return $this->success();
    }

    /**
     * 获取礼品卡信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getGiftcardInfo($condition, $field = '*', $alias = '', $join = [])
    {
        $info = model('giftcard')->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取礼品卡列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getGiftcardList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('giftcard')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取礼品卡商品信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getGiftcardGoodsInfo($condition, $field = '*')
    {
        $info = model('giftcard_goods')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取礼品卡商品列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getGiftcardGoodsList($condition = [], $field = '*', $order = '', $alias = '', $join = '', $limit = null)
    {
        $list = model('giftcard_goods')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }

    /**
     * 获取礼品卡商品分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('giftcard_goods')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取礼品卡活动详情
     * @param $params
     */
    public function getGiftcardDetail($params)
    {
        $site_id = $params[ 'site_id' ] ?? 0;
        $giftcard_id = $params[ 'giftcard_id' ];

        $condition = array (
            [ 'g.giftcard_id', '=', $giftcard_id ]
        );
        if ($site_id > 0) {
            $condition[] = [ 'g.site_id', '=', $site_id ];
        }
        $info = $this->getGiftcardInfo($condition, 'g.*,gc.category_name', 'g', [
                [ 'giftcard_category gc', 'g.category_id=gc.category_id', 'left' ]
            ])[ 'data' ] ?? [];
        if (!empty($info)) {
            //查询活动商品关联项
            $condition = array (
                [ 'giftcard_id', '=', $giftcard_id ]
            );
            if ($site_id > 0) {
                $condition[] = [ 'site_id', '=', $site_id ];
            }
            $list = $this->getGiftcardGoodsList($condition)[ 'data' ] ?? [];
            if (!empty($list)) {
                $list = array_map([ $this, 'itemTran' ], $list);
            }
            $info[ 'goods_list' ] = $list;
        }
        $info = $this->tran($info);
        return $this->success($info);
    }
    /**
     * 获取礼品卡分页列表(部分信息转化)
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardDetailPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = $this->getGiftcardPageList($condition, $page, $page_size, $order, $field);
        if (!empty($list)) {
            foreach ($list[ 'data' ]['list'] as $k => $v) {
                $list[ 'data' ]['list'][$k] =  $this->tran($v);
            }
        }
        return $this->success($list);
    }

    /**
     * 获取礼品卡列表(部分信息转化)
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardDetailList($condition = [], $field = '*', $order = '')
    {
        $list = $this->getGiftcardList($condition, $field, $order);
        if (!empty($list)) {
            foreach ($list[ 'data' ] as $k => $v) {
                $list[ 'data' ][$k] = $this->tran($v);
            }
        }
        return $this->success($list[ 'data' ]);
    }
    /**
     * 获取礼品卡分页列表(部分信息转化)
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardDetailPageListInAdmin($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = $this->getGiftcardPageList($condition, $page, $page_size, $order, $field);
        if (!empty($list)) {
            foreach ($list[ 'data' ]['list'] as $k => $v) {
                $card_right_type = $v[ 'card_right_type' ] ?? '';
                if (!empty($card_right_type)) {
                    $list[ 'data' ]['list'][$k][ 'card_right_type_name' ] = $this->card_right_type_list[ $card_right_type ] ?? '';
                }
                $card_right_goods_type = $v[ 'card_right_goods_type' ] ?? '';
                if (!empty($card_right_goods_type)) {
                    $list[ 'data' ]['list'][$k][ 'card_right_goods_type_name' ] = $this->card_right_goods_type_list[ $card_right_goods_type ] ?? '';
                }
                $status = $v[ 'status' ] ?? -1;
                if ($status != -1) {
                    $list[ 'data' ]['list'][$k][ 'status_name' ] = $this->status_list[ $status ] ?? '';
                }
                $validity_type = $v[ 'validity_type' ] ?? '';
                if (!empty($validity_type)) {
                    $list[ 'data' ]['list'][$k][ 'validity_type_name' ] = $this->validity_type_list[ $validity_type ] ?? '';
                }
                $card_type = $v[ 'card_type' ] ?? '';
                if (!empty($card_type)) {
                    $list[ 'data' ]['list'][$k][ 'card_type_name' ] = $this->card_type_list[ $card_type ] ?? '';
                }
            }
        }
        return $this->success($list);
    }

    /**
     * 获取礼品卡列表(部分信息转化)
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGiftcardDetailListInAdmin($condition = [], $field = '*', $order = '')
    {
        $list = $this->getGiftcardList($condition, $field, $order);
        if (!empty($list)) {
            foreach ($list[ 'data' ] as $k => $v) {
                $card_right_type = $v[ 'card_right_type' ] ?? '';
                if (!empty($card_right_type)) {
                    $list[ 'data' ][$k][ 'card_right_type_name' ] = $this->card_right_type_list[ $card_right_type ] ?? '';
                }
                $card_right_goods_type = $v[ 'card_right_goods_type' ] ?? '';
                if (!empty($card_right_goods_type)) {
                    $list[ 'data' ][$k][ 'card_right_goods_type_name' ] = $this->card_right_goods_type_list[ $card_right_goods_type ] ?? '';
                }
                $status = $v[ 'status' ] ?? -1;
                if ($status != -1) {
                    $list[ 'data' ][$k][ 'status_name' ] = $this->status_list[ $status ] ?? '';
                }
                $validity_type = $v[ 'validity_type' ] ?? '';
                if (!empty($validity_type)) {
                    $list[ 'data' ][$k][ 'validity_type_name' ] = $this->validity_type_list[ $validity_type ] ?? '';
                }
                $card_type = $v[ 'card_type' ] ?? '';
                if (!empty($card_type)) {
                    $list[ 'data' ][$k][ 'card_type_name' ] = $this->card_type_list[ $card_type ] ?? '';
                }
            }
        }
        return $this->success($list[ 'data' ]);
    }

    /**
     * 活动主表的一些映射翻译
     * @param $data
     * @return mixed
     */
    public function tran($data)
    {
        $card_right_type = $data[ 'card_right_type' ] ?? '';
        if (!empty($card_right_type)) {
            $data[ 'card_right_type_name' ] = $this->card_right_type_list[ $card_right_type ] ?? '';
        }
        $card_right_goods_type = $data[ 'card_right_goods_type' ] ?? '';
        if (!empty($card_right_goods_type)) {
            $data[ 'card_right_goods_type_name' ] = $this->card_right_goods_type_list[ $card_right_goods_type ] ?? '';
        }
        $status = $data[ 'status' ] ?? -1;
        if ($status != -1) {
            $data[ 'status_name' ] = $this->status_list[ $status ] ?? '';
        }
        $validity_type = $data[ 'validity_type' ] ?? '';
        if (!empty($validity_type)) {
            $data[ 'validity_type_name' ] = $this->validity_type_list[ $validity_type ] ?? '';
        }
        $card_type = $data[ 'card_type' ] ?? '';
        if (!empty($card_type)) {
            $data[ 'card_type_name' ] = $this->card_type_list[ $card_type ] ?? '';
        }

        $media_ids = $data[ 'media_ids' ] ?? '';
        if (!empty($media_ids)) {
            $media_model = new Media();
            $data[ 'media_list' ] = $media_model->getList([ [ 'media_id', 'in', (string) $media_ids ] ])[ 'data' ] ?? [];
        }

        return $data;
    }

    /**
     * 活动商品表的一些映射翻译
     * @param $data
     * @return mixed
     */
    public function itemTran($data)
    {
        $goods_id = $data[ 'goods_id' ];
        $sku_id = $data[ 'sku_id' ];
        $goods_model = new Goods();
        $sku_condition = array (
            [ 'goods_id', '=', $goods_id ],
            [ 'sku_id', '=', $sku_id ]
        );
        $sku_info = $goods_model->getGoodsSkuInfo($sku_condition)[ 'data' ] ?? [];
        $data[ 'sku_info' ] = $sku_info;
        return $data;
    }

    /**
     * 购买须知拼接
     */
    public function giftcardDesc($data)
    {
        $text = '';

        if (isset($data[ 'valid_time' ])) {
            if ($data[ 'valid_time' ] == 0) {
                $text .= '1.该礼品卡永久有效。<br>';
            } else {
                $text .= '1.该礼品卡' . time_to_date($data[ 'valid_time' ]) . '过期，过期失效，请在有效期内使用。<br>';
            }
        } else {
            if ($data[ 'validity_type' ] == 'forever') {
                $text .= '1.该礼品卡永久有效。<br>';
            } else if ($data[ 'validity_type' ] == 'day') {
                $text .= '1.该礼品卡有效期为购买日起' . $data[ 'validity_day' ] . '天内有效，过期失效，请在有效期内使用。<br>';
            } else if ($data[ 'validity_type' ] == 'date') {
                $text .= '1.该礼品卡' . date('Y-m-d', $data[ 'validity_time' ]) . '过期，过期失效，请在有效期内使用。<br>';
            }
        }

        if ($data[ 'is_allow_transfer' ] == 1) {
            $text .= '2.本卡可在指定门店，可以本人使用，或者转赠他人，本卡仅限于店铺或者外带时消费使用，不适用于会员卡购买或者充值。<br>';
        } else {
            $text .= '2.本卡可在指定门店，可以本人使用，本卡仅限于店铺或者外带时消费使用，不适用于会员卡购买或者充值。<br>';
        }

        $text .= '3.本卡不记名、不挂失、不可兑换现金、不找零。<br>';
        if (isset($data[ 'mobile' ]) && $data[ 'mobile' ]) {
            $text .= '4.如有疑问请拨打：<span style="color: #364385">' . $data[ 'mobile' ] . '</span>';
        }
        return $text;

    }

    /**
     * 生成卡号
     * @param $giftcard_id
     * @param $num
     * @return array
     */
    public function createCardNo($giftcard_id, $num)
    {
        $giftcard_info = model('giftcard')->getInfo([
            ['giftcard_id', '=', $giftcard_id],
        ]);
        if(empty($giftcard_info)){
            return $this->error(null, '礼品卡信息缺失');
        }
        Db::startTrans();
        try{
            $giftcard_info = Db::name('giftcard')->where([['giftcard_id', '=', $giftcard_id]])->lock(true)->find();
            if(empty($giftcard_info)){
                Db::rollback();
                return $this->error(null, '礼品卡信息缺失');
            }

            $start_num = $giftcard_info['card_count'] + 1;
            $end_num = $giftcard_info['card_count'] + $num;
            $card_no_arr = [];
            for($i = $start_num;$i <= $end_num;$i ++){
                $card_no_arr[] = $giftcard_info['card_prefix'].$giftcard_id.sprintf('%04d', $i).$giftcard_info['card_suffix'];
            }
            model('giftcard')->update(['card_count'=>$end_num], [
                ['giftcard_id', '=', $giftcard_id],
            ]);

            Db::commit();
            return $this->success($card_no_arr);
        }catch(\Exception $e){
            Db::rollback();
            return $this->error(null, $e->getMessage());
        }
    }
}
