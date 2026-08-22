<?php
namespace App\Models;

use PDO;
use Exception;
use App\Database;

class BackupManager {
    /**
     * 获取备份目录
     */
    public static function getBackupDir(): string {
        $dir = DATA_PATH . '/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * 获取历史备份列表
     */
    public static function getBackupList(): array {
        $dir = self::getBackupDir();
        $files = scandir($dir);
        $list = [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
            $path = $dir . '/' . $f;
            if (is_file($path)) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $type = ($ext === 'db') ? 'SQLite 单文件数据库' : (($ext === 'sql') ? 'SQL 转储文件' : '备份文件');
                $size = filesize($path);
                $mtime = filemtime($path);

                $list[] = [
                    'filename' => $f,
                    'type' => $type,
                    'ext' => $ext,
                    'size' => $size,
                    'size_formatted' => self::formatSize($size),
                    'time' => $mtime,
                    'time_formatted' => date('Y-m-d H:i:s', $mtime),
                    'path' => $path
                ];
            }
        }

        // 按时间倒序排序
        usort($list, function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        return $list;
    }

    /**
     * 创建当前数据库的备份
     */
    public static function createBackup(): array {
        $config = require APP_PATH . '/Config.php';
        $driver = $config['db_driver'] ?? 'sqlite';
        $backupDir = self::getBackupDir();
        $dateStr = date('Ymd_His');

        if ($driver === 'sqlite') {
            $sqlitePath = $config['sqlite']['path'] ?? (DATA_PATH . '/blog.db');
            if (!file_exists($sqlitePath)) {
                throw new Exception("SQLite 数据库文件不存在: {$sqlitePath}");
            }

            // 1. 备份 .db 文件
            $targetFile = $backupDir . "/backup_sqlite_{$dateStr}.db";
            if (!copy($sqlitePath, $targetFile)) {
                throw new Exception("复制 SQLite 数据库文件失败");
            }

            return [
                'success' => true,
                'filename' => basename($targetFile),
                'size' => filesize($targetFile),
                'size_formatted' => self::formatSize(filesize($targetFile)),
                'message' => 'SQLite 数据库快照备份成功！'
            ];
        } else {
            // 2. MySQL 导出 SQL 文件
            $pdo = Database::getConn();
            $targetFile = $backupDir . "/backup_mysql_{$dateStr}.sql";
            $sqlContent = self::dumpMysqlToSql($pdo);

            if (empty($sqlContent) || strlen($sqlContent) < 50) {
                throw new Exception("MySQL 数据转储生成失败或内容为空");
            }

            $written = file_put_contents($targetFile, $sqlContent);
            if ($written === false || !file_exists($targetFile) || filesize($targetFile) === 0) {
                throw new Exception("写入 MySQL 备份文件失败，请检查 data/backups/ 目录写权限");
            }

            $fileSize = filesize($targetFile);

            return [
                'success' => true,
                'filename' => basename($targetFile),
                'size' => $fileSize,
                'size_formatted' => self::formatSize($fileSize),
                'message' => 'MySQL 数据库 SQL 转储备份成功！'
            ];
        }
    }

    /**
     * 删除指定备份
     */
    public static function deleteBackup(string $filename): bool {
        $filename = basename($filename); // 防路径穿越
        $path = self::getBackupDir() . '/' . $filename;
        if (file_exists($path) && is_file($path)) {
            return @unlink($path);
        }
        return false;
    }

    /**
     * 还原 SQLite 备份
     */
    public static function restoreSqliteBackup(string $filename): array {
        $config = require APP_PATH . '/Config.php';
        $sqlitePath = $config['sqlite']['path'] ?? (DATA_PATH . '/blog.db');
        $filename = basename($filename);
        $sourcePath = self::getBackupDir() . '/' . $filename;

        if (!file_exists($sourcePath)) {
            throw new Exception("指定的备份文件不存在: {$filename}");
        }

        // 先自动备份当前活跃数据库
        if (file_exists($sqlitePath)) {
            $autoBak = self::getBackupDir() . '/pre_restore_' . date('Ymd_His') . '.db';
            @copy($sqlitePath, $autoBak);
        }

        // 还原覆盖
        if (!copy($sourcePath, $sqlitePath)) {
            throw new Exception("还原覆盖 SQLite 数据库失败，请检查文件写权限");
        }

        // 清理缓存并 VACUUM
        clearstatcache();
        $pdo = new PDO("sqlite:" . $sqlitePath);
        $pdo->exec("PRAGMA optimize;");

        return [
            'success' => true,
            'message' => "已成功将数据库还原至备份 [{$filename}]！"
        ];
    }

    /**
     * 核心功能：将 MySQL 数据库一键完整转为 SQLite 数据库 (blog.db)
     */
    public static function convertMysqlToSqlite(array $mysqlConfig = []): array {
        // 1. 自动从配置文件解析 MySQL 连接
        $config = require APP_PATH . '/Config.php';
        $cfgMysql = $config['mysql'] ?? [];

        // 如果未传入或不完整，自动读取系统配置与 c_option.php
        $host = !empty($mysqlConfig['host']) ? $mysqlConfig['host'] : ($cfgMysql['host'] ?? '127.0.0.1');
        $port = !empty($mysqlConfig['port']) ? (int)$mysqlConfig['port'] : (int)($cfgMysql['port'] ?? 3306);
        $dbname = !empty($mysqlConfig['dbname']) ? $mysqlConfig['dbname'] : ($cfgMysql['dbname'] ?? '');
        $username = !empty($mysqlConfig['username']) ? $mysqlConfig['username'] : ($cfgMysql['username'] ?? 'root');
        $password = isset($mysqlConfig['password']) && $mysqlConfig['password'] !== '' ? $mysqlConfig['password'] : ($cfgMysql['password'] ?? '');
        $charset = $cfgMysql['charset'] ?? 'utf8mb4';

        if (empty($dbname)) {
            // 尝试从 c_option.php 检测
            if (!empty($config['c_option_source']) && file_exists($config['c_option_source'])) {
                $cOpt = @include $config['c_option_source'];
                if (is_array($cOpt) && !empty($cOpt['ZC_MYSQL_NAME'])) {
                    $host = $cOpt['ZC_MYSQL_SERVER'] ?? $host;
                    $port = (int)($cOpt['ZC_MYSQL_PORT'] ?? $port);
                    $dbname = $cOpt['ZC_MYSQL_NAME'];
                    $username = $cOpt['ZC_MYSQL_USERNAME'] ?? $username;
                    $password = $cOpt['ZC_MYSQL_PASSWORD'] ?? $password;
                }
            }
        }

        if (empty($dbname)) {
            throw new Exception("未在 c_option.php 或 Config.php 中检测到有效的 MySQL 数据库配置");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ];
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset}";
        }
        $mysqlPdo = new PDO($dsn, $username, $password, $options);

        // 2. 准备目标 SQLite 数据库
        $sqliteDir = DATA_PATH;
        if (!is_dir($sqliteDir)) @mkdir($sqliteDir, 0777, true);
        $targetSqlitePath = $sqliteDir . '/blog.db';

        // 若已存在 blog.db，先自动备份
        if (file_exists($targetSqlitePath)) {
            $backupDir = self::getBackupDir();
            $bakPath = $backupDir . '/pre_convert_' . date('Ymd_His') . '.db';
            @copy($targetSqlitePath, $bakPath);
        }

        // 连接目标 SQLite
        $sqlitePdo = new PDO("sqlite:" . $targetSqlitePath);
        $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sqlitePdo->exec("PRAGMA journal_mode = WAL;");
        $sqlitePdo->exec("PRAGMA synchronous = NORMAL;");

        // 3. 创建纯净 SQLite 表架构
        $sqlitePdo->exec("
            DROP TABLE IF EXISTS zbp_post;
            DROP TABLE IF EXISTS zbp_category;
            DROP TABLE IF EXISTS zbp_tag;
            DROP TABLE IF EXISTS zbp_upload;
            DROP TABLE IF EXISTS zbp_comment;
            DROP TABLE IF EXISTS sys_setting;

            CREATE TABLE zbp_post (
                log_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                log_CateID INTEGER NOT NULL DEFAULT 0,
                log_AuthorID INTEGER NOT NULL DEFAULT 1,
                log_Tag TEXT NOT NULL DEFAULT '',
                log_Status INTEGER NOT NULL DEFAULT 0,
                log_Type INTEGER NOT NULL DEFAULT 0,
                log_Alias TEXT NOT NULL DEFAULT '',
                log_IsTop INTEGER NOT NULL DEFAULT 0,
                log_Order INTEGER NOT NULL DEFAULT 0,
                log_Title TEXT NOT NULL DEFAULT '',
                log_Intro TEXT NOT NULL DEFAULT '',
                log_Content TEXT NOT NULL DEFAULT '',
                log_PostTime INTEGER NOT NULL DEFAULT 0,
                log_CommNums INTEGER NOT NULL DEFAULT 0,
                log_ViewNums INTEGER NOT NULL DEFAULT 0,
                log_Meta TEXT NOT NULL DEFAULT '',
                log_CreateTime INTEGER NOT NULL DEFAULT 0,
                log_UpdateTime INTEGER NOT NULL DEFAULT 0
            );
            CREATE INDEX IF NOT EXISTS idx_post_cate ON zbp_post (log_CateID);
            CREATE INDEX IF NOT EXISTS idx_post_posttime ON zbp_post (log_PostTime);
            CREATE INDEX IF NOT EXISTS idx_post_istop ON zbp_post (log_IsTop);
            CREATE INDEX IF NOT EXISTS idx_post_status ON zbp_post (log_Status);

            CREATE TABLE zbp_category (
                cate_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                cate_Name TEXT NOT NULL DEFAULT '',
                cate_Order INTEGER NOT NULL DEFAULT 0,
                cate_Count INTEGER NOT NULL DEFAULT 0,
                cate_Alias TEXT NOT NULL DEFAULT ''
            );

            CREATE TABLE zbp_tag (
                tag_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                tag_Name TEXT NOT NULL DEFAULT '',
                tag_Count INTEGER NOT NULL DEFAULT 0,
                tag_Alias TEXT NOT NULL DEFAULT ''
            );

            CREATE TABLE zbp_upload (
                ul_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                ul_AuthorID INTEGER NOT NULL DEFAULT 1,
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
            CREATE INDEX IF NOT EXISTS idx_upload_posttime ON zbp_upload (ul_PostTime);

            CREATE TABLE zbp_comment (
                comm_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                comm_LogID INTEGER NOT NULL DEFAULT 0,
                comm_AuthorID INTEGER NOT NULL DEFAULT 0,
                comm_Name TEXT NOT NULL DEFAULT '',
                comm_Content TEXT NOT NULL DEFAULT '',
                comm_Email TEXT NOT NULL DEFAULT '',
                comm_HomePage TEXT NOT NULL DEFAULT '',
                comm_PostTime INTEGER NOT NULL DEFAULT 0,
                comm_IP TEXT NOT NULL DEFAULT '',
                comm_Agent TEXT NOT NULL DEFAULT '',
                comm_ParentID INTEGER NOT NULL DEFAULT 0,
                comm_RootID INTEGER NOT NULL DEFAULT 0,
                comm_IsChecking INTEGER NOT NULL DEFAULT 0,
                comm_Meta TEXT NOT NULL DEFAULT ''
            );
            CREATE INDEX IF NOT EXISTS idx_comment_log ON zbp_comment (comm_LogID);

            CREATE TABLE sys_setting (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL DEFAULT ''
            );
        ");

        // 4. 开始高速事务迁移数据
        $sqlitePdo->beginTransaction();

        $allMysqlTables = $mysqlPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tablesMap = [];
        foreach ($allMysqlTables as $t) {
            $tablesMap[strtolower($t)] = $t;
        }

        $stats = [
            'categories' => 0,
            'tags' => 0,
            'posts' => 0,
            'uploads' => 0,
            'comments' => 0,
            'settings' => 0
        ];

        // 4.1 迁移分类
        if (isset($tablesMap['zbp_category'])) {
            $realTbl = $tablesMap['zbp_category'];
            $stmt = $mysqlPdo->query("SELECT cate_ID, cate_Name, cate_Order, cate_Count, cate_Alias FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT INTO zbp_category (cate_ID, cate_Name, cate_Order, cate_Count, cate_Alias) VALUES (?, ?, ?, ?, ?)");
            while ($row = $stmt->fetch()) {
                $ins->execute([
                    $row['cate_ID'],
                    $row['cate_Name'] ?? '',
                    (int)($row['cate_Order'] ?? 0),
                    (int)($row['cate_Count'] ?? 0),
                    $row['cate_Alias'] ?? ''
                ]);
                $stats['categories']++;
            }
        }

        // 4.2 迁移标签
        if (isset($tablesMap['zbp_tag'])) {
            $realTbl = $tablesMap['zbp_tag'];
            $stmt = $mysqlPdo->query("SELECT tag_ID, tag_Name, tag_Count, tag_Alias FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT INTO zbp_tag (tag_ID, tag_Name, tag_Count, tag_Alias) VALUES (?, ?, ?, ?)");
            while ($row = $stmt->fetch()) {
                $ins->execute([
                    $row['tag_ID'],
                    $row['tag_Name'] ?? '',
                    (int)($row['tag_Count'] ?? 0),
                    $row['tag_Alias'] ?? ''
                ]);
                $stats['tags']++;
            }
        }

        // 4.3 迁移文章（同步执行路径规范化与去冗余）
        if (isset($tablesMap['zbp_post'])) {
            $realTbl = $tablesMap['zbp_post'];
            $stmt = $mysqlPdo->query("SELECT * FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT INTO zbp_post (
                log_ID, log_CateID, log_AuthorID, log_Tag, log_Status, log_Type, log_Alias, log_IsTop, log_Order,
                log_Title, log_Intro, log_Content, log_PostTime, log_CommNums, log_ViewNums, log_Meta, log_CreateTime, log_UpdateTime
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            while ($row = $stmt->fetch()) {
                $intro = $row['log_Intro'] ?? '';
                $content = $row['log_Content'] ?? '';

                // 规范化资源路径
                $intro = str_replace(
                    ['zb_users/upload/', 'zb_system/image/filetype/', 'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'],
                    ['users/upload/', 'users/filetype/', 'users/filetype/'],
                    $intro
                );
                $content = str_replace(
                    ['zb_users/upload/', 'zb_system/image/filetype/', 'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'],
                    ['users/upload/', 'users/filetype/', 'users/filetype/'],
                    $content
                );

                $ins->execute([
                    $row['log_ID'],
                    (int)($row['log_CateID'] ?? 0),
                    (int)($row['log_AuthorID'] ?? 1),
                    $row['log_Tag'] ?? '',
                    (int)($row['log_Status'] ?? 0),
                    (int)($row['log_Type'] ?? 0),
                    $row['log_Alias'] ?? '',
                    (int)($row['log_IsTop'] ?? 0),
                    (int)($row['log_Order'] ?? 0),
                    $row['log_Title'] ?? '',
                    $intro,
                    $content,
                    (int)($row['log_PostTime'] ?? 0),
                    (int)($row['log_CommNums'] ?? 0),
                    (int)($row['log_ViewNums'] ?? 0),
                    $row['log_Meta'] ?? '',
                    (int)($row['log_CreateTime'] ?? 0),
                    (int)($row['log_UpdateTime'] ?? 0)
                ]);
                $stats['posts']++;
            }
        }

        // 4.4 迁移附件记录
        if (isset($tablesMap['zbp_upload'])) {
            $realTbl = $tablesMap['zbp_upload'];
            $stmt = $mysqlPdo->query("SELECT * FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT INTO zbp_upload (
                ul_ID, ul_AuthorID, ul_Size, ul_Name, ul_SourceName, ul_MimeType, ul_PostTime, ul_DownNums, ul_LogID, ul_Intro, ul_Meta
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            while ($row = $stmt->fetch()) {
                $ins->execute([
                    $row['ul_ID'],
                    (int)($row['ul_AuthorID'] ?? 1),
                    (int)($row['ul_Size'] ?? 0),
                    $row['ul_Name'] ?? '',
                    $row['ul_SourceName'] ?? '',
                    $row['ul_MimeType'] ?? '',
                    (int)($row['ul_PostTime'] ?? 0),
                    (int)($row['ul_DownNums'] ?? 0),
                    (int)($row['ul_LogID'] ?? 0),
                    $row['ul_Intro'] ?? '',
                    $row['ul_Meta'] ?? ''
                ]);
                $stats['uploads']++;
            }
        }

        // 4.5 迁移评论（若存在）
        if (isset($tablesMap['zbp_comment'])) {
            $realTbl = $tablesMap['zbp_comment'];
            $stmt = $mysqlPdo->query("SELECT * FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT INTO zbp_comment (
                comm_ID, comm_LogID, comm_AuthorID, comm_Name, comm_Content, comm_Email, comm_HomePage,
                comm_PostTime, comm_IP, comm_Agent, comm_ParentID, comm_RootID, comm_IsChecking, comm_Meta
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            while ($row = $stmt->fetch()) {
                $ins->execute([
                    $row['comm_ID'],
                    (int)($row['comm_LogID'] ?? 0),
                    (int)($row['comm_AuthorID'] ?? 0),
                    $row['comm_Name'] ?? '',
                    $row['comm_Content'] ?? '',
                    $row['comm_Email'] ?? '',
                    $row['comm_HomePage'] ?? '',
                    (int)($row['comm_PostTime'] ?? 0),
                    $row['comm_IP'] ?? '',
                    $row['comm_Agent'] ?? '',
                    (int)($row['comm_ParentID'] ?? 0),
                    (int)($row['comm_RootID'] ?? 0),
                    (int)($row['comm_IsChecking'] ?? 0),
                    $row['comm_Meta'] ?? ''
                ]);
                $stats['comments']++;
            }
        }

        // 4.6 迁移系统设置 sys_setting（若存在）
        if (isset($tablesMap['sys_setting'])) {
            $realTbl = $tablesMap['sys_setting'];
            $stmt = $mysqlPdo->query("SELECT `key`, `value` FROM `{$realTbl}`");
            $ins = $sqlitePdo->prepare("INSERT OR REPLACE INTO sys_setting (key, value) VALUES (?, ?)");
            while ($row = $stmt->fetch()) {
                $ins->execute([$row['key'], $row['value'] ?? '']);
                $stats['settings']++;
            }
        } else {
            // 初始化基础设置
            $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
            $defaultSettings = [
                'site_name' => '技术思维棱镜',
                'site_subtitle' => '专注技术记录、脚本折腾与实战经验分享',
                'author_name' => 'Brian',
                'author_bio' => '热爱技术与折腾',
                'admin_username' => 'admin',
                'admin_password' => $defaultPass
            ];
            $ins = $sqlitePdo->prepare("INSERT OR REPLACE INTO sys_setting (key, value) VALUES (?, ?)");
            foreach ($defaultSettings as $k => $v) {
                $ins->execute([$k, $v]);
                $stats['settings']++;
            }
        }

        // 提交事务
        $sqlitePdo->commit();

        // 5. 执行 SQLite 碎片整理与优化
        $sqlitePdo->exec("VACUUM;");
        $sqlitePdo->exec("PRAGMA optimize;");
        clearstatcache();
        $sqliteFileSize = filesize($targetSqlitePath);

        // 6. 写入安装锁，确保系统就绪
        @file_put_contents(DATA_PATH . '/install.lock', date('Y-m-d H:i:s') . " - Converted from MySQL\n");

        return [
            'success' => true,
            'stats' => $stats,
            'sqlite_file' => 'data/blog.db',
            'sqlite_size' => $sqliteFileSize,
            'sqlite_size_formatted' => self::formatSize($sqliteFileSize),
            'message' => "已成功将 MySQL [{$dbname}] 完整转换并生成独立 SQLite 数据库 (data/blog.db)！"
        ];
    }

    /**
     * 辅助函数：判断 MySQL 表是否存在
     */
    private static function tableExistsMysql(PDO $pdo, string $table): bool {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 辅助函数：导出 MySQL 为标准 SQL 转储（动态扫描全部数据表，高兼容性导出）
     */
    private static function dumpMysqlToSql(PDO $pdo): string {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            throw new Exception("当前 MySQL 数据库中未找到任何数据表");
        }

        $sql = "-- ============================================================\n";
        $sql .= "-- Blog Database Backup (MySQL Dump)\n";
        $sql .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Total Tables: " . count($tables) . "\n";
        $sql .= "-- ============================================================\n\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $tbl) {
            // 导结构
            $stmt = $pdo->query("SHOW CREATE TABLE `{$tbl}`");
            $createRow = $stmt->fetch(PDO::FETCH_NUM);
            if (!$createRow || empty($createRow[1])) continue;

            $sql .= "--\n-- Table structure for `{$tbl}`\n--\n\n";
            $sql .= "DROP TABLE IF EXISTS `{$tbl}`;\n";
            $sql .= $createRow[1] . ";\n\n";

            // 导数据
            $rows = $pdo->query("SELECT * FROM `{$tbl}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sql .= "--\n-- Dumping data for table `{$tbl}` (" . count($rows) . " rows)\n--\n\n";
                $chunkSize = 100;
                $chunks = array_chunk($rows, $chunkSize);
                foreach ($chunks as $chunk) {
                    $fields = array_keys($chunk[0]);
                    $valuesList = [];
                    foreach ($chunk as $row) {
                        $escapedValues = array_map(function ($val) use ($pdo) {
                            if ($val === null) return 'NULL';
                            return $pdo->quote($val);
                        }, array_values($row));
                        $valuesList[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    $sql .= "INSERT INTO `{$tbl}` (`" . implode('`, `', $fields) . "`) VALUES\n" . implode(",\n", $valuesList) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    }

    /**
     * 辅助函数：格式化字节大小
     */
    public static function formatSize(int $bytes): string {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
