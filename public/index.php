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

// Start Session globally if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parse URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = rtrim($parsedUrl['path'] ?? '/', '/');
if (empty($path)) $path = '/';

// 静态资源兜底路由（无论 Nginx 运行目录指向根目录还是 public 目录，均能 100% 正确加载 CSS/JS/图片/附件）
if (strpos($path, '/assets/') === 0 || strpos($path, '/users/') === 0 || strpos($path, '/uploads/') === 0 || strpos($path, '/zb_users/') === 0 || strpos($path, '/zb_system/') === 0) {
    // 智能文件类型图标映射（将所有历史/现代格式请求统一重定向至 22 个纯 SVG 主题图标）
    if (strpos($path, 'filetype') !== false || strpos($path, 'fileTypeImages') !== false) {
        $rawBase = strtolower(pathinfo($path, PATHINFO_FILENAME));
        $cleanBase = str_replace('icon_', '', $rawBase);
        $svgMap = [
            'rar' => 'archive', 'zip' => 'archive', '7z' => 'archive', 'tar' => 'archive', 'gz' => 'archive', 'bz2' => 'archive', 'zba' => 'archive',
            'pdf' => 'pdf',
            'doc' => 'word', 'docx' => 'word', 'dotx' => 'word', 'odt' => 'word', 'rtf' => 'word',
            'xls' => 'excel', 'xlsx' => 'excel', 'csv' => 'excel', 'ods' => 'excel',
            'ppt' => 'powerpoint', 'pptx' => 'powerpoint', 'key' => 'powerpoint',
            'txt' => 'text', 'log' => 'text', 'md' => 'text', 'conf' => 'text', 'ini' => 'text', '_page' => 'text', '_blank' => 'text',
            'exe' => 'exe', 'bat' => 'exe', 'cmd' => 'exe', 'apk' => 'exe', 'msu' => 'exe',
            'iso' => 'iso', 'dmg' => 'iso', 'pat' => 'iso', 'img' => 'iso',
            'py' => 'python', 'sh' => 'shell', 'sql' => 'sql', 'php' => 'php', 'js' => 'js', 'html' => 'html', 'css' => 'css', 'json' => 'json',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'bmp' => 'image', 'webp' => 'image', 'psd' => 'image',
            'mp3' => 'audio', 'wav' => 'audio', 'flac' => 'audio',
            'mp4' => 'video', 'mkv' => 'video', 'avi' => 'video',
            'c' => 'cpp', 'cpp' => 'cpp', 'java' => 'java'
        ];
        $svgName = $svgMap[$cleanBase] ?? $cleanBase;
        $svgFile = ROOT_PATH . '/users/filetype/' . $svgName . '.svg';
        if (file_exists($svgFile)) {
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=86400');
            readfile($svgFile);
            exit;
        }
    }

    $rel = ltrim($path, '/');
    $possibleFiles = [
        PUBLIC_PATH . $path,
        ROOT_PATH . $path,
        ROOT_PATH . '/' . $rel,
        ROOT_PATH . '/users/' . str_replace(['zb_users/', 'uploads/'], '', $rel),
        ROOT_PATH . '/users/upload/' . str_replace(['users/upload/', 'zb_users/upload/', 'uploads/'], '', $rel),
        ROOT_PATH . '/users/filetype/' . str_replace(['users/filetype/', 'zb_system/image/filetype/', 'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'], '', $rel),
        UPLOAD_PATH . str_replace(['/users/upload', '/zb_users/upload', '/uploads'], '', $path)
    ];
    foreach ($possibleFiles as $file) {
        if (file_exists($file) && is_file($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mimes = [
                'css' => 'text/css; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                '7z' => 'application/x-7z-compressed',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain; charset=utf-8',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf'
            ];
            $contentType = $mimes[$ext] ?? 'application/octet-stream';
            // Auto detect SVG XML content for filetype icons (supports .svg / .png / .gif backward compatibility)
            if (strpos($file, 'filetype') !== false) {
                $headerChunk = file_get_contents($file, false, null, 0, 100);
                if (strpos($headerChunk, '<svg') !== false || strpos($headerChunk, '<?xml') !== false) {
                    $contentType = 'image/svg+xml';
                }
            }
            header('Content-Type: ' . $contentType);
            header('Cache-Control: public, max-age=86400');
            readfile($file);
            exit;
        }
    }
}

// 未安装时，除 /install 路由外，自动拦截并重定向至安装与迁移引导程序
$isInstalled = file_exists(DATA_PATH . '/install.lock');
if (!$isInstalled && strpos($path, '/install') !== 0) {
    header('Location: /install');
    exit;
}

// Router Dispatch
use App\Controllers\BlogController;
use App\Controllers\SearchController;
use App\Controllers\AdminController;
use App\Controllers\InstallController;

try {
    switch ($path) {
        case '/install':
            (new InstallController())->index();
            break;

        case '/install/test-db':
            (new InstallController())->testDb();
            break;

        case '/install/migrate':
            (new InstallController())->migrate();
            break;

        case '/install/fresh':
            (new InstallController())->fresh();
            break;

        case '/':
            (new BlogController())->index();
            break;

        case '/post/json':
            (new BlogController())->getPostJson();
            break;

        case '/post/unlock':
            (new BlogController())->unlock();
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

        case '/admin/media/disk-delete-batch':
            (new AdminController())->mediaDiskDeleteBatch();
            break;

        case '/admin/media/disk-clean-orphans':
            (new AdminController())->mediaDiskCleanOrphans();
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

        case '/admin/navigation':
            (new AdminController())->navigation();
            break;

        case '/admin/content-cleaner':
            (new AdminController())->contentCleaner();
            break;

        case '/admin/content-cleaner/action':
            (new AdminController())->apiCleanResponsive();
            break;

        case '/admin/backup':
            (new AdminController())->backup();
            break;

        case '/admin/backup/create':
            (new AdminController())->createBackup();
            break;

        case '/admin/backup/download':
            (new AdminController())->downloadBackup();
            break;

        case '/admin/backup/delete':
            (new AdminController())->deleteBackup();
            break;

        case '/admin/backup/restore':
            (new AdminController())->restoreBackup();
            break;

        case '/admin/backup/convert-mysql-to-sqlite':
            (new AdminController())->convertMysqlToSqlite();
            break;

        default:
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isJson = (
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                strpos($uri, '/action') !== false ||
                strpos($uri, '/api') !== false ||
                strpos($uri, '/json') !== false
            );
            if ($isJson) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => '404 路由不存在: ' . htmlspecialchars($path)]);
                exit;
            }
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p><a href='/'>返回首页</a></p>";
            break;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $isJson = (
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        strpos($uri, '/action') !== false ||
        strpos($uri, '/api') !== false ||
        strpos($uri, '/json') !== false
    );
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()) . ':' . $e->getLine()
        ]);
        exit;
    }
    echo "<h1>500 Server Error</h1><pre>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>";
}
