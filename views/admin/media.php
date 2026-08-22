<?php
$pageTitle = '附件管理与智能清理';
$tab = $_GET['tab'] ?? 'db';
ob_start();
?>

<div class="page-title-row">
    <div>
        <h2 class="page-title">附件管理与智能引用清理</h2>
        <p style="color: #64748b; font-size: 0.88rem; margin-top: 4px;">智能检测全站 620+ 篇文章的图片与文件引用，支持数据库与物理磁盘双重深度扫描清理</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <?php if ($tab === 'disk'): ?>
            <button id="clean-all-disk-orphans-btn" class="btn btn-danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                <span>一键清理磁盘所有孤立文件 🧹</span>
            </button>
        <?php else: ?>
            <button id="clean-all-orphans-btn" class="btn btn-danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                <span>一键清理所有未引用文件 🧹</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs for Mode Switching -->
<div style="display: flex; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--admin-border); padding-bottom: 8px;">
    <a href="/admin/media?tab=db" class="btn <?= $tab === 'db' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.88rem;">
        📁 数据库记录模式
    </a>
    <a href="/admin/media?tab=disk" class="btn <?= $tab === 'disk' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.88rem;">
        💾 物理磁盘全盘深度扫描模式
    </a>
</div>

<!-- Stats Bar -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label"><?= $tab === 'disk' ? '磁盘物理文件总数' : '数据库记录总数' ?></div>
            <div class="stat-val"><?= number_format($stats['total_count']) ?> 个</div>
        </div>
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">总占用存储空间</div>
            <div class="stat-val"><?= $stats['total_size_formatted'] ?></div>
        </div>
        <div class="stat-icon" style="background:#f0fdf4; color:#16a34a;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">正在被文章引用的文件</div>
            <div class="stat-val" style="color: #10b981;"><?= number_format($stats['used_count']) ?> 个</div>
        </div>
        <div class="stat-icon" style="background:#f0fdf4; color:#10b981;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">未引用的孤立文件 (可释放空间)</div>
            <div class="stat-val" style="color: #ef4444;"><?= number_format($stats['orphan_count']) ?> 个 <span style="font-size: 0.95rem; font-weight: normal;">(<?= $stats['orphan_size_formatted'] ?>)</span></div>
        </div>
        <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
    </div>
</div>

<div class="card">
    <!-- Toolbar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="/admin/media" style="display: flex; gap: 10px; align-items: center; flex-shrink: 0;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <input type="text" name="q" class="form-input" style="width: 200px; max-width: 240px;" placeholder="搜索文件名..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            
            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer; user-select: none; white-space: nowrap; flex-shrink: 0;">
                <input type="checkbox" name="orphan" value="1" <?= !empty($_GET['orphan']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <span style="font-weight: 600; color: #dc2626; white-space: nowrap;">仅显示未引用的孤立文件</span>
            </label>

            <button type="submit" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0;">查询</button>
            <?php if (!empty($_GET['q']) || !empty($_GET['orphan'])): ?>
                <a href="/admin/media?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline" style="color: #64748b; white-space: nowrap; flex-shrink: 0;">重置</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: 10px; align-items: center; flex-shrink: 0;">
            <?php if ($tab === 'disk'): ?>
                <button id="disk-batch-delete-btn" class="btn btn-danger btn-sm" disabled>
                    批量删除选中磁盘文件 (0)
                </button>
            <?php else: ?>
                <button id="batch-delete-btn" class="btn btn-danger btn-sm" disabled>
                    批量删除选中项 (0)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Media Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="40"><input type="checkbox" id="<?= $tab === 'disk' ? 'select-all-disk-media' : 'select-all-media' ?>"></th>
                <th width="70">预览</th>
                <th><?= $tab === 'disk' ? '物理路径 / 文件名' : '文件名 / 原始名' ?></th>
                <th>大小</th>
                <th>格式</th>
                <th><?= $tab === 'disk' ? '修改时间' : '上传时间' ?></th>
                <th>引用检测状态</th>
                <th width="90">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['items'])): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">暂无符合条件的附件文件</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td>
                            <?php if ($tab === 'disk'): ?>
                                <input type="checkbox" class="disk-media-select-cb" value="<?= htmlspecialchars($item['rel_path']) ?>">
                            <?php else: ?>
                                <input type="checkbox" class="media-select-cb" value="<?= $item['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="width: 48px; height: 48px; border-radius: 6px; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                <?php if ($item['is_image']): ?>
                                    <img src="<?= $tab === 'disk' ? $item['web_url'] : $item['url'] ?>" alt="<?= htmlspecialchars($tab === 'disk' ? $item['filename'] : $item['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/assets/images/file.svg'; this.style.padding='10px';">
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">
                                        <?= pathinfo($tab === 'disk' ? $item['filename'] : $item['name'], PATHINFO_EXTENSION) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($tab === 'disk'): ?>
                                <div style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($item['filename']) ?></div>
                                <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">zb_users/upload<?= htmlspecialchars($item['rel_path']) ?></div>
                                <?php if (!$item['in_db']): ?>
                                    <span style="font-size: 0.7rem; background: #fef3c7; color: #b45309; padding: 1px 4px; border-radius: 3px; font-weight: 600;">未在数据库中登记</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($item['name']) ?></div>
                                <div style="font-size: 0.78rem; color: #94a3b8;"><?= htmlspecialchars($item['source_name'] ?: '-') ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['size_formatted'] ?></td>
                        <td><span style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($item['mime']) ?></span></td>
                        <td><?= $item['date_formatted'] ?></td>
                        <td>
                            <?php if ($item['is_orphan']): ?>
                                <span class="badge-orphan">未被任何文章引用 (可清理)</span>
                            <?php else: ?>
                                <span class="badge-used">已在 <?= $item['ref_count'] ?> 篇文章中引用</span>
                                <div style="margin-top: 4px; font-size: 0.75rem; color: #64748b;">
                                    <?php foreach ($item['referencing_posts'] as $rp): ?>
                                        <div>• <a href="/?id=<?= $rp['post_id'] ?>" target="_blank" style="color: #2563eb;"><?= htmlspecialchars($rp['title']) ?></a></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tab === 'disk'): ?>
                                <button onclick="deleteSingleDiskFile('<?= htmlspecialchars(addslashes($item['rel_path'])) ?>')" class="btn btn-danger btn-sm" style="padding: 3px 8px;">
                                    删除
                                </button>
                            <?php else: ?>
                                <button onclick="deleteSingleMedia(<?= $item['id'] ?>)" class="btn btn-danger btn-sm" style="padding: 3px 8px;">
                                    删除
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($data['total_pages'] > 1): ?>
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap;">
            <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                <?php if ($i <= 8 || $i === $data['total_pages'] || abs($i - $data['page']) <= 2): ?>
                    <a href="?tab=<?= htmlspecialchars($tab) ?>&page=<?= $i ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&orphan=<?= urlencode($_GET['orphan'] ?? '') ?>" 
                       class="btn btn-sm <?= $i === $data['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width: 32px; justify-content: center;">
                        <?= $i ?>
                    </a>
                <?php elseif ($i == 9): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function deleteSingleMedia(id) {
    if (!confirm('确定删除此附件文件及其数据库记录？')) return;
    const form = new FormData();
    form.append('ids', id);
    const res = await fetch('/admin/media/delete-batch', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        window.location.reload();
    } else {
        alert('删除失败');
    }
}

async function deleteSingleDiskFile(relPath) {
    if (!confirm('确定从服务器物理磁盘中永久删除此文件？此操作不可逆！')) return;
    const form = new FormData();
    form.append('paths', relPath);
    const res = await fetch('/admin/media/disk-delete-batch', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        window.location.reload();
    } else {
        alert('删除失败');
    }
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
