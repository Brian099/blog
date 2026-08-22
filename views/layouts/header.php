<?php
/** @var array $siteSettings */
/** @var array $categories */
$siteName = $siteSettings['site_name'] ?? '技术思维棱镜';
$siteSubtitle = $siteSettings['site_subtitle'] ?? '';
$siteLogo = $siteSettings['site_logo'] ?? '';
$customNav = !empty($siteSettings['custom_nav']) ? json_decode($siteSettings['custom_nav'], true) : null;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($currentPost) && $currentPost ? htmlspecialchars($currentPost['title']) . ' - ' : '' ?><?= htmlspecialchars($siteName) ?><?= $siteSubtitle ? ' | ' . htmlspecialchars($siteSubtitle) : '' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= file_exists(APP_PATH . '/../public/assets/css/style.css') ? filemtime(APP_PATH . '/../public/assets/css/style.css') : time() ?>">
    <!-- Highlight.js for professional code highlighting (Localized Light Theme) -->
    <link rel="stylesheet" href="/assets/css/highlight.github.min.css">
    <script src="/assets/js/highlight.min.js"></script>
    <script>window.SITE_NAME = <?= json_encode($siteName) ?>;</script>
</head>
<body>

<header class="site-header">
    <div class="header-left">
        <button id="sidebar-toggle-btn" class="icon-btn sidebar-toggle-btn" title="切换文章目录">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <a href="/" class="site-logo" title="<?= htmlspecialchars($siteSubtitle) ?>">
            <?php if (!empty($siteLogo)): ?>
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="max-height: 28px; width: auto; object-fit: contain; border-radius: 4px; vertical-align: middle;">
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($siteName) ?></span>
        </a>

        <nav class="nav-links">
            <?php if (is_array($customNav) && !empty($customNav)): ?>
                <?php 
                $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
                foreach ($customNav as $nav): 
                    $navName = trim($nav['name'] ?? '');
                    $navUrl = trim($nav['url'] ?? '/');
                    $navTarget = ($nav['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                    if (empty($navName)) continue;

                    $isActive = false;
                    if ($navUrl === '/' && empty($_GET['cate']) && empty($_GET['tag']) && $currentUri === '/') {
                        $isActive = true;
                    } elseif ($navUrl === $currentUri) {
                        $isActive = true;
                    } elseif (!empty($_GET['cate']) && strpos($navUrl, 'cate=' . (int)$_GET['cate']) !== false) {
                        $isActive = true;
                    }
                ?>
                    <a href="<?= htmlspecialchars($navUrl) ?>" 
                       target="<?= $navTarget ?>" 
                       class="<?= $isActive ? 'active' : '' ?>">
                        <?= htmlspecialchars($navName) ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- 默认导航：全部文章 + 全部分类 -->
                <a href="/" class="<?= empty($_GET['cate']) && empty($_GET['tag']) ? 'active' : '' ?>">全部文章</a>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <a href="/?cate=<?= $cat['cate_ID'] ?>" class="<?= (isset($_GET['cate']) && (int)$_GET['cate'] === (int)$cat['cate_ID']) ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['cate_Name']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>

    <div class="header-right">
        <button id="search-toggle-btn" class="icon-btn" title="快捷搜索 (按 / 唤出)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span style="font-size: 0.8rem; color: var(--text-light);">搜索 <kbd style="background:var(--bg-hover); padding:1px 4px; border-radius:3px; font-size:0.75rem;">/</kbd></span>
        </button>

        <button id="theme-toggle-btn" class="icon-btn" title="深色 / 浅色模式切换">
            <svg id="theme-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <a href="/admin" class="icon-btn" title="管理后台" target="_blank">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </a>
    </div>
</header>

<!-- Mobile Sidebar Backdrop Overlay -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>
