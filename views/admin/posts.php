<?php
$pageTitle = '文章管理';
ob_start();
?>

<div class="page-title-row">
    <h2 class="page-title">全部文章 (<?= number_format($data['total']) ?>)</h2>
    <a href="/admin/posts/edit" class="btn btn-primary">+ 新建文章</a>
</div>

<div class="card">
    <form method="GET" action="/admin/posts" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" name="q" class="form-input" style="max-width: 240px;" placeholder="搜索标题或内容..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        
        <select name="cate" class="form-select" style="max-width: 180px;">
            <option value="">全部分类</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['cate_ID'] ?>" <?= (isset($_GET['cate']) && (int)$_GET['cate'] === (int)$cat['cate_ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['cate_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-outline">筛选</button>
        <?php if (!empty($_GET['q']) || !empty($_GET['cate'])): ?>
            <a href="/admin/posts" class="btn btn-outline" style="color: #64748b;">清空条件</a>
        <?php endif; ?>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th width="45%">标题</th>
                <th>分类</th>
                <th>状态</th>
                <th>发布日期</th>
                <th>阅读量</th>
                <th width="150" style="text-align: right; padding-right: 18px;">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['items'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">暂无文章记录</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <?php if (!empty($item['log_IsTop'])): ?>
                                    <span style="font-size: 0.7rem; background: #eff6ff; color: #2563eb; padding: 1px 4px; border-radius: 3px;">置顶</span>
                                <?php endif; ?>
                                <a href="/admin/posts/edit?id=<?= $item['log_ID'] ?>" style="color: inherit;"><?= htmlspecialchars($item['log_Title']) ?></a>
                            </div>
                            <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 2px;">
                                <?= htmlspecialchars(\App\Helpers::getSnippet($item['log_Content'], 60)) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($item['cate_Name'] ?: '未分类') ?></td>
                        <td>
                            <?php if ((int)$item['log_Status'] === 0): ?>
                                <span style="color: #10b981; font-weight: 500;">公开</span>
                            <?php else: ?>
                                <span style="color: #f59e0b; font-weight: 500;">草稿</span>
                            <?php endif; ?>
                        </td>
                        <td><?= \App\Helpers::formatDate((int)$item['log_PostTime'], 'Y-m-d') ?></td>
                        <td><?= number_format((int)$item['log_ViewNums']) ?></td>
                        <td style="text-align: right; padding-right: 18px;">
                            <div class="action-btn-group">
                                <a href="/admin/posts/edit?id=<?= $item['log_ID'] ?>" class="btn btn-outline btn-sm">编辑</a>
                                <a href="/?id=<?= $item['log_ID'] ?>" target="_blank" class="btn btn-outline btn-sm">预览</a>
                                <button onclick="deletePost(<?= $item['log_ID'] ?>)" class="btn btn-danger btn-sm">删除</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($data['total_pages'] > 1): ?>
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 24px;">
            <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                <?php if ($i <= 10 || $i === $data['total_pages'] || abs($i - $data['page']) <= 2): ?>
                    <a href="?page=<?= $i ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&cate=<?= urlencode($_GET['cate'] ?? '') ?>" 
                       class="btn btn-sm <?= $i === $data['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width: 32px; justify-content: center;">
                        <?= $i ?>
                    </a>
                <?php elseif ($i == 11): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function deletePost(id) {
    if (!confirm('确定要永久删除这篇文章吗？')) return;
    const form = new FormData();
    form.append('id', id);
    const res = await fetch('/admin/posts/delete', { method: 'POST', body: form });
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
