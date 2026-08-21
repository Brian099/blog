<?php
use App\Models\Setting;
$loginSiteName = Setting::get('site_name', '技术思维棱镜');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?= htmlspecialchars($loginSiteName) ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 36px 32px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($loginSiteName) ?></h2>
            <p style="color: #64748b; font-size: 0.88rem; margin-top: 4px;">管理员登录控制台</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="padding: 10px 14px; background: #fee2e2; color: #dc2626; border-radius: 6px; font-size: 0.85rem; margin-bottom: 16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" name="username" class="form-input" placeholder="默认 admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-input" placeholder="默认 admin123" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px; margin-top: 8px;">
                立即登录
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="/" style="color: #64748b; font-size: 0.85rem;">← 返回博客前台</a>
        </div>
    </div>
</div>
</body>
</html>
