<?php
/**
 * Application Entry & Router
 */

require_once dirname(__DIR__) . '/app/Config.php';

// Simple PSR-4 style autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Parse URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = rtrim($parsedUrl['path'] ?? '/', '/');
if (empty($path)) $path = '/';

// Static file fallback for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $filePath = PUBLIC_PATH . $path;
    if ($path !== '/' && file_exists($filePath) && !is_dir($filePath)) {
        return false;
    }
}

// Router Dispatch
use App\Controllers\BlogController;
use App\Controllers\SearchController;
use App\Controllers\AdminController;

try {
    switch ($path) {
        case '/':
            (new BlogController())->index();
            break;

        case '/api/post':
            (new BlogController())->apiGetPost((int)($_GET['id'] ?? 0));
            break;

        case '/api/search':
            (new SearchController())->search();
            break;

        case '/admin':
            (new AdminController())->dashboard();
            break;

        case '/admin/login':
            (new AdminController())->login();
            break;

        case '/admin/logout':
            (new AdminController())->logout();
            break;

        case '/admin/posts':
            (new AdminController())->posts();
            break;

        case '/admin/posts/edit':
            (new AdminController())->postEdit();
            break;

        case '/admin/posts/save':
            (new AdminController())->postSave();
            break;

        case '/admin/posts/delete':
            (new AdminController())->postDelete();
            break;

        case '/admin/taxonomy':
            (new AdminController())->taxonomy();
            break;

        case '/admin/taxonomy/category-save':
            (new AdminController())->categorySave();
            break;

        case '/admin/taxonomy/category-delete':
            (new AdminController())->categoryDelete();
            break;

        case '/admin/taxonomy/tag-save':
            (new AdminController())->tagSave();
            break;

        case '/admin/taxonomy/tag-delete':
            (new AdminController())->tagDelete();
            break;

        case '/admin/media':
            (new AdminController())->media();
            break;

        case '/admin/media/delete-batch':
            (new AdminController())->mediaDeleteBatch();
            break;

        case '/admin/media/clean-orphans':
            (new AdminController())->mediaCleanOrphans();
            break;

        case '/admin/ueditor-api':
            (new AdminController())->ueditorApi();
            break;

        case '/admin/upload':
            (new AdminController())->upload();
            break;

        case '/admin/settings':
            (new AdminController())->settings();
            break;

        default:
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p><a href='/'>返回首页</a></p>";
            break;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<h1>500 Server Error</h1><pre>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>";
}
