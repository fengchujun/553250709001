<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'MEMBERWITHDRAW',
        'title' => '会员提现',
        'url' => 'memberwithdraw://shop/withdraw/transfer',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 0,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
    ],
    [
        'parent' => 'CONFIG_BASE_ORDER',
        'name' => 'MEMBER_WITHDRAW_CONFIG',
        'title' => '提现设置',
        'url' => 'shop/memberwithdraw/config',
        'is_show' => 1,
        'sort' => 3,
    ],
];
