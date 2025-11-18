<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\notes\model\share;

use addon\notes\model\Notes as NotesModel;
use app\model\share\WchatShareBase as BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 分享
 */
class WchatShare extends BaseModel
{
    protected $config = [
        [
            'title' => '店铺笔记列表',
            'config_key' => 'WCHAT_SHARE_CONFIG_NOTES_LIST',
            'path' => [ '/pages_tool/store_notes/note_list' ],
            'method_prefix' => 'noteList',
        ],
        [
            'title' => '店铺笔记分享',
            'config_key' => 'WCHAT_SHARE_CONFIG_NOTES_DETAIL',
            'path' => [ '/pages_tool/store_notes/note_detail' ],
            'method_prefix' => 'noteDetail',
        ],
    ];

    protected $sort = 4;

    /**
     * 店铺笔记列表
     * @param $param
     * @return array
     */
    protected function noteListShareData($param)
    {
        //跳转路径
        $link = $this->getShareLink($param);
        $config_data = $this->noteListShareConfig($param)[ 'value' ];

        $data = [
            'link' => $link,
            'desc' => $config_data[ 'desc' ],
            'imgUrl' => $config_data[ 'imgUrl' ],
            'title' => $config_data[ 'title' ]
        ];
        return [
            'permission' => [
                'hideOptionMenu' => false,
                'hideMenuItems' => [],
            ],
            'data' => $data,//分享内容
        ];
    }

    /**
     * 店铺笔记分享配置
     * @param $param
     * @return array
     */
    public function noteListShareConfig($param)
    {
        $site_id = $param[ 'site_id' ];
        $config = $param[ 'config' ];

        $config_model = new ConfigModel();
        $data = $config_model->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', $config[ 'config_key' ] ] ])[ 'data' ];
        if (empty($data[ 'value' ])) {
            $data[ 'value' ] = [
                'title' => "店铺笔记",
                'desc' => "好物精选\n向您推荐",
                'imgUrl' => ''
            ];
        }
        if (empty($data[ 'value' ][ 'imgUrl' ])) {
            $data[ 'value' ][ 'imgUrl' ] = img('addon/notes/icon.png');
        }
        return [
            'value' => $data[ 'value' ],
        ];
    }

    /**
     * 店铺笔记分享数据
     * @param $param
     * @return array
     */
    protected function noteDetailShareData($param)
    {
        $site_id = $param[ 'site_id' ] ?? 0;
        parse_str(parse_url($param[ 'url' ])[ 'query' ] ?? '', $query);
        if (isset($query[ 'note_id' ]) || isset($query[ 'id' ])) {
            $note_id = $query['id'] ?? $query['note_id'];
            $condition = [
                [ 'site_id', '=', $site_id ],
                [ 'note_id', '=', $note_id ]
            ];

            $note_model = new NotesModel();
            $note_detail = $note_model->getNotesDetailInfo($condition, '*', 2)[ 'data' ];
            if (!empty($note_detail)) {
                $title = $note_detail[ 'note_title' ];
                $desc = $note_detail[ 'note_title' ];
                $link = $this->getShareLink($param);
                $image_url = img(explode(',', $note_detail[ 'cover_img' ])[ 0 ]);

                $data = [
                    'title' => $title,
                    'desc' => $desc,
                    'link' => $link,
                    'imgUrl' => $image_url,
                    'detail' => $note_detail,
                ];
                return [
                    'permission' => [
                        'hideOptionMenu' => false,
                        'hideMenuItems' => [],
                    ],
                    'data' => $data,//分享内容
                ];
            }
        }
    }
}
