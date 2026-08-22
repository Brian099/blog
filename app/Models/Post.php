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
                } elseif (preg_match('/password[:=]([^\s;,]+)/i', (string)$row['log_Meta'], $pm) && !empty($pm[1])) {
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
    public static function getDetail(int $id, bool $forceUnlocked = false): ?array {
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
            } elseif (preg_match('/password[:=]([^\s;,]+)/i', (string)$post['log_Meta'], $m)) {
                $meta['password'] = trim($m[1]);
            }
        }

        $password = $meta['password'] ?? '';
        $isProtected = !empty($password);

        // 独立文章密码校验（不存入 Session，刷新页面需重新输入）
        $isUnlocked = false;
        if (!$isProtected) {
            $isUnlocked = true;
        } else {
            if ($forceUnlocked) {
                $isUnlocked = true;
            } elseif (!empty($_SESSION['admin_logged_in'])) {
                // 已登录的博客管理员可直接免密查看
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
     * 验证文章访问密码
     */
    public static function unlock(int $id, string $inputPassword): bool {
        $post = self::getDetail($id);
        if (!$post || !$post['is_protected']) {
            return true;
        }

        return (trim($inputPassword) === (string)$post['password']);
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
     * 保存文章（新增/更新，自动进行自适应排版优化与尺寸清洗）
     */
    public static function save(array $data, ?int $id = null): int {
        $time = time();
        $title = trim($data['title'] ?? '无标题');
        $rawContent = $data['content'] ?? '';
        $content = self::cleanResponsiveHtml($rawContent);
        $intro = $data['intro'] ?? Helpers::getSnippet($content, 180);
        $cateId = (int)($data['cate_id'] ?? 0);
        $status = (int)($data['status'] ?? 0);
        $isTop = (int)($data['is_top'] ?? 0);
        $tagsRaw = trim($data['tags_raw'] ?? '');
        $alias = trim($data['alias'] ?? '');

        if ($id && $id > 0) {
            // 获取原有 meta
            $old = Database::fetchOne("SELECT log_Meta FROM zbp_post WHERE log_ID = ?", [$id]);
            $meta = [];
            if (!empty($old['log_Meta'])) {
                $decoded = json_decode($old['log_Meta'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                } elseif (preg_match('/password[:=]([^\s;,]+)/i', (string)$old['log_Meta'], $pm)) {
                    $meta['password'] = trim($pm[1]);
                }
            }
            if (isset($data['password'])) {
                $pwd = trim($data['password']);
                if (!empty($pwd)) {
                    $meta['password'] = $pwd;
                } else {
                    unset($meta['password']);
                }
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

    /**
     * 解析并清洗 CSS 规则，移除定宽、定高及 nowrap 限制，保留其他正常样式
     */
    public static function cleanCssStyle(string $style): string {
        $rules = explode(';', $style);
        $kept = [];
        foreach ($rules as $rule) {
            $rule = trim($rule);
            if (empty($rule) || strpos($rule, ':') === false) continue;
            $parts = explode(':', $rule, 2);
            $prop = strtolower(trim($parts[0]));
            $val = strtolower(trim($parts[1] ?? ''));
            
            // 过滤禁止换行与固定尺寸限制
            if (in_array($prop, ['text-wrap-mode', 'text-wrap', 'white-space', 'word-break'])) {
                continue;
            }
            if (in_array($prop, ['width', 'height', 'min-width', 'max-width', 'min-height', 'max-height'])) {
                continue;
            }
            if (empty($val)) {
                continue;
            }
            $kept[] = "{$prop}: {$val}";
        }
        return implode('; ', $kept);
    }

    /**
     * 清洗 HTML 中的图片/表格长宽限制、禁止换行属性与定宽 Style 限制
     */
    public static function cleanResponsiveHtml(string $html): string {
        if (empty($html)) return $html;

        // 1. 处理 <img> 标签：移除 width, height 属性，并清理 style 中的尺寸限制
        $html = preg_replace_callback('/<img\b([^>]*)>/is', function($m) {
            $attrs = $m[1];
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*(["\']).*?\1/is', '', $attrs);
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*[\d%pxpt]+/is', '', $attrs);
            
            $attrs = preg_replace_callback('/\bstyle\s*=\s*(["\'])(.*?)\1/is', function($sm) {
                $quote = $sm[1];
                $cleanStyle = self::cleanCssStyle($sm[2]);
                return $cleanStyle ? " style={$quote}{$cleanStyle}{$quote}" : '';
            }, $attrs);
            
            $attrs = preg_replace('/\s*style\s*=\s*(["\'])\s*\1/is', '', $attrs);
            $attrs = trim(preg_replace('/\s+/', ' ', $attrs));
            return '<img' . ($attrs ? ' ' . $attrs : '') . '>';
        }, $html);

        // 2. 处理 <table>, <tr>, <td>, <th>, <col>, <colgroup>, <tbody>, <thead> 等表格相关标签
        $html = preg_replace_callback('/<(table|tr|td|th|col|colgroup|tbody|thead)\b([^>]*)>/is', function($m) {
            $tag = $m[1];
            $attrs = $m[2];
            
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*(["\']).*?\1/is', '', $attrs);
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*[\d%pxpt]+/is', '', $attrs);
            $attrs = preg_replace('/\s+nowrap(?:\s*=\s*(["\']).*?\1)?/is', '', $attrs);
            
            $attrs = preg_replace_callback('/\bstyle\s*=\s*(["\'])(.*?)\1/is', function($sm) {
                $quote = $sm[1];
                $cleanStyle = self::cleanCssStyle($sm[2]);
                return $cleanStyle ? " style={$quote}{$cleanStyle}{$quote}" : '';
            }, $attrs);
            
            $attrs = preg_replace('/\s*style\s*=\s*(["\'])\s*\1/is', '', $attrs);
            $attrs = trim(preg_replace('/\s+/', ' ', $attrs));
            return "<{$tag}" . ($attrs ? ' ' . $attrs : '') . '>';
        }, $html);

        // 3. 处理 <div>, <p>, <span>, <section>, <article>, <figure> 等文本容器
        $html = preg_replace_callback('/<(div|p|span|section|article|figure)\b([^>]*)>/is', function($m) {
            $tag = $m[1];
            $attrs = $m[2];
            
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*(["\']).*?\1/is', '', $attrs);
            $attrs = preg_replace('/\s*(?:width|height)\s*=\s*[\d%pxpt]+/is', '', $attrs);
            $attrs = preg_replace('/\s+nowrap(?:\s*=\s*(["\']).*?\1)?/is', '', $attrs);

            $attrs = preg_replace_callback('/\bstyle\s*=\s*(["\'])(.*?)\1/is', function($sm) {
                $quote = $sm[1];
                $cleanStyle = self::cleanCssStyle($sm[2]);
                return $cleanStyle ? " style={$quote}{$cleanStyle}{$quote}" : '';
            }, $attrs);

            $attrs = preg_replace('/\s*style\s*=\s*(["\'])\s*\1/is', '', $attrs);
            $attrs = trim(preg_replace('/\s+/', ' ', $attrs));
            return "<{$tag}" . ($attrs ? ' ' . $attrs : '') . '>';
        }, $html);

        // 4. 清理残留的空 style 属性
        $html = preg_replace('/\s*style\s*=\s*(["\'])\s*\1/is', '', $html);

        return $html;
    }

    /**
     * 扫描全站文章中的自适应排版限制问题（用于后台预览与诊断）
     */
    public static function scanResponsiveIssues(): array {
        $rows = Database::query("SELECT log_ID, log_Title, log_Content, log_PostTime FROM zbp_post WHERE log_Type = 0 ORDER BY log_ID ASC");
        
        $total = count($rows);
        $issuesCount = 0;
        $issueList = [];

        foreach ($rows as $row) {
            $content = $row['log_Content'] ?? '';
            $cleaned = self::cleanResponsiveHtml($content);
            
            if ($cleaned !== $content) {
                $issuesCount++;
                $issues = [];
                if (preg_match('/<img[^>]+(?:width|height)=/i', $content) || preg_match('/<img[^>]+style=[\'"][^\'"]*?(?:width|height)/i', $content)) {
                    $issues[] = '图片定宽/高';
                }
                if (preg_match('/<(?:table|td|th|col|colgroup)[^>]+(?:width|height|nowrap)/i', $content) || preg_match('/<(?:table|td|th)[^>]+style=[\'"][^\'"]*?(?:width|height|white-space)/i', $content)) {
                    $issues[] = '表格定宽/禁换行';
                }
                if (preg_match('/<(?:div|p|span)[^>]+style=[\'"][^\'"]*?(?:width:\s*\d+|white-space:\s*nowrap)/i', $content)) {
                    $issues[] = '容器定宽/禁止换行';
                }
                if (empty($issues)) {
                    $issues[] = '其他尺寸与样式限制';
                }

                $issueList[] = [
                    'id' => (int)$row['log_ID'],
                    'title' => $row['log_Title'],
                    'date' => Helpers::formatDate((int)$row['log_PostTime'], 'Y-m-d'),
                    'issues' => $issues,
                    'diff_bytes' => strlen($content) - strlen($cleaned)
                ];
            }
        }

        return [
            'total_scanned' => $total,
            'issues_found' => $issuesCount,
            'clean_rate' => $total > 0 ? round((($total - $issuesCount) / $total) * 100, 1) : 100,
            'items' => $issueList
        ];
    }

    /**
     * 批量执行全站文章自适应排版清洗与修复
     */
    public static function batchCleanResponsive(): array {
        $rows = Database::query("SELECT log_ID, log_Title, log_Content, log_Intro FROM zbp_post WHERE log_Type = 0 ORDER BY log_ID ASC");
        
        $total = count($rows);
        $updatedCount = 0;
        $totalBytesSaved = 0;
        $fixedList = [];

        Database::beginTransaction();
        try {
            foreach ($rows as $row) {
                $id = (int)$row['log_ID'];
                $content = $row['log_Content'] ?? '';
                $intro = $row['log_Intro'] ?? '';

                $cleanedContent = self::cleanResponsiveHtml($content);
                $cleanedIntro = self::cleanResponsiveHtml($intro);

                if ($cleanedContent !== $content || $cleanedIntro !== $intro) {
                    $diff = (strlen($content) - strlen($cleanedContent)) + (strlen($intro) - strlen($cleanedIntro));
                    $totalBytesSaved += $diff;
                    $updatedCount++;

                    Database::execute(
                        "UPDATE zbp_post SET log_Content = ?, log_Intro = ? WHERE log_ID = ?",
                        [$cleanedContent, $cleanedIntro, $id]
                    );

                    $fixedList[] = [
                        'id' => $id,
                        'title' => $row['log_Title'],
                        'bytes_saved' => $diff
                    ];
                }
            }
            Database::commit();
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'total_scanned' => $total,
            'updated_count' => $updatedCount,
            'bytes_saved' => $totalBytesSaved,
            'fixed_list' => $fixedList
        ];
    }

    /**
     * 分批轮询清洗自适应排版（每批处理 $limit 篇，带实时进度，杜绝超时）
     */
    public static function cleanResponsiveChunk(int $offset = 0, int $limit = 30): array {
        $totalRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM zbp_post WHERE log_Type = 0");
        $total = (int)($totalRow['cnt'] ?? 0);

        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $rows = Database::query("SELECT log_ID, log_Title, log_Content, log_Intro FROM zbp_post WHERE log_Type = 0 ORDER BY log_ID ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);

        $updatedCount = 0;
        $bytesSaved = 0;

        foreach ($rows as $row) {
            $id = (int)$row['log_ID'];
            $content = $row['log_Content'] ?? '';
            $intro = $row['log_Intro'] ?? '';

            $cleanedContent = self::cleanResponsiveHtml($content);
            $cleanedIntro = self::cleanResponsiveHtml($intro);

            if ($cleanedContent !== $content || $cleanedIntro !== $intro) {
                $diff = (strlen($content) - strlen($cleanedContent)) + (strlen($intro) - strlen($cleanedIntro));
                $bytesSaved += $diff;
                $updatedCount++;

                Database::execute(
                    "UPDATE zbp_post SET log_Content = ?, log_Intro = ? WHERE log_ID = ?",
                    [$cleanedContent, $cleanedIntro, $id]
                );
            }
        }

        $nextOffset = $offset + count($rows);
        $done = ($nextOffset >= $total || empty($rows));

        return [
            'total' => $total,
            'processed_count' => count($rows),
            'current_offset' => $offset,
            'next_offset' => $nextOffset,
            'updated_count' => $updatedCount,
            'bytes_saved' => $bytesSaved,
            'done' => $done
        ];
    }
}
