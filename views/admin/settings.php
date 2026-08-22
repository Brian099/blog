<?php
$pageTitle = '系统设置';
ob_start();
?>

<form method="POST" action="/admin/settings" id="settings-form">
    <!-- 顶部标题栏与唯一的保存按钮 -->
    <div class="page-title-row">
        <div>
            <h2 class="page-title">系统基本配置</h2>
            <p class="page-subtitle">管理网站品牌 Logo、站点名称、副标题、博主公开资料及管理员安全账户</p>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; font-weight: 600; font-size: 0.92rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>保存系统设置</span>
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div style="padding: 12px 18px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: var(--radius); margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 2x2 严整对称卡片网格布局（四大模块高度严格保持一致） -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
        
        <!-- 模块 1：站点 Logo 与品牌标识 -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; min-height: 260px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <div>
                        <h3 class="card-title">站点 Logo 与品牌标识</h3>
                        <p class="card-desc">配置展示在网站头部与后台侧边栏的品牌图标</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <!-- Logo 实时预览方块 -->
                        <div id="logo-preview-box" style="width: 60px; height: 60px; border-radius: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; position: relative;">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <img id="logo-preview-img" src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo 预览" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div id="logo-default-icon" style="color: #0284c7; display: flex; align-items: center; justify-content: center;">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 路径与上传控制 -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" id="site_logo_input" name="site_logo" class="form-input" placeholder="Logo 图片 URL 或点击右侧上传" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" oninput="updateLogoPreview(this.value)">
                                <button type="button" onclick="document.getElementById('logo_file_input').click()" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0; padding: 0 14px; height: 38px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    <span>上传</span>
                                </button>
                                <button type="button" onclick="clearLogo()" class="btn btn-outline" style="padding: 0 10px; height: 38px; color: #ef4444;" title="清除 Logo（恢复默认几何图标）">
                                    ✕
                                </button>
                            </div>
                            <input type="file" id="logo_file_input" accept="image/*" style="display: none;" onchange="handleLogoUpload(this)">
                        </div>
                    </div>
                </div>
            </div>

            <div id="logo-upload-tip" style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 12px; line-height: 1.5;">
                💡 建议使用透明背景 PNG / SVG 图标。留空则显示默认科技几何图标。
            </div>
        </div>

        <!-- 模块 2：博主公开资料 -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; min-height: 260px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div>
                        <h3 class="card-title">博主公开资料</h3>
                        <p class="card-desc">展示于文章详情底部作者栏及博客关于信息</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">博主昵称 (Author Name)</label>
                    <input type="text" name="author_name" class="form-input" value="<?= htmlspecialchars($settings['author_name'] ?? 'Brian') ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">博主个人简介 (Author Bio)</label>
                    <textarea name="author_bio" class="form-textarea" style="min-height: 60px; height: 60px; padding: 6px 12px;" placeholder="介绍一下你的技术方向与博客主题..."><?= htmlspecialchars($settings['author_bio'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 模块 3：站点基础信息 -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; min-height: 260px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #fdf4ff; color: #c026d3; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    </div>
                    <div>
                        <h3 class="card-title">站点基础信息</h3>
                        <p class="card-desc">展示于前台首页标题、SEO 元标签与浏览器标签栏</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">站点名称 (Site Name) <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="site_name" class="form-input" value="<?= htmlspecialchars($settings['site_name'] ?? '技术思维棱镜') ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">站点副标题 / 口号 (Subtitle / Slogan)</label>
                    <input type="text" name="site_subtitle" class="form-input" value="<?= htmlspecialchars($settings['site_subtitle'] ?? '专注技术记录、脚本折腾与实战经验分享') ?>">
                </div>
            </div>

            <div style="color: var(--admin-text-muted); font-size: 0.78rem; margin-top: 12px; line-height: 1.5;">
                前台标题后缀，展示在浏览器标签及分享卡片中。
            </div>
        </div>

        <!-- 模块 4：管理员安全设置 -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; min-height: 260px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <div>
                        <h3 class="card-title">管理员安全设置</h3>
                        <p class="card-desc">管理后台登录凭据与密码安全保护</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">管理用户名 (Username) <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="admin_username" class="form-input" value="<?= htmlspecialchars($settings['admin_username'] ?? 'admin') ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 0.84rem;">管理登录密码 (Password)</label>
                    <input type="password" name="admin_password" class="form-input" placeholder="留空表示保持原密码不变" autocomplete="new-password">
                </div>
            </div>

            <div style="color: var(--admin-text-muted); font-size: 0.78rem; margin-top: 12px; line-height: 1.5;">
                🔒 留空则保持原密码不变。输入新密码后将采用 Bcrypt 强哈希加密存储。
            </div>
        </div>
    </div>
</form>

<script>
// 实时更新 Logo 预览
function updateLogoPreview(url) {
    const box = document.getElementById('logo-preview-box');
    url = (url || '').trim();
    if (url) {
        box.innerHTML = `<img id="logo-preview-img" src="${url}" alt="Logo 预览" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.parentElement.innerHTML='<span style=\\'font-size:0.7rem;color:#ef4444;font-weight:600;\\'>无效</span>'">`;
    } else {
        box.innerHTML = `<div id="logo-default-icon" style="color: #0284c7; display: flex; align-items: center; justify-content: center;"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>`;
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
            tip.innerText = '✅ Logo 图片上传成功！请点击右上角「保存系统设置」生效。';
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
