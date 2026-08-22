<?php
namespace App;

class Helpers {
    /**
     * 替换 Z-Blog 内部宏与路径
     */
    public static function processContent(string $content): string {
        // 统一宏替换与路径规整为现代化纯净 /users/upload/ 与 /users/filetype/
        $content = str_replace(
            [
                '{#ZC_BLOG_HOST#}zb_users/upload/',
                '{#ZC_BLOG_HOST#}users/upload/',
                '{#ZC_BLOG_HOST#}upload/',
                '{#ZC_BLOG_HOST#}zb_system/image/filetype/',
                '{#ZC_BLOG_HOST#}users/filetype/',
                '{#ZC_BLOG_HOST#}zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/',
                '/zb_users/upload/',
                '/uploads/',
                '/zb_system/image/filetype/',
                'zb_system/image/filetype/',
                '/zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/',
                'zb_users/plugin/Neditor/dialogs/attachment/fileTypeImages/'
            ],
            [
                '/users/upload/',
                '/users/upload/',
                '/users/upload/',
                '/users/filetype/',
                '/users/filetype/',
                '/users/filetype/',
                '/users/upload/',
                '/users/upload/',
                '/users/filetype/',
                '/users/filetype/',
                '/users/filetype/',
                '/users/filetype/'
            ],
            $content
        );
        $content = str_replace('{#ZC_BLOG_HOST#}', '/', $content);
        
        // 修复部分转义字符
        $content = htmlspecialchars_decode($content, ENT_QUOTES);
        // 如果内部有多重转义的HTML，再次转换
        if (strpos($content, '&lt;p&gt;') !== false || strpos($content, '&lt;img') !== false) {
            $content = htmlspecialchars_decode($content, ENT_QUOTES);
        }

        // 给图片添加 lazy 属性及灯箱点击标记
        $content = preg_replace('/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i', '<img $1 src="$2" $3 class="zoomable-img" loading="lazy">', $content);

        // 处理代码块，确保支持 highlight.js
        // 匹配 pre 或 pre > code
        $content = preg_replace_callback('/<pre(?:\s+class=["\']([^"\']*)["\'])?>([\s\S]*?)<\/pre>/i', function($matches) {
            $cls = $matches[1] ?? '';
            $inner = $matches[2];
            
            // 如果内部没有 code 标签，包一层 code
            if (stripos($inner, '<code') === false) {
                // 探测语言
                $lang = 'plaintext';
                if (stripos($cls, 'lang-') !== false || stripos($cls, 'language-') !== false) {
                    $lang = str_ireplace(['lang-', 'language-'], '', $cls);
                }
                return '<div class="code-block-wrapper"><div class="code-header"><span class="mac-dots"><i></i><i></i><i></i></span><span class="code-lang">' . htmlspecialchars($lang) . '</span><button class="copy-code-btn" type="button" title="复制代码">复制</button></div><pre><code class="language-' . htmlspecialchars($lang) . '">' . $inner . '</code></pre></div>';
            } else {
                return '<div class="code-block-wrapper"><div class="code-header"><span class="mac-dots"><i></i><i></i><i></i></span><button class="copy-code-btn" type="button" title="复制代码">复制</button></div><pre' . ($cls ? ' class="' . $cls . '"' : '') . '>' . $inner . '</pre></div>';
            }
        }, $content);

        return $content;
    }

    /**
     * 提取第一张图片作为文章封面
     */
    public static function extractCoverImage(string $content): ?string {
        $content = self::processContent($content);
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * 计算阅读时长 (中文字符 + 英文单词)
     */
    public static function estimateReadingTime(string $content): int {
        $plain = strip_tags($content);
        $charCount = mb_strlen(preg_replace('/\s+/', '', $plain), 'UTF-8');
        $minutes = max(1, (int)ceil($charCount / 350));
        return $minutes;
    }

    /**
     * 格式化友好时间 (如 2026年08月19日)
     */
    public static function formatDate(int $timestamp, string $format = 'Y年m月d日'): string {
        if ($timestamp <= 0) return '未知日期';
        return date($format, $timestamp);
    }

    /**
     * 获取年份
     */
    public static function getYear(int $timestamp): string {
        if ($timestamp <= 0) return '其他';
        return date('Y', $timestamp);
    }

    /**
     * 截取纯文本摘要
     */
    public static function getSnippet(string $content, int $length = 120): string {
        $plain = strip_tags(self::processContent($content));
        $plain = str_replace(["\r", "\n", "\t", '&nbsp;'], ' ', $plain);
        $plain = preg_replace('/\s+/', ' ', $plain);
        if (mb_strlen($plain, 'UTF-8') > $length) {
            return mb_substr($plain, 0, $length, 'UTF-8') . '...';
        }
        return $plain;
    }

    /**
     * 格式化文件大小 (字节 -> KB/MB)
     */
    public static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
