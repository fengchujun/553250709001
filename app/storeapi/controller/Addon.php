<?php

namespace app\storeapi\controller;

class Addon extends BaseStoreApi
{
    public function __construct()
    {
        $this->site_id = request()->siteid();
    }

    /**
     * 插件是否存在
     */
    public function addonIsExit()
    {
        $addon = array_filter(array_map(function($item) {
            if (addon_is_exit($item, $this->site_id)) return $item;
        }, [ 'store', 'stock', 'scale', 'weighgoods', 'cardservice' ]));

        $addon = array_values($addon);

        return $this->response($this->success($addon));
    }
}