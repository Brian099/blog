<?php
$pageTitle = '数据备份与转换';
ob_start();
?>

<div class="settings-container" style="max-width: 1000px;">
    
    <!-- 顶部数据库运行状态卡片 -->
    <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; border: 1px solid #e2e8f0; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(56, 189, 248, 0.12); color: #0284c7; display: flex; align-items: center; justify-content: center;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0;">当前数据库：<?= strtoupper($driver) ?></h3>
                    <span style="font-size: 0.75rem; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 9999px; font-weight: 600;">运行中</span>
                </div>
                <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                    <?php if ($driver === 'sqlite'): ?>
                        文件存储：<code style="background:#f1f5f9; color:#0f172a; padding:2px 6px; border-radius:4px; font-size:0.8rem;"><?= htmlspecialchars($sqliteInfo['path']) ?></code> (体积: <?= $sqliteInfo['size_formatted'] ?>)
                    <?php else: ?>
                        连接目标：<code style="background:#f1f5f9; color:#0f172a; padding:2px 6px; border-radius:4px; font-size:0.8rem;"><?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>:<?= $mysqlConfig['port'] ?? 3306 ?> / <?= htmlspecialchars($mysqlConfig['dbname'] ?? '') ?></code>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <button onclick="createBackup()" id="btn-create-bak" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>创建全新快照备份</span>
            </button>
        </div>
    </div>

    <!-- 模块一：数据库快照备份与历史管理 -->
    <div class="settings-card" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 class="settings-card-title" style="margin-bottom: 4px;">📦 历史备份列表与导出</h3>
                <p class="settings-card-desc">备份文件保存在服务器 <code>data/backups/</code> 目录，支持直接下载到本地或一键还原。</p>
            </div>
            <span style="font-size: 0.85rem; color: #64748b;">共 <?= count($backups) ?> 个备份文件</span>
        </div>

        <?php if (empty($backups)): ?>
            <div style="text-align: center; padding: 36px 20px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; color: #64748b;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div>暂无历史备份，点击右上角“创建全新快照备份”即可一键生成！</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>备份文件名</th>
                            <th>类型</th>
                            <th>体积大小</th>
                            <th>创建时间</th>
                            <th style="text-align: right;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                        <code><?= htmlspecialchars($b['filename']) ?></code>
                                    </div>
                                </td>
                                <td><span style="font-size: 0.8rem; color: #475569;"><?= $b['type'] ?></span></td>
                                <td><span style="font-size: 0.85rem; font-weight: 600; color: #0f172a;"><?= $b['size_formatted'] ?></span></td>
                                <td><span style="font-size: 0.8rem; color: #64748b;"><?= $b['time_formatted'] ?></span></td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <a href="/admin/backup/download?file=<?= urlencode($b['filename']) ?>" class="btn btn-outline btn-sm" title="下载到本地">
                                            📥 下载
                                        </a>
                                        <?php if ($b['ext'] === 'db'): ?>
                                            <button onclick="restoreBackup('<?= htmlspecialchars($b['filename']) ?>')" class="btn btn-outline btn-sm" style="color: #b45309; border-color: #fcd34d;" title="一键覆盖还原至此备份">
                                                🔄 还原
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="deleteBackup('<?= htmlspecialchars($b['filename']) ?>')" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fca5a5;" title="删除备份">
                                            🗑️ 删除
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 模块二：MySQL 一键转 SQLite 便携工具 (仅在当前使用 MySQL 模式时显示) -->
    <?php if ($driver !== 'sqlite'): ?>
        <div class="settings-card">
            <div style="display: flex; gap: 14px; align-items: flex-start; margin-bottom: 20px;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(245, 158, 11, 0.12); color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
                </div>
                <div>
                    <h3 class="settings-card-title" style="margin-bottom: 4px;">🔄 将 MySQL 数据库一键转为 SQLite (blog.db)</h3>
                    <p class="settings-card-desc">
                        系统已通过后台身份验证，将<strong>直接自动从现有配置文件（<code>c_option.php</code> / <code>Config.php</code>）中读取 MySQL 连接信息</strong>，无需重复输入。点击下方按钮即可一键提取全部文章、分类、标签、附件与评论，并生成单文件免运维的 <strong><code>data/blog.db</code></strong>。
                    </p>
                </div>
            </div>

            <!-- 自动识别到的配置预览卡片 -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>已自动识别的源 MySQL 数据库配置：</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.85rem;">
                    <div><span style="color: #64748b;">服务器地址:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>:<?= (int)($mysqlConfig['port'] ?? 3306) ?></strong></div>
                    <div><span style="color: #64748b;">数据库名称:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($mysqlConfig['dbname'] ?: '未设置(将自动探测)') ?></strong></div>
                    <div><span style="color: #64748b;">登录用户名:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($mysqlConfig['username'] ?? 'root') ?></strong></div>
                    <div><span style="color: #64748b;">配置文件源:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($config['c_option_source'] ? basename($config['c_option_source']) : 'app/Config.php') ?></strong></div>
                </div>
            </div>

            <form id="convert-form" onsubmit="handleConvert(event)">
                <!-- 高级自定义连接折叠区 (选填) -->
                <div style="margin-bottom: 18px;">
                    <a href="javascript:void(0)" onclick="toggleAdvancedDb()" style="font-size: 0.85rem; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <span id="adv-arrow">▶</span> <span>高级选项：自定义指定其他 MySQL 目标连接 (通常无需修改)</span>
                    </a>
                </div>

                <div id="advanced-db-fields" style="display: none; background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">MySQL 服务器地址 (Host)</label>
                            <input type="text" id="m_host" name="host" class="form-control" value="<?= htmlspecialchars($mysqlConfig['host'] ?? '127.0.0.1') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">端口 (Port)</label>
                            <input type="number" id="m_port" name="port" class="form-control" value="<?= (int)($mysqlConfig['port'] ?? 3306) ?>">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 14px;">
                        <div class="form-group">
                            <label class="form-label">MySQL 数据库名称 (Database Name)</label>
                            <input type="text" id="m_dbname" name="dbname" class="form-control" value="<?= htmlspecialchars($mysqlConfig['dbname'] ?? '') ?>" placeholder="例如: zblog 或 giraff">
                        </div>
                        <div class="form-group">
                            <label class="form-label">MySQL 用户名 (Username)</label>
                            <input type="text" id="m_username" name="username" class="form-control" value="<?= htmlspecialchars($mysqlConfig['username'] ?? 'root') ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 14px;">
                        <label class="form-label">MySQL 密码 (Password)</label>
                        <input type="password" id="m_password" name="password" class="form-control" value="<?= htmlspecialchars($mysqlConfig['password'] ?? '') ?>" placeholder="留空则使用默认配置密码">
                    </div>
                </div>

                <div id="convert-result-box" style="display: none; margin-top: 16px; padding: 14px 18px; border-radius: 8px; font-size: 0.9rem;"></div>

                <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <button type="submit" id="btn-start-convert" class="btn btn-primary" style="background: #f59e0b; border-color: #d97706; font-weight: 600; padding: 10px 20px;">
                        <span>⚡ 立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>
                    </button>
                    <button type="button" onclick="testMysqlConnection()" id="btn-test-mysql" class="btn btn-outline">
                        <span>🔌 测试当前 MySQL 连通性</span>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
// 折叠/展开高级数据库配置
function toggleAdvancedDb() {
    const el = document.getElementById('advanced-db-fields');
    const arrow = document.getElementById('adv-arrow');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arrow.innerText = '▼';
    } else {
        el.style.display = 'none';
        arrow.innerText = '▶';
    }
}

// 1. 创建备份
async function createBackup() {
    const btn = document.getElementById('btn-create-bak');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ 正在生成备份...</span>';

    try {
        const res = await fetch('/admin/backup/create', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            alert(json.message + '\n生成文件: ' + json.filename + ' (' + json.size_formatted + ')');
            location.reload();
        } else {
            alert('备份失败: ' + (json.error || '未知错误'));
            btn.disabled = false;
            btn.innerHTML = '<span>创建全新快照备份</span>';
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<span>创建全新快照备份</span>';
    }
}

// 2. 删除备份
async function deleteBackup(filename) {
    if (!confirm(`确定要永久删除备份文件 [${filename}] 吗？`)) return;

    const fd = new FormData();
    fd.append('file', filename);

    try {
        const res = await fetch('/admin/backup/delete', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert('删除失败: ' + (json.error || '未知原因'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    }
}

// 3. 还原 SQLite 备份
async function restoreBackup(filename) {
    if (!confirm(`⚠️ 危险操作确认：\n\n确定要将当前数据库还原覆盖为备份 [${filename}] 吗？\n系统将在还原前自动备份当前活跃数据库。`)) return;

    const fd = new FormData();
    fd.append('file', filename);

    try {
        const res = await fetch('/admin/backup/restore', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            location.reload();
        } else {
            alert('还原失败: ' + (json.error || '未知原因'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    }
}

// 4. 测试 MySQL 连接
async function testMysqlConnection() {
    const btn = document.getElementById('btn-test-mysql');
    btn.disabled = true;
    btn.innerText = '正在测试连接...';

    const fd = new FormData();
    fd.append('type', 'mysql');
    const hostEl = document.getElementById('m_host');
    if (hostEl && hostEl.value) {
        fd.append('host', document.getElementById('m_host').value);
        fd.append('port', document.getElementById('m_port').value);
        fd.append('dbname', document.getElementById('m_dbname').value);
        fd.append('username', document.getElementById('m_username').value);
        fd.append('password', document.getElementById('m_password').value);
    }

    try {
        const res = await fetch('/install/test-db', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            alert('✅ ' + json.message);
        } else {
            alert('❌ 连接失败: ' + (json.error || '请检查配置'));
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerText = '🔌 测试当前 MySQL 连通性';
    }
}

// 5. 执行 MySQL 转 SQLite（直接读取配置，无需重复输入）
async function handleConvert(e) {
    e.preventDefault();
    if (!confirm('确定要从当前配置的 MySQL 数据库完整提取并生成纯净 SQLite (data/blog.db) 吗？\n\n系统将自动完成：\n1. 结构与数据提取\n2. 资源路径规范化\n3. VACUUM 碎片整理与优化\n（若已存在 blog.db，会自动进行安全备份）')) return;

    const btn = document.getElementById('btn-start-convert');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ 正在从配置的 MySQL 迁移数据表并重构 SQLite (请稍候)...</span>';

    const form = document.getElementById('convert-form');
    const fd = new FormData(form);
    const resBox = document.getElementById('convert-result-box');

    try {
        const res = await fetch('/admin/backup/convert-mysql-to-sqlite', { method: 'POST', body: fd });
        const json = await res.json();

        resBox.style.display = 'block';
        if (json.success) {
            const st = json.stats;
            resBox.style.background = '#ecfdf5';
            resBox.style.border = '1px solid #a7f3d0';
            resBox.style.color = '#065f46';
            resBox.innerHTML = `
                <div style="font-weight: 700; font-size: 1rem; margin-bottom: 6px;">🎉 ${json.message}</div>
                <div style="font-size: 0.85rem; line-height: 1.6;">
                    • 迁移分类: <strong>${st.categories}</strong> 个 | 标签: <strong>${st.tags}</strong> 个<br>
                    • 迁移文章: <strong>${st.posts}</strong> 篇 (已自动规范化路径)<br>
                    • 迁移附件: <strong>${st.uploads}</strong> 个 | 评论: <strong>${st.comments}</strong> 条<br>
                    • 生成 SQLite 文件: <code>${json.sqlite_file}</code> (体积: <strong>${json.sqlite_size_formatted}</strong>)
                </div>
            `;
            alert('转换成功！SQLite 数据库已生成在 data/blog.db');
            setTimeout(() => { location.reload(); }, 2000);
        } else {
            resBox.style.background = '#fef2f2';
            resBox.style.border = '1px solid #fecaca';
            resBox.style.color = '#991b1b';
            resBox.innerHTML = `<strong>转换失败：</strong> ${json.error || '未知错误'}`;
            btn.disabled = false;
            btn.innerHTML = '<span>⚡ 立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>';
        }
    } catch (e) {
        alert('请求异常: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<span>⚡ 立即从当前配置的 MySQL 转换并生成 SQLite (blog.db)</span>';
    }
}
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/admin/layout.php';
