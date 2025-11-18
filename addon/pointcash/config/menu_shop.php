<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_POINGCASH',
        'title' => '积分抵现',
        'url' => 'pointcash://shop/config/index',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => 'addon/pointcash/shop/view/public/img/point_site.png',
        'picture_select' => '',
        'sort' => 100,
    ]

];
