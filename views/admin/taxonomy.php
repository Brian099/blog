<?php
$pageTitle = '分类与标签管理';
ob_start();
?>

<div class="page-title-row">
    <h2 class="page-title">分类与标签管理</h2>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Categories Panel -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">分类列表 (<?= count($categories) ?>)</h3>
            <button onclick="showCatModal()" class="btn btn-primary btn-sm">+ 新建分类</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>名称</th>
                    <th>别名</th>
                    <th>文章数</th>
                    <th width="90">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($cat['cate_Name']) ?></td>
                        <td><?= htmlspecialchars($cat['cate_Alias'] ?: '-') ?></td>
                        <td><?= $cat['cate_Count'] ?> 篇</td>
                        <td style="text-align: right;">
                            <div class="action-btn-group">
                                <button onclick="editCat(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn btn-outline btn-sm">编辑</button>
                                <button onclick="deleteCat(<?= $cat['cate_ID'] ?>)" class="btn btn-danger btn-sm">删除</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tags Panel -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">标签列表 (<?= count($tags) ?>)</h3>
            <button onclick="showTagModal()" class="btn btn-primary btn-sm">+ 新建标签</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>标签名</th>
                    <th>别名</th>
                    <th>关联数</th>
                    <th width="110" style="text-align: right;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag): ?>
                    <tr>
                        <td style="font-weight: 600;">#<?= htmlspecialchars($tag['tag_Name']) ?></td>
                        <td><?= htmlspecialchars($tag['tag_Alias'] ?: '-') ?></td>
                        <td><?= $tag['tag_Count'] ?> 次</td>
                        <td style="text-align: right;">
                            <div class="action-btn-group">
                                <button onclick="editTag(<?= htmlspecialchars(json_encode($tag)) ?>)" class="btn btn-outline btn-sm">编辑</button>
                                <button onclick="deleteTag(<?= $tag['tag_ID'] ?>)" class="btn btn-danger btn-sm">删除</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showCatModal() {
    const name = prompt('输入新分类名称:');
    if (!name) return;
    const form = new FormData();
    form.append('name', name);
    fetch('/admin/taxonomy/category-save', { method: 'POST', body: form }).then(() => window.location.reload());
}

function editCat(cat) {
    const name = prompt('修改分类名称:', cat.cate_Name);
    if (!name) return;
    const form = new FormData();
    form.append('id', cat.cate_ID);
    form.append('name', name);
    fetch('/admin/taxonomy/category-save', { method: 'POST', body: form }).then(() => window.location.reload());
}

function deleteCat(id) {
    if (!confirm('确定删除该分类？（所属文章将被设为未分类）')) return;
    const form = new FormData();
    form.append('id', id);
    fetch('/admin/taxonomy/category-delete', { method: 'POST', body: form }).then(() => window.location.reload());
}

function showTagModal() {
    const name = prompt('输入新标签名称:');
    if (!name) return;
    const form = new FormData();
    form.append('name', name);
    fetch('/admin/taxonomy/tag-save', { method: 'POST', body: form }).then(() => window.location.reload());
}

function editTag(tag) {
    const name = prompt('修改标签名称:', tag.tag_Name);
    if (!name) return;
    const form = new FormData();
    form.append('id', tag.tag_ID);
    form.append('name', name);
    fetch('/admin/taxonomy/tag-save', { method: 'POST', body: form }).then(() => window.location.reload());
}

function deleteTag(id) {
    if (!confirm('确定删除该标签？')) return;
    const form = new FormData();
    form.append('id', id);
    fetch('/admin/taxonomy/tag-delete', { method: 'POST', body: form }).then(() => window.location.reload());
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
