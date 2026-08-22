<?php
namespace App;

use PDO;
use Exception;

class Database {
    private static ?PDO $instance = null;

    public static function getConn(): PDO {
        if (self::$instance === null) {
            $config = require APP_PATH . '/Config.php';

            // 核心数据库连接优先级规则：
            // 1. 优先使用本地 SQLite blog.db（只要存在 blog.db 文件，即使存在 c_option.php 也优先使用本地 SQLite）
            // 2. 无本地 SQLite blog.db 文件时，才尝试连接 MySQL (如来自 c_option.php 或环境变量)
            $sqlitePath = $config['sqlite']['path'] ?? (DATA_PATH . '/blog.db');
            
            if (file_exists($sqlitePath)) {
                try {
                    self::$instance = new PDO("sqlite:" . $sqlitePath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec("PRAGMA journal_mode = WAL;");
                    return self::$instance;
                } catch (Exception $e) {
                    // SQLite 异常，继续尝试备用驱动
                }
            }

            // 无本地 SQLite db 文件，尝试连接 MySQL
            $m = $config['mysql'] ?? [];
            if (!empty($m['host']) && !empty($m['dbname'])) {
                try {
                    $charset = !empty($m['charset']) ? $m['charset'] : 'utf8mb4';
                    if (strtolower($charset) === 'utf8') $charset = 'utf8mb4';
                    $port = (int)($m['port'] ?? 3306);
                    $dsn = "mysql:host={$m['host']};port={$port};dbname={$m['dbname']};charset={$charset}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 2, // 2秒超时快速检测
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                    ];
                    self::$instance = new PDO($dsn, $m['username'] ?? 'root', $m['password'] ?? '', $options);
                    return self::$instance;
                } catch (Exception $e) {
                    // MySQL 无法连通，记录原因供抛出
                    $lastError = $e->getMessage();
                }
            }

            // 若处于 CLI、API、AJAX 或 /install/ 子路由中，抛出标准异常让调用者捕获并返回 JSON
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApiOrInstallAction = (
                strpos($uri, '/install/') !== false ||
                strpos($uri, '/api/') !== false ||
                strpos($uri, '/admin/') !== false ||
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                php_sapi_name() === 'cli'
            );

            if ($isApiOrInstallAction) {
                $errDetail = isset($lastError) ? " (MySQL 错误: {$lastError})" : "";
                throw new Exception("数据库连接失败：未检测到 SQLite (data/blog.db) 且无法连接 MySQL{$errDetail}。请先配置数据库或在安装向导中选择 SQLite。");
            }

            // 前台普通页面访问且未安装，自动重定向到安装向导
            if (strpos($uri, '/install') === false) {
                header('Location: /install');
                exit;
            }

            throw new Exception("数据库未连接");
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

    public static function beginTransaction(): bool {
        return self::getConn()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConn()->commit();
    }

    public static function rollBack(): bool {
        return self::getConn()->rollBack();
    }
}
