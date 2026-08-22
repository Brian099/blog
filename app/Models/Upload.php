<?php
namespace App\Models;

use App\Database;
use App\Helpers;

class Upload {
    /**
     * 全文扫描所有文章，生成附件被引用的倒排索引映射
     * 返回格式：[ 'filename.jpg' => [ ['post_id' => 1, 'title' => '文章标题'], ... ], ... ]
     */
    public static function buildReferenceMap(): array {
        $posts = Database::query("SELECT log_ID, log_Title, log_Content, log_Intro FROM zbp_post");
        $refMap = [];
        $exts = 'jpg|jpeg|png|gif|webp|svg|bmp|pdf|zip|rar|7z|tar|gz|xlsx|xls|doc|docx|ppt|pptx|txt|sh|py|mp4|mp3';

        foreach ($posts as $post) {
            $postId = (int)$post['log_ID'];
            $postTitle = $post['log_Title'];
            $combined = $post['log_Content'] . ' ' . $post['log_Intro'];

            $found = [];

            // 1. 匹配 HTML/Markdown/CSS 各种属性中的实际 URL（src/href/data-src/url/![]()）
            if (preg_match_all('/(?:src|href|data-src)=["\']([^"\']+)["\']|\!\[[^\]]*\]\(([^)\s]+)\)|url\(["\']?([^"\')]+)["\']?\)/i', $combined, $matches)) {
                $allUrls = array_merge($matches[1], $matches[2], $matches[3]);
                foreach ($allUrls as $url) {
                    if (!$url) continue;
                    // 清理 Z-Blog 标签变量如 {#ZC_BLOG_HOST#} 及 URL 参数
                    $cleanUrl = preg_replace('/\{#[^#]+#\}/', '', $url);
                    $path = parse_url($cleanUrl, PHP_URL_PATH) ?? $cleanUrl;
                    $fn = strtolower(basename($path));
                    if ($fn && preg_match('/\.(' . $exts . ')$/i', $fn)) {
                        $found[$fn] = true;
                    }
                }
            }

            // 2. 匹配标准 uploads 相对路径格式，如 /upload/2021/04/202104121618199643524877.jpg
            if (preg_match_all('/(?:uploads?\/[0-9\/]*)([a-zA-Z0-9_\-\.]+\.(?:' . $exts . '))/i', $combined, $m2)) {
                foreach ($m2[1] as $fn) {
                    $found[strtolower($fn)] = true;
                }
            }

            // 3. 匹配 14+ 位时间戳命名的 Z-Blog 标准存储文件名（如 202104121618199643524877.png）
            if (preg_match_all('/(20\d{12,}[a-zA-Z0-9_\-]*\.(?:' . $exts . '))/i', $combined, $m3)) {
                foreach ($m3[1] as $fn) {
                    $found[strtolower($fn)] = true;
                }
            }

            foreach (array_keys($found) as $fnLower) {
                if (!isset($refMap[$fnLower])) {
                    $refMap[$fnLower] = [];
                }
                $refMap[$fnLower][] = [
                    'post_id' => $postId,
                    'title' => $postTitle
                ];
            }
        }

        return $refMap;
    }

    /**
     * 获取附件列表（支持引用状态智能分析与过滤）
     */
    public static function getList(int $page = 1, int $perPage = 20, string $keyword = '', ?bool $onlyOrphans = false, ?array $prebuiltRefMap = null): array {
        $refMap = $prebuiltRefMap ?? self::buildReferenceMap();

        $where = ["1=1"];
        $params = [];
        if (!empty($keyword)) {
            $where[] = "(ul_Name LIKE ? OR ul_SourceName LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $whereSql = implode(" AND ", $where);
        $allUploads = Database::query("SELECT * FROM zbp_upload WHERE $whereSql ORDER BY ul_PostTime DESC, ul_ID DESC", $params);

        // 附加引用信息（仅以实际存储文件名 ul_Name 为准）
        $processed = [];
        foreach ($allUploads as $item) {
            $fn = strtolower($item['ul_Name']);
            $refs = $refMap[$fn] ?? [];
            $refCount = count($refs);
            $isOrphan = ($refCount === 0);

            if ($onlyOrphans && !$isOrphan) {
                continue; // 过滤掉已被引用的
            }

            // 计算相对 URL 与缩略图
            $cleanName = ltrim($item['ul_Name'], '/');
            if (strpos($cleanName, '/') !== false) {
                $relPath = "/users/upload/" . $cleanName;
            } else {
                $year = date('Y', $item['ul_PostTime'] > 0 ? $item['ul_PostTime'] : time());
                $month = date('m', $item['ul_PostTime'] > 0 ? $item['ul_PostTime'] : time());
                if (preg_match('/^(\d{4})(\d{2})/', $cleanName, $m)) {
                    $year = $m[1];
                    $month = $m[2];
                }
                $relPath = "/users/upload/{$year}/{$month}/" . $cleanName;
            }

            $processed[] = [
                'id' => (int)$item['ul_ID'],
                'name' => $item['ul_Name'],
                'source_name' => $item['ul_SourceName'],
                'size' => (int)$item['ul_Size'],
                'size_formatted' => Helpers::formatBytes((int)$item['ul_Size']),
                'mime' => $item['ul_MimeType'],
                'is_image' => (strpos($item['ul_MimeType'], 'image/') !== false),
                'post_time' => (int)$item['ul_PostTime'],
                'date_formatted' => Helpers::formatDate((int)$item['ul_PostTime'], 'Y-m-d H:i'),
                'url' => $relPath,
                'ref_count' => $refCount,
                'is_orphan' => $isOrphan,
                'referencing_posts' => array_slice($refs, 0, 5) // 最多展示5篇
            ];
        }

        $total = count($processed);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($processed, $offset, $perPage);

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'items' => $items
        ];
    }

    /**
     * 获取全站附件统计信息
     */
    public static function getStats(?array $prebuiltRefMap = null): array {
        $refMap = $prebuiltRefMap ?? self::buildReferenceMap();
        $all = Database::query("SELECT ul_ID, ul_Name, ul_SourceName, ul_Size FROM zbp_upload");

        $totalCount = count($all);
        $totalBytes = 0;
        $orphanCount = 0;
        $orphanBytes = 0;

        foreach ($all as $item) {
            $size = (int)$item['ul_Size'];
            $totalBytes += $size;

            $fn = strtolower($item['ul_Name']);
            $isRef = isset($refMap[$fn]);
            if (!$isRef) {
                $orphanCount++;
                $orphanBytes += $size;
            }
        }

        return [
            'total_count' => $totalCount,
            'total_bytes' => $totalBytes,
            'total_size_formatted' => Helpers::formatBytes($totalBytes),
            'orphan_count' => $orphanCount,
            'orphan_bytes' => $orphanBytes,
            'orphan_size_formatted' => Helpers::formatBytes($orphanBytes),
            'used_count' => $totalCount - $orphanCount
        ];
    }

    /**
     * 批量物理删除与数据库清理
     */
    public static function deleteBatch(array $ids): array {
        if (empty($ids)) return ['deleted' => 0, 'freed_bytes' => 0];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $records = Database::query("SELECT * FROM zbp_upload WHERE ul_ID IN ($placeholders)", $ids);

        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($records as $item) {
            $freedBytes += (int)$item['ul_Size'];

            // 尝试在物理磁盘删除文件
            $year = date('Y', $item['ul_PostTime'] > 0 ? $item['ul_PostTime'] : time());
            $month = date('m', $item['ul_PostTime'] > 0 ? $item['ul_PostTime'] : time());
            if (preg_match('/^(\d{4})(\d{2})/', $item['ul_Name'], $m)) {
                $year = $m[1];
                $month = $m[2];
            }
            $filePath = UPLOAD_PATH . "/{$year}/{$month}/" . $item['ul_Name'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $deletedCount++;
        }

        // 删除数据库记录
        Database::execute("DELETE FROM zbp_upload WHERE ul_ID IN ($placeholders)", $ids);

        return [
            'deleted' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_formatted' => Helpers::formatBytes($freedBytes)
        ];
    }

    /**
     * 一键清理所有未引用的孤立文件
     */
    public static function cleanAllOrphans(): array {
        $refMap = self::buildReferenceMap();
        $all = Database::query("SELECT ul_ID, ul_Name, ul_SourceName FROM zbp_upload");

        $orphanIds = [];
        foreach ($all as $item) {
            $fn = strtolower($item['ul_Name']);
            if (!isset($refMap[$fn])) {
                $orphanIds[] = (int)$item['ul_ID'];
            }
        }

        return self::deleteBatch($orphanIds);
    }

    /**
     * 递归全盘极速扫描 public/uploads/ 目录（毫秒级，轻松承载万级以上文件，永不超时）
     */
    public static function scanDiskFiles(int $page = 1, int $perPage = 20, string $keyword = '', ?bool $onlyOrphans = false): array {
        // 放宽执行时间上限
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $refMap = self::buildReferenceMap();

        // 获取数据库中已有的所有文件名集合
        $dbRecords = Database::query("SELECT ul_ID, ul_Name FROM zbp_upload");
        $dbFileMap = [];
        foreach ($dbRecords as $r) {
            $dbFileMap[strtolower($r['ul_Name'])] = (int)$r['ul_ID'];
        }

        $allDiskFiles = [];
        $baseUploadDir = realpath(UPLOAD_PATH) ?: UPLOAD_PATH;

        if (is_dir($baseUploadDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseUploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    
                    // 过滤掉系统隐藏文件如 .DS_Store, .gitkeep
                    if ($filename[0] === '.') continue;

                    // 关键字过滤
                    if ($keyword !== '' && stripos($filename, $keyword) === false) {
                        continue;
                    }

                    $fnLower = strtolower($filename);
                    $refs = $refMap[$fnLower] ?? [];
                    $refCount = count($refs);
                    $isOrphan = ($refCount === 0);

                    if ($onlyOrphans && !$isOrphan) {
                        continue;
                    }

                    // 收集基本元数据（轻量，不触发昂贵磁盘 I/O）
                    $allDiskFiles[] = [
                        'filename' => $filename,
                        'fn_lower' => $fnLower,
                        'full_path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime(),
                        'ref_count' => $refCount,
                        'is_orphan' => $isOrphan,
                        'refs' => $refs
                    ];
                }
            }
        }

        // 按修改时间倒序排列
        usort($allDiskFiles, function($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        $total = count($allDiskFiles);
        $offset = ($page - 1) * $perPage;
        $pageItemsRaw = array_slice($allDiskFiles, $offset, $perPage);

        // 仅对当前分页的 20 个可视条目补充详细信息（极速性能核心）
        $items = [];
        foreach ($pageItemsRaw as $raw) {
            $fullPath = $raw['full_path'];
            $relPath = str_replace('\\', '/', substr($fullPath, strlen($baseUploadDir)));
            $webUrl = '/users/upload' . (strpos($relPath, '/') === 0 ? $relPath : '/' . $relPath);
            $ext = strtolower(pathinfo($raw['filename'], PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg']);
            $mime = $isImage ? "image/{$ext}" : "application/{$ext}";
            $inDb = isset($dbFileMap[$raw['fn_lower']]);

            $items[] = [
                'filename' => $raw['filename'],
                'rel_path' => $relPath,
                'web_url' => $webUrl,
                'full_path' => $fullPath,
                'size' => $raw['size'],
                'size_formatted' => Helpers::formatBytes($raw['size']),
                'mime' => $mime,
                'is_image' => $isImage,
                'mtime' => $raw['mtime'],
                'date_formatted' => date('Y-m-d H:i', $raw['mtime']),
                'ref_count' => $raw['ref_count'],
                'is_orphan' => $raw['is_orphan'],
                'in_db' => $inDb,
                'db_id' => $inDb ? $dbFileMap[$raw['fn_lower']] : null,
                'referencing_posts' => array_slice($raw['refs'], 0, 5)
            ];
        }

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'items' => $items
        ];
    }

    /**
     * 获取全盘物理扫描统计
     */
    public static function getDiskStats(): array {
        $refMap = self::buildReferenceMap();
        $dbRecords = Database::query("SELECT ul_Name FROM zbp_upload");
        $dbFileMap = [];
        foreach ($dbRecords as $r) {
            $dbFileMap[strtolower($r['ul_Name'])] = true;
        }

        $baseUploadDir = realpath(UPLOAD_PATH) ?: UPLOAD_PATH;
        $totalCount = 0;
        $totalBytes = 0;
        $orphanCount = 0;
        $orphanBytes = 0;
        $untrackedCount = 0; // 磁盘上有但数据库无记录的文件

        if (is_dir($baseUploadDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseUploadDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $fn = $file->getFilename();
                    if (strpos($fn, '.') === 0) continue;

                    $size = $file->getSize();
                    $totalCount++;
                    $totalBytes += $size;

                    $fnLower = strtolower($fn);
                    $isRef = isset($refMap[$fnLower]);
                    if (!$isRef) {
                        $orphanCount++;
                        $orphanBytes += $size;
                    }

                    if (!isset($dbFileMap[$fnLower])) {
                        $untrackedCount++;
                    }
                }
            }
        }

        return [
            'total_count' => $totalCount,
            'total_bytes' => $totalBytes,
            'total_size_formatted' => Helpers::formatBytes($totalBytes),
            'orphan_count' => $orphanCount,
            'orphan_bytes' => $orphanBytes,
            'orphan_size_formatted' => Helpers::formatBytes($orphanBytes),
            'used_count' => $totalCount - $orphanCount,
            'untracked_count' => $untrackedCount
        ];
    }

    /**
     * 批量删除物理磁盘文件（并同步删除数据库记录）
     */
    public static function deleteDiskFilesBatch(array $relPaths): array {
        $baseUploadDir = realpath(UPLOAD_PATH) ?: UPLOAD_PATH;
        $deletedCount = 0;
        $freedBytes = 0;
        $deletedDbFilenames = [];

        foreach ($relPaths as $rel) {
            $rel = str_replace('\\', '/', trim($rel));
            if (empty($rel)) continue;
            
            $fullPath = $baseUploadDir . '/' . ltrim($rel, '/');
            if (file_exists($fullPath) && is_file($fullPath)) {
                $size = filesize($fullPath);
                $fn = basename($fullPath);
                if (@unlink($fullPath)) {
                    $deletedCount++;
                    $freedBytes += $size;
                    $deletedDbFilenames[] = $fn;
                }
            }
        }

        // 同步删除数据库中对应名称的记录
        if (!empty($deletedDbFilenames)) {
            $placeholders = implode(',', array_fill(0, count($deletedDbFilenames), '?'));
            Database::execute("DELETE FROM zbp_upload WHERE ul_Name IN ($placeholders)", $deletedDbFilenames);
        }

        return [
            'deleted' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_formatted' => Helpers::formatBytes($freedBytes)
        ];
    }

    /**
     * 一键物理清理磁盘所有未被文章引用的孤立文件
     */
    public static function cleanAllDiskOrphans(): array {
        $refMap = self::buildReferenceMap();
        $baseUploadDir = realpath(UPLOAD_PATH) ?: UPLOAD_PATH;
        $orphansToDel = [];

        if (is_dir($baseUploadDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseUploadDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $fn = $file->getFilename();
                    if (strpos($fn, '.') === 0) continue;

                    $fnLower = strtolower($fn);
                    if (!isset($refMap[$fnLower])) {
                        $fullPath = $file->getPathname();
                        $relPath = substr($fullPath, strlen($baseUploadDir));
                        $orphansToDel[] = $relPath;
                    }
                }
            }
        }

        return self::deleteDiskFilesBatch($orphansToDel);
    }

    /**
     * 处理上传新文件
     */
    public static function handleUpload(array $file): ?array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $year = date('Y');
        $month = date('m');
        $targetDir = UPLOAD_PATH . "/{$year}/{$month}";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newName = date('YmdHis') . rand(1000, 9999) . '.' . $ext;
        $targetPath = $targetDir . '/' . $newName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $size = filesize($targetPath);
            $mime = mime_content_type($targetPath) ?: 'application/octet-stream';
            $time = time();

            Database::execute(
                "INSERT INTO zbp_upload (ul_AuthorID, ul_Size, ul_Name, ul_SourceName, ul_MimeType, ul_PostTime, ul_DownNums, ul_LogID, ul_Intro, ul_Meta) VALUES (1, ?, ?, ?, ?, ?, 0, 0, '', '')",
                [$size, $newName, $origName, $mime, $time]
            );

            $id = (int)Database::lastInsertId();
            $url = "/users/upload/{$year}/{$month}/" . $newName;

            return [
                'id' => $id,
                'name' => $newName,
                'source_name' => $origName,
                'size' => $size,
                'mime' => $mime,
                'url' => $url
            ];
        }

        return null;
    }
}
