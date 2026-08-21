<?php
namespace App;

use PDO;
use Exception;

class Database {
    private static ?PDO $instance = null;

    public static function getConn(): PDO {
        if (self::$instance === null) {
            $config = require APP_PATH . '/Config.php';
            $driver = $config['db_driver'] ?? 'sqlite';

            try {
                if ($driver === 'sqlite') {
                    $dbPath = $config['sqlite']['path'];
                    self::$instance = new PDO("sqlite:" . $dbPath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec("PRAGMA journal_mode = WAL;");
                } else {
                    $m = $config['mysql'];
                    $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['dbname']};charset={$m['charset']}";
                    self::$instance = new PDO($dsn, $m['username'], $m['password']);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): array {
        $stmt = self::getConn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::getConn()->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch();
        return $res ? $res : null;
    }

    public static function execute(string $sql, array $params = []): int {
        $stmt = self::getConn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string {
        return self::getConn()->lastInsertId();
    }
}
