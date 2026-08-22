<?php
namespace App\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Upload;
use App\Models\Setting;
use App\Helpers;

class AdminController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function checkAuth(): bool {
        return !empty($_SESSION['admin_logged_in']);
    }

    private function requireAuth(): void {
        if (!$this->checkAuth()) {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * 登录页与登录处理
     */
    public function login(): void {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $adminUser = Setting::get('admin_username', 'admin');
            $adminPass = Setting::get('admin_password', '');

            $isValid = false;
            if ($username === $adminUser) {
                if (!empty($adminPass)) {
                    if (password_verify($password, $adminPass)) {
                        $isValid = true;
                    } elseif ($password === $adminPass) {
                        // 兼容历史明文密码匹配，并自动平滑升级为安全加密存储
                        $isValid = true;
                        Setting::set('admin_password', password_hash($password, PASSWORD_DEFAULT));
                    }
                } elseif ($password === 'admin123') {
                    $isValid = true;
                    Setting::set('admin_password', password_hash('admin123', PASSWORD_DEFAULT));
                }
            }

            if ($isValid) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: /admin');
                exit;
            } else {
                $error = '用户名或密码错误！';
            }
        }

        require VIEW_PATH . '/admin/login.php';
    }

    public function logout(): void {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        header('Location: /admin/login');
        exit;
    }

    /**
     * 仪表盘
     */
    public function dashboard(): void {
        $this->requireAuth();
        
        $postCount = (int)\App\Database::fetchOne("SELECT COUNT(*) as c FROM zbp_post WHERE log_Type=0")['c'];
        $cateCount = (int)\App\Database::fetchOne("SELECT COUNT(*) as c FROM zbp_category")['c'];
        $tagCount = (int)\App\Database::fetchOne("SELECT COUNT(*) as c FROM zbp_tag")['c'];
        $mediaStats = Upload::getStats();

        $recentPosts = Post::getAdminList(1, 6)['items'];

        require VIEW_PATH . '/admin/dashboard.php';
    }

    /**
     * 文章列表
     */
    public function posts(): void {
        $this->requireAuth();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $keyword = trim($_GET['q'] ?? '');
        $cateId = isset($_GET['cate']) && $_GET['cate'] !== '' ? (int)$_GET['cate'] : null;
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;

        $data = Post::getAdminList($page, 15, $keyword, $cateId, $status);
        $categories = Category::getAll();

        require VIEW_PATH . '/admin/posts.php';
    }

    /**
     * 文章编辑 / 创建
     */
    public function postEdit(): void {
        $this->requireAuth();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $post = null;
        if ($id) {
            $post = Post::getDetail($id);
            if (!$post) {
                die('文章不存在');
            }
        }

        $categories = Category::getAll();
        $tags = Tag::getAll();

        require VIEW_PATH . '/admin/post-edit.php';
    }

    /**
     * 保存文章 (POST)
     */
    public function postSave(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        
        // 构造 tags_raw 格式，如 {1}{15}
        $selectedTags = $_POST['tags'] ?? [];
        $tagsRaw = '';
        if (is_array($selectedTags)) {
            foreach ($selectedTags as $tId) {
                if ((int)$tId > 0) {
                    $tagsRaw .= '{' . (int)$tId . '}';
                }
            }
        }

        $saveData = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'intro' => $_POST['intro'] ?? '',
            'cate_id' => (int)($_POST['cate_id'] ?? 0),
            'status' => (int)($_POST['status'] ?? 0),
            'is_top' => (int)($_POST['is_top'] ?? 0),
            'tags_raw' => $tagsRaw,
            'alias' => $_POST['alias'] ?? ''
        ];

        $savedId = Post::save($saveData, $id);
        echo json_encode(['success' => true, 'id' => $savedId]);
    }

    /**
     * 删除文章 (POST)
     */
    public function postDelete(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Post::delete($id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效 ID']);
        }
    }

    /**
     * 分类与标签管理
     */
    public function taxonomy(): void {
        $this->requireAuth();

        $categories = Category::getAll();
        $tags = Tag::getAll();

        require VIEW_PATH . '/admin/taxonomy.php';
    }

    public function categorySave(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $savedId = Category::save($_POST, $id);
        echo json_encode(['success' => true, 'id' => $savedId]);
    }

    public function categoryDelete(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['id'] ?? 0);
        Category::delete($id);
        echo json_encode(['success' => true]);
    }

    public function tagSave(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $savedId = Tag::save($_POST, $id);
        echo json_encode(['success' => true, 'id' => $savedId]);
    }

    public function tagDelete(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['id'] ?? 0);
        Tag::delete($id);
        echo json_encode(['success' => true]);
    }

    /**
     * 附件管理与智能清理核心模块 (支持数据库模式与磁盘全盘深度模式)
     */
    public function media(): void {
        $this->requireAuth();

        $tab = $_GET['tab'] ?? 'db'; // 'db' or 'disk'
        $page = max(1, (int)($_GET['page'] ?? 1));
        $keyword = trim($_GET['q'] ?? '');
        $onlyOrphans = !empty($_GET['orphan']);

        if ($tab === 'disk') {
            $data = Upload::scanDiskFiles($page, 20, $keyword, $onlyOrphans);
            $stats = Upload::getDiskStats();
        } else {
            $data = Upload::getList($page, 20, $keyword, $onlyOrphans);
            $stats = Upload::getStats();
        }

        require VIEW_PATH . '/admin/media.php';
    }

    /**
     * 批量删除选中的附件 (数据库模式)
     */
    public function mediaDeleteBatch(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string)$ids);
        }
        $cleanIds = array_filter(array_map('intval', $ids));

        $res = Upload::deleteBatch($cleanIds);
        echo json_encode(['success' => true, 'result' => $res]);
    }

    /**
     * 一键清理所有未引用的孤立文件 (数据库模式)
     */
    public function mediaCleanOrphans(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $res = Upload::cleanAllOrphans();
        echo json_encode(['success' => true, 'result' => $res]);
    }

    /**
     * 批量删除选中的物理磁盘文件 (磁盘深度模式)
     */
    public function mediaDiskDeleteBatch(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $paths = $_POST['paths'] ?? [];
        if (!is_array($paths)) {
            $paths = explode(',', (string)$paths);
        }
        $cleanPaths = array_filter(array_map('trim', $paths));

        $res = Upload::deleteDiskFilesBatch($cleanPaths);
        echo json_encode(['success' => true, 'result' => $res]);
    }

    /**
     * 一键清理磁盘所有未引用的孤立物理文件 (磁盘深度模式)
     */
    public function mediaDiskCleanOrphans(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $res = Upload::cleanAllDiskOrphans();
        echo json_encode(['success' => true, 'result' => $res]);
    }

    /**
     * UEditor / UEditorPlus 统一后端 API (配置读取、图片与文件上传、Base64粘贴上传、远程图片本地化)
     */
    public function ueditorApi(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $action = $_GET['action'] ?? '';

        if ($action === 'config') {
            // UEditor 标准前端配置响应
            echo json_encode([
                "imageActionName" => "uploadimage",
                "imageFieldName" => "upfile",
                "imageMaxSize" => 20480000,
                "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".webp"],
                "imageCompressEnable" => true,
                "imageCompressBorder" => 1600,
                "imageInsertAlign" => "none",
                "imageUrlPrefix" => "",
                "scrawlActionName" => "uploadscrawl",
                "scrawlFieldName" => "upfile",
                "snapscreenActionName" => "uploadimage",
                "catcherActionName" => "catchimage",
                "catcherFieldName" => "source",
                "catcherMaxSize" => 20480000,
                "catcherAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".webp"],
                "fileActionName" => "uploadfile",
                "fileFieldName" => "upfile",
                "fileMaxSize" => 51200000,
                "fileAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".zip", ".rar", ".7z", ".tar", ".gz", ".doc", ".docx", ".xls", ".xlsx", ".pdf", ".txt"]
            ]);
            return;
        }

        // 1. 标准文件上传 (拖拽/文件选择)
        if (in_array($action, ['uploadimage', 'uploadfile'])) {
            $fileField = 'upfile';
            if (!empty($_FILES[$fileField])) {
                $res = Upload::handleUpload($_FILES[$fileField]);
                if ($res) {
                    echo json_encode([
                        "state" => "SUCCESS",
                        "url" => $res['url'],
                        "title" => $res['name'],
                        "original" => $res['source_name'],
                        "type" => "." . pathinfo($res['name'], PATHINFO_EXTENSION),
                        "size" => $res['size']
                    ]);
                    return;
                }
            }
            
            // 兼容 Base64 格式的截图粘贴
            if (!empty($_POST[$fileField])) {
                $base64Data = $_POST[$fileField];
                $res = $this->saveBase64Image($base64Data);
                if ($res) {
                    echo json_encode([
                        "state" => "SUCCESS",
                        "url" => $res['url'],
                        "title" => $res['name'],
                        "original" => "paste.png",
                        "type" => ".png",
                        "size" => $res['size']
                    ]);
                    return;
                }
            }

            echo json_encode(["state" => "上传失败"]);
            return;
        }

        // 2. 涂鸦/截图 Base64 上传
        if ($action === 'uploadscrawl') {
            $base64Data = $_POST['upfile'] ?? '';
            $res = $this->saveBase64Image($base64Data);
            if ($res) {
                echo json_encode([
                    "state" => "SUCCESS",
                    "url" => $res['url'],
                    "title" => $res['name'],
                    "original" => "scrawl.png",
                    "type" => ".png",
                    "size" => $res['size']
                ]);
                return;
            }
            echo json_encode(["state" => "涂鸦保存失败"]);
            return;
        }

        // 3. 远程外链图片自动抓取到本地
        if ($action === 'catchimage') {
            $sources = $_POST['source'] ?? [];
            $list = [];
            foreach ($sources as $imgUrl) {
                $saved = $this->downloadRemoteImage($imgUrl);
                if ($saved) {
                    $list[] = [
                        "state" => "SUCCESS",
                        "url" => $saved['url'],
                        "size" => $saved['size'],
                        "title" => $saved['name'],
                        "original" => $imgUrl,
                        "source" => $imgUrl
                    ];
                }
            }
            echo json_encode([
                "state" => !empty($list) ? "SUCCESS" : "FAIL",
                "list" => $list
            ]);
            return;
        }

        echo json_encode(["state" => "未知操作"]);
    }

    /**
     * 保存 Base64 粘贴图片到 uploads 目录
     */
    private function saveBase64Image(string $base64Data): ?array {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Data, $result)) {
            $type = $result[2];
            $base64Data = base64_decode(str_replace($result[1], '', $base64Data));
        } else {
            $base64Data = base64_decode($base64Data);
            $type = 'png';
        }

        if (!$base64Data) return null;

        $year = date('Y');
        $month = date('m');
        $targetDir = UPLOAD_PATH . "/{$year}/{$month}";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newName = date('YmdHis') . rand(1000, 9999) . '.' . $type;
        $targetPath = $targetDir . '/' . $newName;

        if (file_put_contents($targetPath, $base64Data)) {
            $size = strlen($base64Data);
            $time = time();
            \App\Database::execute(
                "INSERT INTO zbp_upload (ul_AuthorID, ul_Size, ul_Name, ul_SourceName, ul_MimeType, ul_PostTime, ul_DownNums, ul_LogID, ul_Intro, ul_Meta) VALUES (1, ?, ?, ?, 'image/{$type}', ?, 0, 0, '', '')",
                [$size, $newName, 'paste_' . $newName, $time]
            );

            return [
                'id' => (int)\App\Database::lastInsertId(),
                'name' => $newName,
                'size' => $size,
                'url' => "/users/upload/{$year}/{$month}/" . $newName
            ];
        }

        return null;
    }

    /**
     * 自动抓取远程外链图片到本地
     */
    private function downloadRemoteImage(string $url): ?array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($data)) return null;

        $ext = 'jpg';
        if (strpos($contentType, 'png') !== false) $ext = 'png';
        elseif (strpos($contentType, 'gif') !== false) $ext = 'gif';
        elseif (strpos($contentType, 'webp') !== false) $ext = 'webp';

        $year = date('Y');
        $month = date('m');
        $targetDir = UPLOAD_PATH . "/{$year}/{$month}";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newName = date('YmdHis') . rand(1000, 9999) . '.' . $ext;
        $targetPath = $targetDir . '/' . $newName;

        if (file_put_contents($targetPath, $data)) {
            $size = strlen($data);
            $time = time();
            \App\Database::execute(
                "INSERT INTO zbp_upload (ul_AuthorID, ul_Size, ul_Name, ul_SourceName, ul_MimeType, ul_PostTime, ul_DownNums, ul_LogID, ul_Intro, ul_Meta) VALUES (1, ?, ?, ?, '{$contentType}', ?, 0, 0, '', '')",
                [$size, $newName, basename($url), $time]
            );

            return [
                'id' => (int)\App\Database::lastInsertId(),
                'name' => $newName,
                'size' => $size,
                'url' => "/users/upload/{$year}/{$month}/" . $newName
            ];
        }

        return null;
    }

    /**
     * 图片上传接口（用于编辑器直接拖拽/粘贴）
     */
    public function upload(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        if (!empty($_FILES['file'])) {
            $res = Upload::handleUpload($_FILES['file']);
            if ($res) {
                echo json_encode(['success' => true, 'url' => $res['url'], 'file' => $res]);
                return;
            }
        }
        echo json_encode(['success' => false, 'error' => '上传失败']);
    }

    /**
     * 站点配置管理
     */
    public function settings(): void {
        $this->requireAuth();

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            
            // 密码安全加密处理：若填写了新密码则进行安全哈希加密，留空则保持原密码不变
            if (isset($data['admin_password'])) {
                $newPass = trim($data['admin_password']);
                if ($newPass !== '') {
                    $data['admin_password'] = password_hash($newPass, PASSWORD_DEFAULT);
                } else {
                    unset($data['admin_password']);
                }
            }

            Setting::updateMultiple($data);
            $message = '系统设置已保存成功！';
        }

        $settings = Setting::getAll();
        require VIEW_PATH . '/admin/settings.php';
    }

    /**
     * 内容排版与自适应优化工具页
     */
    public function contentCleaner(): void {
        $this->requireAuth();
        $scan = Post::scanResponsiveIssues();
        require VIEW_PATH . '/admin/content-cleaner.php';
    }

    /**
     * 内容自适应清洗 API（执行扫描或批量修复）
     */
    public function apiCleanResponsive(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $action = $_POST['action'] ?? $_GET['action'] ?? 'scan';

        try {
            if ($action === 'clean') {
                $res = Post::batchCleanResponsive();
                echo json_encode(['success' => true, 'data' => $res]);
            } else {
                $scan = Post::scanResponsiveIssues();
                echo json_encode(['success' => true, 'data' => $scan]);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 数据备份与转换管理页
     */
    public function backup(): void {
        $this->requireAuth();
        $config = require APP_PATH . '/Config.php';
        $driver = $config['db_driver'] ?? 'sqlite';
        $backups = \App\Models\BackupManager::getBackupList();
        
        // 读取已存在的 MySQL 配置（若有）
        $mysqlConfig = $config['mysql'] ?? [];
        $sqliteInfo = [
            'path' => $config['sqlite']['path'] ?? (DATA_PATH . '/blog.db'),
            'exists' => file_exists($config['sqlite']['path'] ?? (DATA_PATH . '/blog.db')),
            'size_formatted' => file_exists($config['sqlite']['path'] ?? (DATA_PATH . '/blog.db')) ? \App\Models\BackupManager::formatSize(filesize($config['sqlite']['path'])) : '0 B'
        ];

        require VIEW_PATH . '/admin/backup.php';
    }

    /**
     * 创建备份 API
     */
    public function createBackup(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $res = \App\Models\BackupManager::createBackup();
            echo json_encode($res);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 下载指定备份文件
     */
    public function downloadBackup(): void {
        $this->requireAuth();
        $file = basename($_GET['file'] ?? '');
        $path = \App\Models\BackupManager::getBackupDir() . '/' . $file;

        if (empty($file) || !file_exists($path)) {
            http_response_code(404);
            die('备份文件不存在');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * 删除备份 API
     */
    public function deleteBackup(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $file = basename($_POST['file'] ?? '');
            $res = \App\Models\BackupManager::deleteBackup($file);
            echo json_encode(['success' => $res, 'message' => $res ? '备份已成功删除' : '删除失败']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 还原 SQLite 备份 API
     */
    public function restoreBackup(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $file = basename($_POST['file'] ?? '');
            $res = \App\Models\BackupManager::restoreSqliteBackup($file);
            echo json_encode($res);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * MySQL 转 SQLite API
     */
    public function convertMysqlToSqlite(): void {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $mysqlConfig = [
                'host' => trim($_POST['host'] ?? '127.0.0.1'),
                'port' => (int)($_POST['port'] ?? 3306),
                'dbname' => trim($_POST['dbname'] ?? ''),
                'username' => trim($_POST['username'] ?? 'root'),
                'password' => $_POST['password'] ?? ''
            ];

            $res = \App\Models\BackupManager::convertMysqlToSqlite($mysqlConfig);
            echo json_encode($res);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}


