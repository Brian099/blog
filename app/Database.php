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
                    $charset = !empty($m['charset']) ? $m['charset'] : 'utf8mb4';
                    // 统一为 utf8mb4 保证 emoji 与多语言支持
                    if (strtolower($charset) === 'utf8') $charset = 'utf8mb4';

                    $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['dbname']};charset={$charset}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 2, // 2秒超时快速检测
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                    ];
                    self::$instance = new PDO($dsn, $m['username'], $m['password'], $options);
                }
            } catch (Exception $e) {
                // 若 MySQL 无法连通（例如本地测试环境没有 NAS Docker 网络 172.17.0.1），且存在本地 SQLite 数据库，则平滑回退至 SQLite
                if ($driver === 'mysql' && !empty($config['sqlite']['path']) && file_exists($config['sqlite']['path'])) {
                    try {
                        $dbPath = $config['sqlite']['path'];
                        self::$instance = new PDO("sqlite:" . $dbPath);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        self::$instance->exec("PRAGMA journal_mode = WAL;");
                        return self::$instance;
                    } catch (Exception $se) {
                        // fallback failed, continue to error display
                    }
                }

                die("<div style='font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;color:#991b1b;line-height:1.6;'>
                    <h3 style='margin-top:0;'>❌ 数据库连接异常</h3>
                    <p>驱动类型: <strong>" . strtoupper($driver) . "</strong></p>
                    <p>错误信息: " . htmlspecialchars($e->getMessage()) . "</p>
                    <p style='font-size:0.85rem;color:#7f1d1d;'>提示：当前优先尝试连接 <code>c_option.php</code> 中的 MySQL 数据库。若在本地 Windows 测试环境运行，请确认 MySQL 服务已启动，或移除本地目录下的 <code>c_option.php</code> 即可自动切回本地 SQLite (blog.db)。</p>
                </div>");
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
