<?php
namespace App\Models;

use App\Database;
use PDO;

class Setting {
    private static array $cache = [];
    private static bool $tableChecked = false;

    public static function ensureTable(): void {
        if (self::$tableChecked) return;
        self::$tableChecked = true;

        try {
            $pdo = Database::getConn();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `sys_setting` (
                    `key` TEXT PRIMARY KEY,
                    `value` TEXT NOT NULL DEFAULT ''
                );");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `sys_setting` (
                    `key` varchar(191) NOT NULL,
                    `value` longtext NOT NULL,
                    PRIMARY KEY (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // 预置默认站点配置（如果表为空）
            $countRow = Database::fetchOne("SELECT COUNT(*) as c FROM `sys_setting`");
            if (empty($countRow['c'])) {
                $defaults = [
                    'site_name' => '技术思维棱镜',
                    'site_subtitle' => '专注技术记录、脚本折腾与实战经验分享',
                    'author_name' => 'Brian',
                    'author_bio' => '热爱技术与折腾',
                    'admin_username' => 'admin',
                    'admin_password' => password_hash('admin123', PASSWORD_DEFAULT)
                ];
                foreach ($defaults as $k => $v) {
                    if ($driver === 'sqlite') {
                        Database::execute("INSERT OR IGNORE INTO `sys_setting` (`key`, `value`) VALUES (?, ?)", [$k, $v]);
                    } else {
                        Database::execute("INSERT IGNORE INTO `sys_setting` (`key`, `value`) VALUES (?, ?)", [$k, $v]);
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录异常日志以便排查
            error_log("Setting::ensureTable Error: " . $e->getMessage());
        }
    }

    public static function get(string $key, string $default = ''): string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        self::ensureTable();
        try {
            $row = Database::fetchOne("SELECT `value` FROM `sys_setting` WHERE `key` = ?", [$key]);
            $val = $row ? $row['value'] : $default;
        } catch (\Exception $e) {
            error_log("Setting::get Error: " . $e->getMessage());
            $val = $default;
        }
        self::$cache[$key] = $val;
        return $val;
    }

    public static function getAll(): array {
        self::ensureTable();
        try {
            $rows = Database::query("SELECT `key`, `value` FROM `sys_setting`");
            $res = [];
            foreach ($rows as $r) {
                $res[$r['key']] = $r['value'];
            }
            return $res;
        } catch (\Exception $e) {
            error_log("Setting::getAll Error: " . $e->getMessage());
            return [];
        }
    }

    public static function set(string $key, string $value): void {
        self::ensureTable();
        try {
            $driver = Database::getConn()->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                Database::execute(
                    "INSERT INTO `sys_setting` (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.value",
                    [$key, $value]
                );
            } else {
                Database::execute(
                    "INSERT INTO `sys_setting` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                    [$key, $value]
                );
            }
            self::$cache[$key] = $value;
        } catch (\Exception $e) {
            error_log("Setting::set Error: " . $e->getMessage());
        }
    }

    public static function updateMultiple(array $pairs): void {
        foreach ($pairs as $k => $v) {
            self::set($k, (string)$v);
        }
    }
}
