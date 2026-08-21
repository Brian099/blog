<?php
namespace App\Models;

use App\Database;

class Tag {
    public static function getAll(): array {
        return Database::query("SELECT * FROM zbp_tag ORDER BY tag_Count DESC, tag_ID ASC");
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM zbp_tag WHERE tag_ID = ?", [$id]);
    }

    public static function save(array $data, ?int $id = null): int {
        $name = trim($data['name'] ?? '');
        $alias = trim($data['alias'] ?? '');

        if ($id && $id > 0) {
            Database::execute("UPDATE zbp_tag SET tag_Name = ?, tag_Alias = ? WHERE tag_ID = ?", [$name, $alias, $id]);
            return $id;
        } else {
            Database::execute("INSERT INTO zbp_tag (tag_Name, tag_Alias) VALUES (?, ?)", [$name, $alias]);
            return (int)Database::lastInsertId();
        }
    }

    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM zbp_tag WHERE tag_ID = ?", [$id]) > 0;
    }
}
