<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\v3tov4\model;

use app\model\BaseModel;
use think\facade\Db;

/**
 * V3版本升级
 */
class Upgrade extends BaseModel
{
    private $db = 'v3';

    private $task_class = [
        'goods' => [
            'name' => '商品',
            'class' => 'addon\v3tov4\model\Goods',
            'is_show' => 1,
            'introduction' => '迁移商品、商品分类、商品标签等数据',
            'desc' => <<<EOT
变动说明
1、商品标签（ns_goods_group）转移到商品分组（ns_goods_label），丢失图片
2、商品分类（ns_goods_category），废弃pc端模板、手机端模板设置、是否显示、关联商品类型ID，完善上下级关联字段
3、相册图片直接查询存表
4、丢失商品类型数据
5、丢失商品评价数据
6、丢失回收站数据
7、丢失阶梯优惠数据
8、丢失积分设置
9、丢失会员折扣
10、丢失分销设置
11、丢失卡券商品
12、丢失网盘以及下载商品
13、丢失商品品牌，已废弃
14、丢失商品规格
15、ns_goods表字段变动说明
    1、移除brand_id，品牌id
    2、category_id_1、category_id_2、category_id_3合并到category_id、category_json字段
    3、promotion_price转移到ns_goods_sku表中的discount_price字段
    4、移除point_exchange_type、point_exchange，积分兑换字段
    5、移除give_point字段，购买商品赠送积分
    6、移除shop_id字段，店铺id
    7、移除is_member_discount字段，参与会员折扣
    8、shipping_fee对应is_free_shipping字段，是否免邮
    9、shipping_fee_id对应shipping_template字段，指定运费模板id
    10、stock对应goods_stock字段，商品库存
    11、min_stock_alarm对应goods_stock_alarm字段，库存预警
    12、移除star字段，好评星级
    13、移除shares字段，分享数
    14、evaluates对应evaluate字段，评价数
    15、移除province_id、city_id，地区id字段
    16、picture对应goods_image字段，商品主图路径
    17、goods_content对应description字段，商品详情
    18、移除QRcode字段，商品二维码
    19、移除is_stock_visible字段，页面不显示库存
    20、移除is_hot字段，是否热销商品
    21、移除is_recommend字段，是否推荐
    22、移除is_new字段，是否新品
    23、移除is_pre_sale字段，是否预售
    24、移除is_bill字段，是否开具增值税发票
    25、移除img_id_array字段，商品图片序列
    26、移除sku_img_array字段，商品sku应用图片列表
    27、移除match_point、match_ratio字段，实物与描述相符（根据评价计算）、百分比
    28、移除real_sales字段，实际销量
    29、goods_weight转移到ns_goods_sku表中的weight字段，重量（单位g）
    30、goods_volume转移到ns_goods_sku表中的volume字段，体积（单位立方米）
    31、移除shipping_fee_type字段，计价方式1.重量2.体积3.计件
    32、移除extend_category_id、extend_category_id_1、extend_category_id_2、extend_category_id_3字段
    33、移除production_date字段，生产日期
    34、移除shelf_life字段，保质期
    35、移除pc_custom_template字段，pc端商品自定义模板
    36、移除wap_custom_template字段，wap端商品自定义模板
    37、goods_video_address对应video_url字段，视频
    38、移除max_use_point字段，积分抵现最大可用积分数
    39、移除is_open_presell字段，是否支持预售
    40、移除presell_time、presell_day字段，预售发货时间/天数
    41、移除presell_delivery_type字段，预售发货方式1
    42、移除presell_price字段，预售金额
    43、goods_unit对应unit字段，单位
    44、移除decimal_reservation_number字段，价格保留方式 0 去掉角和分，1去掉分，2 保留角和分
    45、移除integral_give_type字段，积分赠送类型 0固定值 1按比率
16、ns_goods_sku表字段变动说明
    1、promote_price对应discount_price字段，促销价格
    2、移除QRcode字段，商品二维码
    3、移除sku_img_array字段，sku图片序列
    4、移除extend_json字段，虚拟扩展
EOT
        ],
        'member' => [
            'name' => '会员',
            'class' => 'addon\v3tov4\model\Member',
            'is_show' => 1,
            'introduction' => '迁移会员、等级、标签、收货地址、商品收藏、足迹、账户流水等数据',
            'desc' => <<<EOT
变动说明
1、丢失会员账户数据
2、会员主表 数据表: sys_user -> ns_member
    1、member_id 由 v3 sys_user uid对应转入
    2、source_member 来源会员id 查询v3  nfx_shop_member_association
    3、fenxiao_id 分销商id  查询会员是否是分销商 是则为自身分销商id 否查询上级分销商
    4、username  由 v3 sys_user user_name 字段对应转入
    5、nickname  由 v3 sys_user nick_name 字段对应转入
    6、mobile  由 v3 sys_user user_tel 字段对应转入
    7、email  由 v3 sys_user user_email 字段对应转入
    8、password  由 v3 sys_user user_password 字段对应转入
    9、headimg  头像需从v3站点进行拉取
    10、member_level、member_level_name、member_label、member_label_name 这些字段需关联v3 ns_member 查询到 会员等级 会员标签 去这两表中查询
    11、wx_openid 公众号openid v3 sys_user wx_openid 字段对应转入
    12、weapp_openid 小程序openid  v3 sys_user wx_applet_openid 字段对应转入
    13、realname  由v3 sys_user real_name 字段对应转入
    14、sex 由v3 sys_user sex 字段对应转入
    15、location  由v3 sys_user location 字段对应转入
    16、birthday  由v3 sys_user birthday 字段对应转入
    17、reg_time 由v3 sys_user reg_time 字段对应转入
    18、point 积分 由v3  ns_member_account point 字段对应转入
    19、balance 储值余额 由v3  ns_member_account balance 字段对应转入
EOT
        ],
        'fenxiao' => [
            'name' => '分销',
            'class' => 'addon\v3tov4\model\Fenxiao',
            'introduction' => '迁移分销商、分销商等级等数据',
            'is_show' => 0,
            'desc' => <<<EOT
变动说明
1、分销商 数据表:nfx_promoter -> ns_fenxiao 
    1、fenxiao_id  由v3 nfx_promoter promoter_id 字段对应转入
    2、fenxiao_no 按v4分销商编号生成规则生成
    3、fenxiao_name 由v3 nfx_promoter promoter_shop_name 字段对应转入
    4、mobile 由v3 nfx_promoter balance 字段对应转入
    5、member_id 由v3 nfx_promoter uid 字段对应转入
    6、level_id 由v3 nfx_promoter promoter_level 字段对应转入
    7、level_name 查询对应分销商等级名称
    8、parent 由v3 nfx_promoter parent_promoter 字段对应转入
    9、grand_parent 查询上上级分销商id
    10、account 当前佣金 由v3 nfx_promoter （commossion_total - commission_cash） 总佣金 - 已提现佣金
    11、account_withdraw 已提现佣金 由v3 nfx_promoter commission_cash 字段对应转入
    12、create_time 由v3 nfx_promoter audit_time 字段对应转入
    13、total_commission 累计佣金 由v3 nfx_promoter commossion_total 字段对应转入
2、分销商申请  数据表:nfx_promoter -> ns_fenxiao_apply
    1、fenxiao_name 由v3 nfx_promoter promoter_shop_name 字段对应转入
    2、parent 由v3 nfx_promoter parent_promoter 字段对应转入
    3、member_id 由v3 nfx_promoter uid 字段对应转入
    4、mobile 查询会员相应数据
    5、nickname 查询会员相应数据
    6、headimg 查询会员相应数据
    7、level_id 由v3 nfx_promoter promoter_level 字段对应转入
    8、level_name 查询相应分销商等级名称
    9、create_time 由v3 nfx_promoter regidter_time 字段对应转入
3、分销等级数据表：nfx_promoter_level -> ns_fenxiao_level
    1、level_id 由v3 nfx_promoter_level level_id 字段对应转入
    2、level_name 由v3 nfx_promoter_level level_name 字段对应转入
    3、one_rate 由v3 nfx_promoter_level  level_0 字段对应转入
    4、two_rate 由v3 nfx_promoter_level level_1 字段对应转入
    5、three_rate 由v3 nfx_promoter_level level_2 字段对应转入
    6、create_time 由v3 nfx_promoter_level create_time 字段对应转入
4、分销商品 （规则不同不做迁移）
EOT
        ],
//        'order' => [
//            'name' => '订单',
//            'class' => 'addon\v3tov4\model\Goods'
//        ]
    ];

    private $page_size = 10;

    /**
     * 获取数据迁移项
     * @return array
     */
    public function getTaskClass()
    {
        return $this->task_class;
    }

    /**
     * 获取分页
     * @return int
     */
    public function getPageSize()
    {
        return $this->page_size;
    }

    /**
     * 获取分页列表
     * @param $table
     * @param $where
     * @param $page
     * @param $page_size
     * @param $ailas
     * @param $join
     */
    final protected function getPageList($table, $where = [], $field = '*', $page = 1, $page_size = 10, $alias = '', $join = null)
    {
        $table = Db::connect($this->db)->table($table);
        if (!empty($join)) {
            $table = $this->parseJoin($table, $join);
        }
        $list = $table->alias($alias)->where($where)->field($field)->limit($page_size)->page($page)->select()->toArray();
        return $list;
    }

    /**
     * 获取列表
     * @param $table
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    final protected function getList($table, $where = [], $field = '*', $order = "", $alias = '', $join = null)
    {
        $table = Db::connect($this->db)->table($table);
        if (!empty($join)) {
            $table = $this->parseJoin($table, $join);
        }
        $list = $table->alias($alias)->where($where)->order($order)->field($field)->select()->toArray();
        return $list;
    }

    /**
     * 查询单条数据
     * @param $table
     * @param array $where
     * @param string $field
     * @param string $alias
     * @param null $join
     * @return mixed
     */
    final protected function getInfo($table, $where = [], $field = '*', $alias = 'a', $join = null)
    {
        $table = Db::connect($this->db)->table($table);
        if (!empty($join)) {
            $table = $this->parseJoin($table, $join);
        }
        $info = $table->alias($alias)->where($where)->field($field)->find();
        return $info;
    }

    /**
     * sql查询
     * @param $sql
     * @return mixed
     */
    final protected function query($sql)
    {
        $res = Db::connect($this->db)->query($sql);
        return $res;
    }

    /**
     * 查询数量
     * @param $table
     * @param array $where
     * @param string $field
     */
    final protected function getCount($table, $where = [], $field = '*')
    {
        $table = Db::connect($this->db)->table($table);
        $count = $table->where($where)->count($field);
        return $count;
    }

    /**
     * join分析
     * @access protected
     * @param array $join
     * @param array $options 查询条件
     * @return string
     */
    final protected function parseJoin($db_obj, $join)
    {
        foreach ($join as $item) {
            list($table, $on, $type) = $item;
            $type = strtolower($type);
            switch ( $type ) {
                case "left":
                    $db_obj = $db_obj->leftJoin($table, $on);
                    break;
                case "inner":
                    $db_obj = $db_obj->join($table, $on);
                    break;
                case "right":
                    $db_obj = $db_obj->rightjoin($table, $on);
                    break;
                case "full":
                    $db_obj = $db_obj->fulljoin($table, $on);
                    break;
                default:
                    break;
            }
        }
        return $db_obj;
    }

    /**
     * 获取数据同步任务列表
     */
    public function getSyncTask($class)
    {
        $task_class = [];
        $class_array = explode(',', $class);
        foreach ($class_array as $item) {
            if (isset($this->task_class[ $item ])) {
                array_push($task_class, $this->task_class[ $item ][ 'class' ]);
                if ($item == 'member') {
                    array_push($task_class, $this->task_class[ 'fenxiao' ][ 'class' ]);
                }
            }
        }
        try {
            $methods = $this->getTaskMethod($task_class);
            $task = [];
            foreach ($methods as $method => $class_name) {
                $class = new $class_name();
                $count = $class->$method();
                if ($count > 0) {
                    for ($i = 0; $i < ceil(( $count / $this->page_size )); $i++) {
                        array_push($task, [
                            'class' => $class_name,
                            'method' => str_replace('Count', 'List', $method),
                            'page' => $i + 1,
                            'page_size' => $this->page_size
                        ]);
                    }
                }
            }
            return $task;
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 执行任务
     */
    public function run($task)
    {
        try {

            [ 'class' => $class_name, 'method' => $method, 'page' => $page, 'page_size' => $page_size ] = $task;
            $class = new $class_name();
            $res = $class->$method($page, $page_size);
            return $res;
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取类中的方法
     * @param $class_array
     * @return array
     * @throws \ReflectionException
     */
    private function getTaskMethod($class_array)
    {
        $method_array = [];
        foreach ($class_array as $class_name) {
            $class = new \ReflectionClass($class_name);
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                if (strpos($method->name, 'Count') !== false && $method->name != 'getCount') {
                    $method_array[ $method->name ] = $method->class;
                }
            }
        }
        return $method_array;
    }
}