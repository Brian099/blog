<?php
/**
 * Global Configuration & Z-Blog c_option.php Auto-Adapter
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('APP_PATH')) define('APP_PATH', ROOT_PATH . '/app');
if (!defined('VIEW_PATH')) define('VIEW_PATH', ROOT_PATH . '/views');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('DATA_PATH')) define('DATA_PATH', ROOT_PATH . '/data');

// 自动适配上传目录（优先使用 Z-Blog 原生 zb_users/upload/，否则使用 public/uploads/）
if (!defined('UPLOAD_PATH')) {
    if (is_dir(ROOT_PATH . '/zb_users/upload')) {
        define('UPLOAD_PATH', ROOT_PATH . '/zb_users/upload');
    } elseif (is_dir(PUBLIC_PATH . '/uploads')) {
        define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
    } else {
        define('UPLOAD_PATH', ROOT_PATH . '/uploads');
    }
}

// 自动检测并适配 Z-Blog 现有的 c_option.php 配置文件
$zblogOptionFile = null;
$possibleOptionPaths = [
    ROOT_PATH . '/zb_users/c_option.php',
    ROOT_PATH . '/c_option.php',
    dirname(ROOT_PATH) . '/zb_users/c_option.php',
    dirname(ROOT_PATH) . '/c_option.php'
];

foreach ($possibleOptionPaths as $path) {
    if (file_exists($path)) {
        $zblogOptionFile = $path;
        break;
    }
}

// 基础默认配置
$dbDriver = 'sqlite';
$sqliteConfig = [
    'path' => DATA_PATH . '/blog.db'
];
$mysqlConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'zblog',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// 如果检测到现有 Z-Blog 的 c_option.php，自动无缝提取 MySQL 配置
if ($zblogOptionFile) {
    $cOpt = @include $zblogOptionFile;
    if (is_array($cOpt)) {
        $dbType = strtolower($cOpt['ZC_DATABASE_TYPE'] ?? '');
        if (in_array($dbType, ['mysql', 'mysqli', 'pdo_mysql'])) {
            $dbDriver = 'mysql';
            $mysqlConfig = [
                'host' => $cOpt['ZC_MYSQL_SERVER'] ?? '127.0.0.1',
                'port' => (int)($cOpt['ZC_MYSQL_PORT'] ?? 3306),
                'dbname' => $cOpt['ZC_MYSQL_NAME'] ?? 'zblog',
                'username' => $cOpt['ZC_MYSQL_USERNAME'] ?? 'root',
                'password' => $cOpt['ZC_MYSQL_PASSWORD'] ?? '',
                'charset' => $cOpt['ZC_MYSQL_CHARSET'] ?? 'utf8mb4'
            ];
        } elseif (in_array($dbType, ['sqlite', 'sqlite3', 'pdo_sqlite'])) {
            $dbDriver = 'sqlite';
            if (!empty($cOpt['ZC_SQLITE_NAME'])) {
                $sqlitePath = $cOpt['ZC_SQLITE_NAME'];
                if (!file_exists($sqlitePath) && file_exists(ROOT_PATH . '/zb_users/data/' . $sqlitePath)) {
                    $sqlitePath = ROOT_PATH . '/zb_users/data/' . $sqlitePath;
                }
                $sqliteConfig['path'] = $sqlitePath;
            }
        }
    }
}

return [
    // 数据库驱动：'mysql' 或 'sqlite'（若检测到 c_option.php 则自动无缝设为 mysql）
    'db_driver' => $dbDriver,

    // SQLite 配置
    'sqlite' => $sqliteConfig,

    // MySQL 配置
    'mysql' => $mysqlConfig,

    // 站点基础路径配置
    'site_url' => '', // 自动识别
    'upload_url' => is_dir(ROOT_PATH . '/zb_users/upload') ? '/zb_users/upload' : '/uploads',
    
    // 后台管理 Session 标识
    'admin_session_key' => 'zblog_admin_user',

    // 适配的 c_option.php 来源路径
    'c_option_source' => $zblogOptionFile
];
