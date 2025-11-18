<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //短信方式
        'OssType'  => [
            'addon\qiniu\event\OssType'
        ],
        'Put'      => [
            'addon\qiniu\event\Put'
        ],
        'CloseOss' => [
            'addon\qiniu\event\CloseOss'
        ],
        'ClearAlbumPic' => [
            'addon\qiniu\event\ClearAlbumPic'
        ],
        // 展示活动
        'ShowPromotion' => [
            'addon\qiniu\event\ShowPromotion',
        ],
    ],

    'subscribe' => [
    ],
];
