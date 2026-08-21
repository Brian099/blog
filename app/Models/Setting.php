<?php
namespace App\Models;

use App\Database;

class Setting {
    private static array $cache = [];

    private static bool $tableChecked = false;

    public static function ensureTable(): void {
        if (self::$tableChecked) return;
        self::$tableChecked = true;

        $config = require APP_PATH . '/Config.php';
        $driver = $config['db_driver'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                Database::execute("CREATE TABLE IF NOT EXISTS sys_setting (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '')");
            } else {
                Database::execute("CREATE TABLE IF NOT EXISTS `sys_setting` (`key` varchar(191) NOT NULL, `value` longtext NOT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // 预置默认站点配置（如果表为空）
            $countRow = Database::fetchOne("SELECT COUNT(*) as c FROM sys_setting");
            if (empty($countRow['c'])) {
                $defaults = [
                    'site_name' => '技术思维棱镜',
                    'site_subtitle' => '专注技术记录、脚本折腾与实战经验分享',
                    'author_name' => 'Brian',
                    'author_bio' => '热爱技术与折腾',
                    'admin_username' => 'admin',
                    'admin_password' => 'admin123'
                ];
                foreach ($defaults as $k => $v) {
                    if ($driver === 'sqlite') {
                        Database::execute("INSERT OR IGNORE INTO sys_setting (key, value) VALUES (?, ?)", [$k, $v]);
                    } else {
                        Database::execute("INSERT IGNORE INTO `sys_setting` (`key`, `value`) VALUES (?, ?)", [$k, $v]);
                    }
                }
            }
        } catch (\Exception $e) {
            // 容错处理
        }
    }

    public static function get(string $key, string $default = ''): string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        self::ensureTable();
        try {
            $row = Database::fetchOne("SELECT value FROM sys_setting WHERE key = ?", [$key]);
            $val = $row ? $row['value'] : $default;
        } catch (\Exception $e) {
            $val = $default;
        }
        self::$cache[$key] = $val;
        return $val;
    }

    public static function getAll(): array {
        self::ensureTable();
        try {
            $rows = Database::query("SELECT key, value FROM sys_setting");
            $res = [];
            foreach ($rows as $r) {
                $res[$r['key']] = $r['value'];
            }
            return $res;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function set(string $key, string $value): void {
        self::ensureTable();
        $config = require APP_PATH . '/Config.php';
        $driver = $config['db_driver'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                Database::execute(
                    "INSERT INTO sys_setting (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value",
                    [$key, $value]
                );
            } else {
                Database::execute(
                    "INSERT INTO `sys_setting` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                    [$key, $value]
                );
            }
        } catch (\Exception $e) {}

        self::$cache[$key] = $value;
    }

    public static function updateMultiple(array $pairs): void {
        foreach ($pairs as $k => $v) {
            self::set($k, (string)$v);
        }
    }
}
