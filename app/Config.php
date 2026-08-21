<?php
/**
 * Global Configuration
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('DATA_PATH', ROOT_PATH . '/data');

return [
    // Database Driver: 'sqlite' or 'mysql'
    'db_driver' => 'sqlite',

    // SQLite config
    'sqlite' => [
        'path' => DATA_PATH . '/blog.db'
    ],

    // MySQL config (for production migration if needed)
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'zblog',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],

    // Site basic info
    'site_url' => '', // auto-detected
    'upload_url' => '/uploads',
    
    // Admin secret
    'admin_session_key' => 'zblog_admin_user'
];
