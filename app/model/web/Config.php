<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\web;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use app\model\system\Upgrade;
use app\model\upload\Upload;

/**
 * 网站系统性设置
 */
class Config extends BaseModel
{
    //缓存类型
    private $cache_list = [
        [
            'name' => '数据缓存',
            'desc' => '清除cache数据缓存',
            'key' => 'content',
            'btn' => '清除',
            'icon' => 'public/static/img/cache/data.png'
        ],
        [
            'name' => '数据表缓存',
            'desc' => '新增/修改数据表后，需要清除数据表缓存',
            'key' => 'data_table_cache',
            'btn' => '清除',
            'icon' => 'public/static/img/cache/data_table.png'
        ],
        [
            'name' => '模板缓存',
            'desc' => '模板缓存',
            'key' => 'template_cache',
            'btn' => '清除',
            'icon' => 'public/static/img/cache/template.png'
        ],
        [
            'name' => '刷新菜单',
            'desc' => '新增/修改插件菜单后，需要刷新插件菜单',
            'key' => 'menu_cache',
            'btn' => '刷新',
            'icon' => 'public/static/img/cache/template.png'
        ],
        [
            'name' => '刷新自定义模板',
            'desc' => '新增/修改自定义组件后，需要刷新自定义模板',
            'key' => 'diy_view',
            'btn' => '刷新',
            'icon' => 'public/static/img/cache/template.png'
        ],
    ];

    /**
     * 验证码设置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setCaptchaConfig($data, $site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '验证码设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'CAPTCHA_CONFIG']]);
        return $res;
    }

    /**
     * 查询验证码设置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getCaptchaConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'CAPTCHA_CONFIG']]);

        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'shop_login' => 1,
                'shop_reception_login' => 1,
                'shop_reception_register' => 1
            ];
        } else {
            if (isset($res[ 'data' ][ 'value' ][ 'shop_reception_login' ]) === false) {
                $res[ 'data' ][ 'value' ][ 'shop_reception_login' ] = 1;
            }
            if (isset($res[ 'data' ][ 'value' ][ 'shop_reception_register' ]) === false) {
                $res[ 'data' ][ 'value' ][ 'shop_reception_register' ] = 1;
            }
        }
        return $res;
    }

    /**
     * 默认图上传配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setDefaultImg($data, $site_id = 0, $app_module = 'shop')
    {
        $config_info = $this->getDefaultImg($site_id, $app_module)[ 'data' ][ 'value' ];
        if (!empty($config_info)) {
            $upload_model = new Upload();
            if ($data[ 'goods' ] && $config_info[ 'goods' ] && $data[ 'goods' ] != $config_info[ 'goods' ]) {
                $upload_model->deletePic($config_info[ 'goods' ], $site_id);
            }
            if ($data[ 'head' ] && $config_info[ 'head' ] && $data[ 'head' ] != $config_info[ 'head' ]) {
                $upload_model->deletePic($config_info[ 'head' ], $site_id);
            }
            if ($data[ 'store' ] && $config_info[ 'store' ] && $data[ 'store' ] != $config_info[ 'store' ]) {
                $upload_model->deletePic($config_info[ 'store' ], $site_id);
            }
            if ($data[ 'article' ] && $config_info[ 'article' ] && $data[ 'article' ] != $config_info[ 'article' ]) {
                $upload_model->deletePic($config_info[ 'article' ], $site_id);
            }
        }

        $config = new ConfigModel();
        $res = $config->setConfig($data, '默认图设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'DEFAULT_IMAGE']]);
        return $res;
    }

    /**
     * 默认图查询上传配置
     * @param $site_id
     * @param string $app_model
     * @return array
     */
    public function getDefaultImg($site_id, $app_model = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_model], ['config_key', '=', 'DEFAULT_IMAGE']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'goods' => 'public/static/img/default_img/square.png',
                'head' => 'public/static/img/default_img/head.png',
                'store' => 'public/static/img/default_img/store.png',
                'article' => 'public/static/img/default_img/article.png',
            ];
        }

        if (empty($res[ 'data' ][ 'value' ][ 'head' ])) {
            $res[ 'data' ][ 'value' ][ 'head' ] = 'public/static/img/default_img/head.png';
        }
        if (empty($res[ 'data' ][ 'value' ][ 'article' ])) {
            $res[ 'data' ][ 'value' ][ 'article' ] = 'public/static/img/default_img/article.png';
        }
        if (empty($res[ 'data' ][ 'value' ][ 'store' ])) {
            $res[ 'data' ][ 'value' ][ 'store' ] = 'public/static/img/default_img/store.png';
        }
        return $res;
    }

    /**
     * 获取缓存类型
     */
    public function getCacheList()
    {
        return $this->cache_list;
    }

    public function setCopyright($data, $site_id = 1, $app_model = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '版权设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_model], ['config_key', '=', 'COPYRIGHT']]);
        return $res;
    }

    /**
     * 获取版权信息
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getCopyright($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'COPYRIGHT']]);

        $auth_info = cache('auth_info_copyright');
        if (empty($auth_info)) {
            $upgrade_model = new Upgrade();
            $auth_info = $upgrade_model->authInfo();
            cache('auth_info_copyright', $auth_info, ['expire' => 604800]);
        }
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'logo' => '',
                'company_name' => '',
                'copyright_link' => '',
                'copyright_desc' => '',
                'icp' => '',
                'business_show_link' => '',//营业执照亮照链接
                'gov_record' => '',
                'gov_url' => '',
                'market_supervision_url' => ''
            ];
        } else {
            if (is_null($auth_info) || $auth_info[ 'code' ] != 0) {
                $res[ 'data' ][ 'value' ][ 'logo' ] = '';
                $res[ 'data' ][ 'value' ][ 'company_name' ] = '';
                $res[ 'data' ][ 'value' ][ 'copyright_link' ] = '';
                $res[ 'data' ][ 'value' ][ 'copyright_desc' ] = '';
            }

        }
        // 检查是否授权
        if (!empty($auth_info) && $auth_info[ 'code' ] >= 0) {
            $res[ 'data' ][ 'value' ][ 'auth' ] = true;
        } else {
            $res[ 'data' ][ 'value' ][ 'auth' ] = false;
        }
        return $res;
    }

    /**
     * 授权设置
     * @param $data
     * @param int $site_id
     * @param string $app_model
     * @return array
     */
    public function setAuth($data, $site_id = 1, $app_model = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '授权设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_model], ['config_key', '=', 'AUTH']]);
        return $res;
    }

    /**
     * 获取授权设置
     * @return array
     */
    public function getAuth($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'AUTH']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'code' => '',
            ];
        }
        return $res;
    }

    /**
     * 地图设置
     * @param $data
     * @param int $site_id
     * @param string $app_model
     * @return array
     */
    public function setMapConfig($data, $site_id = 1, $app_model = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '地图设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_model], ['config_key', '=', 'MAP_CONFIG']]);
        return $res;
    }

    /**
     * 获取地图设置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getMapConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'MAP_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'tencent_map_key' => '',
                'wap_is_open' => 1, // 手机端是否开启定位
                'wap_valid_time' => 5 // 手机端定位有效期/分钟，过期后将重新获取定位信息，0为不过期
            ];
        }
        $res[ 'data' ][ 'value' ][ 'wap_is_open' ] = $res[ 'data' ][ 'value' ][ 'wap_is_open' ] ?? 1;
        $res[ 'data' ][ 'value' ][ 'wap_valid_time' ] = $res[ 'data' ][ 'value' ][ 'wap_valid_time' ] ?? 5;
        return $res;
    }

    /**
     * 设置获取H5域名配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function seth5DomainName($data, $site_id = 1, $app_module = 'shop')
    {
        $search = '/^([hH][tT]{2}[pP]:\/\/|[hH][tT]{2}[pP][sS]:\/\/)(([A-Za-z0-9-~]+)\.)+([A-Za-z0-9-~\/])+$/';
        if ($data[ 'deploy_way' ] == 'separate') {
            if (!preg_match($search, $data[ 'domain_name_h5' ])) {
                return $this->error('', '请输入正确的域名地址');
            }
        }
        // 默认部署，更新店铺域名
        if ($data[ 'deploy_way' ] == 'default') {
            $this->setShopDomainConfig([
                'domain_name' => __ROOT__
            ], $site_id);
        }
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'H5域名配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'H5_DOMAIN_NAME']]);

        return $res;
    }

    /**
     * 获取H5域名配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getH5DomainName($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'H5_DOMAIN_NAME']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_h5' => __ROOT__ . '/h5',
                'deploy_way' => 'default'
            ];
        }
        return $res;
    }

    /**
     * 设置域名跳转配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setDomainJumpConfig($data, $site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '获取域名跳转配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'DOMAIN_JUMP_CONFIG']]);
        return $res;
    }

    /**
     * 获取域名跳转配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getDomainJumpConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([
            ['site_id', '=', $site_id],
            ['app_module', '=', $app_module],
            ['config_key', '=', 'DOMAIN_JUMP_CONFIG']
        ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'jump_type' => 3, // 1：用户前台，2：商家后台，3：引导页
            ];
        }
        return $res;
    }

    /**
     * 设置获取PC域名配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setPcDomainName($data, $site_id = 1, $app_module = 'shop')
    {
        $search = '/^([hH][tT]{2}[pP]:\/\/|[hH][tT]{2}[pP][sS]:\/\/)(([A-Za-z0-9-~]+)\.)+([A-Za-z0-9-~\/])+$/';
        if ($data[ 'deploy_way' ] == 'separate') {
            if (!preg_match($search, $data[ 'domain_name_pc' ])) {
                return $this->error('', '请输入正确的域名地址');
            }
        }
        // 默认部署，更新店铺域名
        if ($data[ 'deploy_way' ] == 'default') {
            $this->setShopDomainConfig([
                'domain_name' => __ROOT__
            ], $site_id);
        }
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'PC域名配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'PC_DOMAIN_NAME']]);
        return $res;
    }

    /**
     * 获取PC域名配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getPcDomainName($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'PC_DOMAIN_NAME']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_pc' => __ROOT__ . '/web',
                'deploy_way' => 'default'
            ];
        } else {
            if ($res[ 'data' ][ 'value' ][ 'domain_name_pc' ] == '' || empty($res[ 'data' ][ 'value' ][ 'deploy_way' ]) || $res[ 'data' ][ 'value' ][ 'deploy_way' ] == 'default') {
                $res[ 'data' ][ 'value' ] = [
                    'domain_name_pc' => __ROOT__ . '/web'
                ];
            }
            $res[ 'data' ][ 'value' ][ 'deploy_way' ] = $res[ 'data' ][ 'value' ][ 'deploy_way' ] ?? 'default';
        }
        return $res;
    }

    /**
     * 设置热门搜索关键词
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setHotSearchWords($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品热门搜索关键词', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_HOT_SEARCH_WORDS_CONFIG']]);
        return $res;
    }

    /**
     * 获取热门搜索关键词
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getHotSearchWords($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_HOT_SEARCH_WORDS_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'words' => ''
            ];
        }
        return $res;
    }

    /**
     * 设置商品推荐—猜你喜欢
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setGuessYouLike($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品推荐', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_GUESS_YOU_LIKE_CONFIG']]);
        return $res;
    }

    /**
     * 获取商品推荐—猜你喜欢
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getGuessYouLike($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_GUESS_YOU_LIKE_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'title' => '猜你喜欢',
                'supportPage' => ['goods_detail', 'cart', 'collect', 'pay', 'order_detail', 'super_member', 'guafen', 'fenxiao_level'],
                'sources' => 'sort',
                'goodsIds' => [],
                'fontWeight' => false,
                'padding' => 10,
                'cartEvent' => 'detail',
                'text' => '购买',
                'textColor' => '#FFFFFF',
                'theme' => 'default',
                'aroundRadius' => 25,
                'control' => true,
                'bgColor' => '#FF6A00',
                'style' => 'button',
                'nameLineMode' => 'single',
                'iconDiy' => [
                    'iconType' => 'icon',
                    'icon' => '',
                    'style=' => [
                        'fontSize' => '60',
                        'iconBgColor' => [],
                        'iconBgColorDeg' => 0,
                        'iconBgImg' => '',
                        'bgRadius' => 0,
                        'iconColor' => ['#000000'],
                        'iconColorDeg' => 0
                    ]
                ],
            ];
        }
        $res[ 'data' ][ 'value' ][ 'nameLineMode' ] = $res[ 'data' ][ 'value' ][ 'nameLineMode' ] ?? 'single'; // 商品名称，单行、多行展示
        return $res;
    }

    /**
     * 设置商品列表配置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setGoodsListConfig($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品列表配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_LIST_CONFIG']]);
        return $res;
    }

    /**
     * 设置商品列表配置
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getGoodsListConfig($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_LIST_CONFIG']]);
        //数据格式化
        if (empty($res[ 'data' ][ 'value' ])) {
            $data = [
                'fontWeight' => false,
                'padding' => 10,
                'cartEvent' => 'detail',
                'text' => '购买',
                'textColor' => '#FFFFFF',
                'theme' => 'default',
                'aroundRadius' => 25,
                'control' => true,
                'bgColor' => '#FF6A00',
                'style' => 'button',
                'nameLineMode' => 'single',
                'iconDiy' => [
                    'iconType' => 'icon',
                    'icon' => '',
                    'style=' => [
                        'fontSize' => '60',
                        'iconBgColor' => [],
                        'iconBgColorDeg' => 0,
                        'iconBgImg' => '',
                        'bgRadius' => 0,
                        'iconColor' => ['#000000'],
                        'iconColorDeg' => 0
                    ]
                ]
            ];
            $res[ 'data' ][ 'value' ] = $data;
        }
        $res[ 'data' ][ 'value' ][ 'nameLineMode' ] = $res[ 'data' ][ 'value' ][ 'nameLineMode' ] ?? 'single'; // 商品名称，单行、多行展示
        return $res;
    }

    /**
     * 设置默认搜索关键词
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setDefaultSearchWords($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '默认搜索关键词', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_DEFAULT_SEARCH_WORDS_CONFIG']]);
        return $res;
    }

    /**
     * 获取默认搜索关键词
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getDefaultSearchWords($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_DEFAULT_SEARCH_WORDS_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'words' => '搜索 商品'
            ];
        }
        return $res;
    }

    /**
     * 设置商品排序方式
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setGoodsSort($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品默认排序方式', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_SORT_CONFIG']]);
        return $res;
    }

    /**
     * 获取商品排序方式
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getGoodsSort($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_SORT_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'type' => 'asc',
                'default_value' => 100
            ];
        }
        return $res;
    }

    /**
     * 设置PC端首页分类设置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setCategoryConfig($data, $site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'PC端首页分类设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SHOP_CATEGORY_CONFIG']]);
        return $res;
    }

    /**
     * 获取pc首页商品分类配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getCategoryConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SHOP_CATEGORY_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'category' => 1,
                'img' => 1
            ];
        }
        return $res;
    }

    /**
     * 设置商品详情配置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setGoodsDetailConfig($data, $site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品详情配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_DETAIL_CONFIG']]);
        return $res;
    }

    /**
     * 获取商品详情配置
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getGoodsDetailConfig($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_DETAIL_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'nav_bar_switch' => 0, // 是否透明，0：不透明，1：透明
                'introduction_color' => '#303133',
            ];
        }
        return $res;
    }

    /**
     * 设置店铺域名配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setShopDomainConfig($data, $site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '店铺域名配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SHOP_DOMAIN_CONFIG']]);
        return $res;
    }

    /**
     * 获取店铺域名配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getShopDomainConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SHOP_DOMAIN_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name' => __ROOT__,
            ];
            $this->setShopDomainConfig($res[ 'data' ][ 'value' ], $site_id);
        }
        return $res;
    }

    public function checkQqMapKey($tencent_map_key, $type = 0)
    {
        $qq_map = new \app\model\map\QqMap(['key' => $tencent_map_key]);
        $res = $qq_map->addressToDetail([
            'address' => '山西省太原市人民政府',
        ]);

        if (!empty($res)) {
            if ($res[ 'status' ] != 0 && $type == 0) {
                $res[ 'message' ] = '腾讯地图KEY无效';
            }
        }
        return $res;
    }

    /**
     * 设置商品编码设置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setGoodsNo($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品编码设置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_NO_CONFIG']]);
        return $res;
    }

    /**
     * 获取商品编码设置
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getGoodsNo($site_id, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'GOODS_NO_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'uniqueness_switch' => 1,
            ];
        }
        return $res;
    }

    /**
     * 设置后台主题风格配置
     * @param $data
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function setThemeConfig($data, $site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '后台主题风格配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SHOP_THEME_CONFIG']]);
        return $res;
    }

    /**
     * 获取后台主题风格配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getThemeConfig($site_id = 1, $app_module = 'shop')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([
            ['site_id', '=', $site_id],
            ['app_module', '=', $app_module],
            ['config_key', '=', 'SHOP_THEME_CONFIG']
        ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = $this->getThemeList()[ 'data' ][ 0 ];
        }
        return $res;
    }

    /**
     * 后台主题风格列表
     * @return array
     */
    public function getThemeList()
    {
        //  todo 这里支持扩展色调
        $res = [
            [
                'title' => '蓝色', // 标题
                'name' => 'blue', // 标识
                'color' => '#105CFB', // 主色调
                'url' => 'default/common.css' // 样式路径
            ],
            [
                'title' => '橙色',
                'name' => 'orange',
                'color' => '#FA6400',
                'url' => 'orange/common.css'
            ],
            [
                'title' => '粉色',
                'name' => 'pink',
                'color' => '#ff08a7',
                'url' => 'pink/common.css'
            ],
            [
                'title' => '棕色',
                'name' => 'brown',
                'color' => '#CFAF70',
                'url' => 'brown/common.css'
            ],
            [
                'title' => '绿色',
                'name' => 'green',
                'color' => '#19C650',
                'url' => 'green/common.css'
            ],
            [
                'title' => '紫色',
                'name' => 'purple',
                'color' => '#A253FF',
                'url' => 'purple/common.css'
            ],
            [
                'title' => '红色',
                'name' => 'red',
                'color' => '#F4391c',
                'url' => 'red/common.css'
            ]
        ];

        return $this->success($res);
    }


    /**
     * 设置分词配置
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setSplitWordConfig($data, $site_id, $app_module): array
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '分词配置', 1, [['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SPLIT_WORD_CONFIG']]);
        return $res;
    }



    /**
     * 获取分词配置
     */
    public function getSplitWordConfig($site_id=1, $app_module='shop'){
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', $app_module], ['config_key', '=', 'SPLIT_WORD_CONFIG']]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'is_open' => 0,
                'apiKey'=>'',
                'secretKey'=>''
            ];
        }
        return $res;
    }

}