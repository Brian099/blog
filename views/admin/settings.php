<?php
$pageTitle = '系统设置';
ob_start();
?>

<div class="page-title-row">
    <div>
        <h2 class="page-title">系统基本配置</h2>
        <p class="page-subtitle">配置网站 Logo、名称、副标题、博主资料与管理员安全凭据</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div style="padding: 12px 16px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 8px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 760px;">
    <form method="POST" action="/admin/settings">
        <!-- 1. 站点 Logo 设置 -->
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                <span>站点 Logo (Site Logo)</span>
                <span style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: normal;">建议使用透明背景 PNG / SVG 或 WebP 图标</span>
            </label>

            <div style="display: flex; align-items: center; gap: 18px; margin-top: 8px; flex-wrap: wrap;">
                <!-- Logo 实时预览方块 -->
                <div id="logo-preview-box" style="width: 64px; height: 64px; border-radius: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; position: relative;">
                    <?php if (!empty($settings['site_logo'])): ?>
                        <img id="logo-preview-img" src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo 预览" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <div id="logo-default-icon" style="color: #0284c7; display: flex; align-items: center; justify-content: center;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Logo 路径输入与上传控制 -->
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" id="site_logo_input" name="site_logo" class="form-input" placeholder="输入 Logo 图片 URL，或点击右侧上传" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" oninput="updateLogoPreview(this.value)">
                        <button type="button" onclick="document.getElementById('logo_file_input').click()" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0; padding: 0 14px; height: 38px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <span>上传 Logo</span>
                        </button>
                        <button type="button" onclick="clearLogo()" class="btn btn-outline" style="padding: 0 10px; height: 38px; color: #ef4444;" title="清除 Logo（恢复默认图标）">
                            ✕
                        </button>
                    </div>
                    <!-- 隐藏的文件选择器 -->
                    <input type="file" id="logo_file_input" accept="image/*" style="display: none;" onchange="handleLogoUpload(this)">
                    <span id="logo-upload-tip" style="font-size: 0.8rem; color: var(--admin-text-muted);">
                        支持输入站内相对路径（如 <code>/users/upload/...</code>）或完整绝对外链。留空则显示默认科技几何图标。
                    </span>
                </div>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 20px 0;">

        <!-- 2. 站点基本信息 -->
        <div class="form-group">
            <label class="form-label">站点名称</label>
            <input type="text" name="site_name" class="form-input" value="<?= htmlspecialchars($settings['site_name'] ?? '技术思维棱镜') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">站点副标题 / 口号</label>
            <input type="text" name="site_subtitle" class="form-input" value="<?= htmlspecialchars($settings['site_subtitle'] ?? '专注技术记录、脚本折腾与实战经验分享') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">博主昵称</label>
            <input type="text" name="author_name" class="form-input" value="<?= htmlspecialchars($settings['author_name'] ?? 'Brian') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">博主简介</label>
            <textarea name="author_bio" class="form-textarea" style="min-height: 80px;"><?= htmlspecialchars($settings['author_bio'] ?? '') ?></textarea>
        </div>

        <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 24px 0;">

        <!-- 3. 安全设置 -->
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px;">管理员安全设置</h3>

        <div class="form-group">
            <label class="form-label">管理用户名</label>
            <input type="text" name="admin_username" class="form-input" value="<?= htmlspecialchars($settings['admin_username'] ?? 'admin') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">管理登录密码</label>
            <input type="password" name="admin_password" class="form-input" placeholder="留空表示不修改密码" autocomplete="new-password">
            <small style="color:var(--admin-text-muted); font-size:0.8rem; display:block; margin-top:6px; line-height:1.4;">
                🔒 留空则保持现有密码不变。输入新密码保存后将采用工业级 Bcrypt 哈希安全加密，数据库中不会出现明文。
            </small>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; margin-top: 10px; font-weight: 600;">
            保存系统设置
        </button>
    </form>
</div>

<script>
// 实时更新 Logo 预览
function updateLogoPreview(url) {
    const box = document.getElementById('logo-preview-box');
    url = url.trim();
    if (url) {
        box.innerHTML = `<img id="logo-preview-img" src="${url}" alt="Logo 预览" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.parentElement.innerHTML='<span style=\\'font-size:0.7rem;color:#ef4444;font-weight:600;\\'>无效</span>'">`;
    } else {
        box.innerHTML = `<div id="logo-default-icon" style="color: #0284c7; display: flex; align-items: center; justify-content: center;"><svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>`;
    }
}

// 清除 Logo
function clearLogo() {
    document.getElementById('site_logo_input').value = '';
    updateLogoPreview('');
}

// 异步上传 Logo 图片
async function handleLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const tip = document.getElementById('logo-upload-tip');
    
    tip.innerText = '⏳ 正在上传 Logo 图片...';
    tip.style.color = '#2563eb';

    const fd = new FormData();
    fd.append('file', file);

    try {
        const res = await fetch('/admin/upload', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        if (json.success && json.url) {
            document.getElementById('site_logo_input').value = json.url;
            updateLogoPreview(json.url);
            tip.innerText = '✅ Logo 图片上传成功！请点击底部「保存系统设置」生效。';
            tip.style.color = '#16a34a';
        } else {
            alert('Logo 上传失败: ' + (json.error || '未知错误'));
            tip.innerText = '❌ 上传失败，请重试。';
            tip.style.color = '#dc2626';
        }
    } catch (e) {
        alert('上传请求异常: ' + e.message);
        tip.innerText = '❌ 上传失败: ' + e.message;
        tip.style.color = '#dc2626';
    } finally {
        input.value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
