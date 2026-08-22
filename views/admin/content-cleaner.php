<?php
$pageTitle = '排版与自适应优化';
ob_start();
/** @var array $scan */
?>

<div class="page-title-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 class="page-title" style="margin: 0;">文章排版与自适应屏幕优化</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.88rem; margin-top: 4px;">
            一键扫描并清理文章中的图片固定长宽、表格定宽、<code>nowrap</code> 禁止换行等影响手机/多端自适应展示的历史遗留问题。
        </p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button id="rescan-btn" onclick="triggerScan()" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span>重新扫描</span>
        </button>
        <button id="batch-clean-btn" onclick="triggerBatchClean()" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7, #2563eb); border: none; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <span>一键批量修复全站文章</span>
        </button>
    </div>
</div>

<!-- Overview Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
            <div style="font-size: 0.82rem; color: var(--admin-text-muted); font-weight: 500;">已扫描总文章</div>
            <div id="stat-total" style="font-size: 1.6rem; font-weight: 800; color: var(--admin-text);"><?= $scan['total_scanned'] ?></div>
        </div>
    </div>

    <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: <?= $scan['issues_found'] > 0 ? '#fef3c7' : '#dcfce7' ?>; color: <?= $scan['issues_found'] > 0 ? '#d97706' : '#16a34a' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
            <div style="font-size: 0.82rem; color: var(--admin-text-muted); font-weight: 500;">待优化自适应篇数</div>
            <div id="stat-issues" style="font-size: 1.6rem; font-weight: 800; color: <?= $scan['issues_found'] > 0 ? '#d97706' : '#16a34a' ?>;"><?= $scan['issues_found'] ?></div>
        </div>
    </div>

    <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: #f3e8ff; color: #9333ea; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div>
            <div style="font-size: 0.82rem; color: var(--admin-text-muted); font-weight: 500;">排版自适应达标率</div>
            <div id="stat-rate" style="font-size: 1.6rem; font-weight: 800; color: var(--admin-text);"><?= $scan['clean_rate'] ?>%</div>
        </div>
    </div>
</div>

<!-- Feature Explanations Card -->
<div class="card" style="margin-bottom: 24px; background: #f8fafc;">
    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--admin-text); margin-bottom: 8px;">🛠️ 本次清洗与修复将执行以下优化策略：</h3>
    <ul style="margin: 0; padding-left: 20px; font-size: 0.85rem; color: var(--admin-text-muted); line-height: 1.8;">
        <li><strong>图片尺寸智能自适应</strong>：剔除 <code>&lt;img&gt;</code> 标签中固定的 <code>width</code>、<code>height</code> 属性及 <code>style</code> 中的绝对像素尺寸限制，确保手机与高分屏下图片等比自适应缩放；</li>
        <li><strong>表格排版与禁止换行解除</strong>：移除 <code>&lt;table&gt;</code>、<code>&lt;td&gt;</code>、<code>&lt;colgroup&gt;</code> 的强制固定宽度、<code>height</code> 以及 <code>nowrap</code> / <code>white-space: nowrap</code> 限制，让表格文字自然换行并支持平滑横向滚动；</li>
        <li><strong>容器定宽释放</strong>：清理 <code>&lt;div&gt;</code>、<code>&lt;p&gt;</code>、<code>&lt;span&gt;</code> 中遗留的超宽绝对像素宽度限制；</li>
        <li><strong>未来自动化保障</strong>：新系统后续在后台新增或编辑保存文章时，已<strong>全自动内置该自适应排版清洗引擎</strong>，防止再次产生定宽污染。</li>
    </ul>
</div>

<!-- Diagnostics / Affected Articles Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--admin-text); margin: 0;">检测到的文章排版诊断明细</h3>
        <span id="list-badge" style="font-size: 0.8rem; background: var(--admin-bg); border: 1px solid var(--admin-border); padding: 3px 10px; border-radius: 12px; color: var(--admin-text-muted);">
            共 <?= count($scan['items']) ?> 篇需优化
        </span>
    </div>

    <div class="table-responsive">
        <table class="table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 80px;">文章 ID</th>
                    <th>文章标题</th>
                    <th style="width: 130px;">发布日期</th>
                    <th>检测到的排版限制项</th>
                    <th style="width: 100px; text-align: right;">预计清理冗余</th>
                    <th style="width: 110px; text-align: center;">操作</th>
                </tr>
            </thead>
            <tbody id="issues-table-body">
                <?php if (empty($scan['items'])): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 48px; color: #16a34a;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">🎉</div>
                            <div style="font-weight: 600; font-size: 1.05rem;">太棒了！全站文章排版均处于纯净自适应状态</div>
                            <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-top: 4px;">未检测到任何限制屏幕自适应的固定宽度或强制禁止换行代码。</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scan['items'] as $item): ?>
                        <tr id="row-<?= $item['id'] ?>">
                            <td style="font-weight: 600; color: var(--admin-text-muted);"><?= $item['id'] ?></td>
                            <td>
                                <a href="/?id=<?= $item['id'] ?>" target="_blank" style="font-weight: 600; color: var(--admin-text); text-decoration: none;">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </td>
                            <td style="color: var(--admin-text-muted); font-size: 0.85rem;"><?= $item['date'] ?></td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php foreach ($item['issues'] as $iss): ?>
                                        <?php 
                                            $bg = '#fef3c7'; $color = '#b45309';
                                            if (strpos($iss, '表格') !== false) { $bg = '#fee2e2'; $color = '#dc2626'; }
                                            elseif (strpos($iss, '图片') !== false) { $bg = '#e0f2fe'; $color = '#0369a1'; }
                                        ?>
                                        <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; background: <?= $bg ?>; color: <?= $color ?>; font-weight: 500;">
                                            <?= htmlspecialchars($iss) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="text-align: right; color: var(--admin-text-muted); font-size: 0.85rem;">
                                -<?= number_format($item['diff_bytes']) ?> B
                            </td>
                            <td style="text-align: center;">
                                <a href="/admin/posts/edit?id=<?= $item['id'] ?>" target="_blank" class="btn btn-outline btn-sm">
                                    编辑文章
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function triggerScan() {
    const btn = document.getElementById('rescan-btn');
    btn.disabled = true;
    btn.innerHTML = '<span>扫描中...</span>';

    try {
        const res = await fetch('/admin/content-cleaner/action?action=scan');
        const data = await res.json();
        if (data.success) {
            updateUIWithScanResult(data.data);
        } else {
            alert('扫描失败: ' + (data.error || '未知错误'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><span>重新扫描</span>';
    }
}

async function triggerBatchClean() {
    if (!confirm('确定要执行全站文章自适应排版批量清洗吗？\n\n此操作将移除所有文章中图片的定宽/高属性、表格的定宽与 nowrap 限制，并自动安全保存。')) {
        return;
    }

    const btn = document.getElementById('batch-clean-btn');
    btn.disabled = true;
    btn.innerHTML = '<span>正在批量修复中...</span>';

    try {
        const res = await fetch('/admin/content-cleaner/action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clean'
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            alert(`🎉 全站排版自适应修复成功！\n\n- 共扫描文章：${d.total_scanned} 篇\n- 修复并优化文章：${d.updated_count} 篇\n- 清除定宽与冗余限制：${(d.bytes_saved / 1024).toFixed(2)} KB\n\n全站文章现在已具备 100% 完美的移动端/窄屏自适应能力！`);
            triggerScan();
        } else {
            alert('修复失败: ' + (data.error || '未知错误'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span>一键批量修复全站文章</span>';
    }
}

function updateUIWithScanResult(scan) {
    document.getElementById('stat-total').innerText = scan.total_scanned;
    document.getElementById('stat-issues').innerText = scan.issues_found;
    document.getElementById('stat-rate').innerText = scan.clean_rate + '%';
    document.getElementById('list-badge').innerText = `共 ${scan.items.length} 篇需优化`;

    const tbody = document.getElementById('issues-table-body');
    if (!scan.items || scan.items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 48px; color: #16a34a;">
                    <div style="font-size: 2rem; margin-bottom: 8px;">🎉</div>
                    <div style="font-weight: 600; font-size: 1.05rem;">太棒了！全站文章排版均处于纯净自适应状态</div>
                    <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-top: 4px;">未检测到任何限制屏幕自适应的固定宽度或强制禁止换行代码。</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    scan.items.forEach(item => {
        const issuesBadges = item.issues.map(iss => {
            let bg = '#fef3c7', color = '#b45309';
            if (iss.includes('表格')) { bg = '#fee2e2'; color = '#dc2626'; }
            else if (iss.includes('图片')) { bg = '#e0f2fe'; color = '#0369a1'; }
            return `<span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; background: ${bg}; color: ${color}; font-weight: 500;">${iss}</span>`;
        }).join(' ');

        html += `
            <tr id="row-${item.id}">
                <td style="font-weight: 600; color: var(--admin-text-muted);">${item.id}</td>
                <td>
                    <a href="/?id=${item.id}" target="_blank" style="font-weight: 600; color: var(--admin-text); text-decoration: none;">
                        ${escapeHtml(item.title)}
                    </a>
                </td>
                <td style="color: var(--admin-text-muted); font-size: 0.85rem;">${item.date}</td>
                <td>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        ${issuesBadges}
                    </div>
                </td>
                <td style="text-align: right; color: var(--admin-text-muted); font-size: 0.85rem;">
                    -${item.diff_bytes} B
                </td>
                <td style="text-align: center;">
                    <a href="/admin/posts/edit?id=${item.id}" target="_blank" class="btn btn-outline btn-sm">
                        编辑文章
                    </a>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
