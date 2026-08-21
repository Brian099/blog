<?php
namespace App\Models;

use App\Database;

class Setting {
    private static array $cache = [];

    public static function get(string $key, string $default = ''): string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $row = Database::fetchOne("SELECT value FROM sys_setting WHERE key = ?", [$key]);
        $val = $row ? $row['value'] : $default;
        self::$cache[$key] = $val;
        return $val;
    }

    public static function getAll(): array {
        $rows = Database::query("SELECT key, value FROM sys_setting");
        $res = [];
        foreach ($rows as $r) {
            $res[$r['key']] = $r['value'];
        }
        return $res;
    }

    public static function set(string $key, string $value): void {
        Database::execute(
            "INSERT INTO sys_setting (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value",
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    public static function updateMultiple(array $pairs): void {
        foreach ($pairs as $k => $v) {
            self::set($k, (string)$v);
        }
    }
}
