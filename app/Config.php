<?php
/**
 * Global Configuration & Z-Blog c_option.php Auto-Adapter
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('APP_PATH')) define('APP_PATH', ROOT_PATH . '/app');
if (!defined('VIEW_PATH')) define('VIEW_PATH', ROOT_PATH . '/views');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('DATA_PATH')) define('DATA_PATH', ROOT_PATH . '/data');

// 统一使用现代纯净 users/upload/ 作为上传物理存储目录
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . '/users/upload');
}
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}

// 自动检测并适配 Z-Blog 现有的 c_option.php 配置文件
$possibleOptionPaths = [
    ROOT_PATH . '/users/c_option.php',
    ROOT_PATH . '/zb_users/c_option.php',
    ROOT_PATH . '/c_option.php',
    ROOT_PATH . '/old_zblog/zb_users/c_option.php',
    ROOT_PATH . '/old_zblog/c_option.php',
    dirname(ROOT_PATH) . '/users/c_option.php',
    dirname(ROOT_PATH) . '/zb_users/c_option.php',
    dirname(ROOT_PATH) . '/c_option.php'
];

foreach ($possibleOptionPaths as $path) {
    if (file_exists($path)) {
        $zblogOptionFile = $path;
        break;
    }
}

// 默认数据库配置
$sqliteDbPath = DATA_PATH . '/blog.db';
$sqliteExists = file_exists($sqliteDbPath);

$sqliteConfig = [
    'path' => $sqliteDbPath
];
$mysqlConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'zblog',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

$cOptionMysqlAvailable = false;

// 解析现有 Z-Blog 的 c_option.php 配置文件
if ($zblogOptionFile) {
    $cOpt = @include $zblogOptionFile;
    if (is_array($cOpt)) {
        $dbType = strtolower($cOpt['ZC_DATABASE_TYPE'] ?? '');
        if (in_array($dbType, ['mysql', 'mysqli', 'pdo_mysql'])) {
            $cOptionMysqlAvailable = true;
            $mysqlConfig = [
                'host' => $cOpt['ZC_MYSQL_SERVER'] ?? '127.0.0.1',
                'port' => (int)($cOpt['ZC_MYSQL_PORT'] ?? 3306),
                'dbname' => $cOpt['ZC_MYSQL_NAME'] ?? 'zblog',
                'username' => $cOpt['ZC_MYSQL_USERNAME'] ?? 'root',
                'password' => $cOpt['ZC_MYSQL_PASSWORD'] ?? '',
                'charset' => $cOpt['ZC_MYSQL_CHARSET'] ?? 'utf8mb4'
            ];
        } elseif (in_array($dbType, ['sqlite', 'sqlite3', 'pdo_sqlite'])) {
            if (!empty($cOpt['ZC_SQLITE_NAME'])) {
                $customSqlite = $cOpt['ZC_SQLITE_NAME'];
                if (!file_exists($customSqlite) && file_exists(ROOT_PATH . '/zb_users/data/' . $customSqlite)) {
                    $customSqlite = ROOT_PATH . '/zb_users/data/' . $customSqlite;
                }
                if (file_exists($customSqlite)) {
                    $sqliteConfig['path'] = $customSqlite;
                    $sqliteExists = true;
                }
            }
        }
    }
}

// 核心数据库连接优先级规则：
// 1. 优先使用本地 SQLite blog.db（只要存在 blog.db 文件，即使存在 c_option.php 也优先使用本地 SQLite）
// 2. 无本地 SQLite blog.db 文件时，才尝试连接 c_option.php 中的 MySQL 数据库
if ($sqliteExists) {
    $dbDriver = 'sqlite';
} elseif ($cOptionMysqlAvailable) {
    $dbDriver = 'mysql';
} else {
    $dbDriver = 'sqlite';
}

return [
    // 数据库驱动：'sqlite' 或 'mysql'（严格遵循：本地有 blog.db 优先使用 SQLite，无 db 文件则尝试 MySQL）
    'db_driver' => $dbDriver,

    // SQLite 配置
    'sqlite' => $sqliteConfig,

    // MySQL 配置
    'mysql' => $mysqlConfig,

    // 站点基础路径配置
    'site_url' => '', // 自动识别
    'upload_url' => '/users/upload',
    
    // 后台管理 Session 标识
    'admin_session_key' => 'zblog_admin_user',

    // 适配的 c_option.php 来源路径与状态
    'c_option_source' => $zblogOptionFile,
    'c_option_has_mysql' => $cOptionMysqlAvailable,
    'sqlite_db_exists' => $sqliteExists,
    'is_installed' => file_exists(DATA_PATH . '/install.lock')
];
