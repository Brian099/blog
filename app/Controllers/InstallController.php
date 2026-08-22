<?php
namespace App\Controllers;

use PDO;
use Exception;
use App\Database;
use App\Models\Setting;
use App\Models\Post;

class InstallController {
    /**
     * 安装向导引导页
     */
    public function index(): void {
        $config = require APP_PATH . '/Config.php';
        $isInstalled = file_exists(DATA_PATH . '/install.lock');
        $force = isset($_GET['reinstall']) && $_GET['reinstall'] === '1';

        // 检测现有 Z-Blog c_option.php 配置
        $cOptionInfo = null;
        if (!empty($config['c_option_source']) && file_exists($config['c_option_source'])) {
            $cOpt = @include $config['c_option_source'];
            if (is_array($cOpt)) {
                $cOptionInfo = [
                    'file' => $config['c_option_source'],
                    'type' => $cOpt['ZC_DATABASE_TYPE'] ?? 'mysql',
                    'host' => $cOpt['ZC_MYSQL_SERVER'] ?? '127.0.0.1',
                    'port' => $cOpt['ZC_MYSQL_PORT'] ?? 3306,
                    'dbname' => $cOpt['ZC_MYSQL_NAME'] ?? '',
                    'username' => $cOpt['ZC_MYSQL_USERNAME'] ?? '',
                    'charset' => $cOpt['ZC_MYSQL_CHARSET'] ?? 'utf8mb4'
                ];
            }
        }

        require VIEW_PATH . '/install/index.php';
    }

    /**
     * 测试数据库连通性 API
     */
    public function testDb(): void {
        header('Content-Type: application/json; charset=utf-8');
        $type = $_POST['type'] ?? 'sqlite';

        try {
            if ($type === 'sqlite') {
                $path = DATA_PATH . '/blog.db';
                $pdo = new PDO("sqlite:" . $path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo json_encode(['success' => true, 'message' => 'SQLite 本地存储就绪']);
                return;
            }

            // MySQL
            $host = trim($_POST['host'] ?? '127.0.0.1');
            $port = (int)($_POST['port'] ?? 3306);
            $dbname = trim($_POST['dbname'] ?? '');
            $username = trim($_POST['username'] ?? 'root');
            $password = $_POST['password'] ?? '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
            ];
            $pdo = new PDO($dsn, $username, $password, $options);
            echo json_encode(['success' => true, 'message' => "MySQL [{$dbname}] 连接成功！"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 模式 A：从现有 Z-Blog 导入并自动执行全套规整与迁移
     */
    public function migrate(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $config = require APP_PATH . '/Config.php';
            $pdo = Database::getConn();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            // 1. 调用 ZblogMigrator 执行全套目录规整与数据库深度迁移
            $migrateRes = \App\Models\ZblogMigrator::runAll($pdo);
            $dirStats = $migrateRes['directories'] ?? [];
            $dbStats = $migrateRes['database'] ?? [];

            // 2. 初始化 / 更新 sys_setting 表
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS sys_setting (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL DEFAULT ''
                );");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `sys_setting` (
                    `key` varchar(191) NOT NULL,
                    `value` longtext NOT NULL,
                    PRIMARY KEY (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // 3. 写入站点与管理员信息
            $siteName = trim($_POST['site_name'] ?? '技术思维棱镜');
            $siteSubtitle = trim($_POST['site_subtitle'] ?? '专注技术记录、脚本折腾与实战经验分享');
            $authorName = trim($_POST['author_name'] ?? 'Brian');
            $authorBio = trim($_POST['author_bio'] ?? '热爱技术与折腾');
            $adminUser = trim($_POST['admin_username'] ?? 'admin');
            $adminPass = trim($_POST['admin_password'] ?? 'admin123');

            $passHash = password_hash($adminPass, PASSWORD_DEFAULT);

            $settings = [
                'site_name' => $siteName,
                'site_subtitle' => $siteSubtitle,
                'author_name' => $authorName,
                'author_bio' => $authorBio,
                'admin_username' => $adminUser,
                'admin_password' => $passHash
            ];

            foreach ($settings as $k => $v) {
                if ($driver === 'sqlite') {
                    $stmt = $pdo->prepare("INSERT OR REPLACE INTO sys_setting (key, value) VALUES (?, ?)");
                    $stmt->execute([$k, $v]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sys_setting (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                    $stmt->execute([$k, $v]);
                }
            }

            // 4. 写入安装锁
            @file_put_contents(DATA_PATH . '/install.lock', date('Y-m-d H:i:s') . " - Migrated from Z-Blog\n");

            // 统计文章与附件总数
            $postCount = (int)$pdo->query("SELECT COUNT(*) FROM zbp_post")->fetchColumn();
            $uploadCount = (int)$pdo->query("SELECT COUNT(*) FROM zbp_upload")->fetchColumn();

            echo json_encode([
                'success' => true,
                'message' => '从 Z-Blog 自动化规整、迁移并优化成功！',
                'data' => [
                    'driver' => $driver,
                    'total_posts' => $postCount,
                    'total_uploads' => $uploadCount,
                    'cleaned_posts' => $dbStats['cleaned_responsive_posts'] ?? 0,
                    'bytes_saved' => $dbStats['bytes_saved'] ?? 0,
                    'tables_dropped' => $dbStats['tables_dropped'] ?? 0,
                    'columns_dropped' => $dbStats['columns_dropped'] ?? 0,
                    'updated_paths_posts' => $dbStats['updated_paths_posts'] ?? 0,
                    'archived_items' => $dirStats['archived_items'] ?? 0,
                    'synced_icons' => $dirStats['synced_icons'] ?? 0
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 模式 B：全新安装
     */
    public function fresh(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $dbType = $_POST['db_type'] ?? 'sqlite';
            $pdo = null;

            if ($dbType === 'sqlite') {
                $dbDir = DATA_PATH;
                if (!is_dir($dbDir)) @mkdir($dbDir, 0777, true);
                $dbPath = $dbDir . '/blog.db';
                $pdo = new PDO("sqlite:" . $dbPath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec("PRAGMA journal_mode = WAL;");

                // 创建纯净 SQLite 架构
                $pdo->exec("
                DROP TABLE IF EXISTS zbp_post;
                DROP TABLE IF EXISTS zbp_category;
                DROP TABLE IF EXISTS zbp_tag;
                DROP TABLE IF EXISTS zbp_upload;
                DROP TABLE IF EXISTS zbp_comment;
                DROP TABLE IF EXISTS sys_setting;

                CREATE TABLE zbp_category (
                    cate_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                    cate_Name TEXT NOT NULL DEFAULT '',
                    cate_Order INTEGER NOT NULL DEFAULT 0,
                    cate_Type INTEGER NOT NULL DEFAULT 0,
                    cate_Count INTEGER NOT NULL DEFAULT 0,
                    cate_Alias TEXT NOT NULL DEFAULT '',
                    cate_Intro TEXT NOT NULL DEFAULT '',
                    cate_RootID INTEGER NOT NULL DEFAULT 0,
                    cate_ParentID INTEGER NOT NULL DEFAULT 0,
                    cate_Template TEXT NOT NULL DEFAULT '',
                    cate_LogTemplate TEXT NOT NULL DEFAULT '',
                    cate_Meta TEXT NOT NULL DEFAULT '',
                    cate_Group TEXT NOT NULL DEFAULT '',
                    cate_CreateTime INTEGER NOT NULL DEFAULT 0,
                    cate_UpdateTime INTEGER NOT NULL DEFAULT 0,
                    cate_PostTime INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE zbp_tag (
                    tag_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                    tag_Name TEXT NOT NULL DEFAULT '',
                    tag_Order INTEGER NOT NULL DEFAULT 0,
                    tag_Type INTEGER NOT NULL DEFAULT 0,
                    tag_Count INTEGER NOT NULL DEFAULT 0,
                    tag_Alias TEXT NOT NULL DEFAULT '',
                    tag_Intro TEXT NOT NULL DEFAULT '',
                    tag_Template TEXT NOT NULL DEFAULT '',
                    tag_Meta TEXT NOT NULL DEFAULT '',
                    tag_Group TEXT NOT NULL DEFAULT '',
                    tag_CreateTime INTEGER NOT NULL DEFAULT 0,
                    tag_UpdateTime INTEGER NOT NULL DEFAULT 0,
                    tag_PostTime INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE zbp_post (
                    log_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                    log_CateID INTEGER NOT NULL DEFAULT 0,
                    log_AuthorID INTEGER NOT NULL DEFAULT 1,
                    log_Tag TEXT NOT NULL DEFAULT '',
                    log_Status INTEGER NOT NULL DEFAULT 0,
                    log_Type INTEGER NOT NULL DEFAULT 0,
                    log_Alias TEXT NOT NULL DEFAULT '',
                    log_IsTop INTEGER NOT NULL DEFAULT 0,
                    log_IsLock INTEGER NOT NULL DEFAULT 0,
                    log_Title TEXT NOT NULL DEFAULT '',
                    log_Intro TEXT NOT NULL DEFAULT '',
                    log_Content TEXT NOT NULL DEFAULT '',
                    log_PostTime INTEGER NOT NULL DEFAULT 0,
                    log_CommNums INTEGER NOT NULL DEFAULT 0,
                    log_ViewNums INTEGER NOT NULL DEFAULT 0,
                    log_Template TEXT NOT NULL DEFAULT '',
                    log_Meta TEXT NOT NULL DEFAULT '',
                    log_CreateTime INTEGER NOT NULL DEFAULT 0,
                    log_UpdateTime INTEGER NOT NULL DEFAULT 0
                );

                CREATE TABLE zbp_upload (
                    ul_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                    ul_AuthorID INTEGER NOT NULL DEFAULT 0,
                    ul_Size INTEGER NOT NULL DEFAULT 0,
                    ul_Name TEXT NOT NULL DEFAULT '',
                    ul_SourceName TEXT NOT NULL DEFAULT '',
                    ul_MimeType TEXT NOT NULL DEFAULT '',
                    ul_PostTime INTEGER NOT NULL DEFAULT 0,
                    ul_DownNums INTEGER NOT NULL DEFAULT 0,
                    ul_LogID INTEGER NOT NULL DEFAULT 0,
                    ul_Intro TEXT NOT NULL DEFAULT '',
                    ul_Meta TEXT NOT NULL DEFAULT ''
                );

                CREATE TABLE zbp_comment (
                    comm_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                    comm_LogID INTEGER NOT NULL DEFAULT 0,
                    comm_IsChecking INTEGER NOT NULL DEFAULT 0,
                    comm_RootID INTEGER NOT NULL DEFAULT 0,
                    comm_ParentID INTEGER NOT NULL DEFAULT 0,
                    comm_AuthorID INTEGER NOT NULL DEFAULT 0,
                    comm_Name TEXT NOT NULL DEFAULT '',
                    comm_Email TEXT NOT NULL DEFAULT '',
                    comm_HomePage TEXT NOT NULL DEFAULT '',
                    comm_Content TEXT NOT NULL DEFAULT '',
                    comm_PostTime INTEGER NOT NULL DEFAULT 0,
                    comm_IP TEXT NOT NULL DEFAULT '',
                    comm_Agent TEXT NOT NULL DEFAULT '',
                    comm_Meta TEXT NOT NULL DEFAULT ''
                );

                CREATE TABLE sys_setting (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL DEFAULT ''
                );");
            } else {
                // MySQL
                $host = trim($_POST['host'] ?? '127.0.0.1');
                $port = (int)($_POST['port'] ?? 3306);
                $dbname = trim($_POST['dbname'] ?? 'zblog');
                $username = trim($_POST['username'] ?? 'root');
                $password = $_POST['password'] ?? '';
                $charset = 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                ]);

                // 创建纯净 MySQL 架构
                $pdo->exec("
                CREATE TABLE IF NOT EXISTS `zbp_category` (
                  `cate_ID` int(11) NOT NULL AUTO_INCREMENT,
                  `cate_Name` varchar(255) NOT NULL DEFAULT '',
                  `cate_Order` int(11) NOT NULL DEFAULT '0',
                  `cate_Type` int(11) NOT NULL DEFAULT '0',
                  `cate_Count` int(11) NOT NULL DEFAULT '0',
                  `cate_Alias` varchar(255) NOT NULL DEFAULT '',
                  `cate_Intro` text NOT NULL,
                  `cate_RootID` int(11) NOT NULL DEFAULT '0',
                  `cate_ParentID` int(11) NOT NULL DEFAULT '0',
                  `cate_Template` varchar(255) NOT NULL DEFAULT '',
                  `cate_LogTemplate` varchar(255) NOT NULL DEFAULT '',
                  `cate_Meta` longtext NOT NULL,
                  `cate_Group` varchar(255) NOT NULL DEFAULT '',
                  `cate_CreateTime` int(11) NOT NULL DEFAULT '0',
                  `cate_UpdateTime` int(11) NOT NULL DEFAULT '0',
                  `cate_PostTime` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`cate_ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `zbp_tag` (
                  `tag_ID` int(11) NOT NULL AUTO_INCREMENT,
                  `tag_Name` varchar(255) NOT NULL DEFAULT '',
                  `tag_Order` int(11) NOT NULL DEFAULT '0',
                  `tag_Type` int(11) NOT NULL DEFAULT '0',
                  `tag_Count` int(11) NOT NULL DEFAULT '0',
                  `tag_Alias` varchar(255) NOT NULL DEFAULT '',
                  `tag_Intro` text NOT NULL,
                  `tag_Template` varchar(255) NOT NULL DEFAULT '',
                  `tag_Meta` longtext NOT NULL,
                  `tag_Group` varchar(255) NOT NULL DEFAULT '',
                  `tag_CreateTime` int(11) NOT NULL DEFAULT '0',
                  `tag_UpdateTime` int(11) NOT NULL DEFAULT '0',
                  `tag_PostTime` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`tag_ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `zbp_post` (
                  `log_ID` int(11) NOT NULL AUTO_INCREMENT,
                  `log_CateID` int(11) NOT NULL DEFAULT '0',
                  `log_AuthorID` int(11) NOT NULL DEFAULT '1',
                  `log_Tag` varchar(255) NOT NULL DEFAULT '',
                  `log_Status` int(11) NOT NULL DEFAULT '0',
                  `log_Type` int(11) NOT NULL DEFAULT '0',
                  `log_Alias` varchar(255) NOT NULL DEFAULT '',
                  `log_IsTop` int(11) NOT NULL DEFAULT '0',
                  `log_IsLock` tinyint(1) NOT NULL DEFAULT '0',
                  `log_Title` varchar(255) NOT NULL DEFAULT '',
                  `log_Intro` text NOT NULL,
                  `log_Content` longtext NOT NULL,
                  `log_PostTime` int(11) NOT NULL DEFAULT '0',
                  `log_CommNums` int(11) NOT NULL DEFAULT '0',
                  `log_ViewNums` int(11) NOT NULL DEFAULT '0',
                  `log_Template` varchar(255) NOT NULL DEFAULT '',
                  `log_Meta` longtext NOT NULL,
                  `log_CreateTime` int(11) NOT NULL DEFAULT '0',
                  `log_UpdateTime` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`log_ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `zbp_upload` (
                  `ul_ID` int(11) NOT NULL AUTO_INCREMENT,
                  `ul_AuthorID` int(11) NOT NULL DEFAULT '0',
                  `ul_Size` int(11) NOT NULL DEFAULT '0',
                  `ul_Name` varchar(255) NOT NULL DEFAULT '',
                  `ul_SourceName` varchar(255) NOT NULL DEFAULT '',
                  `ul_MimeType` varchar(255) NOT NULL DEFAULT '',
                  `ul_PostTime` int(11) NOT NULL DEFAULT '0',
                  `ul_DownNums` int(11) NOT NULL DEFAULT '0',
                  `ul_LogID` int(11) NOT NULL DEFAULT '0',
                  `ul_Intro` text NOT NULL,
                  `ul_Meta` longtext NOT NULL,
                  PRIMARY KEY (`ul_ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `zbp_comment` (
                  `comm_ID` int(11) NOT NULL AUTO_INCREMENT,
                  `comm_LogID` int(11) NOT NULL DEFAULT '0',
                  `comm_IsChecking` tinyint(1) NOT NULL DEFAULT '0',
                  `comm_RootID` int(11) NOT NULL DEFAULT '0',
                  `comm_ParentID` int(11) NOT NULL DEFAULT '0',
                  `comm_AuthorID` int(11) NOT NULL DEFAULT '0',
                  `comm_Name` varchar(255) NOT NULL DEFAULT '',
                  `comm_Email` varchar(255) NOT NULL DEFAULT '',
                  `comm_HomePage` varchar(255) NOT NULL DEFAULT '',
                  `comm_Content` text NOT NULL,
                  `comm_PostTime` int(11) NOT NULL DEFAULT '0',
                  `comm_IP` varchar(255) NOT NULL DEFAULT '',
                  `comm_Agent` text NOT NULL,
                  `comm_Meta` longtext NOT NULL,
                  PRIMARY KEY (`comm_ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `sys_setting` (
                  `key` varchar(191) NOT NULL,
                  `value` longtext NOT NULL,
                  PRIMARY KEY (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // 写入初始示例数据
            $time = time();
            $pdo->exec("INSERT INTO zbp_category (cate_ID, cate_Name, cate_Count, cate_CreateTime, cate_UpdateTime, cate_PostTime) VALUES (1, '默认分类', 1, {$time}, {$time}, {$time})");
            $pdo->exec("INSERT INTO zbp_tag (tag_ID, tag_Name, tag_Count, tag_CreateTime, tag_UpdateTime, tag_PostTime) VALUES (1, '入门指南', 1, {$time}, {$time}, {$time})");

            $welcomeTitle = '欢迎使用全新的极客博客系统！';
            $welcomeContent = '<p>恭喜您！系统已成功安装就绪。</p><p>本系统采用现代化极简双栏瀑布流设计，支持全站年份归档树索引、文章内容自适应屏显优化、毫秒级全文检索与高效单机运行。</p><p>您可以在后台随时发布新文章、管理分类标签与多媒体资源。</p>';
            $welcomeIntro = '恭喜您！系统已成功安装就绪。本系统采用现代化极简双栏瀑布流设计，支持全站年份归档树索引与高效运行。';

            $stmt = $pdo->prepare("INSERT INTO zbp_post (log_CateID, log_AuthorID, log_Tag, log_Title, log_Intro, log_Content, log_PostTime, log_CreateTime, log_UpdateTime) VALUES (1, 1, '{1}', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$welcomeTitle, $welcomeIntro, $welcomeContent, $time, $time, $time]);

            // 写入配置信息
            $siteName = trim($_POST['site_name'] ?? '我的技术博客');
            $siteSubtitle = trim($_POST['site_subtitle'] ?? '记录思考与技术实践');
            $authorName = trim($_POST['author_name'] ?? 'Admin');
            $authorBio = trim($_POST['author_bio'] ?? '热爱技术与分享');
            $adminUser = trim($_POST['admin_username'] ?? 'admin');
            $adminPass = trim($_POST['admin_password'] ?? 'admin123');

            $passHash = password_hash($adminPass, PASSWORD_DEFAULT);

            $settings = [
                'site_name' => $siteName,
                'site_subtitle' => $siteSubtitle,
                'author_name' => $authorName,
                'author_bio' => $authorBio,
                'admin_username' => $adminUser,
                'admin_password' => $passHash
            ];

            foreach ($settings as $k => $v) {
                if ($dbType === 'sqlite') {
                    $stmt = $pdo->prepare("INSERT OR REPLACE INTO sys_setting (key, value) VALUES (?, ?)");
                    $stmt->execute([$k, $v]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sys_setting (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                    $stmt->execute([$k, $v]);
                }
            }

            // 写入安装锁
            @file_put_contents(DATA_PATH . '/install.lock', date('Y-m-d H:i:s') . " - Fresh Installed ({$dbType})\n");

            echo json_encode([
                'success' => true,
                'message' => '全新博客系统初始化成功！'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
