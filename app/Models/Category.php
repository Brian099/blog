<?php
namespace App\Models;

use App\Database;

class Category {
    public static function getAll(): array {
        return Database::query("SELECT * FROM zbp_category ORDER BY cate_Order ASC, cate_ID ASC");
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM zbp_category WHERE cate_ID = ?", [$id]);
    }

    public static function save(array $data, ?int $id = null): int {
        $name = trim($data['name'] ?? '');
        $alias = trim($data['alias'] ?? '');
        $order = (int)($data['order'] ?? 0);

        if ($id && $id > 0) {
            Database::execute("UPDATE zbp_category SET cate_Name = ?, cate_Alias = ?, cate_Order = ? WHERE cate_ID = ?", [$name, $alias, $order, $id]);
            return $id;
        } else {
            Database::execute("INSERT INTO zbp_category (cate_Name, cate_Alias, cate_Order) VALUES (?, ?, ?)", [$name, $alias, $order]);
            return (int)Database::lastInsertId();
        }
    }

    public static function delete(int $id): bool {
        // 重置所属文章分类为 0
        Database::execute("UPDATE zbp_post SET log_CateID = 0 WHERE log_CateID = ?", [$id]);
        return Database::execute("DELETE FROM zbp_category WHERE cate_ID = ?", [$id]) > 0;
    }
}
