<?php
$pageTitle = '系统设置';
ob_start();
?>

<div class="page-title-row">
    <h2 class="page-title">系统基本配置</h2>
</div>

<?php if (!empty($message)): ?>
    <div style="padding: 12px 16px; background: #dcfce7; color: #15803d; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 680px;">
    <form method="POST" action="/admin/settings">
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

        <hr style="border:none; border-top:1px solid var(--admin-border); margin: 24px 0;">

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

        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; margin-top: 10px;">
            保存系统设置
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
?>
