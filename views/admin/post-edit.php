<?php
$pageTitle = $post ? '编辑文章 - ' . htmlspecialchars($post['title']) : '新建文章';
ob_start();
?>

<!-- UEditor CSS and Local Scripts -->
<link rel="stylesheet" href="/assets/ueditor/themes/default/css/ueditor.min.css">
<script>
    window.UEDITOR_HOME_URL = "/assets/ueditor/";
</script>
<script src="/assets/ueditor/ueditor.config.js"></script>
<script src="/assets/ueditor/ueditor.all.min.js"></script>
<script src="/assets/ueditor/lang/zh-cn/zh-cn.js"></script>

<div class="page-title-row">
    <h2 class="page-title"><?= $post ? '编辑文章' : '新建文章' ?></h2>
    <a href="/admin/posts" class="btn btn-outline">← 返回列表</a>
</div>

<form id="post-edit-form">
    <?php if ($post): ?>
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 24px;">
        <!-- Left: Title and Content Area -->
        <div>
            <div class="card">
                <div class="form-group">
                    <label class="form-label">文章标题 <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" style="font-size: 1.15rem; font-weight: 600;" 
                           value="<?= htmlspecialchars($post['title'] ?? '') ?>" placeholder="输入文章标题..." required>
                </div>

                <div class="form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label class="form-label" style="margin-bottom: 0;">正文内容 (UEditor 经典编辑器)</label>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="triggerEditorAttachmentUpload()" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; padding: 4px 10px; border-radius: var(--radius-sm); cursor: pointer;">
                                📎 上传并插入附件
                            </button>
                            <input type="file" id="quick-attachment-file" style="display: none;" onchange="handleEditorAttachmentUpload(event)">
                        </div>
                        <span style="font-size: 0.78rem; color: #64748b;">支持直接粘贴/拖拽图片，或点击上方「上传并插入附件」</span>
                    </div>
                    <!-- UEditor Container -->
                    <script id="post-content-editor" name="content" type="text/plain" style="width:100%; height:500px;"><?= $post['content_raw'] ?? '' ?></script>
                </div>

                <div class="form-group">
                    <label class="form-label">自定义摘要 / 简介 (留空将自动提取)</label>
                    <textarea name="intro" class="form-textarea" style="min-height: 80px;" placeholder="文章简短摘要..."><?= htmlspecialchars($post['intro'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right: Meta Settings Sidebar -->
        <div>
            <div class="card">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px;">发布设置</h3>

                <div class="form-group">
                    <label class="form-label">所属分类</label>
                    <select name="cate_id" class="form-select">
                        <option value="0">未分类</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['cate_ID'] ?>" <?= (isset($post['cate_id']) && (int)$post['cate_id'] === (int)$cat['cate_ID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['cate_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">关联标签</label>
                    <div style="max-height: 140px; overflow-y: auto; border: 1px solid var(--admin-border); padding: 8px; border-radius: var(--radius); display: flex; flex-direction: column; gap: 4px;">
                        <?php 
                        $curTagsRaw = $post['tags_raw'] ?? '';
                        foreach ($tags as $tag): 
                            $checked = (strpos($curTagsRaw, '{'.$tag['tag_ID'].'}') !== false);
                        ?>
                            <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="tags[]" value="<?= $tag['tag_ID'] ?>" <?= $checked ? 'checked' : '' ?>>
                                <span>#<?= htmlspecialchars($tag['tag_Name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">自定义别名 (Slug)</label>
                    <input type="text" name="alias" class="form-input" value="<?= htmlspecialchars($post['alias'] ?? '') ?>" placeholder="如 excel-split">
                </div>

                <div class="form-group">
                    <label class="form-label">发布状态</label>
                    <select name="status" class="form-select">
                        <option value="0" <?= (isset($post['status']) && (int)$post['status'] === 0) ? 'selected' : '' ?>>公开发布</option>
                        <option value="1" <?= (isset($post['status']) && (int)$post['status'] === 1) ? 'selected' : '' ?>>保存为草稿</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">访问密码保护 (留空表示公开)</label>
                    <input type="text" name="password" class="form-input" value="<?= htmlspecialchars($post['password'] ?? '') ?>" placeholder="设置访问密码，留空则公开">
                    <span style="font-size: 0.75rem; color: #64748b; margin-top: 2px; display: block;">设置密码后，前台用户需输入密码方可查看正文</span>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                        <input type="checkbox" name="is_top" value="1" <?= (!empty($post['is_top'])) ? 'checked' : '' ?>>
                        <span>置顶此文章</span>
                    </label>
                </div>

                <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 16px 0;">

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
                    保存并发布
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function triggerEditorAttachmentUpload() {
    document.getElementById('quick-attachment-file').click();
}

async function handleEditorAttachmentUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    const btn = document.querySelector('button[onclick="triggerEditorAttachmentUpload()"]');
    const origText = btn ? btn.innerHTML : '';
    if (btn) btn.innerHTML = '⏳ 上传中...';

    try {
        const res = await fetch('/admin/upload', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success && window.ueEditor) {
            const fileName = file.name;
            const linkHtml = `<p><a href="${data.url}" title="${fileName}">${fileName}</a></p>`;
            window.ueEditor.execCommand('inserthtml', linkHtml);
        } else {
            alert('上传失败: ' + (data.error || '未知错误'));
        }
    } catch (err) {
        alert('网络请求失败: ' + err.message);
    } finally {
        if (btn) btn.innerHTML = origText;
        e.target.value = '';
    }
}

// 初始化 UEditor
window.ueEditor = null;
document.addEventListener('DOMContentLoaded', () => {
    if (window.UE) {
        // 点击 UEditor 工具栏的附件图标时直接调起本地文件快速上传
        UE.plugin.register('attachment-custom', function() {
            return {
                commands: {
                    'attachment': {
                        execCommand: function() {
                            triggerEditorAttachmentUpload();
                        }
                    }
                }
            };
        });

        window.ueEditor = UE.getEditor('post-content-editor', {
            serverUrl: '/admin/ueditor-api',
            initialFrameHeight: 520,
            autoHeightEnabled: false,
            enableDragUpload: true,
            enablePasteUpload: true,
            catchRemoteImageEnable: true,
            zIndex: 10,
            toolbars: [[
                'fullscreen', 'source', '|', 'undo', 'redo', '|',
                'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
                'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
                'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
                'directionalityltr', 'directionalityrtl', 'indent', '|',
                'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
                'touppercase', 'tolowercase', '|',
                'link', 'unlink', 'anchor', '|',
                'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
                'simpleupload', 'insertimage', 'attachment', 'insertvideo', 'insertcode', 'pagebreak', 'template', 'background', '|',
                'horizontal', 'date', 'time', 'spechars', '|',
                'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittorows', 'splittocols', 'splittocells', '|',
                'preview', 'searchreplace', 'help'
            ]]
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
