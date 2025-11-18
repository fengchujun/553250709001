<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
return [
    'bind' => [

    ],

    'listen' => [

        /**
         * 系统基础事件
         * 完成系统基础化操作执行
         */
        //应用初始化事件
        'AppInit' => [
            'app\event\init\InitConfig',
            'app\event\init\InitAuth',
            'app\event\init\InitAddon',
            'app\event\init\InitCron',


        ],
        'HttpRun' => [
            'app\event\init\HttpRun',
        ],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],

        /**
         * 支付功能事件
         * 对应支付相关功能调用
         */
        //支付异步回调(支付插件完成，作用判定支付成功，返回对应支付编号)
        'PayNotify' => [

        ],

        'Qrcode' => [
            'app\event\Qrcode'
        ],

        //添加门店事件
        'AddStore' => [

        ],

        /******************************************************************营销活动相关事件********************************/

        //关闭游戏
        'CloseGame' => [
            'app\event\promotion\CloseGame'
        ],
        //开启游戏
        'OpenGame' => [
            'app\event\promotion\OpenGame'
        ],
        //营销活动
        'ShowPromotion' => [
            'app\event\promotion\ShowPromotion'
        ],
        /**
         * 营销活动二维码
         */
        'PromotionQrcode' => [
            'app\event\promotion\PromotionQrcode'
        ],
        'PromotionPage' => [
            'app\event\promotion\PromotionPage'
        ],

        /******************************************************************自定义装修事件*********************************/

        // 自定义组件
        'DiyViewUtils' => [
            'app\event\diy\DiyViewUtils',
        ],

        // 自定义页面编辑
        'DiyViewEdit' => [
            'app\event\diy\DiyViewEdit',
        ],

        /*******************************************************************会员相关事件**********************************/
        //添加会员账户数据
        'AddMemberAccount' => [
            'app\event\member\AddMemberAccount',//会员账户变化检测会员等级
        ],
        //会员行为事件
        'MemberAction' => [],
        //会员营销活动标志
        'MemberPromotion' => [],
        //会员注册后执行事件
        'MemberRegister' => [

        ],
        'MemberDetail' => [
            'app\event\member\MemberDetail'
        ],
        'MemberLogin' => [
            'app\event\member\MemberLogin'
        ],
        //会员群体定时刷新
        'CronMemberClusterRefresh' => [
            'app\event\member\CronMemberClusterRefresh'
        ],

        /*******************************************************************微信相关事件**********************************/
        //微信分享数据
        'WchatShareData' => [
            'app\event\wechat\WchatShareData',
        ],
        //微信分享配置
        'WchatShareConfig' => [
            'app\event\wechat\WchatShareConfig',
        ],
        //小程序分享数据
        'WeappShareData' => [
            'app\event\wechat\WeappShareData',
        ],
        //小程序分享配置
        'WeappShareConfig' => [
            'app\event\wechat\WeappShareConfig',
        ],

        /********************************************************************商品相关事件*********************************/
        //商品自动上架
        'CronGoodsTimerOn' => [
            'app\event\goods\CronGoodsTimerOn'
        ],

        //商品自动下架
        'CronGoodsTimerOff' => [
            'app\event\goods\CronGoodsTimerOff'
        ],

        //商品类型，用于商品添加，编辑，搜索
        'GoodsClass' => [
            'app\event\goods\GoodsClass',
            'app\event\goods\VirtualGoodsClass'
        ],

        // 商品删除检测
        'DeleteGoodsCheck' => [
            'app\event\goods\DeleteGoodsCheck',
        ],

        /*******************************************************************订单核销相关功能事件(单独处理)*******************/
        //核销类型
        'VerifyType' => [
        ],
        //核销
        'Verify' => [
            'app\event\verify\PickupOrderVerify',//自提订单核销
            'app\event\verify\VirtualGoodsVerify',//虚拟商品核销
        ],

        // 核销商品临期提醒
        'VerifyOrderOutTime' => [
            'app\event\verify\VerifyOrderOutTime'
        ],
        // 核销码过期
        'CronVerifyCodeExpire' => [
            'app\event\verify\CronVerifyCodeExpire',
        ],

        //核销商品到期自动下架
        'CronVirtualGoodsVerifyOff' =>[
            'app\event\goods\CronVirtualGoodsVerifyOff',
        ],

        /*****************************************************************订单相关事件***********************************/

        //订单创建后执行事件
        'OrderCreate' => [
            'app\event\order\OrderCreate',
        ],
        'OrderCreateAfter' => [
            'app\event\order\OrderCreateAfter',
        ],

        // 订单催付通知（计划任务，针对临近期限）
        'CronOrderUrgePayment' => [
            'app\event\order\CronOrderUrgePayment'
        ],

        //订单支付同步事件
        'OrderPay' => [
            'app\event\order\OrderPay',
        ],
        //订单支付成功异步事件
        'OrderPayAfter' => [
            'app\event\order\OrderPayAfter',
        ],
        //订单支付异步执行
        'OrderPayNotify' => [
            'app\event\order\OrderPayNotify',//商城订单支付异步回调
        ],
        //订单发货事件
        'OrderDelivery' => [],
        //订单发货后自动收货时间
        'CronOrderTakeDelivery' => [
            'app\event\order\CronOrderTakeDelivery'
        ],
        //订单收货事件(后期执行)
        'orderTakeDeliveryAfter' => [], //订单收货
        'OrderComplete' => [
            //订单完成后执行 后续事件
            'app\event\order\OrderComplete',
        ],  //订单完成后执行事件

        //自动执行订单自动完成
        'CronOrderComplete' => [
            'app\event\order\CronOrderComplete'
        ],

        // 自动关闭订单售后
        'CronOrderAfterSaleClose' => [
            'app\event\order\CronOrderAfterSaleClose'
        ],

        'OrderClose' => [], //订单关闭后执行事件
        //订单未支付自动关闭
        'CronOrderClose' => [
            'app\event\order\CronOrderClose'
        ],
        //订单项完成退款操作之后
        'OrderRefundFinish' => [
            'app\event\order\OrderRefundFinish'
        ],
        //通过支付信息获取手机端订单详情路径
        'WapOrderDetailPathByPayInfo' => [
            'app\event\order\WapOrderDetailPathByPayInfo',
        ],
        'OfflinePay' => [
            'app\event\order\OfflinePay',
        ],
        /**************************************************************************************************************/

        // 支付转账结果查询
        'CronPayTransferResult' => [
            'app\event\pay\CronPayTransferResult',
            'app\event\pay\OnlinePayEvent',
        ],

        /*****************************************************************统计相关事件***********************************/
        //店铺统计更新（按日）
        'CronStatShop' => [
            'app\event\stat\CronStatShop'
        ],
        //店铺统计更新（按时）
        'CronStatShopHour' => [
            'app\event\stat\CronStatShopHour'
        ],
        //门店统计更新（按日）
        'CronStatStore' => [
            'app\event\stat\CronStatStore'
        ],
        //门店统计更新（按时）
        'CronStatStoreHour' => [
            'app\event\stat\CronStatStoreHour'
        ],

        'CronStatCron' => [
            'app\event\stat\CronStatCron'
        ],

        /**************************************************************************************************************/
        /******************************************************消息发送相关事件****************************************/
        /**
         * 消息发送
         */
        //消息模板
        'SendMessageTemplate' => [

            // 订单核销通知
            'app\event\message\MessageShopVerified',
            // 核销商品临期提醒
            'app\event\message\MessageVerifyOrderOutTime',
            // 订单催付通知
            'app\event\message\MessageOrderUrgePayment',
            // 订单关闭
            'app\event\message\MessageOrderClose',
            // 订单完成
            'app\event\message\MessageOrderComplete',
            // 订单支付
            'app\event\message\MessageOrderPaySuccess',
            // 订单发货
            'app\event\message\MessageOrderDelivery',

            // 商家同意退款
            'app\event\message\MessageShopRefundAgree',
            // 商家拒绝退款
            'app\event\message\MessageShopRefundRefuse',
            // 核销通知
            'app\event\message\MessageShopVerified',
            // 核销码过期提醒
            'app\event\message\MessageVerifyCodeExpire',

            // 注册验证
            'app\event\message\MessageRegisterCode',
            // 找回密码
            'app\event\message\MessageFindCode',
            // 会员登陆成功
            'app\event\message\MessageLogin',
            // 帐户绑定验证码
            'app\event\message\MessageBindCode',
            // 动态码登陆验证码
            'app\event\message\MessageLoginCode',
            // 支付密码修改通知
            'app\event\message\MessageMemberPayPassword',
            // 设置密码
            'app\event\message\MessageSetPassWord',
            // 买家发起退款提醒
            'app\event\message\MessageOrderRefundApply',
            // 买家已退货提醒
            'app\event\message\MessageOrderRefundDelivery',
            // 买家支付通知商家
            'app\event\message\MessageBuyerPaySuccess',
            // 买家订单完成通知
            'app\event\message\MessageBuyerOrderComplete',
            // 会员申请提现通知
            'app\event\message\MessageUserWithdrawalApply',
            // 会员提现成功通知
            'app\event\message\MessageUserWithdrawalSuccess',

            // 会员提现失败通知
            'app\event\message\MessageUserWithdrawalError',

            // 分销申请提现通知
            'app\event\message\MessageFenxiaoWithdrawalApply',
            // 分销提现成功通知
            'app\event\message\MessageFenxiaoWithdrawalSuccess',
            // 分销提现失败通知
            'app\event\message\MessageFenxiaoWithdrawalError',
            // 分销佣金发放通知
            'app\event\message\MessageOrderCommissionGrant',

            // 会员注销成功通知
            'app\event\message\MessageCancelSuccess',
            // 会员注销失败通知
            'app\event\message\MessageCancelFail',
            // 会员注销申请通知
            'app\event\message\MessageCancelApply',
            // 会员账户变动通知通知
            'app\event\message\MessageAccountChangeNotice',
            // 收银台会员验证验证码
            'app\event\message\MessageCashierMemberVerifyCode',

            //外卖订单 指定配送员后 同步短信推送
            'app\event\message\MessageLocalWaitDelivery',

        ],

        //发送短信
        'sendSms' => [

        ],
        /**********************************************************************网站进行初始化*********************************/
        /**
         * 店铺相关事件
         * 完成店铺相关功能操作
         */
        'AddSite' => [
            'app\event\addsite\AddSiteDiyView',//增加默认自定义数据：主页主页、商品分类、底部导航
            'app\event\addsite\AddMemberLevel',//增加默认会员等级
            'app\event\addsite\AddRegisterAgreement',//增加默认会员注册协议
            'app\event\addsite\AddSiteConfig',//增加默认配置项
            'app\event\addsite\AddSiteDelivery',//增加默认配送管理数据
            'app\event\addsite\AddSiteExpressCompany',//增加默认物流公司数据
            'app\event\addsite\AddMemberClusterCronRefresh',//增加会员群体定时刷新任务
            'app\event\addsite\AddSiteAdv', // 增加默认广告
            'app\event\addsite\AddStoreDiyView', //增加门店主页装修
        ],
        // 添加店铺演示数据
        'AddYanshiData' => [
            'app\event\addsite\AddYanshiData',//增加默认商品相关数据：商品1~3个、商品分类、商品服务
        ],

        // 定时积分任务
        'CronPointTask' => [
            'app\event\account\CronPointTask',
        ],
    ],


    'subscribe' => [
    ],
];
