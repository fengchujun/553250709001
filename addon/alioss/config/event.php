<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //短信方式
        'OssType' => [
            'addon\alioss\event\OssType'
        ],
        'Put' => [
            'addon\alioss\event\Put'
        ],
        'CloseOss' => [
            'addon\alioss\event\CloseOss'
        ],
        'ClearAlbumPic' => [
            'addon\alioss\event\ClearAlbumPic'
        ],
        // 展示活动
        'ShowPromotion' => [
            'addon\alioss\event\ShowPromotion',
        ],
    ],

    'subscribe' => [
    ],
];
