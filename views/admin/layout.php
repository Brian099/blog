<?php
use App\Models\Setting;
$adminSiteName = Setting::get('site_name', '技术思维棱镜');
$adminSiteLogo = Setting::get('site_logo', '');
$currentRoute = $_SERVER['REQUEST_URI'] ?? '/admin';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? '管理后台' ?> - <?= htmlspecialchars($adminSiteName) ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <!-- Left Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <?php if (!empty($adminSiteLogo)): ?>
                <img src="<?= htmlspecialchars($adminSiteLogo) ?>" alt="Logo" style="max-height: 24px; width: auto; object-fit: contain; border-radius: 4px; flex-shrink: 0;">
            <?php else: ?>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#38bdf8"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <?php endif; ?>
            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($adminSiteName) ?>"><?= htmlspecialchars($adminSiteName) ?></span>
        </div>

        <nav class="admin-nav">
            <a href="/admin" class="<?= $currentRoute === '/admin' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>仪表盘</span>
            </a>
            <a href="/admin/posts" class="<?= strpos($currentRoute, '/admin/posts') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>文章管理</span>
            </a>
            <a href="/admin/taxonomy" class="<?= strpos($currentRoute, '/admin/taxonomy') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <span>分类与标签</span>
            </a>
            <a href="/admin/media" class="<?= strpos($currentRoute, '/admin/media') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span>附件管理与清理</span>
            </a>
            <a href="/admin/content-cleaner" class="<?= strpos($currentRoute, '/admin/content-cleaner') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <span>排版与自适应优化</span>
            </a>
            <a href="/admin/backup" class="<?= strpos($currentRoute, '/admin/backup') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>数据备份与转换</span>
            </a>
            <a href="/admin/settings" class="<?= strpos($currentRoute, '/admin/settings') === 0 ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>系统设置</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <span>管理员：<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?></span>
            <a href="/admin/logout" style="color: #ef4444;" title="退出登录">退出</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div style="font-weight: 600; font-size: 1rem; color: var(--admin-text);"><?= $pageTitle ?? '管理后台' ?></div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="/" target="_blank" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>预览前台</span>
                </a>
            </div>
        </header>

        <div class="admin-content">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
