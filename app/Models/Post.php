<?php
namespace App\Models;

use App\Database;
use App\Helpers;

class Post {
    /**
     * 获取按年份分组的文章树（左侧栏使用，包含全量公开文章）
     */
    public static function getYearGroupedTree(?int $categoryId = null, ?int $tagId = null, string $keyword = ''): array {
        $sql = "SELECT log_ID, log_Title, log_PostTime, log_CateID, log_Tag, log_ViewNums, log_IsTop, log_Meta 
                FROM zbp_post 
                WHERE log_Status = 0 AND log_Type = 0";
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND log_CateID = ?";
            $params[] = $categoryId;
        }

        if (!empty($keyword)) {
            $sql .= " AND (log_Title LIKE ? OR log_Content LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        // 置顶优先，其次按发布时间倒序
        $sql .= " ORDER BY log_IsTop DESC, log_PostTime DESC";

        $rows = Database::query($sql, $params);
        
        // 如果有 tagId 过滤，在内存中过滤 tags（Z-blog 使用 {1}{2} 格式）
        if ($tagId !== null && $tagId > 0) {
            $tagNeedle = "{" . $tagId . "}";
            $rows = array_filter($rows, function($r) use ($tagNeedle) {
                return strpos($r['log_Tag'], $tagNeedle) !== false;
            });
        }

        $tree = [];
        foreach ($rows as $row) {
            $year = Helpers::getYear((int)$row['log_PostTime']);
            if (!isset($tree[$year])) {
                $tree[$year] = [];
            }

            $isProtected = false;
            if (!empty($row['log_Meta'])) {
                $meta = json_decode($row['log_Meta'], true);
                if (is_array($meta) && !empty($meta['password'])) {
                    $isProtected = true;
                }
            }

            $tree[$year][] = [
                'id' => (int)$row['log_ID'],
                'title' => $row['log_Title'],
                'post_time' => (int)$row['log_PostTime'],
                'date_str' => Helpers::formatDate((int)$row['log_PostTime'], 'm-d'),
                'is_top' => (int)$row['log_IsTop'],
                'views' => (int)$row['log_ViewNums'],
                'is_protected' => $isProtected
            ];
        }

        return $tree;
    }

    /**
     * 获取单篇文章详情（包含分类、标签解析）
     */
    public static function getDetail(int $id): ?array {
        $sql = "SELECT * FROM zbp_post WHERE log_ID = ?";
        $post = Database::fetchOne($sql, [$id]);
        if (!$post) return null;

        // 获取分类
        $category = null;
        if (!empty($post['log_CateID'])) {
            $category = Category::getById((int)$post['log_CateID']);
        }

        // 解析标签
        $tags = [];
        if (!empty($post['log_Tag'])) {
            if (preg_match_all('/\{(\d+)\}/', $post['log_Tag'], $m)) {
                $tagIds = $m[1];
                if (!empty($tagIds)) {
                    $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                    $tags = Database::query("SELECT tag_ID, tag_Name, tag_Alias FROM zbp_tag WHERE tag_ID IN ($placeholders)", $tagIds);
                }
            }
        }

        // 处理正文 HTML 和阅读时间
        $processedContent = Helpers::processContent($post['log_Content']);
        $readTime = Helpers::estimateReadingTime($post['log_Content']);

        // 上一篇与下一篇
        $prev = Database::fetchOne(
            "SELECT log_ID, log_Title FROM zbp_post WHERE log_Status = 0 AND log_Type = 0 AND log_PostTime < ? ORDER BY log_PostTime DESC LIMIT 1",
            [$post['log_PostTime']]
        );
        $next = Database::fetchOne(
            "SELECT log_ID, log_Title FROM zbp_post WHERE log_Status = 0 AND log_Type = 0 AND log_PostTime > ? ORDER BY log_PostTime ASC LIMIT 1",
            [$post['log_PostTime']]
        );

        // 解析 log_Meta 中的自定义密码
        $meta = [];
        if (!empty($post['log_Meta'])) {
            $decoded = json_decode($post['log_Meta'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            } elseif (preg_match('/password[:=]([^\s;,]+)/i', $post['log_Meta'], $m)) {
                $meta['password'] = trim($m[1]);
            }
        }

        $password = $meta['password'] ?? '';
        $isProtected = !empty($password);

        // 检查当前访问者是否已在 Session 中解锁或为已登录管理员
        $isUnlocked = false;
        if (!$isProtected) {
            $isUnlocked = true;
        } else {
            if (!empty($_SESSION['admin_logged_in'])) {
                $isUnlocked = true;
            } elseif (!empty($_SESSION['unlocked_posts'][(int)$post['log_ID']])) {
                $isUnlocked = true;
            }
        }

        return [
            'id' => (int)$post['log_ID'],
            'title' => $post['log_Title'],
            'content_raw' => $post['log_Content'],
            'content' => $processedContent,
            'intro' => $post['log_Intro'],
            'post_time' => (int)$post['log_PostTime'],
            'date_formatted' => Helpers::formatDate((int)$post['log_PostTime']),
            'read_time' => $readTime,
            'views' => (int)$post['log_ViewNums'],
            'status' => (int)$post['log_Status'],
            'is_top' => (int)$post['log_IsTop'],
            'category' => $category,
            'cate_id' => (int)$post['log_CateID'],
            'tags' => $tags,
            'tags_raw' => $post['log_Tag'],
            'alias' => $post['log_Alias'] ?? '',
            'meta' => $meta,
            'password' => $password,
            'is_protected' => $isProtected,
            'is_unlocked' => $isUnlocked,
            'prev' => $prev,
            'next' => $next
        ];
    }

    /**
     * 验证文章访问密码并解锁
     */
    public static function unlock(int $id, string $inputPassword): bool {
        $post = self::getDetail($id);
        if (!$post || !$post['is_protected']) {
            return true;
        }

        if (trim($inputPassword) === (string)$post['password']) {
            if (!isset($_SESSION['unlocked_posts'])) {
                $_SESSION['unlocked_posts'] = [];
            }
            $_SESSION['unlocked_posts'][$id] = true;
            return true;
        }

        return false;
    }

    /**
     * 后台通过 ID 获取单篇用于编辑
     */
    public static function findById(int $id): ?array {
        return self::getDetail($id);
    }

    /**
     * 获取第一篇默认展示的文章（通常是最新一篇公开文章）
     */
    public static function getFirstPostId(?int $categoryId = null, ?int $tagId = null): ?int {
        $sql = "SELECT log_ID, log_Tag FROM zbp_post WHERE log_Status = 0 AND log_Type = 0";
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND log_CateID = ?";
            $params[] = $categoryId;
        }
        $sql .= " ORDER BY log_IsTop DESC, log_PostTime DESC";
        $rows = Database::query($sql, $params);
        if (empty($rows)) return null;

        if ($tagId !== null && $tagId > 0) {
            $tagNeedle = "{" . $tagId . "}";
            foreach ($rows as $r) {
                if (strpos($r['log_Tag'], $tagNeedle) !== false) {
                    return (int)$r['log_ID'];
                }
            }
            return null;
        }

        return (int)$rows[0]['log_ID'];
    }

    /**
     * 增加阅读计数
     */
    public static function incrementViews(int $id): void {
        Database::execute("UPDATE zbp_post SET log_ViewNums = log_ViewNums + 1 WHERE log_ID = ?", [$id]);
    }

    /**
     * 后台分页查询文章
     */
    public static function getAdminList(int $page = 1, int $perPage = 15, string $keyword = '', ?int $cateId = null, ?int $status = null): array {
        $where = ["log_Type = 0"];
        $params = [];

        if (!empty($keyword)) {
            $where[] = "(log_Title LIKE ? OR log_Content LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($cateId !== null && $cateId > 0) {
            $where[] = "log_CateID = ?";
            $params[] = $cateId;
        }
        if ($status !== null) {
            $where[] = "log_Status = ?";
            $params[] = $status;
        }

        $whereSql = implode(" AND ", $where);
        $total = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM zbp_post WHERE $whereSql", $params)['cnt'];

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.cate_Name FROM zbp_post p 
                LEFT JOIN zbp_category c ON p.log_CateID = c.cate_ID 
                WHERE $whereSql 
                ORDER BY p.log_IsTop DESC, p.log_PostTime DESC 
                LIMIT $perPage OFFSET $offset";
        $list = Database::query($sql, $params);

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'items' => $list
        ];
    }

    /**
     * 保存文章（新增/更新）
     */
    public static function save(array $data, ?int $id = null): int {
        $time = time();
        $title = trim($data['title'] ?? '无标题');
        $content = $data['content'] ?? '';
        $intro = $data['intro'] ?? Helpers::getSnippet($content, 180);
        $cateId = (int)($data['cate_id'] ?? 0);
        $status = (int)($data['status'] ?? 0);
        $isTop = (int)($data['is_top'] ?? 0);
        $tagsRaw = trim($data['tags_raw'] ?? '');
        $alias = trim($data['alias'] ?? '');

        if ($id && $id > 0) {
            // 获取原有 meta
            $old = Database::queryOne("SELECT log_Meta FROM zbp_post WHERE log_ID = ?", [$id]);
            $meta = [];
            if (!empty($old['log_Meta'])) {
                $decoded = json_decode($old['log_Meta'], true);
                if (is_array($decoded)) $meta = $decoded;
            }
            if (isset($data['password'])) {
                $meta['password'] = trim($data['password']);
            }
            $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : '';

            Database::execute(
                "UPDATE zbp_post SET log_Title = ?, log_Content = ?, log_Intro = ?, log_CateID = ?, log_Status = ?, log_IsTop = ?, log_Tag = ?, log_Alias = ?, log_Meta = ?, log_UpdateTime = ? WHERE log_ID = ?",
                [$title, $content, $intro, $cateId, $status, $isTop, $tagsRaw, $alias, $metaJson, $time, $id]
            );
            return $id;
        } else {
            $meta = [];
            if (!empty($data['password'])) {
                $meta['password'] = trim($data['password']);
            }
            $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : '';

            Database::execute(
                "INSERT INTO zbp_post (log_Title, log_Content, log_Intro, log_CateID, log_Status, log_IsTop, log_Tag, log_Alias, log_AuthorID, log_Type, log_Meta, log_PostTime, log_CreateTime, log_UpdateTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, ?)",
                [$title, $content, $intro, $cateId, $status, $isTop, $tagsRaw, $alias, $metaJson, $time, $time, $time]
            );
            return (int)Database::lastInsertId();
        }
    }

    /**
     * 删除文章
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM zbp_post WHERE log_ID = ?", [$id]) > 0;
    }
}
