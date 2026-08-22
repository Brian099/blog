<?php
namespace App\Models;

use PDO;
use Exception;
use App\Database;
use App\Models\Post;

class ZblogMigrator {
    /**
     * 待清理的无用表
     */
    public static array $tablesToDrop = [
        'zbp_member',
        'zbp_module',
        'zbp_config',
        'zbp_sf_praise_count',
        'zbp_sf_praise_basecount',
        'zbp_san_praise_sdk_basecount',
        'zbp_san_praise_sdk_count',
        'zbp_blog_plugin_ytecn_nana_prise'
    ];

    /**
     * 待清理的核心表冗余字段
     */
    public static array $columnsToDrop = [
        'zbp_post' => [
            'log_IsLock',
            'log_Template'
        ],
        'zbp_category' => [
            'cate_Template',
            'cate_LogTemplate',
            'cate_Intro',
            'cate_Meta',
            'cate_Group',
            'cate_RootID',
            'cate_ParentID',
            'cate_Type',
            'cate_CreateTime',
            'cate_UpdateTime',
            'cate_PostTime'
        ],
        'zbp_tag' => [
            'tag_Order',
            'tag_Type',
            'tag_Intro',
            'tag_Template',
            'tag_Meta',
            'tag_Group',
            'tag_CreateTime',
            'tag_UpdateTime',
            'tag_PostTime'
        ]
    ];

    /**
     * 执行全量自动化迁移（目录与文件归整 + 数据库深度清洗优化）
     */
    public static function runAll(?PDO $pdo = null): array {
        // 1. 目录与文件自动化规整
        $dirStats = self::reorganizeDirectories();

        // 2. 数据库深度清洗
        if ($pdo === null) {
            $pdo = Database::getConn();
        }
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dbStats = self::migrateDatabase($pdo, $driver);

        return [
            'directories' => $dirStats,
            'database' => $dbStats
        ];
    }

    /**
     * 1. 自动化规整 Z-Blog 物理目录与文件
     */
    public static function reorganizeDirectories(): array {
        $root = ROOT_PATH;
        $oldZblogDir = $root . '/old_zblog';
        if (!is_dir($oldZblogDir)) {
            @mkdir($oldZblogDir, 0777, true);
        }

        $logs = [];

        // 1. 除了 zb_users，把根目录下的原生 Z-Blog 历史目录（如 zb_system, zb_install 等）和旧文件移动到 old_zblog/
        $legacyDirsToMove = ['zb_system', 'zb_install'];
        foreach ($legacyDirsToMove as $ld) {
            $sourcePath = $root . '/' . $ld;
            $destPath = $oldZblogDir . '/' . $ld;
            if (is_dir($sourcePath)) {
                if (is_dir($destPath)) {
                    self::copyDirRecursive($sourcePath, $destPath);
                    self::removeDirRecursive($sourcePath);
                } else {
                    @rename($sourcePath, $destPath);
                }
                $logs[] = "已将 {$ld}/ 移动至 old_zblog/{$ld}/";
            }
        }

        // 归档根目录遗留的旧 Z-Blog 单文件
        $legacyFiles = ['search.php', 'feed.php', 'admin.php'];
        foreach ($legacyFiles as $lf) {
            $sourceFile = $root . '/' . $lf;
            if (file_exists($sourceFile)) {
                @rename($sourceFile, $oldZblogDir . '/' . $lf);
                $logs[] = "已将 {$lf} 移动至 old_zblog/{$lf}";
            }
        }

        // 2. 把 zb_users 目录改名为 users
        $zbUsersDir = $root . '/zb_users';
        $usersDir = $root . '/users';
        if (is_dir($zbUsersDir)) {
            if (!is_dir($usersDir)) {
                @rename($zbUsersDir, $usersDir);
                $logs[] = "已将 zb_users/ 重命名为 users/";
            } else {
                self::copyDirRecursive($zbUsersDir, $usersDir);
                self::removeDirRecursive($zbUsersDir);
                $logs[] = "已将 zb_users/ 内容合并并规范化至 users/";
            }
        }

        // 确保 users/upload 和 users/filetype 物理目录存在
        if (!is_dir($usersDir . '/upload')) @mkdir($usersDir . '/upload', 0777, true);
        if (!is_dir($usersDir . '/filetype')) @mkdir($usersDir . '/filetype', 0777, true);

        // 3. 把 old_zblog/zb_system/image/filetype 复制到 users/filetype
        $filetypeSources = [
            $oldZblogDir . '/zb_system/image/filetype',
            $root . '/zb_system/image/filetype',
            $usersDir . '/plugin/Neditor/dialogs/attachment/fileTypeImages',
            $oldZblogDir . '/zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages'
        ];
        $syncedIconCount = 0;
        foreach ($filetypeSources as $fSrc) {
            if (is_dir($fSrc)) {
                $files = scandir($fSrc);
                foreach ($files as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $sFile = $fSrc . '/' . $f;
                    $dFile = $usersDir . '/filetype/' . $f;
                    if (is_file($sFile) && !file_exists($dFile)) {
                        @copy($sFile, $dFile);
                        $syncedIconCount++;
                    }
                }
            }
        }
        if ($syncedIconCount > 0) {
            $logs[] = "已同步 {$syncedIconCount} 个历史文件类型图标到 users/filetype/";
        }

        // 4. 把 users 目录中除了 upload (或 uploads)、filetype、c_option.php 移动到 old_zblog/zb_users
        $oldZbUsersDir = $oldZblogDir . '/zb_users';
        if (!is_dir($oldZbUsersDir)) {
            @mkdir($oldZbUsersDir, 0777, true);
        }

        $archivedItemsCount = 0;
        if (is_dir($usersDir)) {
            $allowedInUsers = ['upload', 'uploads', 'filetype', 'c_option.php', '.', '..'];
            $items = scandir($usersDir);
            foreach ($items as $item) {
                if (in_array(strtolower($item), array_map('strtolower', $allowedInUsers))) {
                    continue;
                }
                $itemPath = $usersDir . '/' . $item;
                $destPath = $oldZbUsersDir . '/' . $item;
                if (is_dir($itemPath)) {
                    if (is_dir($destPath)) {
                        self::copyDirRecursive($itemPath, $destPath);
                        self::removeDirRecursive($itemPath);
                    } else {
                        @rename($itemPath, $destPath);
                    }
                    $logs[] = "已归档 users/{$item}/ -> old_zblog/zb_users/{$item}/";
                    $archivedItemsCount++;
                } elseif (is_file($itemPath)) {
                    @rename($itemPath, $destPath);
                    $logs[] = "已归档 users/{$item} -> old_zblog/zb_users/{$item}";
                    $archivedItemsCount++;
                }
            }
        }

        return [
            'success' => true,
            'synced_icons' => $syncedIconCount,
            'archived_items' => $archivedItemsCount,
            'logs' => $logs
        ];
    }

    /**
     * 2. 数据库深度清洗、冗余剔除与路径现代化
     */
    public static function migrateDatabase(PDO $pdo, string $driver): array {
        // 2.1 剔除无用表
        $droppedTables = 0;
        foreach (self::$tablesToDrop as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                $droppedTables++;
            } catch (Exception $e) {}
        }

        // 2.2 剔除冗余字段
        $droppedCols = 0;
        foreach (self::$columnsToDrop as $table => $cols) {
            if (!self::tableExists($pdo, $driver, $table)) continue;

            if ($driver === 'sqlite') {
                $droppedCols += self::dropColumnsSqlite($pdo, $table, $cols);
            } else {
                $existingCols = self::getTableColumns($pdo, $driver, $table);
                foreach ($cols as $col) {
                    if (in_array(strtolower($col), $existingCols)) {
                        try {
                            $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$col}`");
                            $droppedCols++;
                        } catch (Exception $e) {}
                    }
                }
            }
        }

        // 2.3 规范化数据库资源路径 (zb_users/upload -> users/upload, zb_system/image/filetype -> users/filetype)
        $pathRes = self::migrateDatabasePaths($pdo);

        // 2.4 清洗排版定宽高与 nowrap
        $cleanStats = Post::batchCleanResponsive();

        // 2.5 空间压缩
        if ($driver === 'sqlite') {
            try {
                $pdo->exec("VACUUM;");
                $pdo->exec("PRAGMA optimize;");
            } catch (Exception $e) {}
        } else {
            foreach (['zbp_post', 'zbp_category', 'zbp_tag', 'zbp_upload', 'zbp_comment', 'sys_setting'] as $tbl) {
                if (self::tableExists($pdo, $driver, $tbl)) {
                    try { $pdo->exec("OPTIMIZE TABLE `{$tbl}`"); } catch (Exception $e) {}
                }
            }
        }

        return [
            'tables_dropped' => $droppedTables,
            'columns_dropped' => $droppedCols,
            'updated_paths_posts' => $pathRes['updated_posts'] ?? 0,
            'cleaned_responsive_posts' => $cleanStats['updated_count'] ?? 0,
            'bytes_saved' => $cleanStats['bytes_saved'] ?? 0
        ];
    }

    /**
     * 规范化数据库文章路径
     */
    public static function migrateDatabasePaths(PDO $pdo): array {
        $posts = $pdo->query("SELECT log_ID, log_Content, log_Intro FROM zbp_post")->fetchAll(PDO::FETCH_ASSOC);
        $updated = 0;
        $updateStmt = $pdo->prepare("UPDATE zbp_post SET log_Content = ?, log_Intro = ? WHERE log_ID = ?");

        foreach ($posts as $post) {
            $c = $post['log_Content'] ?? '';
            $intro = $post['log_Intro'] ?? '';

            $newC = str_replace(
                [
                    'zb_users/upload/',
                    'zb_system/image/filetype/',
                    'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'
                ],
                [
                    'users/upload/',
                    'users/filetype/',
                    'users/filetype/'
                ],
                $c
            );

            $newIntro = str_replace(
                [
                    'zb_users/upload/',
                    'zb_system/image/filetype/',
                    'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'
                ],
                [
                    'users/upload/',
                    'users/filetype/',
                    'users/filetype/'
                ],
                $intro
            );

            if ($newC !== $c || $newIntro !== $intro) {
                $updateStmt->execute([$newC, $newIntro, $post['log_ID']]);
                $updated++;
            }
        }

        return [
            'updated_posts' => $updated
        ];
    }

    /**
     * SQLite 字段剔除与表重构
     */
    public static function dropColumnsSqlite(PDO $pdo, string $table, array $columnsToDrop): int {
        $existingCols = self::getTableColumns($pdo, 'sqlite', $table);
        $colsLowerToDrop = array_map('strtolower', $columnsToDrop);
        $colsToKeep = [];
        foreach ($existingCols as $c) {
            if (!in_array($c, $colsLowerToDrop)) {
                $colsToKeep[] = $c;
            }
        }

        if (count($colsToKeep) === count($existingCols)) {
            return 0;
        }

        $colsStr = implode(', ', array_map(function($c) { return "`{$c}`"; }, $colsToKeep));
        
        $tempTable = "{$table}_clean_temp";
        $pdo->exec("DROP TABLE IF EXISTS `{$tempTable}`");
        $pdo->exec("CREATE TABLE `{$tempTable}` AS SELECT {$colsStr} FROM `{$table}`");
        $pdo->exec("DROP TABLE `{$table}`");
        $pdo->exec("ALTER TABLE `{$tempTable}` RENAME TO `{$table}`");
        
        return count($existingCols) - count($colsToKeep);
    }

    /**
     * 判断表是否存在
     */
    public static function tableExists(PDO $pdo, string $driver, string $table): bool {
        try {
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
                $stmt->execute([$table]);
                return (bool)$stmt->fetch();
            } else {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                return (bool)$stmt->fetch();
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取表的所有字段名（小写）
     */
    public static function getTableColumns(PDO $pdo, string $driver, string $table): array {
        $cols = [];
        try {
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info(`{$table}`)");
                while ($row = $stmt->fetch()) {
                    $cols[] = strtolower($row['name']);
                }
            } else {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                while ($row = $stmt->fetch()) {
                    $cols[] = strtolower($row['Field']);
                }
            }
        } catch (Exception $e) {}
        return $cols;
    }

    /**
     * 递归拷贝目录
     */
    public static function copyDirRecursive(string $src, string $dst): void {
        if (!is_dir($src)) return;
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::copyDirRecursive($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * 递归删除目录
     */
    public static function removeDirRecursive(string $dir): void {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::removeDirRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
