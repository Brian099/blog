<?php
$pageTitle = '控制台仪表盘';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">文章总数</div>
            <div class="stat-val"><?= number_format($postCount) ?></div>
        </div>
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">分类 / 标签</div>
            <div class="stat-val"><?= $cateCount ?> / <?= $tagCount ?></div>
        </div>
        <div class="stat-icon" style="background:#fdf4ff; color:#c026d3;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">附件总数 / 占用体积</div>
            <div class="stat-val"><?= number_format($mediaStats['total_count']) ?> <span style="font-size: 1rem; color: #64748b; font-weight: normal;">(<?= $mediaStats['total_size_formatted'] ?>)</span></div>
        </div>
        <div class="stat-icon" style="background:#f0fdf4; color:#16a34a;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">孤立未引用附件 (可清理)</div>
            <div class="stat-val" style="color: #dc2626;"><?= number_format($mediaStats['orphan_count']) ?> <span style="font-size: 1rem; color: #dc2626; font-weight: normal;">(<?= $mediaStats['orphan_size_formatted'] ?>)</span></div>
        </div>
        <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        </div>
    </div>
</div>

<div class="card">
    <div class="page-title-row" style="margin-bottom: 16px;">
        <h3 style="font-size: 1.1rem; font-weight: 700;">最新发布的文章</h3>
        <a href="/admin/posts/edit" class="btn btn-primary btn-sm">+ 撰写新文章</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>标题</th>
                <th>分类</th>
                <th>发布时间</th>
                <th>阅读量</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentPosts as $p): ?>
                <tr>
                    <td>
                        <a href="/admin/posts/edit?id=<?= $p['log_ID'] ?>" style="font-weight: 600; color: #2563eb;">
                            <?= htmlspecialchars($p['log_Title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($p['cate_Name'] ?? '未分类') ?></td>
                    <td><?= \App\Helpers::formatDate((int)$p['log_PostTime'], 'Y-m-d') ?></td>
                    <td><?= number_format((int)$p['log_ViewNums']) ?></td>
                    <td>
                        <a href="/admin/posts/edit?id=<?= $p['log_ID'] ?>" class="btn btn-outline btn-sm">编辑</a>
                        <a href="/?id=<?= $p['log_ID'] ?>" target="_blank" class="btn btn-outline btn-sm">查看</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
