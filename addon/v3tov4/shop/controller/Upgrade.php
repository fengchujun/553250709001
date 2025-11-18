<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\v3tov4\shop\controller;

use addon\v3tov4\model\Log;
use app\model\system\Database;
use app\shop\controller\BaseShop;
use addon\v3tov4\model\Upgrade as UpgradeModel;
use addon\v3tov4\model\Log as LogModel;
use think\facade\Cache;

/**
 * 升级
 * @author Administrator
 *
 */
class Upgrade extends BaseShop
{
    /**
     * 数据迁移
     */
    public function index()
    {
        $log = new LogModel();
        $upgrade = new UpgradeModel();
        $task_class = $upgrade->getTaskClass();

        if (request()->isJson()) {
            $index = input('index', -1);
            $class = input('class', '');

            if ($index == -1) {
                // 添加迁移日志
                $class_array = explode(',', $class);
                $log_data = [];
                foreach ($class_array as $k => $v) {
                    if ($task_class[ $v ][ 'is_show' ]) {
                        $log_data[] = [
                            'module' => $v,
                            'title' => $task_class[ $v ][ 'name' ],
                            'remark' => $task_class[ $v ][ 'introduction' ],
                            'create_time' => time()
                        ];
                    }
                }
                $log->addLogList($log_data);
                $task_list = $upgrade->getSyncTask($class);
                if (empty($task_list[ 'code' ])) {
                    Cache::set('upgrade_error_task', '');
                }
                Cache::set('upgrade_task', $task_list);
            } else {
                $task_list = Cache::get('upgrade_task');
                $run_res = $upgrade->run($task_list[ $index ]);
                if ($run_res[ 'code' ] < 0) {
                    $task_error_list = Cache::get('upgrade_error_task');
                    if (empty($task_error_list)) {
                        $task_error_list = [
                            'data' => $task_list[ $index ],
                            'error' => $run_res[ 'message' ]
                        ];
                    } else {
                        array_push($task_error_list, [ 'data' => $task_list[ $index ], 'error' => $run_res[ 'message' ] ]);
                    }
                    Cache::set('upgrade_error_task', $task_error_list);
                }
            }
            $task_error_list = Cache::get('upgrade_error_task');
            if (!empty($task_list[ 'code' ])) {
                return error(-1, $task_list[ 'message' ]);
            } elseif (!empty($task_error_list)) {
                $count = 0;
                foreach ($task_error_list as $k => $v) {
                    if (!empty($v[ 'error' ])) {
                        return error(-1, $v[ 'error' ]);
                    } else {
                        $count++;
                    }
                }
                if ($count == count($task_error_list)) {
                    return success(0, '', [ 'index' => $index, 'total' => count($task_list), 'page_size' => $upgrade->getPageSize() ]);
                }
            } else {
                return success(0, '', [ 'index' => $index, 'total' => count($task_list), 'page_size' => $upgrade->getPageSize() ]);
            }
        } else {
            $this->assign('task_class', $task_class);
            return $this->fetch("upgrade/index");
        }
    }

    /**
     * 备份数据库
     */
    public function backupSql()
    {
        if (request()->isJson()) {
            try {
                $upgrade_no = date('YmdHi');

                $database = new Database();
                ini_set('memory_limit', '500M');
                $size = 300;
                $volumn = 1024 * 1024 * 2;
                $dump = '';
                $last_table = input('last_table', '');
                $series = max(1, input('series', 1));
                if (empty($last_table)) {
                    $catch = true;
                } else {
                    $catch = false;
                }
                $back_sql_root = "upload/backup/{$upgrade_no}/sql";
                if (!is_dir($back_sql_root)) {
                    dir_mkdir($back_sql_root);
                }
                $tables = $database->getDatabaseList();
                if (empty($tables)) {
                    return success();
                }
                foreach ($tables as $table) {
                    $table = array_shift($table);
                    if (!empty($last_table) && $table == $last_table) {
                        $catch = true;
                    }
                    if (!$catch) {
                        continue;
                    }
                    if (!empty($dump)) {
                        $dump .= "\n\n";
                    }
                    if ($table != $last_table) {
                        $row = $database->getTableSchemas($table);
                        $dump .= $row;
                    }
                    $index = 0;
                    if (!empty(input('index'))) {
                        $index = input('index');
                    }
                    //枚举所有表的INSERT语句
                    while (true) {
                        $start = $index * $size;
                        $result = $database->getTableInsertSql($table, $start, $size);
                        if (!empty($result)) {
                            $dump .= $result[ 'data' ];
                            if (strlen($dump) > $volumn) {
                                $bakfile = "{$back_sql_root}/backup-{$series}.sql";
                                $dump .= "\n\n";
                                file_put_contents($bakfile, $dump);
                                ++$series;
                                ++$index;
                                $current = array (
                                    'is_backup_end' => 0,
                                    'last_table' => $table,
                                    'index' => $index,
                                    'series' => $series,
                                );
                                $current_series = $series - 1;
                                return success(0, '正在导出数据, 请不要关闭浏览器, 当前第 ' . $current_series . ' 卷.', $current);
                            }
                        }
                        if (empty($result) || count($result[ 'result' ]) < $size) {
                            break;
                        }
                        ++$index;
                    }
                }
                $back_file = "{$back_sql_root}/backup-{$series}.sql";
                $dump .= "\n\n----MySQL Dump End";
                file_put_contents($back_file, $dump);
                return success(0, '数据库备份完成', [ 'is_backup_end' => 1 ]);
            } catch (\Exception $e) {
                return error(-1, $e->getMessage());
            }
        }
    }

    /**
     * 获取最新的模块迁移状态，防止重复迁移
     * @return array
     */
    public function checkModuleIsUpgrade()
    {
        if (request()->isJson()) {
            $log = new LogModel();
            $upgrade = new UpgradeModel();
            $task_class = $upgrade->getTaskClass();
            $module = input('module', '');
            if (!empty($module)) {
                $module_arr = explode(",", $module);
                $res = [];
                foreach ($module_arr as $k => $v) {
                    if ($task_class[ $v ][ 'is_show' ]) {
                        $item = $log->getLogFirstData($v, 1);
                        $res[] = [
                            'module' => $v,
                            'title' => $task_class[ $v ][ 'name' ],
                            'count' => (int) ( $item[ 'data' ] )
                        ];
                    }
                }
                return success(0, '', $res);
            }
        }
    }

    /**
     * 更新迁移日志状态
     * @return array
     */
    public function updateLogStatus()
    {
        if (request()->isJson()) {
            $log = new LogModel();
            $upgrade = new UpgradeModel();
            $task_class = $upgrade->getTaskClass();
            $module = input('module', '');
            if (!empty($module)) {
                $module_arr = explode(",", $module);
                $res = success(0, '', 0);
                foreach ($module_arr as $k => $v) {
                    if ($task_class[ $v ][ 'is_show' ]) {
                        $log_info = $log->getLogFirstData($v, 0);
                        $log_info = $log_info[ 'data' ];
                        if (!empty($log_info)) {
                            $edit_res = $log->editLog([ 'status' => 1 ], [ [ 'id', '=', $log_info[ 'id' ] ] ]);
                            $res[ 'data' ] = $edit_res[ 'data' ];
                        }
                    }
                }
                return $res;
            }

        }
    }

    public function log()
    {
        if (request()->isJson()) {
            $log = new LogModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [];
            $list = $log->getLogPageList($condition, $page, $page_size);
            return $list;
        } else {
            return $this->fetch("upgrade/log");
        }
    }

    public function deleteLog()
    {
        if (request()->isJson()) {
            $ids = input('ids', '');
            if (!empty($ids)) {
                $log = new LogModel();
                $res = $log->deleteLog($ids);
                return $res;
            }
        }
    }
}