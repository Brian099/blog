<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装与迁移引导 - 现代化极客博客</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        :root {
            --install-bg: #0f172a;
            --install-card-bg: rgba(30, 41, 59, 0.85);
            --install-border: rgba(255, 255, 255, 0.1);
            --install-primary: #38bdf8;
            --install-primary-hover: #0ea5e9;
        }

        body.install-body {
            background-color: var(--install-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(147, 51, 234, 0.15) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
            box-sizing: border-box;
        }

        .install-container {
            width: 100%;
            max-width: 820px;
            background: var(--install-card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--install-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .install-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .install-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px -5px rgba(56, 189, 248, 0.4);
        }

        .install-title {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 8px;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .install-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            margin: 0;
        }

        .mode-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }

        @media (max-width: 640px) {
            .mode-cards { grid-template-columns: 1fr; }
            .install-container { padding: 24px 18px; }
        }

        .mode-card {
            border: 2px solid var(--install-border);
            border-radius: 14px;
            padding: 20px;
            background: rgba(15, 23, 42, 0.6);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .mode-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
            transform: translateY(-2px);
        }

        .mode-card.active {
            border-color: var(--install-primary);
            background: rgba(56, 189, 248, 0.08);
        }

        .mode-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            font-size: 0.72rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .mode-badge-rec {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .mode-badge-new {
            background: rgba(147, 51, 234, 0.2);
            color: #c084fc;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .mode-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mode-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0;
        }

        .form-section {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--install-border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 16px;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 0.85rem;
            color: #cbd5e1;
            font-weight: 600;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 10px 14px;
            color: #f8fafc;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--install-primary);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #0369a1, #1d4ed8);
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .c-option-banner {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            color: #6ee7b7;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        /* Success screen */
        .success-box {
            text-align: center;
            padding: 30px 20px;
        }
        .success-icon {
            font-size: 3.5rem;
            margin-bottom: 16px;
        }
        .success-links {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .btn-link {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .btn-link-primary {
            background: #38bdf8;
            color: #0f172a;
        }
        .btn-link-outline {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="install-body">

<div class="install-container">
    <?php if ($isInstalled && !$force): ?>
        <div class="success-box">
            <div class="success-icon">🔒</div>
            <h2 class="install-title">博客系统已安装就绪</h2>
            <p class="install-subtitle" style="margin-top: 8px;">系统已检测到有效的安装锁和数据库文件，无需重复安装。</p>
            <div class="success-links">
                <a href="/" class="btn-link btn-link-primary">🚀 访问博客首页</a>
                <a href="/admin" class="btn-link btn-link-outline">⚙️ 进入管理后台</a>
                <a href="/install?reinstall=1" class="btn-link btn-link-outline" style="color: #f87171; border-color: rgba(248,113,113,0.3);">🔄 重新配置向导</a>
            </div>
        </div>
    <?php else: ?>
        <div class="install-header">
            <div class="install-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            </div>
            <h1 class="install-title">现代化极客博客系统安装引导</h1>
            <p class="install-subtitle">请选择安装或迁移模式，一键完成数据库连接、冗余清洗与配置初始化</p>
        </div>

        <?php if (!empty($cOptionInfo)): ?>
            <div class="c-option-banner">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"/></svg>
                <div>
                    <strong>已自动检测到现存 Z-Blog 配置文件 <code><?= htmlspecialchars(basename($cOptionInfo['file'])) ?></code></strong>
                    <div style="font-size: 0.8rem; color: #a7f3d0; margin-top: 2px;">
                        数据库：<?= htmlspecialchars($cOptionInfo['type']) ?>://<?= htmlspecialchars($cOptionInfo['host']) ?>:<?= $cOptionInfo['port'] ?>/<?= htmlspecialchars($cOptionInfo['dbname']) ?> (用户: <?= htmlspecialchars($cOptionInfo['username']) ?>)
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form id="install-form" onsubmit="handleInstall(event)">
            <!-- 模式选择 -->
            <div class="mode-cards">
                <div class="mode-card active" id="card-migrate" onclick="selectMode('migrate')">
                    <span class="mode-badge mode-badge-rec">推荐 / 零导入</span>
                    <h3 class="mode-title">
                        <span>🚀</span> 从现有 Z-Blog 迁移
                    </h3>
                    <p class="mode-desc">
                        自动使用 <code>c_option.php</code> 连接现有数据库，保留全部文章、分类、附件与评论，并<strong>自动完成 8 张冗余表剔除与自适应排版清洗</strong>。
                    </p>
                </div>

                <div class="mode-card" id="card-fresh" onclick="selectMode('fresh')">
                    <span class="mode-badge mode-badge-new">纯净新建</span>
                    <h3 class="mode-title">
                        <span>✨</span> 全新初始安装
                    </h3>
                    <p class="mode-desc">
                        优先推荐使用 <strong>SQLite（单文件零配置）</strong>，亦可选用 MySQL，自动创建纯净架构与初始示例数据。
                    </p>
                </div>
            </div>

            <!-- 全新安装时的数据引擎选择 -->
            <div id="fresh-db-config" class="form-section" style="display: none;">
                <h4 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                    <span>数据库存储引擎选择</span>
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">数据库类型</label>
                        <select id="db_type" name="db_type" class="form-control" onchange="toggleDbFields()">
                            <option value="sqlite" selected>SQLite (极力推荐，单文件无需额外服务)</option>
                            <option value="mysql">MySQL / MariaDB</option>
                        </select>
                    </div>
                </div>

                <div id="mysql-fields" style="display: none; margin-top: 16px;" class="form-grid">
                    <div class="form-group">
                        <label class="form-label">MySQL 主机地址</label>
                        <input type="text" name="mysql_host" id="mysql_host" class="form-control" value="127.0.0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">端口</label>
                        <input type="number" name="mysql_port" id="mysql_port" class="form-control" value="3306">
                    </div>
                    <div class="form-group">
                        <label class="form-label">数据库名</label>
                        <input type="text" name="mysql_dbname" id="mysql_dbname" class="form-control" value="zblog">
                    </div>
                    <div class="form-group">
                        <label class="form-label">数据库用户名</label>
                        <input type="text" name="mysql_user" id="mysql_user" class="form-control" value="root">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">数据库密码</label>
                        <input type="password" name="mysql_pass" id="mysql_pass" class="form-control" placeholder="留空则无密码">
                    </div>
                </div>
            </div>

            <!-- 站点与管理员信息 -->
            <div class="form-section">
                <h4 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>站点与管理员账号配置</span>
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">博客名称</label>
                        <input type="text" name="site_name" class="form-control" value="技术思维棱镜" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">博客副标题</label>
                        <input type="text" name="site_subtitle" class="form-control" value="专注技术记录、脚本折腾与实战经验分享">
                    </div>
                    <div class="form-group">
                        <label class="form-label">作者姓名 / 昵称</label>
                        <input type="text" name="author_name" class="form-control" value="Brian" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">作者简述</label>
                        <input type="text" name="author_bio" class="form-control" value="热爱技术与折腾">
                    </div>
                    <div class="form-group">
                        <label class="form-label">后台管理员账号</label>
                        <input type="text" name="admin_username" class="form-control" value="admin" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">后台管理员密码</label>
                        <input type="password" name="admin_password" class="form-control" value="admin123" required>
                    </div>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="submit-btn">
                <span>🚀 立即执行安装与配置初始化</span>
            </button>
        </form>

        <div id="result-box" style="display: none; margin-top: 24px;" class="success-box">
            <div class="success-icon" id="res-icon">🎉</div>
            <h2 class="install-title" id="res-title">安装与初始化成功！</h2>
            <p class="install-subtitle" id="res-desc"></p>
            <div class="success-links">
                <a href="/" class="btn-link btn-link-primary">🚀 访问博客首页</a>
                <a href="/admin" class="btn-link btn-link-outline">⚙️ 进入管理后台</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let currentMode = 'migrate';

function selectMode(mode) {
    currentMode = mode;
    document.getElementById('card-migrate').classList.toggle('active', mode === 'migrate');
    document.getElementById('card-fresh').classList.toggle('active', mode === 'fresh');
    
    const freshBox = document.getElementById('fresh-db-config');
    if (freshBox) {
        freshBox.style.display = mode === 'fresh' ? 'block' : 'none';
    }
}

function toggleDbFields() {
    const type = document.getElementById('db_type').value;
    document.getElementById('mysql-fields').style.display = type === 'mysql' ? 'grid' : 'none';
}

async function handleInstall(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ 正在执行数据库初始化与优化处理...</span>';

    const form = document.getElementById('install-form');
    const formData = new FormData(form);

    const url = currentMode === 'migrate' ? '/install/migrate' : '/install/fresh';

    try {
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            form.style.display = 'none';
            const resBox = document.getElementById('result-box');
            resBox.style.display = 'block';
            
            if (currentMode === 'migrate' && json.data) {
                const d = json.data;
                document.getElementById('res-desc').innerHTML = 
                    `已成功规范化目录并保留 <strong>${d.total_posts}</strong> 篇文章与 <strong>${d.total_uploads}</strong> 个附件！<br>` +
                    `📦 归档旧插件/系统项: ${d.archived_items} 个 | 🎨 同步文件图标: ${d.synced_icons} 个<br>` +
                    `🗑️ 剔除冗余表: ${d.tables_dropped} 个 | 冗余字段: ${d.columns_dropped} 个<br>` +
                    `🔄 规范化路径文章: ${d.updated_paths_posts} 篇 | 📱 修复自适应排版: ${d.cleaned_posts} 篇`;
            } else {
                document.getElementById('res-desc').innerText = json.message || '全新架构与示例文章已成功就绪！';
            }
        } else {
            alert('执行失败：' + (json.error || '未知错误'));
            btn.disabled = false;
            btn.innerHTML = '<span>🚀 立即执行安装与配置初始化</span>';
        }
    } catch (err) {
        alert('请求异常：' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<span>🚀 立即执行安装与配置初始化</span>';
    }
}
</script>
</body>
</html>
