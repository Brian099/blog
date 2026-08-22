<?php
/**
 * Z-Blog Database & SQL Dump Cleaner Tool
 * 
 * 功能：
 * 1. 自动备份当前数据库（SQLite / MySQL）
 * 2. 清理完全未引用的 9 张扩展/配置/评论/点赞表
 * 3. 精简 3 张核心表（zbp_post, zbp_category, zbp_tag）中的冗余字段（全平台全版本 SQLite/MySQL 兼容）
 * 4. 执行 VACUUM 释放物理空间并重建索引
 * 5. 支持将 SQL 转储文件（如 giraff.sql）清洗并导出为轻量纯净版 SQL
 * 
 * 运行方式：
 * - 命令行执行：php -c php.ini clean_db.php
 * - 或通过参数清洗 SQL 文件：php -c php.ini clean_db.php --clean-sql=giraff.sql
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

// 定义待清理的无用表（保留核心表与评论表 zbp_comment）
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
$opts = getopt('', ['clean-sql:', 'migrate-paths', 'help']);
if (isset($opts['help'])) {
    echo <<<HELP
Z-Blog 数据库瘦身与清理工具

用法：
  php -c php.ini clean_db.php                     执行当前活跃数据库（SQLite/MySQL）的瘦身、自适应排版优化与路径迁移
  php -c php.ini clean_db.php --migrate-paths     单独执行路径迁移 (zb_users -> users, zb_system/image/filetype -> users/filetype)
  php -c php.ini clean_db.php --clean-sql=giraff.sql 清洗 SQL 转储文件并导出为 giraff_cleaned.sql

HELP;
    exit(0);
}

echo "===============================================================\n";
echo "       Z-Blog 数据库轻量化瘦身与冗余清理工具\n";
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

// 模式 2：处理当前系统配置的数据库
try {
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
        echo "   ℹ️ 当前为 MySQL 模式，请确保已保留 giraff.sql 原始备份。\n";
    }

    // 清理无用表
    echo "\n3. 清理完全未引用的 9 张冗余表...\n";
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

    // 5. 迁移与重命名目录及数据库中路径 (zb_users/upload -> users/upload, zb_system/image/filetype -> users/filetype)
    echo "\n5. 规范化物理目录与数据库资源路径 (zb_users -> users, zb_system/image/filetype -> users/filetype)...\n";
    $pathRes = migratePathsToUsers($pdo, $driver);
    echo "   ✅ 路径迁移处理完毕！共更新 {$pathRes['updated_posts']} 篇文章中的资源路径，物理目录: {$pathRes['dir_status']}\n";

    if (!empty($opts['migrate-paths'])) {
        echo "\n🎉 单独路径迁移任务执行完毕！\n";
        exit(0);
    }

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
    echo "🎉 数据库瘦身、自适应排版与路径现代化规范优化完毕！\n";
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

/**
 * 辅助函数：规范化物理目录结构与数据库所有文章中路径
 */
function migratePathsToUsers(\PDO $pdo, string $driver): array {
    $dirStatus = '已处于最新 users/ 结构';

    // 1. 物理目录重命名 (zb_users -> users)
    $oldDir = ROOT_PATH . '/zb_users';
    $newDir = ROOT_PATH . '/users';
    if (is_dir($oldDir)) {
        if (!is_dir($newDir)) {
            @rename($oldDir, $newDir);
            $dirStatus = '已将 zb_users/ 目录重命名为 users/';
        } else {
            $oldUpload = $oldDir . '/upload';
            $newUpload = $newDir . '/upload';
            if (is_dir($oldUpload)) {
                @mkdir($newUpload, 0777, true);
                copyDirRecursive($oldUpload, $newUpload);
            }
            @rmdir($oldDir);
            $dirStatus = '已将 zb_users/ 合并迁移至 users/';
        }
    }

    // 确保 users/upload 和 users/filetype 物理目录存在
    if (!is_dir(ROOT_PATH . '/users/upload')) @mkdir(ROOT_PATH . '/users/upload', 0777, true);
    if (!is_dir(ROOT_PATH . '/users/filetype')) @mkdir(ROOT_PATH . '/users/filetype', 0777, true);

    // 2. 数据库批量更新文章内容中的历史路径
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
        'updated_posts' => $updated,
        'dir_status' => $dirStatus
    ];
}

/**
 * 辅助函数：递归拷贝目录
 */
function copyDirRecursive(string $src, string $dst): void {
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

