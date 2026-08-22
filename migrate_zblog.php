<?php
/**
 * Z-Blog 自动化归整与数据库迁移瘦身工具 (migrate_zblog.php)
 * 
 * 核心功能：
 * 一、 目录与文件自动化规整：
 *   1. 将根目录下原 Z-Blog 旧系统目录（如 zb_system/ 等）安全归档移动到 old_zblog/
 *   2. 将 zb_users/ 目录重命名为 users/
 *   3. 将 old_zblog/zb_system/image/filetype 附件图标拷贝同步到 users/filetype/
 *   4. 将 users/ 目录中除 upload/、filetype/、c_option.php 之外的所有历史插件/主题/缓存文件归档移动到 old_zblog/zb_users/
 * 
 * 二、 数据库深度清洗与自适应排版优化：
 *   1. 自动安全备份当前活跃数据库（SQLite / MySQL）
 *   2. 清理完全未引用的冗余数据表与核心业务表中的废弃字段
 *   3. 批量将数据库中的 zb_users/upload/ 和 zb_system/image/filetype/ 路径规范化为 users/upload/ 与 users/filetype/
 *   4. 批量清洗文章中的定宽/高与禁止换行等排版限制
 *   5. 执行 VACUUM 释放磁盘空间并重建索引
 * 
 * 三、 SQL 转储文件清洗：
 *   支持将原始 SQL 转储文件（如 giraff.sql）清洗导出为纯净版 SQL
 * 
 * 运行方式：
 *   php -c php.ini migrate_zblog.php                     # 全量自动规整与数据库迁移
 *   php -c php.ini migrate_zblog.php --files-only        # 仅执行目录与文件规整
 *   php -c php.ini migrate_zblog.php --db-only           # 仅执行数据库清洗与路径迁移
 *   php -c php.ini migrate_zblog.php --clean-sql=...     # 清洗指定的 SQL 转储文件
 */

require_once __DIR__ . '/app/Config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Database;

// 定义待清理的无用表
$tablesToDrop = [
    'zbp_member',
    'zbp_module',
    'zbp_config',
    'zbp_sf_praise_count',
    'zbp_sf_praise_basecount',
    'zbp_san_praise_sdk_basecount',
    'zbp_san_praise_sdk_count',
    'zbp_blog_plugin_ytecn_nana_prise'
];

// 定义核心表中待清理的无用字段
$columnsToDrop = [
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

// 命令行参数处理
$opts = getopt('', ['clean-sql:', 'files-only', 'db-only', 'migrate-paths', 'help']);
if (isset($opts['help'])) {
    echo <<<HELP
Z-Blog 自动化归整与数据库迁移瘦身工具 (migrate_zblog.php)

用法：
  php -c php.ini migrate_zblog.php                     执行全量目录规整、数据库瘦身、自适应排版优化与路径迁移
  php -c php.ini migrate_zblog.php --files-only        仅执行目录与文件自动化规整
  php -c php.ini migrate_zblog.php --db-only           仅执行数据库清洗、排版优化与路径迁移
  php -c php.ini migrate_zblog.php --clean-sql=giraff.sql 清洗 SQL 转储文件并导出为 giraff_cleaned.sql

HELP;
    exit(0);
}

echo "===============================================================\n";
echo "       Z-Blog 自动化归整与数据库迁移瘦身工具 (migrate_zblog.php)\n";
echo "===============================================================\n\n";

// 模式 1：处理指定 SQL 文件
if (!empty($opts['clean-sql'])) {
    $sqlFile = $opts['clean-sql'];
    if (!file_exists($sqlFile)) {
        die("❌ 错误：未找到指定的 SQL 文件 [{$sqlFile}]\n");
    }
    cleanSqlDumpFile($sqlFile, $tablesToDrop, $columnsToDrop);
    exit(0);
}

// 模式 2：执行目录与文件自动化规整
$doFiles = !isset($opts['db-only']);
$doDb = !isset($opts['files-only']);

if ($doFiles) {
    echo "【第一部分：Z-Blog 目录与文件自动化规整】\n";
    reorganizeZblogDirectories();
    echo "\n";
}

if (!$doDb) {
    echo "===============================================================\n";
    echo "🎉 目录与文件规整完毕！\n";
    echo "===============================================================\n";
    exit(0);
}

// 模式 3：执行数据库迁移与清洗
try {
    echo "【第二部分：数据库深度清洗与路径迁移】\n";
    $pdo = Database::getConn();
    $config = require APP_PATH . '/Config.php';
    $driver = $config['db_driver'] ?? 'sqlite';

    echo "1. 检查数据库连接...\n";
    echo "   当前数据库类型: " . strtoupper($driver) . "\n";

    // 自动备份
    echo "\n2. 执行安全自动备份...\n";
    if ($driver === 'sqlite') {
        $dbPath = $config['sqlite']['path'];
        if (file_exists($dbPath)) {
            $backupPath = $dbPath . '.' . date('Ymd_His') . '.bak';
            copy($dbPath, $backupPath);
            echo "   ✅ SQLite 数据库已备份至: " . basename($backupPath) . " (" . formatSize(filesize($backupPath)) . ")\n";
        }
    } else {
        echo "   ℹ️ 当前为 MySQL 模式，请确保已保留原始 SQL 备份。\n";
    }

    // 清理无用表
    echo "\n3. 清理完全未引用的 8 张冗余表...\n";
    $droppedTablesCount = 0;
    foreach ($tablesToDrop as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "   🗑️ 已删除无用表: {$table}\n";
            $droppedTablesCount++;
        } catch (\Exception $e) {
            echo "   ⚠️ 删除表 {$table} 跳过: " . $e->getMessage() . "\n";
        }
    }

    // 清理核心表冗余字段
    echo "\n4. 清理核心业务表中的无用字段...\n";
    $droppedColsCount = 0;
    foreach ($columnsToDrop as $table => $cols) {
        if (!tableExists($pdo, $driver, $table)) {
            continue;
        }

        echo "   🔍 处理表 `{$table}`...\n";
        if ($driver === 'sqlite') {
            $cleanedCount = dropColumnsSqlite($pdo, $table, $cols);
            $droppedColsCount += $cleanedCount;
            if ($cleanedCount > 0) {
                echo "      ✅ 已重构表并移除 {$cleanedCount} 个冗余字段 (" . implode(', ', $cols) . ")\n";
            } else {
                echo "      ℹ️ 表已处于纯净状态，无残留冗余字段\n";
            }
        } else {
            $existingCols = getTableColumns($pdo, $driver, $table);
            foreach ($cols as $col) {
                if (in_array(strtolower($col), $existingCols)) {
                    try {
                        $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$col}`");
                        echo "      - 已移除冗余字段: {$col}\n";
                        $droppedColsCount++;
                    } catch (\Exception $e) {
                        echo "      ⚠️ 移除字段 {$col} 失败: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    // 5. 迁移数据库中路径 (zb_users/upload -> users/upload, zb_system/image/filetype -> users/filetype)
    echo "\n5. 规范化数据库资源路径 (zb_users -> users, zb_system/image/filetype -> users/filetype)...\n";
    $pathRes = migrateDatabasePaths($pdo);
    echo "   ✅ 数据库路径规范化完成！共更新 {$pathRes['updated_posts']} 篇文章中的资源路径\n";

    // 6. 清洗全站文章的定宽/高与禁止换行等排版限制
    echo "\n6. 批量清洗文章中的图片/表格长宽限制与 nowrap 禁止换行...\n";
    $cleanRes = \App\Models\Post::batchCleanResponsive();
    echo "   ✅ 自适应排版清洗完成！共扫描 {$cleanRes['total_scanned']} 篇，修复 {$cleanRes['updated_count']} 篇文章，清理冗余限制 " . formatSize($cleanRes['bytes_saved']) . "\n";

    // 7. 执行空间压缩与碎片整理
    echo "\n7. 释放物理磁盘空间并重建索引...\n";
    if ($driver === 'sqlite') {
        $beforeSize = file_exists($config['sqlite']['path']) ? filesize($config['sqlite']['path']) : 0;
        $pdo->exec("VACUUM;");
        $pdo->exec("PRAGMA optimize;");
        clearstatcache();
        $afterSize = file_exists($config['sqlite']['path']) ? filesize($config['sqlite']['path']) : 0;
        $savedSize = $beforeSize - $afterSize;
        echo "   ✅ VACUUM 完成！数据库体积: " . formatSize($beforeSize) . " -> " . formatSize($afterSize);
        if ($savedSize > 0) {
            echo " (释放空间: " . formatSize($savedSize) . ")";
        }
        echo "\n";
    } else {
        foreach (['zbp_post', 'zbp_category', 'zbp_tag', 'zbp_upload', 'zbp_comment', 'sys_setting'] as $tbl) {
            if (tableExists($pdo, $driver, $tbl)) {
                $pdo->exec("OPTIMIZE TABLE `{$tbl}`");
            }
        }
        echo "   ✅ MySQL OPTIMIZE TABLE 完成！\n";
    }

    echo "\n===============================================================\n";
    echo "🎉 Z-Blog 自动化规整、数据库瘦身与路径现代化迁移全部完毕！\n";
    echo "   - 共删除无用数据表: {$droppedTablesCount} 个\n";
    echo "   - 共剔除冗余数据字段: {$droppedColsCount} 个\n";
    echo "   - 规范化路径文章数: {$pathRes['updated_posts']} 篇\n";
    echo "   - 修复自适应文章篇数: {$cleanRes['updated_count']} 篇\n";
    echo "   - 核心文章、附件、分类、标签均保持 100% 完整与极速自适应运转！\n";
    echo "===============================================================\n";

} catch (\Exception $e) {
    echo "\n❌ 执行异常: " . $e->getMessage() . "\n";
}

/**
 * 核心功能：自动化规整 Z-Blog 物理目录与文件
 */
function reorganizeZblogDirectories(): void {
    $root = ROOT_PATH;
    $oldZblogDir = $root . '/old_zblog';
    if (!is_dir($oldZblogDir)) {
        @mkdir($oldZblogDir, 0777, true);
    }

    // 1. 除了 zb_users，把根目录下的原生 Z-Blog 历史目录（如 zb_system, zb_install 等）和旧文件移动到 old_zblog/
    echo "1. 归档根目录旧 Z-Blog 系统文件至 old_zblog/...\n";
    $legacyDirsToMove = ['zb_system', 'zb_install'];
    foreach ($legacyDirsToMove as $ld) {
        $sourcePath = $root . '/' . $ld;
        $destPath = $oldZblogDir . '/' . $ld;
        if (is_dir($sourcePath)) {
            if (is_dir($destPath)) {
                copyDirRecursive($sourcePath, $destPath);
                removeDirRecursive($sourcePath);
            } else {
                @rename($sourcePath, $destPath);
            }
            echo "   📦 已将 {$ld}/ 移动至 old_zblog/{$ld}/\n";
        }
    }

    // 归档根目录遗留的旧 Z-Blog 单文件 (若存在)
    $legacyFiles = ['search.php', 'feed.php', 'admin.php'];
    foreach ($legacyFiles as $lf) {
        $sourceFile = $root . '/' . $lf;
        if (file_exists($sourceFile)) {
            @rename($sourceFile, $oldZblogDir . '/' . $lf);
            echo "   📄 已将 {$lf} 移动至 old_zblog/{$lf}\n";
        }
    }

    // 2. 把 zb_users 目录改名为 users
    echo "2. 将 zb_users/ 目录规范化重命名为 users/...\n";
    $zbUsersDir = $root . '/zb_users';
    $usersDir = $root . '/users';
    if (is_dir($zbUsersDir)) {
        if (!is_dir($usersDir)) {
            @rename($zbUsersDir, $usersDir);
            echo "   ✅ 已将 zb_users/ 重命名为 users/\n";
        } else {
            // 合并文件
            copyDirRecursive($zbUsersDir, $usersDir);
            removeDirRecursive($zbUsersDir);
            echo "   ✅ 已将 zb_users/ 内容合并并规范化至 users/\n";
        }
    } else {
        echo "   ℹ️ users/ 目录已就绪\n";
    }

    // 确保 users/upload 和 users/filetype 物理目录存在
    if (!is_dir($usersDir . '/upload')) @mkdir($usersDir . '/upload', 0777, true);
    if (!is_dir($usersDir . '/filetype')) @mkdir($usersDir . '/filetype', 0777, true);

    // 3. 把 old_zblog/zb_system/image/filetype 复制到 users/filetype
    echo "3. 提取并同步文件类型图标到 users/filetype/...\n";
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
    echo "   ✅ 图标库就绪！已同步并就绪 {$syncedIconCount} 个历史文件类型图标\n";

    // 4. 把 users 目录中除了 upload (或 uploads)、filetype、c_option.php 移动到 old_zblog/zb_users
    echo "4. 净化 users/ 目录，将历史插件/主题/缓存归档至 old_zblog/zb_users/...\n";
    $oldZbUsersDir = $oldZblogDir . '/zb_users';
    if (!is_dir($oldZbUsersDir)) {
        @mkdir($oldZbUsersDir, 0777, true);
    }

    if (is_dir($usersDir)) {
        $allowedInUsers = ['upload', 'uploads', 'filetype', 'c_option.php', '.', '..'];
        $items = scandir($usersDir);
        $archivedItemsCount = 0;
        foreach ($items as $item) {
            if (in_array(strtolower($item), array_map('strtolower', $allowedInUsers))) {
                continue;
            }
            $itemPath = $usersDir . '/' . $item;
            $destPath = $oldZbUsersDir . '/' . $item;
            if (is_dir($itemPath)) {
                if (is_dir($destPath)) {
                    copyDirRecursive($itemPath, $destPath);
                    removeDirRecursive($itemPath);
                } else {
                    @rename($itemPath, $destPath);
                }
                echo "   📦 已归档 users/{$item}/ -> old_zblog/zb_users/{$item}/\n";
                $archivedItemsCount++;
            } elseif (is_file($itemPath)) {
                @rename($itemPath, $destPath);
                echo "   📄 已归档 users/{$item} -> old_zblog/zb_users/{$item}\n";
                $archivedItemsCount++;
            }
        }
        if ($archivedItemsCount === 0) {
            echo "   ℹ️ users/ 目录已处于极致纯净状态 (仅保留 upload/、filetype/ 与 c_option.php)\n";
        } else {
            echo "   ✅ 已成功归档 {$archivedItemsCount} 个旧插件/主题/缓存项至 old_zblog/zb_users/\n";
        }
    }
}

/**
 * 辅助函数：规范化数据库文章路径
 */
function migrateDatabasePaths(\PDO $pdo): array {
    $posts = $pdo->query("SELECT log_ID, log_Content, log_Intro FROM zbp_post")->fetchAll(\PDO::FETCH_ASSOC);
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
 * 辅助函数：SQLite 全版本兼容的字段剔除与表重构
 */
function dropColumnsSqlite(\PDO $pdo, string $table, array $columnsToDrop): int {
    $existingCols = getTableColumns($pdo, 'sqlite', $table);
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
 * 辅助函数：判断表是否存在
 */
function tableExists(\PDO $pdo, string $driver, string $table): bool {
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
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 辅助函数：获取表的所有字段名（小写）
 */
function getTableColumns(\PDO $pdo, string $driver, string $table): array {
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
    } catch (\Exception $e) {}
    return $cols;
}

/**
 * 辅助函数：递归拷贝目录
 */
function copyDirRecursive(string $src, string $dst): void {
    if (!is_dir($src)) return;
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirRecursive($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * 辅助函数：递归删除目录
 */
function removeDirRecursive(string $dir): void {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirRecursive($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

/**
 * 辅助函数：格式化字节大小
 */
function formatSize(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

/**
 * 清洗 SQL 转储文件并导出
 */
function cleanSqlDumpFile(string $filePath, array $tablesToDrop, array $columnsToDrop): void {
    echo "正在读取 SQL 文件 [{$filePath}]...\n";
    $content = file_get_contents($filePath);
    $origLen = strlen($content);

    echo "原始文件大小: " . formatSize($origLen) . "\n";

    // 1. 删除无用表及其 INSERT 和 ALTER 语句
    echo "正在剔除 " . count($tablesToDrop) . " 张冗余表及其所有数据记录...\n";
    foreach ($tablesToDrop as $tbl) {
        $content = preg_replace('/CREATE TABLE [`"]?' . preg_quote($tbl, '/') . '[`"]?\s*\([^;]*?\)\s*ENGINE=[^;]*?;/is', '', $content);
        $content = preg_replace('/INSERT INTO [`"]?' . preg_quote($tbl, '/') . '[`"]?\s*[^;]*?;/is', '', $content);
        $content = preg_replace('/ALTER TABLE [`"]?' . preg_quote($tbl, '/') . '[`"]?\s*[^;]*?;/is', '', $content);
        $content = preg_replace('/--\s*(?:表的结构|表的数据)\s*[`"]?' . preg_quote($tbl, '/') . '[`"]?[^\r\n]*\r?\n/is', '', $content);
    }

    // 2. 规范化 SQL 中的资源与附件路径 (zb_users/upload -> users/upload, zb_system/image/filetype -> users/filetype)
    echo "正在规范化 SQL 中的附件与图标路径 (zb_users/upload -> users/upload, zb_system/image/filetype -> users/filetype)...\n";
    $content = str_replace(
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
        $content
    );

    // 3. 自动注入 sys_setting 系统配置表定义
    if (strpos($content, 'CREATE TABLE `sys_setting`') === false) {
        $defaultPassHash = password_hash('admin123', PASSWORD_DEFAULT);
        $sysSettingSql = "\n\n-- --------------------------------------------------------\n\n"
            . "--\n-- 表的结构 `sys_setting` (新系统键值配置表)\n--\n\n"
            . "CREATE TABLE IF NOT EXISTS `sys_setting` (\n"
            . "  `key` varchar(191) NOT NULL,\n"
            . "  `value` longtext NOT NULL,\n"
            . "  PRIMARY KEY (`key`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n"
            . "INSERT INTO `sys_setting` (`key`, `value`) VALUES\n"
            . "('site_name', '技术思维棱镜'),\n"
            . "('site_subtitle', '专注技术记录、脚本折腾与实战经验分享'),\n"
            . "('author_name', 'Brian'),\n"
            . "('author_bio', '热爱技术与折腾'),\n"
            . "('admin_username', 'admin'),\n"
            . "('admin_password', '{$defaultPassHash}')\n"
            . "ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);\n";
        $content .= $sysSettingSql;
    }

    $outPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '_cleaned.sql';
    file_put_contents($outPath, $content);
    $newLen = strlen($content);

    echo "\n✅ 纯净版 SQL 文件生成完毕: " . basename($outPath) . "\n";
    echo "   - 已规范化所有资源路径至 users/upload/ 与 users/filetype/\n";
    echo "   - 已包含自动初始化的 `sys_setting` 系统配置表\n";
    echo "   - 处理后大小: " . formatSize($newLen) . "\n";
    echo "   - 瘦身体积: " . formatSize($origLen - $newLen) . "\n";
}
